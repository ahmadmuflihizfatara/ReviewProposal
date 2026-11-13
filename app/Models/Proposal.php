<?php

namespace App\Models;

use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'summary', 'budget', 'file_path'])]
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    /**
     * Format nomor registrasi PL{prodi}/{No}/{Bulan Romawi}/{Tahun}, mis. PLTI/001/XI/2026.
     * Dipakai di validasi server (regex) dan atribut pattern HTML.
     */
    public const REGISTRATION_NUMBER_PATTERN = 'PL[A-Z]{2,5}/(?!000)[0-9]{3}/(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)/(19|20)[0-9]{2}';

    public const MIN_BUDGET = 100_000;

    public const MAX_BUDGET = 1_000_000_000;

    /**
     * Proposal yang sudah disetujui Kaprodi lalu Sekretaris dan boleh dinilai reviewer.
     *
     * @param  Builder<Proposal>  $query
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->whereNotNull('kaprodi_approved_at')->whereNotNull('sekretaris_approved_at');
    }

    public function isApproved(): bool
    {
        return $this->kaprodi_approved_at !== null && $this->sekretaris_approved_at !== null;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'integer',
            'kaprodi_approved_at' => 'datetime',
            'sekretaris_approved_at' => 'datetime',
        ];
    }

    public function budgetLabel(): string
    {
        return Budget::rupiah($this->budget);
    }

    /**
     * Alur persetujuan: Kaprodi → Sekretaris → siap direview.
     */
    public function approvalLabel(): string
    {
        return match (true) {
            $this->sekretaris_approved_at !== null => 'Disetujui Sekretaris',
            $this->kaprodi_approved_at !== null => 'Menunggu Sekretaris',
            default => 'Menunggu Kaprodi',
        };
    }

    /**
     * Nomor registrasi diisi manual oleh reviewer; sebelum itu proposal belum teregistrasi.
     */
    public function registrationLabel(): string
    {
        return $this->registration_number ?? 'Belum teregistrasi';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
