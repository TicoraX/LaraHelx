<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatsController extends Controller
{
    public function __invoke(Request $request): View
    {
        // whereIn en vez de $user->albums(): evita el JOIN y las columnas ambiguas (05 §2).
        $genres = Album::whereIn('artist_id', $request->user()->artists()->select('id'))
            ->selectRaw('genre, count(*) as total')->groupBy('genre')
            ->orderByDesc('total')->orderBy('genre')->pluck('total', 'genre')->all();

        return view('stats', ['genres' => $genres]);
    }
}
