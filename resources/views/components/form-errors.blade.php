@props(['bag' => 'default'])
@php($count = $errors->getBag($bag)->count())
@if ($count)
    <div class="error-summary" role="alert">
        {{ $count === 1 ? 'Hay 1 error en el formulario.' : "Hay {$count} errores en el formulario." }}
    </div>
@endif
