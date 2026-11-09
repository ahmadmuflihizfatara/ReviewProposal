<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reviewer_id', 'scores', 'values', 'notes', 'recommendation_note', 'signed_at'])]
class Review extends Model
{
    /**
     * Aspek substantif yang dinilai: key => [label, bobot (%)].
     *
     * @var array<string, array{0: string, 1: int}>
     */
    public const ASPECTS = [
        'pendahuluan' => ['Pendahuluan: latar belakang, rumusan masalah, dan tujuan', 15],
        'tinjauan_pustaka' => ['Tinjauan pustaka dan kebaruan gagasan', 15],
        'metode' => ['Metode pelaksanaan', 25],
        'luaran' => ['Potensi luaran dan manfaat', 20],
        'biaya_jadwal' => ['Kewajaran biaya dan jadwal kegiatan', 15],
        'penulisan' => ['Sistematika dan kualitas penulisan', 10],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'values' => 'array',
            'notes' => 'array',
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
