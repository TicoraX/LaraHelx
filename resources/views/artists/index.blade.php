@extends('layouts.app')

@section('title', 'Artistas')

@push('head')
    @vite(['resources/css/datatables.css', 'resources/js/datatables.js'])
@endpush

@section('content')
    <header class="page-head">
        <div>
            <h1>Artistas</h1>
            <p><span class="tabular">{{ $artists->count() }}</span> {{ $artists->count() === 1 ? 'artista' : 'artistas' }} en tu catálogo</p>
        </div>
        @if ($artists->isNotEmpty())
            <div class="page-head__actions">
                <a class="btn btn--primary" href="{{ route('artists.create') }}">Nuevo artista</a>
            </div>
        @endif
    </header>

    @if ($artists->isEmpty())
        <div class="empty">
            <h2>Todavía no tienes artistas</h2>
            <p>Cada álbum cuelga de un artista. Crea el primero con su nombre, país y foto, y después le agregas su discografía.</p>
            <a class="btn btn--primary" href="{{ route('artists.create') }}">Crear el primero</a>
        </div>
    @else
        <section class="panel" aria-labelledby="t-lista">
            <h2 id="t-lista" class="visually-hidden">Lista de artistas</h2>
            <div class="table-scroll">
                <table class="dataTable" data-datatable>
                    <thead>
                        <tr>
                            <th scope="col"><span class="visually-hidden">Foto</span></th>
                            <th scope="col">Nombre</th>
                            <th scope="col" class="hide-sm">País</th>
                            <th scope="col" class="hide-sm">Género</th>
                            <th scope="col" class="dt-type-numeric">Álbumes</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($artists as $artist)
                            <tr>
                                <td>
                                    @if ($artist->photo_path)
                                        <img class="sleeve sleeve--xs" src="{{ asset('storage/'.$artist->photo_path) }}" alt="" width="40" height="40" loading="lazy">
                                    @else
                                        <x-galleta :name="$artist->name" />
                                    @endif
                                </td>
                                <td><a class="row-title" href="{{ route('artists.show', $artist) }}">{{ $artist->name }}</a></td>
                                <td class="hide-sm">{{ $artist->country }}</td>
                                <td class="hide-sm">{{ $artist->genre }}</td>
                                <td class="dt-type-numeric">{{ $artist->albums_count }}</td>
                                <td class="cell-actions">
                                    <a class="link-action" href="{{ route('artists.edit', $artist) }}">Editar<span class="visually-hidden"> a {{ $artist->name }}</span></a>
                                    <x-delete-form :action="route('artists.destroy', $artist)" :name="$artist->name" :albums="$artist->albums_count" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
