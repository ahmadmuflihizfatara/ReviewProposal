<?php

namespace Tests\Feature;

use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use App\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProposalReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_reviewer_role(): void
    {
        $this->post('/register', [
            'name' => 'Rina',
            'email' => 'rina@example.com',
            'role' => 'reviewer',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(Role::Reviewer, User::firstWhere('email', 'rina@example.com')->role);
        $this->get('/')->assertRedirect(route('reviews.index'));
    }

    public function test_account_can_register_and_login_with_free_input(): void
    {
        $this->post('/register', ['name' => 'X', 'email' => 'bukan email', 'role' => 'issuer', 'password' => ''])
            ->assertRedirect(route('dashboard'));
        $this->post('/logout');

        $this->post('/login', ['email' => 'bukan email', 'password' => ''])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_issuer_submits_proposal_without_registration_number(): void
    {
        Storage::fake();
        $issuer = User::factory()->create();

        $this->actingAs($issuer)->post('/proposals', [
            'title' => 'Sistem Irigasi Pintar',
            'summary' => 'Ringkasan proposal.',
            'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $proposal = Proposal::sole();
        $this->assertNull($proposal->registration_number);
        Storage::assertExists($proposal->file_path);
    }

    public function test_issuer_can_submit_proposal_without_file(): void
    {
        $this->actingAs(User::factory()->create())->post('/proposals', ['title' => 'T', 'summary' => 'S'])
            ->assertSessionHasNoErrors();

        $proposal = Proposal::sole();
        $this->assertNull($proposal->file_path);
        $this->get(route('proposals.show', $proposal))->assertOk()->assertDontSee('Unduh berkas');
        $this->get(route('proposals.file', $proposal))->assertNotFound();
    }

    public function test_reviewer_completes_four_step_review(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $proposal = Proposal::factory()->create();

        // Langkah 1: pilih proposal dan isi nomor registrasi manual.
        $this->actingAs($reviewer)
            ->get(route('reviews.create', ['proposal' => $proposal->id, 'registration_number' => 'prp-2026-0001']))
            ->assertOk()
            ->assertSee($proposal->title)
            ->assertSee('PRP-2026-0001');

        // Langkah 2–4: skor, catatan, rekomendasi.
        $scores = array_fill_keys(array_keys(Review::ASPECTS), '5');
        $scores['metode'] = '7';

        $this->post('/reviews', [
            'proposal_id' => $proposal->id,
            'registration_number' => 'prp-2026-0001',
            'scores' => $scores,
            'values' => array_fill_keys(array_keys(Review::ASPECTS), 'A+'),
            'notes' => array_fill_keys(array_keys(Review::ASPECTS), 'Cukup baik.'),
            'recommendation_note' => 'Layak didanai',
        ])->assertRedirect(route('proposals.show', $proposal));

        $this->assertSame('PRP-2026-0001', $proposal->fresh()->registration_number);
        $review = Review::sole();
        $this->assertSame('7', $review->scores['metode']);
        $this->assertSame('A+', $review->values['metode']);
        $this->assertSame($reviewer->id, $review->reviewer_id);
        $this->assertNotNull($review->signed_at);

        // Tidak bisa menilai dua kali.
        $this->post('/reviews', [
            'proposal_id' => $proposal->id,
            'registration_number' => 'PRP-2026-0001',
            'scores' => $scores,
            'notes' => array_fill_keys(array_keys(Review::ASPECTS), 'Lagi.'),
            'recommendation_note' => 'Layak didanai',
        ]);
        $this->assertSame(1, Review::count());

        // Pengusul melihat hasil penilaian.
        $this->actingAs($proposal->issuer)
            ->get(route('proposals.show', $proposal))
            ->assertOk()
            ->assertSee('Layak didanai')
            ->assertSee('A+');
    }

    public function test_review_accepts_any_input_and_empty_notes(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $proposal = Proposal::factory()->create();

        $this->actingAs($reviewer)->post('/reviews', [
            'proposal_id' => $proposal->id,
            'registration_number' => 'PRP-2026-0002',
            'scores' => array_fill_keys(array_keys(Review::ASPECTS), 'abc'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('abc', Review::sole()->scores['metode']);
    }

    public function test_duplicate_registration_number_and_email_are_reported_not_crashed(): void
    {
        $taken = Proposal::factory()->registered()->create();
        $proposal = Proposal::factory()->create();

        $this->actingAs(User::factory()->reviewer()->create())
            ->post('/reviews', ['proposal_id' => $proposal->id, 'registration_number' => $taken->registration_number])
            ->assertSessionHasErrors('registration_number');
        $this->assertNull($proposal->fresh()->registration_number);

        $user = User::factory()->create();
        $this->post('/logout');
        $this->post('/register', ['name' => 'X', 'email' => $user->email, 'role' => 'issuer'])
            ->assertSessionHasErrors('email');
    }

    public function test_roles_are_enforced(): void
    {
        $issuer = User::factory()->create();
        $otherProposal = Proposal::factory()->create();
        $reviewer = User::factory()->reviewer()->create();

        $this->actingAs($issuer)->get('/reviews/create')->assertForbidden();
        $this->actingAs($issuer)->get(route('proposals.show', $otherProposal))->assertForbidden();
        $this->actingAs($reviewer)->get('/proposals/create')->assertForbidden();
    }
}
