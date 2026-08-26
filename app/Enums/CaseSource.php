<?php

declare(strict_types=1);

namespace App\Enums;

enum CaseSource: string
{
    case Staff = 'staff';
    case MobileApp = 'mobile_app';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'AC Office Staff',
            self::MobileApp => 'Mobile App',
        };
    }
}
