@extends('layouts.app')

@section('title', 'Álbumes')

@push('head')
    @vite(['resources/css/datatables.css', 'resources/js/datatables.js'])
@endpush

@section('content')
    <header class="page-head">
        <div>
            <h1>Álbumes</h1>
            <p><span class="tabular">{{ $albums->count() }}</span> {{ $albums->count() === 1 ? 'álbum' : 'álbumes' }} en tu catálogo</p>
        </div>
        @if ($albums->isNotEmpty())
            <div class="page-head__actions">
                <a class="btn btn--primary" href="{{ route('albums.create') }}">Nuevo álbum</a>
            </div>
        @endif
    </header>

    @if (! $hasArtists)
        <div class="empty">
            <h2>Primero crea un artista</h2>
            <p>Cada álbum pertenece a un artista de tu catálogo. Crea uno y después le agregas su discografía.</p>
            <a class="btn btn--primary" href="{{ route('artists.create') }}">Crear un artista</a>
        </div>
    @elseif ($albums->isEmpty())
        <div class="empty">
            <h2>Todavía no tienes álbumes</h2>
            <p>Agrega el primero con su título, año, género y portada.</p>
            <a class="btn btn--primary" href="{{ route('albums.create') }}">Crear el primero</a>
        </div>
    @else
        <section class="panel" aria-labelledby="t-lista">
            <h2 id="t-lista" class="visually-hidden">Lista de álbumes</h2>
            <div class="table-scroll">
                <table class="dataTable" data-datatable>
                    <thead>
                        <tr>
                            <th scope="col"><span class="visually-hidden">Portada</span></th>
                            <th scope="col">Título</th>
                            <th scope="col">Artista</th>
                            <th scope="col" class="dt-type-numeric hide-sm">Año</th>
                            <th scope="col" class="hide-sm">Género</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($albums as $album)
                            <tr>
                                <td>
                                    @if ($album->cover_path)
                                        <img class="sleeve sleeve--xs" src="{{ asset('storage/'.$album->cover_path) }}" alt="" width="40" height="40" loading="lazy">
                                    @else
                                        <x-galleta :name="$album->title" />
                                    @endif
                                </td>
                                <td><a class="row-title" href="{{ route('albums.show', $album) }}">{{ $album->title }}</a></td>
                                <td>{{ $album->artist_name }}</td>
                                <td class="dt-type-numeric hide-sm">{{ $album->year }}</td>
                                <td class="hide-sm">{{ $album->genre }}</td>
                                <td class="cell-actions">
                                    <a class="link-action" href="{{ route('albums.edit', $album) }}">Editar<span class="visually-hidden"> {{ $album->title }}</span></a>
                                    <x-delete-form :action="route('albums.destroy', $album)" :name="$album->title" :person="false" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
