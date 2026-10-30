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
                <x-galleta :name="auth()->user()->name" />
                <div>
                    <div class="who__name">{{ auth()->user()->name }}</div>
                    <div class="who__mail">{{ auth()->user()->email }}</div>
                </div>
            </div>

            <nav class="nav">
                <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Inicio</a>
                <a href="{{ route('artists.index') }}" @if (request()->routeIs('artists.*')) aria-current="page" @endif>Artistas</a>
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
