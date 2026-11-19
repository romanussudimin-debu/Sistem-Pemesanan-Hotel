<?php

// app/Http/Controllers/Admin/RoomController.php

namespace App\Http\Controllers\Admin;

use App\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomRequest;
use App\Models\Branch;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** A-07 Kelola kamar (FR-24, FR-27, W-08). */
class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
            'status' => ['nullable', Rule::enum(RoomStatus::class)],
            'q' => ['nullable', 'string', 'max:10'],
        ]);

        $rooms = Room::with('roomType.branch')
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->whereHas('roomType', fn ($t) => $t->where('branch_id', $v)))
            ->when($filters['room_type_id'] ?? null, fn ($q, $v) => $q->where('room_type_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('number', 'like', "%{$v}%"))
            ->join('room_types', 'room_types.id', '=', 'rooms.room_type_id')
            ->orderBy('room_types.branch_id')->orderBy('rooms.number')
            ->select('rooms.*')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rooms.index', [
            'rooms' => $rooms,
            'filters' => $filters,
            'branches' => Branch::orderBy('name')->get(),
            'roomTypes' => RoomType::orderBy('name')->get(['id', 'name', 'branch_id']),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Room(['status' => RoomStatus::Available]));
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        Room::create($request->validated());

        return redirect()->route('admin.rooms.index')->with('success', __('messages.saved'));
    }

    public function edit(Room $room): View
    {
        return $this->form($room->load('roomType'));
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return redirect()->route('admin.rooms.index')->with('success', __('messages.saved'));
    }

    public function destroy(Room $room): RedirectResponse
    {
        if ($room->hasBookingHistory()) { // FR-27: sarankan menonaktifkan
            return back()->with('error', __('messages.cannot_delete_in_use', ['item' => __('Kamar').' '.$room->number]));
        }

        $room->delete();

        return redirect()->route('admin.rooms.index')->with('success', __('messages.deleted'));
    }

    private function form(Room $room): View
    {
        return view('admin.rooms.form', [
            'room' => $room,
            'roomTypes' => RoomType::with('branch')->orderBy('branch_id')->orderBy('name')->get(),
            // W-08b: peringatan bila kamar masih punya reservasi mendatang
            'upcomingBookings' => $room->exists ? $room->nights()->where('stay_date', '>=', today()->toDateString())
                ->distinct()->count('reservation_room_id') : 0,
        ]);
    }
}
