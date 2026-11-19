{{-- resources/views/components/admin/delete-button.blade.php — tombol hapus dengan konfirmasi (Heuristik #5) --}}
@props(['action', 'label' => null])
<form method="POST" action="{{ $action }}" onsubmit="return confirm('{{ __('Delete this item?') }}')" class="inline">
    @csrf @method('DELETE')
    <button class="text-sm font-medium text-red-600 hover:underline">{{ $label ?? __('Delete') }}</button>
</form>
