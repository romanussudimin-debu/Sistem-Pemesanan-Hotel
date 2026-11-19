<?php

// app/Http/Controllers/Admin/BranchController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** A-05 Kelola cabang (FR-22, FR-27). */
class BranchController extends Controller
{
    public function index(): View
    {
        return view('admin.branches.index', [
            'branches' => Branch::withCount(['roomTypes', 'rooms', 'reservations'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.branches.form', ['branch' => new Branch(['is_active' => true])]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        Branch::create($this->payload($request));

        return redirect()->route('admin.branches.index')->with('success', __('messages.saved'));
    }

    public function edit(Branch $branch): View
    {
        return view('admin.branches.form', compact('branch'));
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $branch->update($this->payload($request));

        return redirect()->route('admin.branches.index')->with('success', __('messages.saved'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->reservations()->exists()) { // FR-27 / BR-19
            return back()->with('error', __('messages.cannot_delete_in_use', ['item' => $branch->name]));
        }

        $branch->delete();

        return redirect()->route('admin.branches.index')->with('success', __('messages.deleted'));
    }

    private function payload(BranchRequest $request): array
    {
        $data = $request->safe()->except('cover_image');
        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('branches', 'public');
        }

        return $data;
    }
}
