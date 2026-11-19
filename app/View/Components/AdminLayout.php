<?php

// app/View/Components/AdminLayout.php

namespace App\View\Components;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\View\Component;
use Illuminate\View\View;

class AdminLayout extends Component
{
    public function __construct(public string $title = '') {}

    public function render(): View
    {
        return view('layouts.admin', [
            // Badge antrean verifikasi di sidebar (W-07)
            'pendingVerifications' => Payment::where('status', PaymentStatus::Submitted)->count(),
        ]);
    }
}
