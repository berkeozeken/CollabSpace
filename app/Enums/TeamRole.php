<?php

namespace App\Enums;

enum TeamRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case MEMBER = 'member';

    public static function values(): array
    {
        return array_map(fn(self $r) => $r->value, self::cases());
    }
}
