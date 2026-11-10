<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KaprodiController extends Controller
{
    public function index(): View
    {
        return view('kaprodi.index', [
            'pendingProposals' => Proposal::with('issuer')->whereNull('kaprodi_approved_at')->latest()->get(),
            'approvedProposals' => Proposal::with('issuer')->whereNotNull('kaprodi_approved_at')->latest('kaprodi_approved_at')->get(),
        ]);
    }

    public function approve(Proposal $proposal): RedirectResponse
    {
        // Update bersyarat agar persetujuan ganda tidak menimpa waktu persetujuan pertama.
        $approved = Proposal::whereKey($proposal->id)
            ->whereNull('kaprodi_approved_at')
            ->update(['kaprodi_approved_at' => now()]);

        return back()->with('status', $approved
            ? "Proposal \"{$proposal->title}\" disetujui dan diteruskan ke Sekretaris."
            : 'Proposal ini sudah disetujui sebelumnya.');
    }
}
