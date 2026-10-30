<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ArtistRequest;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ArtistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ArtistResource::collection(
            $request->user()->artists()->withCount('albums')->orderBy('name')->paginate(15)
        );
    }

    public function store(ArtistRequest $request): JsonResponse
    {
        $artist = $request->user()->artists()->create($request->validated());

        return (new ArtistResource($artist->loadCount('albums')))->response()
            ->header('Location', route('api.artists.show', $artist));
    }

    public function show(Artist $artist): ArtistResource
    {
        Gate::authorize('view', $artist);

        return new ArtistResource($artist->loadCount('albums'));
    }

    public function update(ArtistRequest $request, Artist $artist): ArtistResource
    {
        Gate::authorize('update', $artist);
        // PUT reemplaza: los opcionales omitidos quedan en null (05 §3).
        $artist->update(['country' => null, 'bio' => null, ...$request->validated()]);

        return new ArtistResource($artist->loadCount('albums'));
    }

    public function destroy(Artist $artist): JsonResponse
    {
        Gate::authorize('delete', $artist);
        $artist->deleteWithImages();

        return response()->json(null, 204);
    }
}
