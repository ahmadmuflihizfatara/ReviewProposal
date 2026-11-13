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

    /**
     * @return array<string, mixed>
     */
    private function validReview(Proposal $proposal, string $registrationNumber = 'PLTI/001/XI/2026'): array
    {
        $keys = array_keys(Review::ASPECTS);

        return [
            'proposal_id' => $proposal->id,
            'registration_number' => $registrationNumber,
            'scores' => array_fill_keys($keys, '80'),
            'notes' => array_fill_keys($keys, 'Cukup baik dan jelas.'),
            'recommendation_note' => 'Layak didanai dengan perbaikan minor.',
        ];
    }

    public function test_user_can_register_with_reviewer_role(): void
    {
        $this->post('/register', [
            'name' => 'Rina Wati',
            'email' => 'Rina@Example.com',
            'role' => 'reviewer',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame(Role::Reviewer, User::firstWhere('email', 'rina@example.com')->role);
        $this->get('/')->assertRedirect(route('reviews.index'));
    }

    public function test_registration_rejects_invalid_input(): void
    {
        $existing = User::factory()->create();

        $this->post('/register', [
            'name' => 'X1',
            'email' => 'bukan email',
            'role' => 'admin',
            'password' => 'lemah',
            'password_confirmation' => 'beda',
        ])->assertSessionHasErrors(['name', 'email', 'role', 'password']);

        $this->post('/register', [
            'name' => 'Budi',
            'email' => $existing->email,
            'role' => 'issuer',
            'password' => 'semuakecil1',
            'password_confirmation' => 'semuakecil1',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
        $this->assertSame(1, User::count());
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $this->post('/login', ['email' => 'bukan email', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_issuer_submits_proposal_with_valid_input(): void
    {
        Storage::fake();
        $issuer = User::factory()->create();

        $this->actingAs($issuer)->post('/proposals', [
            'title' => 'Sistem Irigasi Pintar',
            'summary' => str_repeat('Ringkasan proposal. ', 5),
            'budget' => '15000000',
            'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $proposal = Proposal::sole();
        $this->assertNull($proposal->registration_number);
        $this->assertSame(15_000_000, $proposal->budget);
        Storage::assertExists($proposal->file_path);
    }

    public function test_proposal_submission_rejects_invalid_input(): void
    {
        Storage::fake();

        $this->actingAs(User::factory()->create())->post('/proposals', [
            'title' => 'Pendek',
            'summary' => 'Terlalu singkat.',
            'budget' => '50000',
            'file' => UploadedFile::fake()->create('proposal.docx', 100, 'application/msword'),
        ])->assertSessionHasErrors(['title', 'summary', 'budget', 'file']);

        $this->post('/proposals', [
            'title' => 'Judul yang cukup panjang',
            'summary' => str_repeat('a', 60),
            'budget' => '2000000000',
        ])->assertSessionHasErrors(['budget', 'file']);

        $this->assertSame(0, Proposal::count());
    }

    public function test_reviewer_completes_sequential_review(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $proposal = Proposal::factory()->approved()->create();

        // Langkah 1 belum diisi: langkah 2–4 belum tampil.
        $this->actingAs($reviewer)
            ->get(route('reviews.create', ['proposal' => $proposal->id]))
            ->assertOk()
            ->assertDontSee('2. Skor untuk setiap aspek');

        // Nomor registrasi salah format: langkah 2–4 tetap tertutup.
        $this->get(route('reviews.create', ['proposal' => $proposal->id, 'registration_number' => 'PRP-2026-0001']))
            ->assertOk()
            ->assertSee('Format nomor registrasi')
            ->assertDontSee('2. Skor untuk setiap aspek');

        // Nomor valid (huruf kecil dikapitalkan): langkah 2–4 terbuka.
        $this->get(route('reviews.create', ['proposal' => $proposal->id, 'registration_number' => 'pltI/001/xi/2026']))
            ->assertOk()
            ->assertSee('PLTI/001/XI/2026')
            ->assertSee('2. Skor untuk setiap aspek');

        // Nilai dari input diabaikan: selalu dihitung server dari skor × bobot.
        $input = $this->validReview($proposal, 'pltI/001/xi/2026');
        $input['scores']['metodologi'] = '85';
        $input['values'] = array_fill_keys(array_keys(Review::ASPECTS), '100');

        $this->post('/reviews', $input)
            ->assertRedirect(route('proposals.show', $proposal));

        $this->assertSame('PLTI/001/XI/2026', $proposal->fresh()->registration_number);
        $review = Review::sole();
        $this->assertSame(80, $review->scores['metode']);
        $this->assertEquals(16, $review->values['latar_belakang']);  // 80 × 20%
        $this->assertEquals(12.75, $review->values['metodologi']);  // 85 × 15%
        $this->assertEquals(8, $review->values['referensi']);       // 80 × 10%
        $this->assertEquals(80.75, $review->totalValue());
        $this->assertNotNull($review->signed_at);

        // Tidak bisa menilai dua kali.
        $this->post('/reviews', $this->validReview($proposal));
        $this->assertSame(1, Review::count());

        // Pengusul melihat hasil penilaian.
        $this->actingAs($proposal->issuer)
            ->get(route('proposals.show', $proposal))
            ->assertOk()
            ->assertSee('Layak didanai dengan perbaikan minor.')
            ->assertSee('12,75')
            ->assertSee('80,75');
    }

    public function test_review_rejects_out_of_range_scores_and_empty_notes(): void
    {
        $proposal = Proposal::factory()->approved()->create();
        $input = $this->validReview($proposal);
        $input['scores']['metode'] = '101';
        $input['scores']['luaran'] = '-1';
        $input['scores']['referensi'] = '12.5';
        $input['notes']['metode'] = '';
        $input['recommendation_note'] = '';

        $this->actingAs(User::factory()->reviewer()->create())
            ->post('/reviews', $input)
            ->assertSessionHasErrors(['scores.metode', 'scores.luaran', 'scores.referensi', 'notes.metode', 'recommendation_note']);

        $this->assertSame(0, Review::count());
        $this->assertNull($proposal->fresh()->registration_number);
    }

    public function test_registration_number_must_match_format_and_be_unique(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        Proposal::factory()->approved()->create(['registration_number' => 'PLTI/001/XI/2026']);
        $proposal = Proposal::factory()->approved()->create();

        foreach (['PLTI/001/XI/2026', 'PLTI/000/XI/2026', 'PLTI/001/13/2026', 'TI/001/XI/2026', 'PLT/001/XI/2026'] as $number) {
            $this->actingAs($reviewer)
                ->post('/reviews', $this->validReview($proposal, $number))
                ->assertSessionHasErrors('registration_number');
        }

        $this->assertNull($proposal->fresh()->registration_number);
        $this->assertSame(0, Review::count());
    }

    public function test_reviewer_cannot_review_unapproved_proposal(): void
    {
        $reviewer = User::factory()->reviewer()->create();

        foreach ([Proposal::factory()->create(), Proposal::factory()->approvedByKaprodi()->create()] as $proposal) {
            $this->actingAs($reviewer)
                ->get(route('reviews.create', ['proposal' => $proposal->id]))
                ->assertRedirect(route('reviews.index'));

            $this->post('/reviews', $this->validReview($proposal))->assertRedirect(route('reviews.index'));
        }

        $this->assertSame(0, Review::count());
        $this->get(route('reviews.index'))->assertOk()->assertSee('2 proposal lain masih menunggu persetujuan');
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

    public function test_docs_page_lists_validation_rules(): void
    {
        $this->get(route('docs'))
            ->assertOk()
            ->assertSee('PLTI/001/XI/2026')
            ->assertSee('Bilangan bulat 0–100')
            ->assertSee('Manajemen referensi')
            ->assertSee('skor × bobot')
            ->assertSee('8–64 karakter');
    }
}
