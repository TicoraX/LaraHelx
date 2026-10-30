<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlbumRequest;
use App\Models\Album;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Una consulta: el hasManyThrough ya une artists; columnas calificadas (05 §2).
        return view('albums.index', [
            'albums' => $user->albums()
                ->select('albums.id', 'albums.title', 'albums.year', 'albums.genre', 'albums.cover_path', 'artists.name as artist_name')
                ->orderBy('albums.title')->get(),
            'hasArtists' => $user->artists()->exists(),
        ]);
    }

    public function create(Request $request): View
    {
        // ?artist= preselecciona desde el detalle del artista; un id ajeno no aparece en el selector.
        return $this->form($request, (new Album)->forceFill(['artist_id' => $request->query('artist')]));
    }

    public function store(AlbumRequest $request): RedirectResponse
    {
        $album = $request->user()->artists()->findOrFail($request->validated('artist_id'))->albums()->create([
            ...$request->safe()->except(['artist_id', 'cover']),
            // I7: nombre aleatorio y extensión derivada del MIME.
            'cover_path' => $request->file('cover')?->store('covers', 'public'),
        ]);

        return redirect()->route('albums.show', $album)->with('status', 'Álbum creado');
    }

    public function show(Album $album): View
    {
        Gate::authorize('view', $album);

        return view('albums.show', ['album' => $album->load('artist:id,name')]);
    }

    public function edit(Request $request, Album $album): View
    {
        Gate::authorize('update', $album);

        return $this->form($request, $album);
    }

    public function update(AlbumRequest $request, Album $album): RedirectResponse
    {
        Gate::authorize('update', $album);
        $old = $album->cover_path;
        $new = $request->file('cover')?->store('covers', 'public');
        // artist_id ya pasó por I3 (exists con user_id); forceFill porque no es asignable en masa.
        $album->forceFill([...$request->safe()->except('cover'), 'cover_path' => $new ?? $old])->save();
        if ($new && $old) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('albums.show', $album)->with('status', 'Álbum actualizado');
    }

    public function destroy(Album $album): RedirectResponse
    {
        Gate::authorize('delete', $album);
        // La portada se borra solo si el DELETE pasó (03 §4).
        $album->delete();
        if ($album->cover_path) {
            Storage::disk('public')->delete($album->cover_path);
        }

        return redirect()->route('albums.index')->with('status', 'Álbum eliminado');
    }

    /** Selector con solo los artistas del usuario (H8); sin artistas, la vista muestra el CTA. */
    private function form(Request $request, Album $album): View
    {
        return view('albums.form', [
            'album' => $album,
            'artists' => $request->user()->artists()->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
