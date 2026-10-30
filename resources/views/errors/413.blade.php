@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title', 'El archivo es demasiado grande')

@section('content')
    <x-error-page code="413" title="El archivo es demasiado grande" hint="Sube una imagen de hasta 2 MB." />
@endsection
