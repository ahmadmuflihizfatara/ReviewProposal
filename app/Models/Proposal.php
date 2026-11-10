<?php

namespace App\Models;

use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
