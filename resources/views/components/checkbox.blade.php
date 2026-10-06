@props(['name', 'label', 'checked' => false, 'value' => '1', 'hint' => null])
@php $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name).($value !== '1' ? '_'.$value : '')); @endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex items-start gap-2 font-normal">
        <input type="checkbox" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" class="mt-0.5"
               @checked(old() ? in_array($value, (array) old(str_replace('[]', '', $name))) || old($name) == $value : $checked)
               {{ $attributes->except(['class', 'id']) }}>
        <span>{{ $label }}{{ $slot }}</span>
    </label>
    @if ($hint)<p class="hint ml-6">{{ $hint }}</p>@endif
    @error(str_replace('[]', '', $name))<p class="field-error">{{ $message }}</p>@enderror
</div>
