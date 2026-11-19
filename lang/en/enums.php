<?php

// lang/en/enums.php

return [
    'user_role' => ['admin' => 'Admin', 'customer' => 'Customer'],
    'room_status' => ['available' => 'Available', 'maintenance' => 'Maintenance', 'inactive' => 'Inactive'],
    'payment_method' => ['gateway' => 'Payment Gateway (Simulated)', 'bank_transfer' => 'Bank Transfer'],
    'payment_status' => [
        'pending' => 'Pending', 'submitted' => 'Awaiting Verification', 'approved' => 'Approved',
        'rejected' => 'Rejected', 'failed' => 'Failed', 'cancelled' => 'Cancelled',
    ],
    'refund_status' => ['none' => 'None', 'pending' => 'Pending', 'processed' => 'Processed'],
    'reservation_status' => [
        'pending_payment' => 'Awaiting Payment', 'awaiting_verification' => 'Awaiting Verification', 'paid' => 'Paid',
        'checked_in' => 'Checked-in', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'expired' => 'Expired',
    ],
];
