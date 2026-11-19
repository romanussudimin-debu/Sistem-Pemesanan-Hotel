<?php

// lang/en/notifications.php

return [
    'greeting' => 'Hello, :name!',
    'reason' => 'Reason: :reason',
    'action_view' => 'View Booking',
    'action_verify' => 'Verify Now',
    'created' => [
        'subject' => 'Booking :code is awaiting payment',
        'line' => 'Your booking at :branch (:check_in – :check_out) was created. Total :total. Please complete payment before :deadline.',
    ],
    'paid' => [
        'subject' => 'Booking :code is paid',
        'line' => 'We have received payment for your booking at :branch (:check_in – :check_out). Your voucher is available on the booking page.',
    ],
    'proof_submitted' => [
        'subject' => '[Admin] New transfer receipt for :code',
        'line' => 'A customer uploaded a :total transfer receipt for booking :code at :branch. Please verify within 24 hours.',
    ],
    'proof_rejected' => [
        'subject' => 'Payment receipt for :code rejected',
        'line' => 'We could not accept the transfer receipt for booking :code. Please upload a new one before :deadline.',
    ],
    'cancelled' => [
        'subject' => 'Booking :code cancelled',
        'line' => 'Your booking at :branch (:check_in – :check_out) has been cancelled. Refund amount: :refund.',
    ],
    'expired' => [
        'subject' => 'Booking :code expired',
        'line' => 'Your booking at :branch expired because payment was not received before the deadline. The room has been released.',
    ],
];
