<?php

// lang/en/messages.php

return [
    'room_just_sold_out' => 'Sorry, the :room room was just booked by another guest. Latest availability is shown.',
    'single_branch_only' => 'A booking may only cover one branch.',
    'cannot_cancel' => 'This booking cannot be cancelled.',
    'cannot_check_in' => 'Check-in is only possible for paid bookings on the stay dates.',
    'cannot_check_out' => 'Check-out is only possible for checked-in guests.',
    'not_payable' => 'This booking cannot be paid (already paid, cancelled, or past the payment deadline).',
    'invalid_callback' => 'Invalid payment callback.',
    'payment_already_processed' => 'This payment has already been processed by another admin.',
    'cancelled_after_rejections' => 'Automatically cancelled after the payment receipt was rejected 3 times.',
    'cannot_delete_in_use' => ':item cannot be deleted because it has booking history. Deactivate it instead.',
    'rate_overlap' => 'The rate period overlaps the existing ":label" rate.',

    'reservation_created' => 'Booking :code was created. Please complete payment before the deadline.',
    'proof_uploaded' => 'Transfer receipt received and awaiting admin verification.',
    'payment_success' => 'Payment successful. Your booking is now paid.',
    'payment_failed' => 'Payment failed. Please try again or choose another method.',
    'reservation_cancelled' => 'Booking :code has been cancelled.',
    'payment_approved' => 'Payment approved. Booking :code is now paid.',
    'payment_rejected' => 'Payment rejected.',
    'checked_in' => 'Guest of booking :code has checked in.',
    'checked_out' => 'Guest of booking :code has checked out.',
    'saved' => 'Saved successfully.',
    'deleted' => 'Deleted successfully.',
    'locale_changed' => 'Language changed.',
];
