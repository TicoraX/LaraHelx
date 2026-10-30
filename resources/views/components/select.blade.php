@props(['name', 'label', 'options', 'value' => null, 'placeholder', 'help' => null])
{{-- Mismo contrato que x-field (04 §6); $options es [valor => texto]. --}}
@php
    $error = $errors->first($name);
    $selected = (string) old($name, $value);
@endphp
<div class="field">
    <label for="{{ $name }}">{{ $label }}</label>
    <select {{ $attributes->merge(['class' => 'input']) }} id="{{ $name }}" name="{{ $name }}"
        aria-describedby="{{ $name }}-msg" @if ($error) aria-invalid="true" @endif>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $text)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $text }}</option>
        @endforeach
    </select>
    <p class="{{ $error ? 'field__error' : 'field__help' }}" id="{{ $name }}-msg">{{ $error ?? $help }}</p>
</div>
