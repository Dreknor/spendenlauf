@props(['name', 'label' => null, 'options' => [], 'selected' => null, 'placeholder' => null, 'hint' => null, 'required' => false])
@php $id = $attributes->get('id', $name); $aktuell = (string) old($name, $selected instanceof \BackedEnum ? $selected->value : $selected); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}">{{ $label }}@if ($required)<span class="text-esz-600"> *</span>@endif</label>
    @endif
    <select name="{{ $name }}" id="{{ $id }}" @required($required) {{ $attributes->except(['class', 'id'])->class(['mt-1 block w-full rounded-lg border-slate-300 py-2 shadow-sm focus:border-esz-500 focus:ring-esz-500 sm:text-sm']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected($aktuell === (string) $value)>{{ $text }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="field-error">{{ $message }}</p>@enderror
</div>
