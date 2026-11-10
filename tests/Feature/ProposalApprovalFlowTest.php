<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_issuer_submits_proposal_with_budget(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/proposals', ['title' => 'T', 'summary' => 'S', 'budget' => '15000000'])
            ->assertRedirect();

        $proposal = Proposal::sole();
        $this->assertSame(15_000_000, $proposal->budget);
        $this->assertSame('Menunggu Kaprodi', $proposal->approvalLabel());
    }

    public function test_full_approval_flow_deducts_available_budget(): void
    {
        $proposal = Proposal::factory()->create(['budget' => 30_000_000]);
        $kaprodi = User::factory()->kaprodi()->create();
        $sekretaris = User::factory()->sekretaris()->create();

        // Sekretaris belum bisa menyetujui sebelum Kaprodi.
        Budget::current()->update(['amount' => 100_000_000]);
        $this->actingAs($sekretaris)->post(route('sekretaris.approve', $proposal));
        $this->assertNull($proposal->fresh()->sekretaris_approved_at);

        $this->actingAs($kaprodi)->get(route('kaprodi.index'))->assertOk()->assertSee($proposal->title);
        $this->actingAs($kaprodi)->post(route('kaprodi.approve', $proposal))->assertRedirect();
        $this->assertNotNull($proposal->fresh()->kaprodi_approved_at);

        $this->actingAs($sekretaris)->get(route('sekretaris.index'))->assertOk()->assertSee($proposal->title)->assertSee('Rp 100.000.000');
        $this->actingAs($sekretaris)->post(route('sekretaris.approve', $proposal))->assertRedirect();
        $this->assertNotNull($proposal->fresh()->sekretaris_approved_at);
        $this->assertSame(70_000_000, Budget::current()->amount);

        // Persetujuan ganda tidak mengurangi anggaran dua kali.
        $this->actingAs($sekretaris)->post(route('sekretaris.approve', $proposal));
        $this->assertSame(70_000_000, Budget::current()->amount);
    }

    public function test_sekretaris_cannot_approve_when_budget_is_insufficient(): void
    {
        $proposal = Proposal::factory()->approvedByKaprodi()->create(['budget' => 50_000_000]);
        Budget::current()->update(['amount' => 10_000_000]);

        $this->actingAs(User::factory()->sekretaris()->create())
            ->post(route('sekretaris.approve', $proposal))
            ->assertSessionHas('status', fn (string $status) => str_contains($status, 'tidak mencukupi'));

        $this->assertNull($proposal->fresh()->sekretaris_approved_at);
        $this->assertSame(10_000_000, Budget::current()->amount);
    }

    public function test_sekretaris_updates_available_budget(): void
    {
        $this->actingAs(User::factory()->sekretaris()->create())
            ->put(route('sekretaris.budget'), ['amount' => '250000000'])
            ->assertRedirect();

        $this->assertSame(250_000_000, Budget::current()->amount);
    }

    public function test_reviewer_sees_all_proposals_regardless_of_approval(): void
    {
        $unapproved = Proposal::factory()->create();
        $kaprodiOnly = Proposal::factory()->approvedByKaprodi()->create();

        $this->actingAs(User::factory()->reviewer()->create())
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSee($unapproved->title)
            ->assertSee($kaprodiOnly->title)
            ->assertSee('Menunggu Kaprodi')
            ->assertSee('Menunggu Sekretaris');
    }

    public function test_approval_roles_are_enforced(): void
    {
        $proposal = Proposal::factory()->create();
        $issuer = User::factory()->create();
        $kaprodi = User::factory()->kaprodi()->create();
        $sekretaris = User::factory()->sekretaris()->create();

        $this->actingAs($issuer)->post(route('kaprodi.approve', $proposal))->assertForbidden();
        $this->actingAs($issuer)->put(route('sekretaris.budget'), ['amount' => 1])->assertForbidden();
        $this->actingAs($sekretaris)->post(route('kaprodi.approve', $proposal))->assertForbidden();
        $this->actingAs($kaprodi)->post(route('sekretaris.approve', $proposal))->assertForbidden();
        $this->actingAs($kaprodi)->get('/proposals/create')->assertForbidden();
        $this->actingAs($kaprodi)->get(route('proposals.show', $proposal))->assertOk();
        $this->actingAs($kaprodi)->get('/')->assertRedirect(route('kaprodi.index'));
        $this->actingAs($sekretaris)->get('/')->assertRedirect(route('sekretaris.index'));
    }
}
