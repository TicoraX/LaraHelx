<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExternalUserRequest;
use App\Services\GoRestClient;
use App\Services\GoRestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Pantallas de /external/users (03 §2). Ninguna escritura finge éxito: ante un error se vuelve con el mensaje. */
class ExternalUserController extends Controller
{
    public function __construct(private GoRestClient $gorest) {}

    public function index(): View
    {
        try {
            $users = $this->gorest->list();
        } catch (GoRestException $e) {
            $error = $e->getMessage();
        }

        return view('external.index', ['users' => $users ?? [], 'error' => $error ?? session('error'), 'call' => $this->gorest->lastCall]);
    }

    public function create(): View
    {
        return view('external.form', ['user' => []]);
    }

    public function store(ExternalUserRequest $request): RedirectResponse
    {
        try {
            $user = $this->gorest->create($request->validated());
        } catch (GoRestException $e) {
            return $this->back($e);
        }

        return $this->done("Usuario creado en GoRest (id {$user['id']})");
    }

    public function edit(int $id): View|RedirectResponse
    {
        try {
            $user = $this->gorest->find($id);
        } catch (GoRestException $e) {
            return redirect()->route('external.users.index')->with('error', $e->getMessage());
        }

        return view('external.form', ['user' => $user]);
    }

    public function update(ExternalUserRequest $request, int $id): RedirectResponse
    {
        try {
            $this->gorest->update($id, $request->validated());
        } catch (GoRestException $e) {
            return $this->back($e);
        }

        return $this->done("Usuario actualizado en GoRest (id {$id})");
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->gorest->delete($id);
        } catch (GoRestException $e) {
            return redirect()->route('external.users.index')->with('error', $e->getMessage());
        }

        return $this->done("Usuario eliminado de GoRest (id {$id})");
    }

    /** Los 422 de GoRest van junto a cada campo; cualquier otro error, en el resumen (`gorest`). */
    private function back(GoRestException $e): RedirectResponse
    {
        return back()->withInput()->withErrors($e->fieldErrors ?: ['gorest' => $e->getMessage()]);
    }

    private function done(string $message): RedirectResponse
    {
        return redirect()->route('external.users.index')->with('status', $message);
    }
}
