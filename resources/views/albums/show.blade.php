@extends('layouts.app')

@section('title', $album->title)

@section('content')
    <section class="detail" aria-labelledby="t-album">
        @if ($album->cover_path)
            <img class="sleeve sleeve--xl" src="{{ asset('storage/'.$album->cover_path) }}" alt="Portada de {{ $album->title }}" width="240" height="240">
        @else
            <div class="sleeve sleeve--xl sleeve--empty"><x-galleta class="galleta--lg" :name="$album->title" /></div>
        @endif

        <div class="detail__data">
            <h1 id="t-album">{{ $album->title }}</h1>
            <p><a href="{{ route('artists.show', $album->artist) }}">{{ $album->artist->name }}</a></p>
            <p class="detail__meta"><span class="tabular">{{ $album->year }}</span> · {{ $album->genre }}</p>

            <div class="page-head__actions">
                <a class="btn btn--secondary" href="{{ route('albums.edit', $album) }}">Editar</a>
                <x-delete-form :action="route('albums.destroy', $album)" :name="$album->title" :person="false" />
            </div>
        </div>
    </section>
@endsection
