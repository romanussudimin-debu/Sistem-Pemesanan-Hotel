{{-- resources/views/components/flash.blade.php — pesan sukses/galat (Tahap 4, 4.3.4) --}}
@foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-red-200 bg-red-50 text-red-800', 'status' => 'border-sky-200 bg-sky-50 text-sky-800'] as $key => $classes)
    @if (session($key) && ! ($key === 'status' && in_array(session('status'), ['profile-updated', 'password-updated', 'verification-link-sent'])))
        <div x-data="{ show: true }" x-show="show" x-transition role="{{ $key === 'error' ? 'alert' : 'status' }}"
             class="mb-4 flex items-start justify-between gap-4 rounded-lg border px-4 py-3 text-sm {{ $classes }}">
            <span>{{ session($key) }}</span>
            <button @click="show = false" class="font-bold opacity-60 hover:opacity-100" aria-label="{{ __('Close') }}">&times;</button>
        </div>
    @endif
@endforeach
