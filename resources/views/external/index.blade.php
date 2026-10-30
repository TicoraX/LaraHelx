@extends('layouts.app')

@section('title', 'API externa')

@section('content')
    <header class="page-head">
        <div>
            <h1>API externa</h1>
            <p>Usuarios que creaste en GoRest desde esta aplicación.</p>
        </div>
        @if (! $error)
            <div class="page-head__actions">
                <a class="btn btn--primary" href="{{ route('external.users.create') }}">Nuevo usuario</a>
            </div>
        @endif
    </header>

    @if ($error)
        <div class="error-summary" role="alert">
            <p>{{ $error }}</p>
            <a class="btn btn--secondary" href="{{ route('external.users.index') }}">Reintentar</a>
        </div>
    @elseif (count($users) === 0)
        <div class="empty">
            <h2>Todavía no creaste usuarios externos</h2>
            <p>GoRest es un servicio público de práctica. Crea un usuario y lo verás aquí; solo puedes modificar los que creaste con este token.</p>
            <a class="btn btn--primary" href="{{ route('external.users.create') }}">Crear uno</a>
        </div>
    @else
        <section class="panel" aria-labelledby="t-usuarios">
            <h2 id="t-usuarios" class="visually-hidden">Usuarios externos</h2>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Nombre</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Género</th>
                            <th scope="col">Estado</th>
                            <th scope="col"><span class="visually-hidden">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <th scope="row">{{ $user['name'] }}</th>
                                <td>{{ $user['email'] }}</td>
                                <td>{{ ['male' => 'Masculino', 'female' => 'Femenino'][$user['gender']] ?? $user['gender'] }}</td>
                                <td>{{ ['active' => 'Activo', 'inactive' => 'Inactivo'][$user['status']] ?? $user['status'] }}</td>
                                <td class="cell-actions">
                                    <a class="link-action" href="{{ route('external.users.edit', $user['id']) }}">Editar<span class="visually-hidden"> a {{ $user['name'] }}</span></a>
                                    <x-delete-form :action="route('external.users.destroy', $user['id'])" :name="$user['name']" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    {{-- Recuadro técnico (03 §5): muestra la llamada hecha a GoRest, con el token enmascarado. --}}
    @if ($call)
        <section class="panel" aria-labelledby="t-llamada">
            <h2 id="t-llamada">Última llamada a GoRest</h2>
            <dl class="defs">
                <div><dt>Método</dt><dd><code>{{ $call['method'] }}</code></dd></div>
                <div><dt>URL</dt><dd><code>{{ $call['url'] }}</code></dd></div>
                @foreach ($call['headers'] as $name => $value)
                    <div><dt>Cabecera {{ $name }}</dt><dd><code>{{ $value }}</code></dd></div>
                @endforeach
            </dl>
        </section>
    @endif
@endsection
