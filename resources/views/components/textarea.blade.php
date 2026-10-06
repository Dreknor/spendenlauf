@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'required' => false, 'rows' => 4])
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $name }}">{{ $label }}@if ($required)<span class="text-esz-600"> *</span>@endif</label>
    @endif
    <textarea name="{{ $name }}" id="{{ $name }}" rows="{{ $rows }}" @required($required) {{ $attributes->except('class') }}>{{ old($name, $value) }}</textarea>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="field-error">{{ $message }}</p>@enderror
</div>
