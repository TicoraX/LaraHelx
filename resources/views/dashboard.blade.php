@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <header class="page-head">
        <div>
            <h1>Hola, {{ auth()->user()->name }}</h1>
            <p>Tu catálogo de artistas y álbumes.</p>
        </div>
    </header>
@endsection
