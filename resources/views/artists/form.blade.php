@extends('layouts.app')

@php($editing = $artist->exists)

@section('title', $editing ? 'Editar artista' : 'Nuevo artista')

@section('content')
    <header class="page-head">
        <h1>{{ $editing ? 'Editar a '.$artist->name : 'Nuevo artista' }}</h1>
    </header>

    <form class="panel panel--form form" method="POST" enctype="multipart/form-data" data-submit-once novalidate
        action="{{ $editing ? route('artists.update', $artist) : route('artists.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-form-errors />

        <x-field name="name" label="Nombre" :value="$artist->name" maxlength="120" required autofocus />
        {{-- Se escribe con sugerencias; el servidor solo acepta nombres de la lista (config discoteca.countries). --}}
        <x-field name="country" label="País" :value="$artist->country" list="paises" autocomplete="off"
            help="Escribe y elige de la lista." />
        <datalist id="paises">
            @foreach (config('discoteca.countries') as $country)
                <option value="{{ $country }}">
            @endforeach
        </datalist>

        <x-select name="genre" label="Género" placeholder="Elige un género" required
            :options="array_combine(config('discoteca.genres'), config('discoteca.genres'))" :value="$artist->genre" />

        @php($error = $errors->first('bio'))
        <div class="field">
            <label for="bio">Biografía</label>
            <textarea class="input" id="bio" name="bio" rows="5" maxlength="1000" aria-describedby="bio-msg" @if ($error) aria-invalid="true" @endif>{{ old('bio', $artist->bio) }}</textarea>
            <p class="{{ $error ? 'field__error' : 'field__help' }}" id="bio-msg">{{ $error ?? 'Opcional, hasta 1000 caracteres.' }}</p>
        </div>

        @if ($artist->photo_path)
            <img class="sleeve sleeve--sm" src="{{ asset('storage/'.$artist->photo_path) }}" alt="Foto actual de {{ $artist->name }}" width="120" height="120">
        @endif
        <x-field name="photo" :label="$artist->photo_path ? 'Cambiar foto' : 'Foto'" type="file" accept="image/jpeg,image/png,image/webp"
            help="JPG, PNG o WebP, hasta 2 MB." />

        <div class="page-head__actions">
            <button class="btn btn--primary" type="submit" data-busy-text="Guardando…">Guardar</button>
            <a class="btn btn--secondary" href="{{ $editing ? route('artists.show', $artist) : route('dashboard') }}">Cancelar</a>
        </div>
    </form>
@endsection
