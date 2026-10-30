<?php

namespace App\Policies;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Segunda línea de I2 (05 §2): el Route::bind con alcance de usuario ya da 404 a lo ajeno.
 * Esto cubre el caso de que alguien cambie el binding; responde igual, 404.
 */
class ArtistPolicy
{
    public function view(User $user, Artist $artist): Response
    {
        return $user->id === $artist->user_id ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Artist $artist): Response
    {
        return $this->view($user, $artist);
    }

    public function delete(User $user, Artist $artist): Response
    {
        return $this->view($user, $artist);
    }
}
