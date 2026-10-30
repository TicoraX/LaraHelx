@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <header class="page-head">
        <div>
            <h1>Hola, {{ auth()->user()->name }}</h1>
            <p>Tu catálogo de artistas y álbumes.</p>
        </div>
    </header>

    @if ($artists === 0)
        <div class="empty">
            <h2>Todavía no tienes artistas</h2>
            <p>Cada álbum cuelga de un artista. Crea el primero y después le agregas su discografía.</p>
            <a class="btn btn--primary" href="{{ route('artists.create') }}">Crear tu primer artista</a>
        </div>
    @else
        <dl class="defs">
            <div>
                <dt>Artistas</dt>
                <dd><span class="tabular" data-count="artists">{{ $artists }}</span> · <a class="link-action" href="{{ route('artists.index') }}">Ver artistas</a></dd>
            </div>
            <div>
                <dt>Álbumes</dt>
                <dd><span class="tabular" data-count="albums">{{ $albums }}</span> · <a class="link-action" href="{{ route('albums.index') }}">Ver álbumes</a></dd>
            </div>
        </dl>
    @endif
@endsection
