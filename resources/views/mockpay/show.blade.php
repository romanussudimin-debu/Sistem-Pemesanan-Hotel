{{-- resources/views/mockpay/show.blade.php — P-08 MockPay: halaman SIMULASI gateway (bukan pembayaran nyata) --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>@include('layouts.partials.head', ['title' => 'MockPay'])</head>
<body class="flex min-h-screen items-center justify-center bg-indigo-950 p-4 font-sans text-slate-800">
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="bg-indigo-600 px-6 py-4 text-white">
            <p class="text-lg font-bold">MockPay</p>
            <p class="text-xs uppercase tracking-wider text-indigo-100">{{ __('Simulation page — not a real payment') }}</p>
        </div>
        <dl class="space-y-2 px-6 py-5 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Merchant</dt><dd class="font-semibold">Nusa Stay Hotels</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Reference') }}</dt><dd class="font-mono">{{ $payment->gateway_ref }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">{{ __('Booking') }}</dt><dd class="font-mono">{{ $payment->reservation->code }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt>{{ __('Amount') }}</dt><dd class="font-bold">{{ money($payment->amount) }}</dd></div>
        </dl>
        <form method="POST" action="{{ route('mockpay.complete', $payment->gateway_ref) }}" class="grid grid-cols-2 gap-3 px-6 pb-6">
            @csrf
            <button name="result" value="success" class="btn bg-emerald-600 text-white hover:bg-emerald-700">✓ {{ __('Simulate SUCCESS') }}</button>
            <button name="result" value="failed" class="btn bg-red-600 text-white hover:bg-red-700">✗ {{ __('Simulate FAILURE') }}</button>
        </form>
    </div>
</body>
</html>
