@extends('layouts.app')

@section('title', 'Mi perfil')

@section('content')
    <header class="page-head">
        <div>
            <h1>Mi perfil</h1>
            <p>Tus datos, tu contraseña y los tokens de la API.</p>
        </div>
    </header>

    @php($user = auth()->user())

    <section class="panel panel--form" aria-labelledby="t-datos">
        <h2 id="t-datos">Mis datos</h2>
        <form class="form" method="POST" action="{{ route('user-profile-information.update') }}" enctype="multipart/form-data" data-submit-once novalidate>
            @csrf
            @method('PUT')
            <x-form-errors bag="updateProfileInformation" />
            <x-field bag="updateProfileInformation" id="profile-name" name="name" label="Nombre" :value="$user->name" maxlength="120" autocomplete="name" required />
            <x-field bag="updateProfileInformation" id="profile-email" name="email" label="Correo" type="email" :value="$user->email" maxlength="255" autocomplete="email" spellcheck="false" required />
            @if ($user->photo_path)
                <img class="sleeve sleeve--sm" src="{{ asset('storage/'.$user->photo_path) }}" alt="Tu foto actual" width="120" height="120">
            @endif
            <x-field bag="updateProfileInformation" id="profile-photo" name="photo" :label="$user->photo_path ? 'Cambiar foto' : 'Foto'" type="file" accept="image/jpeg,image/png,image/webp"
                help="JPG, PNG o WebP, hasta 2 MB." />
            <div class="page-head__actions">
                <button class="btn btn--primary" type="submit" data-busy-text="Guardando…">Guardar datos</button>
            </div>
        </form>
    </section>

    <section class="panel panel--form" aria-labelledby="t-clave">
        <h2 id="t-clave">Cambiar contraseña</h2>
        <form class="form" method="POST" action="{{ route('user-password.update') }}" data-submit-once novalidate>
            @csrf
            @method('PUT')
            <x-form-errors bag="updatePassword" />
            <x-field bag="updatePassword" id="pw-current" name="current_password" label="Contraseña actual" type="password" autocomplete="current-password" required />
            <x-field bag="updatePassword" id="pw-new" name="password" label="Contraseña nueva" type="password" autocomplete="new-password" help="Al menos 8 caracteres." required />
            <x-field bag="updatePassword" id="pw-confirm" name="password_confirmation" label="Repite la contraseña nueva" type="password" autocomplete="new-password" required />
            <div class="page-head__actions">
                <button class="btn btn--primary" type="submit" data-busy-text="Guardando…">Cambiar contraseña</button>
            </div>
            <p class="field__help">Al cambiarla se cierran tus otras sesiones y se revocan tus tokens de API.</p>
        </form>
    </section>

    <section class="panel panel--form" aria-labelledby="t-tokens">
        <h2 id="t-tokens">Tokens de API</h2>

        @if (session('new_token'))
            <div class="flash" role="status">
                <span>Copia tu token ahora, no podrás verlo de nuevo: <code>{{ session('new_token') }}</code></span>
            </div>
        @endif

        @if ($tokens->isEmpty())
            <div class="empty">
                <p>No tienes tokens de API. Un token deja que otra aplicación, como Postman, consulte tu catálogo sin tu contraseña.</p>
            </div>
        @else
            <dl class="defs">
                @foreach ($tokens as $token)
                    <div>
                        <dt>{{ $token->name }}</dt>
                        <dd>
                            <span class="tabular">Creado el {{ $token->created_at->timezone('America/Bogota')->format('d/m/Y') }}</span>
                            <form method="POST" action="{{ route('tokens.destroy', $token->id) }}" data-submit-once>
                                @csrf
                                @method('DELETE')
                                <button class="link-action link-action--danger" type="submit" data-busy-text="Revocando…">Revocar<span class="visually-hidden"> {{ $token->name }}</span></button>
                            </form>
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif

        <form class="form" method="POST" action="{{ route('tokens.store') }}" data-submit-once novalidate>
            @csrf
            <x-form-errors />
            <x-field id="token-name" name="token_name" label="Nombre del token" maxlength="60" help="Por ejemplo: Postman." required />
            <div class="page-head__actions">
                <button class="btn btn--primary" type="submit" data-busy-text="Creando…">Crear token</button>
            </div>
        </form>
    </section>
@endsection
