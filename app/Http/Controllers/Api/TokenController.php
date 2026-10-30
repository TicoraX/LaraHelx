<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:60'],
        ]);

        $user = User::where('email', Str::lower($data['email']))->first();

        // Mismo mensaje con correo inexistente o clave mala: no enumera usuarios (05 §5, 25).
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['Las credenciales no son válidas']]);
        }

        return response()->json(['token' => $user->createToken($data['device_name'])->plainTextToken], 201);
    }
}
