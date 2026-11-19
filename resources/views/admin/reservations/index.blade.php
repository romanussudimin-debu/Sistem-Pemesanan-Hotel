{{-- resources/views/admin/reservations/index.blade.php — A-02 Daftar reservasi (FR-28) --}}
<x-admin-layout :title="__('Bookings')">
    <form method="GET" class="card card-body grid gap-3 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
        <div class="lg:col-span-2"><label class="label" for="q">{{ __('Search code / guest') }}</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input"></div>
        <div><label class="label" for="branch_id">{{ __('Branch') }}</label><select id="branch_id" name="branch_id" class="input"><option value="">{{ __('All') }}</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(($filters['branch_id'] ?? '') == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <div><label class="label" for="status">{{ __('Status') }}</label><select id="status" name="status" class="input"><option value="">{{ __('All') }}</option>@foreach (App\Enums\ReservationStatus::cases() as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? '') === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
        <div><label class="label" for="from">{{ __('Check-in from') }}</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input"></div>
        <div class="flex gap-2"><div class="flex-1"><label class="label" for="to">{{ __('to') }}</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input"></div><button class="btn-primary self-end">{{ __('Filter') }}</button></div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Guest') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Stay') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reservations as $r)
                    <tr class="hover:bg-slate-50">
                        <td><a href="{{ route('admin.reservations.show', $r) }}" class="link font-mono">{{ $r->code }}</a></td>
                        <td>{{ $r->guest_name }}<br><span class="text-xs text-slate-400">{{ $r->user->email }}</span></td>
                        <td>{{ Str::after($r->branch->name, 'Nusa Stay ') }}</td>
                        <td>{{ date_local($r->check_in) }} → {{ date_local($r->check_out) }}</td>
                        <td>{{ money($r->total_amount) }}</td>
                        <td><x-status-badge :status="$r->status" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">{{ __('No bookings match the filter.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $reservations->links() }}
</x-admin-layout>
