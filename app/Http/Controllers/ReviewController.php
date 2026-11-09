<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Review;
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
     * Bila valid, form langkah 2–4 ditampilkan.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $proposal = Proposal::find($request->query('proposal'));

        if (! $proposal) {
            return redirect()->route('reviews.index');
        }

        $registrationNumber = strtoupper(trim((string) $request->query('registration_number')));
        $registrationError = null;

        if ($proposal->registration_number) {
            $registrationNumber = $proposal->registration_number;
        } elseif ($registrationNumber === '') {
            $registrationError = 'Nomor registrasi wajib diisi.';
        } elseif (Proposal::where('registration_number', $registrationNumber)->exists()) {
            $registrationError = "Nomor registrasi “{$registrationNumber}” sudah dipakai proposal lain.";
        }

        $proposalReady = $registrationError === null;

        return view('reviews.create', [
            'registrationNumber' => $registrationNumber,
            'registrationError' => $registrationError,
            'proposal' => $proposal,
            'proposalReady' => $proposalReady,
            'existingReview' => $proposal->reviews()->where('reviewer_id', $request->user()->id)->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $aspectRules = [];
        foreach (array_keys(Review::ASPECTS) as $key) {
            $aspectRules["scores.$key"] = ['nullable', 'string', 'max:255'];
            $aspectRules["values.$key"] = ['nullable', 'string', 'max:255'];
            $aspectRules["notes.$key"] = ['nullable', 'string', 'max:2000'];
        }

        $data = $request->validate([
            'proposal_id' => ['required', 'integer', 'exists:proposals,id'],
            'registration_number' => ['required', 'string', 'max:50'],
            ...$aspectRules,
            'recommendation_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $proposal = Proposal::findOrFail($data['proposal_id']);

        if ($proposal->registration_number === null) {
            $number = strtoupper(trim($data['registration_number']));
            $taken = Proposal::where('registration_number', $number)->exists();
            if ($taken) {
                return back()->withInput()->withErrors(['registration_number' => 'Nomor registrasi sudah dipakai proposal lain.']);
            }
            $proposal->forceFill(['registration_number' => $number])->save();
        }

        $alreadyReviewed = $proposal->reviews()->where('reviewer_id', $request->user()->id)->exists();
        if ($alreadyReviewed) {
            return redirect()->route('proposals.show', $proposal)
                ->with('status', 'Anda sudah menilai proposal ini.');
        }

        $proposal->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'scores' => $data['scores'] ?? [],
            'values' => $data['values'] ?? [],
            'notes' => $data['notes'] ?? [],
            'recommendation_note' => $data['recommendation_note'] ?? null,
            'signed_at' => now(),
        ]);

        return redirect()->route('proposals.show', $proposal)
            ->with('status', 'Penilaian tersimpan.');
    }
}
