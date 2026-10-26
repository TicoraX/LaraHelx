@extends('layouts.guest')

@section('title', 'Olvidé mi contraseña')

@section('content')
    <section class="auth">
        <div>
            <h1>Olvidé mi contraseña</h1>
            <p class="auth__intro">Escribe tu correo y te enviamos un enlace para crear una contraseña nueva. Vence a los 15 minutos.</p>
        </div>

        <x-flash />

        <form class="panel form" method="POST" action="{{ route('password.email') }}" data-submit-once novalidate>
            @csrf
            <x-form-errors />
            <x-field name="email" label="Correo" type="email" autocomplete="email" spellcheck="false" required autofocus />
            <button class="btn btn--primary btn--block" type="submit" data-busy-text="Enviando…">Enviar enlace</button>
        </form>

        <div class="auth__links">
            <a class="link-action" href="{{ route('login') }}">Volver a iniciar sesión</a>
        </div>
    </section>
@endsection
