@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'No encontramos esta página')

@section('content')
    <x-error-page code="404" title="No encontramos esta página" hint="Revisa la dirección o vuelve al inicio." />
@endsection
