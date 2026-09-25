<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Customer = 'customer';

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin || $this === self::SuperAdmin;
    }

    public function isCustomer(): bool
    {
        return $this === self::Customer;
    }
}
