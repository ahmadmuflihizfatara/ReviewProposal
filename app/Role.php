<?php

namespace App;

enum Role: string
{
    case Reviewer = 'reviewer';
    case Issuer = 'issuer';
    case Kaprodi = 'kaprodi';
    case Sekretaris = 'sekretaris';

    public function label(): string
    {
        return match ($this) {
            self::Reviewer => 'Reviewer',
            self::Issuer => 'Pengusul',
            self::Kaprodi => 'Kaprodi',
            self::Sekretaris => 'Sekretaris',
        };
    }

    /**
     * Nama route halaman utama tiap peran.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Reviewer => 'reviews.index',
            self::Issuer => 'proposals.index',
            self::Kaprodi => 'kaprodi.index',
            self::Sekretaris => 'sekretaris.index',
        };
    }
}
