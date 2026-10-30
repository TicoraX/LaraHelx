@extends('layouts.app')

@php($editing = $album->exists)

@section('title', $editing ? 'Editar álbum' : 'Nuevo álbum')

@section('content')
    <header class="page-head">
        <h1>{{ $editing ? 'Editar '.$album->title : 'Nuevo álbum' }}</h1>
    </header>

    @if ($artists->isEmpty())
        {{-- 03 §2: sin artistas no hay formulario; primero se crea un artista. --}}
        <div class="empty">
            <h2>Primero crea un artista</h2>
            <p>Cada álbum pertenece a un artista de tu catálogo.</p>
            <a class="btn btn--primary" href="{{ route('artists.create') }}">Crear un artista</a>
        </div>
    @else
        <form class="panel panel--form form" method="POST" enctype="multipart/form-data" data-submit-once novalidate
            action="{{ $editing ? route('albums.update', $album) : route('albums.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif
            <x-form-errors />

            <x-select name="artist_id" label="Artista" placeholder="Elige un artista" required
                :options="$artists" :value="$album->artist_id" />
            <x-field name="title" label="Título" :value="$album->title" maxlength="160" required />
            <x-field name="year" label="Año" class="input--year" :value="$album->year" inputmode="numeric" maxlength="4" required />
            <x-select name="genre" label="Género" placeholder="Elige un género" required
                :options="array_combine(config('discoteca.genres'), config('discoteca.genres'))" :value="$album->genre" />

            @if ($album->cover_path)
                <img class="sleeve sleeve--sm" src="{{ asset('storage/'.$album->cover_path) }}" alt="Portada actual de {{ $album->title }}" width="120" height="120">
            @endif
            <x-field name="cover" :label="$album->cover_path ? 'Cambiar portada' : 'Portada'" type="file" accept="image/jpeg,image/png,image/webp"
                help="JPG, PNG o WebP, hasta 2 MB." />

            <div class="page-head__actions">
                <button class="btn btn--primary" type="submit" data-busy-text="Guardando…">Guardar</button>
                <a class="btn btn--secondary" href="{{ $editing ? route('albums.show', $album) : route('albums.index') }}">Cancelar</a>
            </div>
        </form>
    @endif
@endsection
