{{-- resources/views/components/lang-switch.blade.php — pengalih bahasa ID|EN (FR-05) --}}
<div class="inline-flex overflow-hidden rounded-lg border border-slate-300 text-xs font-semibold" role="group" aria-label="{{ __('Language') }}">
    @foreach (config('hotel.locales') as $locale)
        <a href="{{ route('locale.switch', $locale) }}" hreflang="{{ $locale }}"
           class="px-2 py-1 {{ app()->getLocale() === $locale ? 'bg-brand-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-100' }}"
           @if (app()->getLocale() === $locale) aria-current="true" @endif>{{ strtoupper($locale) }}</a>
    @endforeach
</div>
