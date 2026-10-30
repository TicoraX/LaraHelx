@extends('layouts.app')

@section('title', $artist->name)

@section('content')
    <section class="detail" aria-labelledby="t-artista">
        @if ($artist->photo_path)
            <img class="sleeve sleeve--xl" src="{{ asset('storage/'.$artist->photo_path) }}" alt="Foto de {{ $artist->name }}" width="240" height="240">
        @else
            <div class="sleeve sleeve--xl sleeve--empty"><x-galleta class="galleta--lg" :name="$artist->name" /></div>
        @endif

        <div class="detail__data">
            <h1 id="t-artista">{{ $artist->name }}</h1>
            <p class="detail__meta">{{ collect([$artist->country, $artist->genre])->filter()->implode(' · ') }}</p>
            @if ($artist->bio)
                <p>{{ $artist->bio }}</p>
            @endif

            <div class="page-head__actions">
                <a class="btn btn--primary" href="{{ route('albums.create', ['artist' => $artist->id]) }}">Agregar álbum</a>
                <a class="btn btn--secondary" href="{{ route('artists.edit', $artist) }}">Editar</a>
                <x-delete-form :action="route('artists.destroy', $artist)" :name="$artist->name" :albums="$albums->count()" />
            </div>
        </div>
    </section>

    <section aria-labelledby="t-albumes">
        <h2 id="t-albumes">Álbumes</h2>
        @if ($albums->isEmpty())
            <div class="empty">
                <p>Este artista todavía no tiene álbumes.</p>
                <a class="btn btn--primary" href="{{ route('albums.create', ['artist' => $artist->id]) }}">Agregar álbum</a>
            </div>
        @else
            <ul class="covers">
                @foreach ($albums as $album)
                    <li>
                        <a class="covers__link" href="{{ route('albums.show', $album) }}">
                            @if ($album->cover_path)
                                <img class="sleeve" src="{{ asset('storage/'.$album->cover_path) }}" alt="" width="160" height="160" loading="lazy">
                            @else
                                <div class="sleeve sleeve--empty"><x-galleta :name="$album->title" /></div>
                            @endif
                            <span class="covers__title">{{ $album->title }}</span>
                        </a>
                        <span class="covers__year tabular">{{ $album->year }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
