<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArtistRequest;
use App\Models\Artist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
        $old = $artist->photo_path;
        $new = $request->file('photo')?->store('artists', 'public');
        // Si la fila no se guarda, la excepción sube y la foto vieja queda intacta (03 §4).
        $artist->update([...$request->safe()->except('photo'), 'photo_path' => $new ?? $old]);
        if ($new && $old) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('artists.show', $artist)->with('status', 'Artista actualizado');
    }

    public function destroy(Artist $artist): RedirectResponse
    {
        Gate::authorize('delete', $artist);
        // I4: las rutas se juntan antes; el DELETE con su cascada es atómico y los archivos
        // se borran solo si la fila ya no está. Si el DELETE falla, archivos y fila siguen ahí.
        $paths = $artist->albums()->pluck('cover_path')->push($artist->photo_path)->filter()->all();
        $artist->delete();
        Storage::disk('public')->delete($paths);

        return redirect()->route('artists.index')->with('status', 'Artista eliminado');
    }
}
