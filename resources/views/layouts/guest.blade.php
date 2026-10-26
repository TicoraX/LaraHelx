<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Discoteca</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-layout="guest">
    <a class="skip-link" href="#contenido">Saltar al contenido</a>

    <header class="guest-head">
        <a class="wordmark" href="{{ route('home') }}">Discoteca</a>
        @auth
            <a class="link-action" href="{{ route('dashboard') }}">Ir al dashboard</a>
        @else
            <a class="link-action" href="{{ route('login') }}">Iniciar sesión</a>
        @endauth
    </header>

    <main class="guest-main" id="contenido">
        @yield('content')
    </main>
</body>
</html>
