<?php

namespace App\Enums;

enum TenantRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function label(): string
    {
        return match($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
        };
    }
}
