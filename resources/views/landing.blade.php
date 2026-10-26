@extends('layouts.guest')

@section('title', 'Tus artistas y álbumes, en orden')

@section('content')
    <section class="landing">
        <h1 class="landing__title">Tus artistas y álbumes, en orden.</h1>
        <p class="landing__lead">Discoteca guarda tu colección de música: cada artista con su foto y cada álbum con su portada, año y género.</p>
        <div class="landing__actions">
            @auth
                <a class="btn btn--primary" href="{{ route('dashboard') }}">Ir al dashboard</a>
            @else
                <a class="btn btn--primary" href="{{ route('register') }}">Crear cuenta</a>
                <a class="link-action" href="{{ route('login') }}">Iniciar sesión</a>
            @endauth
        </div>
    </section>

    <section class="landing__features" aria-labelledby="t-guarda">
        <h2 id="t-guarda">Qué guarda Discoteca</h2>
        <dl class="defs">
            <div><dt>Artistas con foto</dt><dd>Nombre, país, género y una biografía corta.</dd></div>
            <div><dt>Álbumes con portada</dt><dd>Título, año y género, colgados de su artista.</dd></div>
            <div><dt>Gráfico por género</dt><dd>Cuántos álbumes tienes de cada género.</dd></div>
            <div><dt>PDF por artista</dt><dd>Una ficha descargable con su foto y su discografía.</dd></div>
        </dl>
    </section>

    <footer class="landing__foot">
        <a class="link-action" href="https://github.com/TicoraX/LaraHelx">Código en GitHub</a>
        <span class="tabular">{{ now()->year }}</span>
    </footer>
@endsection
