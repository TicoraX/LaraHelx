<?php

namespace App\Policies;

use App\Models\Album;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/** Segunda línea de I2, como ArtistPolicy: el dueño del álbum es el de su artista (05 §1). */
class AlbumPolicy
{
    public function view(User $user, Album $album): Response
    {
        return $user->id === $album->artist->user_id ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Album $album): Response
    {
        return $this->view($user, $album);
    }

    public function delete(User $user, Album $album): Response
    {
        return $this->view($user, $album);
    }
}
