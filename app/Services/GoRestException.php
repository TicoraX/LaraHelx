<?php

namespace App\Services;

use RuntimeException;

/** Error de GoRest ya traducido a un mensaje para el usuario; en un 422 trae los errores por campo. */
class GoRestException extends RuntimeException
{
    /** @param  array<string, string>  $fieldErrors */
    public function __construct(string $message, public readonly array $fieldErrors = [])
    {
        parent::__construct($message);
    }
}
