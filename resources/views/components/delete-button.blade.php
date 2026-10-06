@props(['action', 'confirm' => 'Wirklich löschen?', 'label' => 'Löschen'])
<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" {{ $attributes->only('class') }}>
    @csrf
    @method('DELETE')
    <button type="submit" class="btn-light btn-sm text-red-600! hover:bg-red-50!">
        <x-icon name="trash" class="size-4" /> <span @class(['sr-only' => $label === ''])>{{ $label ?: 'Löschen' }}</span>
    </button>
</form>
