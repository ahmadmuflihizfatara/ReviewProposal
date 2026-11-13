<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Review;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('reviews.index', [
            'reviews' => $user->reviews()->with('proposal')->latest()->get(),
            'pendingProposals' => Proposal::approved()
                ->whereDoesntHave('reviews', fn ($query) => $query->where('reviewer_id', $user->id))
                ->latest('sekretaris_approved_at')
                ->get(),
            'awaitingApprovalCount' => Proposal::whereNull('sekretaris_approved_at')->count(),
        ]);
    }

    /**
     * Langkah 1: reviewer mengisi nomor registrasi. Langkah 2–4 baru tampil
     * setelah nomor registrasi valid, sehingga alur penilaian berjalan berurutan.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $proposal = Proposal::find($request->query('proposal'));

        if (! $proposal) {
            return redirect()->route('reviews.index');
        }

        if (! $proposal->isApproved()) {
            return $this->notApproved();
        }

        $registrationNumber = $proposal->registration_number
            ?? strtoupper(trim((string) $request->query('registration_number')));
        $registrationError = null;

        if ($proposal->registration_number === null && $request->has('registration_number')) {
            $registrationError = Validator::make(
                ['registration_number' => $registrationNumber],
                ['registration_number' => $this->registrationNumberRules($proposal)],
            )->errors()->first('registration_number') ?: null;
        }

        $registrationReady = $proposal->registration_number !== null
            || ($request->has('registration_number') && $registrationError === null);

        return view('reviews.create', [
            'registrationNumber' => $registrationNumber,
            'registrationError' => $registrationError,
            'registrationReady' => $registrationReady,
            'proposal' => $proposal,
            'existingReview' => $proposal->reviews()->where('reviewer_id', $request->user()->id)->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['registration_number' => strtoupper(trim((string) $request->input('registration_number')))]);
        $request->validate(['proposal_id' => ['required', 'integer', 'exists:proposals,id']]);
        $proposal = Proposal::findOrFail($request->input('proposal_id'));

        if (! $proposal->isApproved()) {
            return $this->notApproved();
        }

        if ($proposal->reviews()->where('reviewer_id', $request->user()->id)->exists()) {
            return redirect()->route('proposals.show', $proposal)
                ->with('status', 'Anda sudah menilai proposal ini.');
        }

        $data = $request->validate([
            'registration_number' => $proposal->registration_number === null ? $this->registrationNumberRules($proposal) : ['nullable'],
            ...$this->aspectRules(),
            'recommendation_note' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($proposal->registration_number === null) {
            try {
                $proposal->forceFill(['registration_number' => $data['registration_number']])->save();
            } catch (UniqueConstraintViolationException) {
                return back()->withInput()->withErrors(['registration_number' => 'Nomor registrasi sudah dipakai proposal lain.']);
            }
        }

        $proposal->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'scores' => array_map('intval', $data['scores']),
            // Nilai tidak diterima dari input: selalu dihitung dari skor × bobot.
            'values' => Review::valuesFromScores($data['scores']),
            'notes' => $data['notes'],
            'recommendation_note' => $data['recommendation_note'],
            'signed_at' => now(),
        ]);

        return redirect()->route('proposals.show', $proposal)
            ->with('status', 'Penilaian tersimpan.');
    }

    /**
     * @return array<int, mixed>
     */
    private function registrationNumberRules(Proposal $proposal): array
    {
        return [
            'required',
            'string',
            'regex:#^'.Proposal::REGISTRATION_NUMBER_PATTERN.'$#',
            Rule::unique('proposals', 'registration_number')->ignore($proposal),
        ];
    }

    /**
     * Skor tiap aspek: bilangan bulat 0–100. Catatan tiap aspek: 10–2000 karakter.
     *
     * @return array<string, array<int, string>>
     */
    private function aspectRules(): array
    {
        $aspectKeys = 'array:'.implode(',', array_keys(Review::ASPECTS));
        $rules = [
            'scores' => ['required', $aspectKeys],
            'notes' => ['required', $aspectKeys],
        ];

        foreach (array_keys(Review::ASPECTS) as $key) {
            $rules["scores.$key"] = ['required', 'integer', 'between:0,100'];
            $rules["notes.$key"] = ['required', 'string', 'min:10', 'max:2000'];
        }

        return $rules;
    }

    private function notApproved(): RedirectResponse
    {
        return redirect()->route('reviews.index')
            ->with('status', 'Proposal belum disetujui Kaprodi dan Sekretaris, sehingga belum bisa dinilai.');
    }
}
