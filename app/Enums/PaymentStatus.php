<?php

// app/Enums/PaymentStatus.php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';       // gateway: menunggu callback
    case Submitted = 'submitted';   // transfer: bukti diunggah, menunggu verifikasi
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("enums.payment_status.{$this->value}");
    }
}
