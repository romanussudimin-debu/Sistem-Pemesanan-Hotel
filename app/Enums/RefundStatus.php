<?php

// app/Enums/RefundStatus.php

namespace App\Enums;

enum RefundStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Processed = 'processed';

    public function label(): string
    {
        return __("enums.refund_status.{$this->value}");
    }
}
