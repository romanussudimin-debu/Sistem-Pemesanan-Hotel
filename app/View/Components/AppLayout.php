<?php

// app/View/Components/AppLayout.php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/** Layout publik & pelanggan (navbar atas + footer). */
class AppLayout extends Component
{
    public function __construct(public string $title = '') {}

    public function render(): View
    {
        return view('layouts.app');
    }
}
