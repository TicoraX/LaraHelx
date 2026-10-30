<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Discoteca</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body data-layout="app">
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <div class="shell">
        <aside class="rail" aria-label="Navegación principal">
            <a class="wordmark" href="{{ route('dashboard') }}">Discoteca</a>

            <div class="who">
                @if (auth()->user()->photo_path)
                    <img class="sleeve sleeve--xs" src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="" width="40" height="40">
                @else
                    <x-galleta :name="auth()->user()->name" />
                @endif
                <div>
                    <div class="who__name">{{ auth()->user()->name }}</div>
                    <div class="who__mail">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <nav class="nav">
                <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Inicio</a>
                <a href="{{ route('artists.index') }}" @if (request()->routeIs('artists.*')) aria-current="page" @endif>Artistas</a>
                <a href="{{ route('albums.index') }}" @if (request()->routeIs('albums.*')) aria-current="page" @endif>Álbumes</a>
                <a href="{{ route('stats') }}" @if (request()->routeIs('stats')) aria-current="page" @endif>Estadísticas</a>
                <a href="{{ route('external.users.index') }}" @if (request()->routeIs('external.users.*')) aria-current="page" @endif>API externa</a>
                <a href="{{ route('profile') }}" @if (request()->routeIs('profile')) aria-current="page" @endif>Mi perfil</a>
            </nav>

            <form class="rail__foot" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-rail" type="submit">Cerrar sesión</button>
            </form>
        </aside>

        <main class="main" id="contenido">
            <x-flash />
            @yield('content')
        </main>
    </div>

    {{-- Confirmación de borrado (03 §2). Los textos los pone app.js desde data-confirm del formulario. --}}
    <dialog class="dialog" id="confirmar" aria-labelledby="confirmar-t" aria-describedby="confirmar-d">
        <h2 id="confirmar-t"></h2>
        <p id="confirmar-d"></p>
        <form class="dialog__actions" method="dialog">
            <button class="btn btn--secondary" value="cancel" autofocus>Cancelar</button>
            <button class="btn btn--danger" value="ok">Eliminar</button>
        </form>
    </dialog>
</body>
</html>
