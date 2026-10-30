@props(['code', 'title', 'hint'])
{{-- Página de error propia (03 §2, 04 §7): el código en tono secundario, la frase, qué hacer y el camino de vuelta. --}}
<section class="error-page">
    <p class="error-page__code tabular">{{ $code }}</p>
    <h1>{{ $title }}</h1>
    <p>{{ $hint }}</p>
    @auth
        <a class="btn btn--secondary" href="{{ route('dashboard') }}">Volver al dashboard</a>
    @else
        <a class="btn btn--secondary" href="{{ route('home') }}">Volver al inicio</a>
    @endauth
</section>
