<?php

// app/Http/Controllers/Admin/FacilityController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacilityRequest;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** A-08 Kelola fasilitas (FR-25). */
class FacilityController extends Controller
{
    public function index(): View
    {
        return view('admin.facilities.index', ['facilities' => Facility::withCount('roomTypes')->orderBy('name_id')->get()]);
    }

    public function create(): View
    {
        return view('admin.facilities.form', ['facility' => new Facility]);
    }

    public function store(FacilityRequest $request): RedirectResponse
    {
        Facility::create($request->validated());

        return redirect()->route('admin.facilities.index')->with('success', __('messages.saved'));
    }

    public function edit(Facility $facility): View
    {
        return view('admin.facilities.form', compact('facility'));
    }

    public function update(FacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->update($request->validated());

        return redirect()->route('admin.facilities.index')->with('success', __('messages.saved'));
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $facility->delete(); // pivot ikut terhapus (cascade); tidak memengaruhi reservasi

        return redirect()->route('admin.facilities.index')->with('success', __('messages.deleted'));
    }
}
