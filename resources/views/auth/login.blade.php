@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <section class="auth">
        <div>
            <h1>Iniciar sesión</h1>
            <p class="auth__intro">Entra a tu catálogo de artistas y álbumes.</p>
        </div>

        <x-flash />

        <form class="panel form" method="POST" action="{{ route('login') }}" data-submit-once novalidate>
            @csrf
            <x-form-errors />
            <x-field name="email" label="Correo" type="email" autocomplete="email" spellcheck="false" required autofocus />
            <x-field name="password" label="Contraseña" type="password" autocomplete="current-password" required />
            <label class="check">
                <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                Recordarme en este equipo
            </label>
            <button class="btn btn--primary btn--block" type="submit" data-busy-text="Entrando…">Entrar</button>
        </form>

        <div class="auth__links">
            <a class="link-action" href="{{ route('password.request') }}">Olvidé mi contraseña</a>
            <a class="link-action" href="{{ route('register') }}">Crear una cuenta</a>
        </div>
    </section>
@endsection
