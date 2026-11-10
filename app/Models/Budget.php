<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Anggaran yang tersedia untuk mendanai proposal. Hanya ada satu baris.
 */
#[Fillable(['amount'])]
class Budget extends Model
{
    /**
     * Ambil (atau buat) satu-satunya baris anggaran.
     */
    public static function current(bool $lockForUpdate = false): self
    {
        return static::query()->when($lockForUpdate, fn ($query) => $query->lockForUpdate())->firstOrCreate([], ['amount' => 0]);
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    public function label(): string
    {
        return self::rupiah($this->amount);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}
