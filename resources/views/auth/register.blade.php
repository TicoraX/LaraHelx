@extends('layouts.guest')

@section('title', 'Crear cuenta')

@section('content')
    <section class="auth">
        <div>
            <h1>Crear cuenta</h1>
            <p class="auth__intro">Tu catálogo es privado: solo tú ves tus artistas y álbumes.</p>
        </div>

        <form class="panel form" method="POST" action="{{ route('register') }}" data-submit-once novalidate>
            @csrf
            <x-form-errors />
            <x-field name="name" label="Nombre" autocomplete="name" required autofocus />
            <x-field name="email" label="Correo" type="email" autocomplete="email" spellcheck="false" required />
            <x-field name="password" label="Contraseña" type="password" autocomplete="new-password" help="Mínimo 8 caracteres." required />
            <x-field name="password_confirmation" label="Repite la contraseña" type="password" autocomplete="new-password" required />
            <button class="btn btn--primary btn--block" type="submit" data-busy-text="Creando…">Crear cuenta</button>
        </form>

        <div class="auth__links">
            <a class="link-action" href="{{ route('login') }}">Ya tengo cuenta</a>
        </div>
    </section>
@endsection
