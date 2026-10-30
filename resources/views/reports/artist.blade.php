<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha de {{ $artist->name }}</title>
    {{-- dompdf entiende CSS 2.1: sin flex, grid, variables ni oklch (04 §9). Colores en hex. --}}
    <style>
        @page { margin: 18mm 16mm 20mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #161b22; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .head td { padding-bottom: 6pt; }
        .brand { font-size: 18pt; font-weight: bold; }
        .meta { font-size: 9pt; color: #4f565f; text-align: right; }
        .band { height: 4pt; background: #f3cf31; margin-bottom: 16pt; }
        .photo-col { width: 136px; }
        .cover-col { width: 48px; }
        .photo { width: 120px; height: 120px; }
        .initials { width: 118px; height: 118px; border: 1px solid #d5d9df; text-align: center; line-height: 118px; font-size: 28pt; font-weight: bold; color: #4f565f; }
        .name { font-size: 20pt; font-weight: bold; margin: 0 0 4pt; }
        .sub { color: #4f565f; margin: 0 0 8pt; }
        .bio { font-size: 10pt; margin: 0; }
        h2 { font-size: 12pt; margin: 22pt 0 6pt; }
        .albums th { font-size: 9pt; font-weight: bold; text-align: left; border-bottom: 1pt solid #161b22; padding: 4pt 4pt; }
        .albums td { border-bottom: 0.5pt solid #d5d9df; padding: 4pt; vertical-align: middle; }
        .albums tr { page-break-inside: avoid; }
        .num { text-align: right; }
        .cover { width: 40px; height: 40px; }
        .empty { color: #4f565f; }
        .foot { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8pt; color: #4f565f; border-top: 0.5pt solid #d5d9df; padding-top: 4pt; }
        .foot .page:after { content: "Página " counter(page); }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td class="brand">Discoteca</td>
            <td class="meta">Ficha de artista<br>{{ now()->timezone('America/Bogota')->format('d/m/Y') }}</td>
        </tr>
    </table>
    <div class="band"></div>

    <table>
        <tr>
            <td class="photo-col">
                @if ($artist->photo_path)
                    <img class="photo" src="{{ App\Support\Images::thumbnail($artist->photo_path, 240) }}" alt="">
                @else
                    <div class="initials">{{ collect(preg_split('/\s+/u', trim($artist->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}</div>
                @endif
            </td>
            <td>
                <p class="name">{{ $artist->name }}</p>
                <p class="sub">{{ collect([$artist->country, $artist->genre])->filter()->implode(' · ') }}</p>
                @if ($artist->bio)
                    <p class="bio">@foreach (preg_split('/\R/u', $artist->bio) as $line){{ $line }}<br>@endforeach</p>
                @endif
            </td>
        </tr>
    </table>

    <h2>Álbumes</h2>
    @if ($albums->isEmpty())
        <p class="empty">Este artista todavía no tiene álbumes.</p>
    @else
        <table class="albums">
            <thead>
                <tr><th class="cover-col"></th><th>Título</th><th class="num">Año</th><th>Género</th></tr>
            </thead>
            <tbody>
                @foreach ($albums as $album)
                    <tr>
                        <td>
                            @if ($album->cover_path)
                                <img class="cover" src="{{ App\Support\Images::thumbnail($album->cover_path, 80) }}" alt="">
                            @endif
                        </td>
                        <td>{{ $album->title }}</td>
                        <td class="num">{{ $album->year }}</td>
                        <td>{{ $album->genre }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="foot">Discoteca · {{ auth()->user()->name }} · <span class="page"></span></div>
</body>
</html>
