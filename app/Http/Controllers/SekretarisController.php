<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SekretarisController extends Controller
{
    public function index(): View
    {
        return view('sekretaris.index', [
            'budget' => Budget::current(),
            'pendingProposals' => Proposal::with('issuer')
                ->whereNotNull('kaprodi_approved_at')
                ->whereNull('sekretaris_approved_at')
                ->latest('kaprodi_approved_at')
                ->get(),
            'approvedProposals' => Proposal::with('issuer')
                ->whereNotNull('sekretaris_approved_at')
                ->latest('sekretaris_approved_at')
                ->get(),
        ]);
    }

    public function updateBudget(Request $request): RedirectResponse
    {
        Budget::current()->update(['amount' => max(0, (int) $request->input('amount'))]);

        return back()->with('status', 'Anggaran tersedia diperbarui.');
    }

    /**
     * Setujui proposal bila anggaran tersedia mencukupi, lalu kurangi anggaran sebesar anggaran proposal.
     */
    public function approve(Proposal $proposal): RedirectResponse
    {
        $message = DB::transaction(function () use ($proposal): string {
            $budget = Budget::current(lockForUpdate: true);

            if ($proposal->budget > $budget->amount) {
                return "Anggaran tidak mencukupi: diajukan {$proposal->budgetLabel()}, tersedia {$budget->label()}.";
            }

            $approved = Proposal::whereKey($proposal->id)
                ->whereNotNull('kaprodi_approved_at')
                ->whereNull('sekretaris_approved_at')
                ->update(['sekretaris_approved_at' => now()]);

            if (! $approved) {
                return 'Proposal belum disetujui Kaprodi atau sudah disetujui Sekretaris.';
            }

            $budget->decrement('amount', $proposal->budget);

            return "Proposal \"{$proposal->title}\" disetujui. Sisa anggaran {$budget->label()}.";
        });

        return back()->with('status', $message);
    }
}
