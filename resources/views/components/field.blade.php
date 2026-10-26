@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'bag' => 'default'])
@php
    $error = $errors->getBag($bag)->first($name);
    $id = $attributes->get('id', $name);
@endphp
<div class="field">
    <label for="{{ $id }}">{{ $label }}</label>
    <input {{ $attributes->merge(['class' => 'input']) }}
        id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        aria-describedby="{{ $id }}-msg"
        @if ($error) aria-invalid="true" @endif>
    {{-- El error reemplaza la ayuda en el mismo lugar (04 §6). --}}
    @if ($error)
        <p class="field__error" id="{{ $id }}-msg">{{ $error }}</p>
    @else
        <p class="field__help" id="{{ $id }}-msg">{{ $help }}</p>
    @endif
</div>
