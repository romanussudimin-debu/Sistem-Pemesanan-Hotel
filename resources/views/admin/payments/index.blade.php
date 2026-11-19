{{-- resources/views/admin/payments/index.blade.php — A-04 Antrean verifikasi pembayaran --}}
@php use App\Enums\PaymentStatus as P; @endphp
<x-admin-layout :title="__('Payment Verification')">
    <nav class="flex flex-wrap gap-2 text-sm">
        @foreach ([P::Submitted, P::Approved, P::Rejected] as $s)
            <a href="{{ route('admin.payments.index', ['status' => $s->value]) }}" class="rounded-full px-3 py-1 {{ $status === $s->value ? 'bg-brand-700 text-white' : 'bg-white ring-1 ring-slate-300 hover:bg-slate-100' }}">{{ $s->label() }}</a>
        @endforeach
    </nav>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Uploaded') }}</th><th>{{ __('Booking') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Branch') }}</th><th>{{ __('Amount') }}</th><th></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $p)
                    @php $late = $p->status === P::Submitted && $p->created_at->lt(now()->subDay()); @endphp
                    <tr class="{{ $late ? 'bg-red-50' : '' }}">
                        <td>{{ datetime_local($p->created_at) }}<br><span class="text-xs {{ $late ? 'font-semibold text-red-600' : 'text-slate-400' }}">{{ $p->created_at->diffForHumans() }}</span></td>
                        <td class="font-mono">{{ $p->reservation->code }}</td>
                        <td>{{ $p->reservation->user->name }}</td>
                        <td>{{ Str::after($p->reservation->branch->name, 'Nusa Stay ') }}</td>
                        <td>{{ money($p->amount) }}</td>
                        <td class="text-right"><a href="{{ route('admin.payments.show', $p) }}" class="btn-primary btn-sm">{{ $p->status === P::Submitted ? __('Review') : __('Details') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">{{ __('Nothing to verify. 🎉') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $payments->links() }}
</x-admin-layout>
