<?php

// app/Http/Controllers/Admin/RoomRateController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRateRequest;
use App\Models\RoomRate;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** A-09 Kelola tarif musiman (FR-26). Reservasi lama tidak terpengaruh karena tarif disimpan sebagai snapshot. */
class RoomRateController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.room-rates.index', [
            'rates' => RoomRate::with('roomType.branch')
                ->when($request->integer('room_type_id'), fn ($q, $id) => $q->where('room_type_id', $id))
                ->orderByDesc('start_date')->paginate(20)->withQueryString(),
            'roomTypes' => RoomType::with('branch')->orderBy('branch_id')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new RoomRate(['room_type_id' => $request->integer('room_type_id') ?: null]));
    }

    public function store(RoomRateRequest $request): RedirectResponse
    {
        RoomRate::create($request->validated());

        return redirect()->route('admin.room-rates.index')->with('success', __('messages.saved'));
    }

    public function edit(RoomRate $roomRate): View
    {
        return $this->form($roomRate);
    }

    public function update(RoomRateRequest $request, RoomRate $roomRate): RedirectResponse
    {
        $roomRate->update($request->validated());

        return redirect()->route('admin.room-rates.index')->with('success', __('messages.saved'));
    }

    public function destroy(RoomRate $roomRate): RedirectResponse
    {
        $roomRate->delete();

        return redirect()->route('admin.room-rates.index')->with('success', __('messages.deleted'));
    }

    private function form(RoomRate $rate): View
    {
        return view('admin.room-rates.form', [
            'rate' => $rate,
            'roomTypes' => RoomType::with('branch')->orderBy('branch_id')->orderBy('name')->get(),
        ]);
    }
}
