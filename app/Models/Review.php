<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reviewer_id', 'scores', 'values', 'notes', 'recommendation_note', 'signed_at'])]
class Review extends Model
{
    /**
     * Aspek substantif yang dinilai: key => [label, bobot (%)]. Total bobot 100%.
     *
     * @var array<string, array{0: string, 1: int}>
     */
    public const ASPECTS = [
        'latar_belakang' => ['Latar belakang dan perumusan masalah sosial/teknis yang ingin dipecahkan', 20],
        'metodologi' => ['Metodologi solusi yang ditawarkan', 15],
        'metode' => ['Metode pelaksanaan (sasaran jumlah peserta, adanya rencana survei pengukuran kepuasan dan peningkatan pengetahuan)', 20],
        'kebermanfaatan' => ['Peluang kebermanfaatan dan keberlanjutan', 15],
        'referensi' => ['Manajemen referensi', 10],
        'luaran' => ['Peluang luaran: HKI, publikasi jurnal/konferensi Abdimas', 20],
    ];

    /**
     * Nilai tiap aspek = skor (0–100) × bobot. Dibulatkan 2 desimal.
     *
     * @param  array<string, int|string>  $scores
     * @return array<string, float>
     */
    public static function valuesFromScores(array $scores): array
    {
        $values = [];

        foreach (self::ASPECTS as $key => [, $weight]) {
            $values[$key] = round((int) $scores[$key] * $weight / 100, 2);
        }

        return $values;
    }

    /**
     * Jumlah nilai semua aspek (0–100), atau null bila ada aspek tanpa nilai angka
     * (mis. penilaian lama sebelum aspek dan bobot diubah).
     */
    public function totalValue(): ?float
    {
        $total = 0;

        foreach (array_keys(self::ASPECTS) as $key) {
            $value = $this->values[$key] ?? null;

            if (! is_numeric($value)) {
                return null;
            }

            $total += $value;
        }

        return round($total, 2);
    }

    /**
     * Format angka gaya Indonesia tanpa nol desimal berlebih: 12.5 → "12,5".
     */
    public static function formatNumber(mixed $number): string
    {
        if (! is_numeric($number)) {
            return (string) ($number ?? '-');
        }

        return rtrim(rtrim(number_format((float) $number, 2, ',', '.'), '0'), ',');
    }

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
