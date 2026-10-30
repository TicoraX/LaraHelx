@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'No tienes acceso a esto')

@section('content')
    <x-error-page code="403" title="No tienes acceso a esto" hint="Esta página pertenece a otra cuenta o no está disponible para ti." />
@endsection
