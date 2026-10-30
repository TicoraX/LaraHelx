@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'Tu sesión expiró')

@section('content')
    <x-error-page code="419" title="Tu sesión expiró" hint="Vuelve a cargar la página y repite la acción. No se guardó nada." />
@endsection
