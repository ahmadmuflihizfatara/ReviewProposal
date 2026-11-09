<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('reviews.index', [
            'reviews' => $user->reviews()->with('proposal')->latest()->get(),
            'pendingProposals' => Proposal::whereDoesntHave('reviews', fn ($query) => $query->where('reviewer_id', $user->id))
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Langkah 1: reviewer memilih proposal dan mengisi nomor registrasi secara manual.
     * Form langkah 2–4 kemudian ditampilkan.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $proposal = Proposal::find($request->query('proposal'));

        if (! $proposal) {
            return redirect()->route('reviews.index');
        }

        $registrationNumber = $proposal->registration_number
            ?? strtoupper(trim((string) $request->query('registration_number')));

        return view('reviews.create', [
            'registrationNumber' => $registrationNumber,
            'proposal' => $proposal,
            'existingReview' => $proposal->reviews()->where('reviewer_id', $request->user()->id)->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->all();
        $proposal = Proposal::findOrFail($request->input('proposal_id'));

        if ($proposal->registration_number === null) {
            $number = strtoupper(trim((string) $request->input('registration_number')));

            try {
                $proposal->forceFill(['registration_number' => $number === '' ? null : $number])->save();
            } catch (UniqueConstraintViolationException) {
                return back()->withInput()->withErrors(['registration_number' => 'Nomor registrasi sudah dipakai proposal lain.']);
            }
        }

        $alreadyReviewed = $proposal->reviews()->where('reviewer_id', $request->user()->id)->exists();
        if ($alreadyReviewed) {
            return redirect()->route('proposals.show', $proposal)
                ->with('status', 'Anda sudah menilai proposal ini.');
        }

        $proposal->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'scores' => (array) ($data['scores'] ?? []),
            'values' => (array) ($data['values'] ?? []),
            'notes' => (array) ($data['notes'] ?? []),
            'recommendation_note' => $data['recommendation_note'] ?? null,
            'signed_at' => now(),
        ]);

        return redirect()->route('proposals.show', $proposal)
            ->with('status', 'Penilaian tersimpan.');
    }
}
