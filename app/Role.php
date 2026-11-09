<?php

namespace App;

enum Role: string
{
    case Reviewer = 'reviewer';
    case Issuer = 'issuer';

    public function label(): string
    {
        return match ($this) {
            self::Reviewer => 'Reviewer',
            self::Issuer => 'Pengusul',
        };
    }
}
