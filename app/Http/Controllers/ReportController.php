<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/** Ficha PDF de un artista (CONTEXT.md: la ruta dice "reports" por convención técnica). */
class ReportController extends Controller
{
    public function artist(Artist $artist): Response
    {
        Gate::authorize('view', $artist);

        return Pdf::loadView('reports.artist', ['artist' => $artist, 'albums' => $artist->albums()->orderBy('year')->get()])
            ->download('artista-'.Str::slug($artist->name).'.pdf');
    }
}
