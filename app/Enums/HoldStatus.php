<?php

namespace App\Enums;

enum HoldStatus: string
{
    case Active = 'active';
    case Released = 'released';
    case Converted = 'converted';
    case Expired = 'expired';
}
