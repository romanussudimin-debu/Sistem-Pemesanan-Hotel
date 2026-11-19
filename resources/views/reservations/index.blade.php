{{-- resources/views/reservations/index.blade.php — P-10 Riwayat pesanan (W-06) --}}
@php use App\Enums\ReservationStatus as S; @endphp
<x-app-layout :title="__('My Bookings')">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6">
        <h1 class="text-2xl font-bold">{{ __('My Bookings') }}</h1>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <nav class="flex flex-wrap gap-2 text-sm" aria-label="{{ __('Filter') }}">
                @foreach (['all' => __('All'), 'active' => __('Active'), 'pending' => __('Awaiting Payment'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')] as $key => $label)
                    <a href="{{ route('reservations.index', ['tab' => $key]) }}" class="rounded-full px-3 py-1 {{ $tab === $key ? 'bg-brand-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-100' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <form method="GET"><input type="hidden" name="tab" value="{{ $tab }}"><input name="q" value="{{ request('q') }}" placeholder="{{ __('Search code…') }}" class="input py-1.5 sm:w-48" aria-label="{{ __('Search code…') }}"></form>
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($reservations as $r)
                <article class="card card-body">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <p class="font-mono font-semibold">{{ $r->code }}</p>
                        <x-status-badge :status="$r->status" />
                    </div>
                    <p class="mt-1 text-sm text-slate-700">{{ $r->branch->name }} · {{ $r->rooms->groupBy('room_type_id')->map(fn ($g) => $g->first()->roomType->name.' × '.$g->count())->join(', ') }}</p>
                    <p class="text-sm text-slate-500">{{ date_local($r->check_in) }} → {{ date_local($r->check_out) }} · {{ trans_choice(':count night|:count nights', $r->nights, ['count' => $r->nights]) }} · <strong class="text-slate-800">{{ money($r->total_amount) }}</strong></p>
                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <p class="text-xs text-amber-700">@if ($r->status === S::PendingPayment)⏱ {{ __('Pay before :time', ['time' => datetime_local($r->payment_deadline)]) }}@endif</p>
                        <div class="flex gap-2">
                            @if ($r->status === S::PendingPayment)<a href="{{ route('payments.show', $r) }}" class="btn-primary btn-sm">{{ __('Pay') }}</a>@endif
                            @can('voucher', $r)<a href="{{ route('reservations.voucher', $r) }}" class="btn-secondary btn-sm">{{ __('Voucher') }}</a>@endcan
                            <a href="{{ route('reservations.show', $r) }}" class="btn-secondary btn-sm">{{ __('Details') }}</a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="card card-body text-center text-slate-600">
                    <p>{{ __('No bookings yet.') }}</p>
                    <a href="{{ route('home') }}" class="btn-primary mt-4">{{ __('Find a room') }}</a>
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $reservations->links() }}</div>
    </div>
</x-app-layout>
