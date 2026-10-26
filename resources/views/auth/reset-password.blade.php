@extends('layouts.guest')

@section('title', 'Contraseña nueva')

@section('content')
    <section class="auth">
        <div>
            <h1>Contraseña nueva</h1>
            <p class="auth__intro">Elige una contraseña de al menos 8 caracteres.</p>
        </div>

        <form class="panel form" method="POST" action="{{ route('password.update') }}" data-submit-once novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <x-form-errors />
            <x-field name="email" label="Correo" type="email" :value="$request->email" autocomplete="email" spellcheck="false" required />
            <x-field name="password" label="Contraseña nueva" type="password" autocomplete="new-password" required autofocus />
            <x-field name="password_confirmation" label="Repite la contraseña" type="password" autocomplete="new-password" required />
            <button class="btn btn--primary btn--block" type="submit">Guardar contraseña</button>
        </form>

        {{-- Si el enlace venció o ya se usó, Fortify devuelve el error en el campo de correo (03 §2). --}}
        <div class="auth__links">
            <a class="link-action" href="{{ route('password.request') }}">Pedir un enlace nuevo</a>
        </div>
    </section>
@endsection
