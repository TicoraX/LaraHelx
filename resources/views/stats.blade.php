@extends('layouts.app')

@section('title', 'Estadísticas')

@if ($genres)
    @push('head')
        @vite('resources/js/stats.js')
    @endpush
@endif

@section('content')
    <header class="page-head">
        <div>
            <h1>Estadísticas</h1>
            <p>Cómo se reparte tu colección.</p>
        </div>
    </header>

    @if (! $genres)
        <div class="empty">
            <h2>Agrega álbumes para ver tus estadísticas</h2>
            <p>El gráfico cuenta tus álbumes por género. Crea uno y vuelve aquí.</p>
            <a class="btn btn--primary" href="{{ route('albums.create') }}">Agregar un álbum</a>
        </div>
    @else
        <section class="panel" aria-labelledby="t-generos">
            <h2 id="t-generos">Álbumes por género</h2>
            <div class="chart">
                <canvas id="chart-genres" role="img"
                    aria-label="Álbumes por género: {{ collect($genres)->map(fn ($n, $g) => "{$g} {$n}")->implode(', ') }}"></canvas>
            </div>
            <script type="application/json" id="stats-data">@json(['labels' => array_keys($genres), 'values' => array_values($genres)])</script>

            <table class="data-table">
                <caption class="visually-hidden">Álbumes por género</caption>
                <thead>
                    <tr><th scope="col">Género</th><th scope="col" class="data-table__num">Álbumes</th></tr>
                </thead>
                <tbody>
                    @foreach ($genres as $genre => $total)
                        <tr><th scope="row">{{ $genre }}</th><td class="data-table__num">{{ $total }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
