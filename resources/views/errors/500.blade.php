@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'Algo falló de nuestro lado')

@section('content')
    <x-error-page code="500" title="Algo falló de nuestro lado" hint="Intenta de nuevo en unos minutos." />
@endsection
