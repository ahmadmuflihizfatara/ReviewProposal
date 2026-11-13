<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        return view('proposals.index', [
            'proposals' => $request->user()->proposals()->withCount('reviews')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('proposals.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:10', 'max:255'],
            'summary' => ['required', 'string', 'min:50', 'max:5000'],
            'budget' => ['required', 'integer', 'between:'.Proposal::MIN_BUDGET.','.Proposal::MAX_BUDGET],
            'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ]);

        $proposal = $request->user()->proposals()->create([
            'title' => $data['title'],
            'summary' => $data['summary'],
            'budget' => (int) $data['budget'],
            'file_path' => $request->file('file')->store('proposals'),
        ]);

        return redirect()->route('proposals.show', $proposal)
            ->with('status', 'Proposal diajukan dan menunggu persetujuan Kaprodi.');
    }

    public function show(Request $request, Proposal $proposal): View
    {
        Gate::authorize('view-proposal', $proposal);

        $user = $request->user();

        // Pengusul melihat semua penilaian; reviewer hanya penilaiannya sendiri.
        $reviews = $proposal->reviews()
            ->with('reviewer')
            ->when($user->isReviewer(), fn ($query) => $query->where('reviewer_id', $user->id))
            ->latest()
            ->get();

        return view('proposals.show', [
            'proposal' => $proposal->load('issuer'),
            'reviews' => $reviews,
        ]);
    }

    public function download(Proposal $proposal): StreamedResponse
    {
        Gate::authorize('view-proposal', $proposal);
        abort_if($proposal->file_path === null, 404);

        return Storage::download($proposal->file_path, ($proposal->registration_number ?? "proposal-{$proposal->id}").'.pdf');
    }
}
