<?php

// app/Http/Controllers/Admin/RoomTypeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoomTypeRequest;
use App\Models\Branch;
use App\Models\Facility;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/** A-06 Kelola tipe kamar: info, foto, fasilitas (FR-23, FR-25, FR-27). */
class RoomTypeController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.room-types.index', [
            'roomTypes' => RoomType::with('branch')->withCount('rooms')
                ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
                ->orderBy('branch_id')->orderBy('base_price')->paginate(15)->withQueryString(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new RoomType(['is_active' => true, 'capacity' => 2]));
    }

    public function store(RoomTypeRequest $request): RedirectResponse
    {
        DB::transaction(fn () => $this->save(new RoomType, $request));

        return redirect()->route('admin.room-types.index')->with('success', __('messages.saved'));
    }

    public function edit(RoomType $roomType): View
    {
        return $this->form($roomType->load('facilities', 'images', 'rates'));
    }

    public function update(RoomTypeRequest $request, RoomType $roomType): RedirectResponse
    {
        DB::transaction(fn () => $this->save($roomType, $request));

        return redirect()->route('admin.room-types.index')->with('success', __('messages.saved'));
    }

    public function destroy(RoomType $roomType): RedirectResponse
    {
        if ($roomType->reservationRooms()->exists()) { // FR-27
            return back()->with('error', __('messages.cannot_delete_in_use', ['item' => $roomType->name]));
        }

        $roomType->delete();

        return redirect()->route('admin.room-types.index')->with('success', __('messages.deleted'));
    }

    public function destroyImage(RoomType $roomType, RoomTypeImage $image): RedirectResponse
    {
        abort_unless($image->room_type_id === $roomType->id, 404);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', __('messages.deleted'));
    }

    private function form(RoomType $roomType): View
    {
        return view('admin.room-types.form', [
            'roomType' => $roomType,
            'branches' => Branch::orderBy('name')->get(),
            'facilities' => Facility::orderBy('name_id')->get(),
        ]);
    }

    private function save(RoomType $roomType, RoomTypeRequest $request): void
    {
        $roomType->fill($request->safe()->except(['facilities', 'images']))->save();
        $roomType->facilities()->sync($request->validated('facilities', []));

        $next = (int) $roomType->images()->max('sort_order') + ($roomType->images()->exists() ? 1 : 0);
        foreach ($request->file('images', []) as $i => $file) {
            $roomType->images()->create(['path' => $file->store('room-types', 'public'), 'sort_order' => $next + $i]);
        }
    }
}
