{{-- resources/views/admin/reports/print.blade.php — laporan siap cetak / simpan PDF (FR-35) --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>@include('layouts.partials.head', ['title' => __('Reports')])</head>
<body class="bg-white p-8 font-sans text-slate-800">
    <div class="no-print mb-6"><button onclick="window.print()" class="btn-primary btn-sm">{{ __('Print / Save as PDF') }}</button></div>
    <div class="flex items-center gap-3 border-b border-slate-300 pb-4">
        <x-application-logo class="h-10 w-10" />
        <div>
            <h1 class="text-xl font-bold">Nusa Stay Hotels — {{ ['occupancy' => __('Occupancy'), 'revenue' => __('Revenue'), 'reservations' => __('Bookings'), 'top' => __('Best-sellers')][$tab] }}</h1>
            <p class="text-sm text-slate-500">{{ date_local($from) }} – {{ date_local($to) }} · {{ $branchId ? App\Models\Branch::find($branchId)->name : __('All branches') }} · {{ __('Printed') }} {{ datetime_local(now()) }}</p>
        </div>
    </div>
    <div class="mt-6">@include('admin.reports.partials.table')</div>
</body>
</html>
