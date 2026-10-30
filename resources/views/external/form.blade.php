@extends('layouts.app')

@php($editing = isset($user['id']))

@section('title', $editing ? 'Editar usuario externo' : 'Nuevo usuario externo')

@section('content')
    <header class="page-head">
        <h1>{{ $editing ? 'Editar a '.$user['name'] : 'Nuevo usuario externo' }}</h1>
    </header>

    <form class="panel panel--form form" method="POST" data-submit-once novalidate
        action="{{ $editing ? route('external.users.update', $user['id']) : route('external.users.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-form-errors />
        @if ($errors->has('gorest'))
            <p class="field__error" role="alert">{{ $errors->first('gorest') }}</p>
        @endif

        <x-field name="name" label="Nombre" :value="$user['name'] ?? null" maxlength="100" required autofocus />
        <x-field name="email" label="Correo" type="email" :value="$user['email'] ?? null" maxlength="150" spellcheck="false"
            help="Tiene que terminar en @discoteca.test." required />
        <x-select name="gender" label="Género" placeholder="Elige una opción" required
            :options="['male' => 'Masculino', 'female' => 'Femenino']" :value="$user['gender'] ?? null" />
        <x-select name="status" label="Estado" placeholder="Elige una opción" required
            :options="['active' => 'Activo', 'inactive' => 'Inactivo']" :value="$user['status'] ?? null" />

        <div class="page-head__actions">
            <button class="btn btn--primary" type="submit" data-busy-text="Guardando…">Guardar</button>
            <a class="btn btn--secondary" href="{{ route('external.users.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
