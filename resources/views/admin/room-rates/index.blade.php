{{-- resources/views/admin/room-rates/index.blade.php — A-09 Tarif musiman (FR-26) --}}
<x-admin-layout :title="__('Seasonal Rates')">
    <x-slot name="actions"><a href="{{ route('admin.room-rates.create', ['room_type_id' => request('room_type_id')]) }}" class="btn-primary btn-sm">+ {{ __('Add Rate') }}</a></x-slot>
    <form method="GET"><select name="room_type_id" class="input w-auto" onchange="this.form.submit()"><option value="">{{ __('All room types') }}</option>@foreach ($roomTypes as $t)<option value="{{ $t->id }}" @selected(request('room_type_id') == $t->id)>{{ $t->branch->code }} · {{ $t->name }}</option>@endforeach</select></form>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Room type') }}</th><th>{{ __('Label') }}</th><th>{{ __('Period') }}</th><th>{{ __('Rate / night') }}</th><th>{{ __('vs base') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rates as $rate)
                    <tr><td>{{ $rate->roomType->branch->code }} · {{ $rate->roomType->name }}</td><td>{{ $rate->label }}</td><td>{{ date_local($rate->start_date) }} – {{ date_local($rate->end_date) }}</td><td>{{ money($rate->price) }}</td>
                        <td>{{ $rate->roomType->base_price > 0 ? sprintf('%+d%%', round(($rate->price / $rate->roomType->base_price - 1) * 100)) : '–' }}</td>
                        <td class="space-x-3 text-right"><a href="{{ route('admin.room-rates.edit', $rate) }}" class="link">{{ __('Edit') }}</a><x-admin.delete-button :action="route('admin.room-rates.destroy', $rate)" /></td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">{{ __('No seasonal rates.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rates->links() }}
    <p class="text-xs text-slate-500">{{ __('Existing bookings are not affected: rates are stored as a snapshot at booking time.') }}</p>
</x-admin-layout>
