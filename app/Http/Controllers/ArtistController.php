<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArtistRequest;
use App\Models\Artist;
use App\Support\Images;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ArtistController extends Controller
{
    public function index(Request $request): View
    {
        // Sin bio (TEXT) y con withCount: una sola consulta para toda la tabla (05 del framework, reglas 1 y 4).
        return view('artists.index', ['artists' => $request->user()->artists()
            ->select('id', 'user_id', 'name', 'country', 'genre', 'photo_path')
            ->withCount('albums')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('artists.form', ['artist' => new Artist]);
    }

    public function store(ArtistRequest $request): RedirectResponse
    {
        $artist = $request->user()->artists()->create([
            ...$request->safe()->except('photo'),
            // I7: store() genera el nombre aleatorio y deriva la extensión del MIME.
            'photo_path' => $request->file('photo')?->store('artists', 'public'),
        ]);

        return redirect()->route('artists.show', $artist)->with('status', 'Artista creado');
    }

    public function show(Artist $artist): View
    {
        Gate::authorize('view', $artist);

        return view('artists.show', ['artist' => $artist, 'albums' => $artist->albums()->orderBy('year')->get()]);
    }

    public function edit(Artist $artist): View
    {
        Gate::authorize('update', $artist);

        return view('artists.form', ['artist' => $artist]);
    }

    public function update(ArtistRequest $request, Artist $artist): RedirectResponse
    {
        Gate::authorize('update', $artist);
        Images::replace($request->file('photo'), $artist->photo_path, 'artists',
            fn ($path) => $artist->update([...$request->safe()->except('photo'), 'photo_path' => $path]));

        return redirect()->route('artists.show', $artist)->with('status', 'Artista actualizado');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        Gate::authorize('delete', $artist);
        $artist->deleteWithImages();

        return redirect()->route('artists.index')->with('status', 'Artista eliminado');
    }
}
