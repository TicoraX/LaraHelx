@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'Esta acción no está disponible aquí')

@section('content')
    <x-error-page code="405" title="Esta acción no está disponible aquí" hint="La dirección existe, pero no acepta esa operación. Vuelve al inicio e inténtalo de nuevo." />
@endsection
