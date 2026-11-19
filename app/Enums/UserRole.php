<?php

// app/Enums/UserRole.php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';

    public function label(): string
    {
        return __("enums.user_role.{$this->value}");
    }
}
