@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'bag' => 'default'])
@php
    $error = $errors->getBag($bag)->first($name);
    $id = $attributes->get('id', $name);
    $isPassword = $type === 'password';
    // La confirmación avisa en vivo si coincide con su par del mismo formulario (app.js).
    $isConfirmation = $isPassword && str_ends_with($name, '_confirmation');
@endphp
<div class="field">
    <label for="{{ $id }}">{{ $label }}</label>
    @if ($isPassword)
        <div class="password">
    @endif
    <input {{ $attributes->merge(['class' => 'input']) }}
        id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
        @if (! $isPassword) value="{{ old($name, $value) }}" @endif
        @if ($isConfirmation) data-match="{{ Str::beforeLast($name, '_confirmation') }}" @endif
        aria-describedby="{{ $id }}-msg"
        @if ($error) aria-invalid="true" @endif>
    @if ($isPassword)
            {{-- Oculto hasta que app.js lo activa: sin JS no hace nada. --}}
            <button class="password__toggle" type="button" data-toggle-password hidden
                aria-controls="{{ $id }}" aria-pressed="false" aria-label="Mostrar contraseña">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    <path class="password__slash" d="M4 4l16 16" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            </button>
        </div>
    @endif
    {{-- El error reemplaza la ayuda en el mismo lugar (04 §6). --}}
    @if ($error)
        <p class="field__error" id="{{ $id }}-msg">{{ $error }}</p>
    @else
        <p class="field__help" id="{{ $id }}-msg" @if ($isConfirmation) aria-live="polite" @endif>{{ $help }}</p>
    @endif
</div>
