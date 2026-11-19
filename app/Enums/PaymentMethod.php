<?php

// app/Enums/PaymentMethod.php

namespace App\Enums;

enum PaymentMethod: string
{
    case Gateway = 'gateway';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return __("enums.payment_method.{$this->value}");
    }
}
