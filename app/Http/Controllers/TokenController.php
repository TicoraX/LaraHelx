<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/** Tokens de API desde la UI (03 §1): el valor en claro viaja una sola vez, por flash. */
class TokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['token_name' => ['required', 'string', 'max:60']]);

        return redirect()->route('profile')
            ->with('new_token', $request->user()->createToken($data['token_name'])->plainTextToken);
    }

    public function destroy(PersonalAccessToken $apiToken): RedirectResponse
    {
        $apiToken->delete();

        return redirect()->route('profile')->with('status', 'Token revocado');
    }
}
