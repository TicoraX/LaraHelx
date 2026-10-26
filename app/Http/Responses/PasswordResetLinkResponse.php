<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * "Olvidé mi contraseña" responde lo mismo exista o no el correo (02 §3): Fortify por defecto
 * devuelve "We can't find a user..." para INVALID_USER y un aviso propio para RESET_THROTTLED.
 */
class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public const MESSAGE = 'Si el correo existe, te enviamos un enlace.';

    public function toResponse($request)
    {
        return back()->with('status', self::MESSAGE);
    }
}
