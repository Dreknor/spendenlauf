@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])
@php $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name)); $dot = str_replace(['[', ']'], ['.', ''], $name); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}">{{ $label }}@if ($required)<span class="text-esz-600"> *</span>@endif</label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
           value="{{ $type === 'password' ? '' : old($dot, $value) }}"
           @required($required)
           {{ $attributes->except(['class', 'id'])->class(['border-red-400' => $errors->has($dot)]) }}>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($dot)<p class="field-error">{{ $message }}</p>@enderror
</div>
