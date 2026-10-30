<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Images;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Valida y guarda nombre, correo y foto (05 §3). El correo ya llega en minúsculas (fortify.lowercase_usernames).
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => Images::rules(),
        ])->validateWithBag('updateProfileInformation');

        Images::replace($input['photo'] ?? null, $user->photo_path, 'avatars', fn ($path) => $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'photo_path' => $path,
        ])->save());
    }
}
