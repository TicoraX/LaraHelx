<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Frontera con GoRest (02 §6, 03 §2). Los errores se clasifican por código de estado, nunca por el
 * texto del cuerpo, y una respuesta con forma inesperada es un error: no se pinta a medias.
 */
class GoRestClient
{
    /** @var array{method: string, url: string, headers: array<string, string>}|null Última llamada, para el recuadro técnico. */
    public ?array $lastCall = null;

    /** @return list<array<string, mixed>> */
    public function list(): array
    {
        $users = $this->send('GET', '/users', ['email' => 'discoteca.test', 'per_page' => 100])->json();
        if (! is_array($users) || ! array_is_list($users)) {
            throw new GoRestException('Respuesta inesperada de GoRest');
        }

        return $users;
    }

    /** @return array<string, mixed> */
    public function find(int $id): array
    {
        return $this->user($this->send('GET', "/users/{$id}"));
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->user($this->send('POST', '/users', $data));
    }

    /**
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        return $this->user($this->send('PUT', "/users/{$id}", $data));
    }

    public function delete(int $id): void
    {
        $this->send('DELETE', "/users/{$id}");
    }

    /** @return array<string, mixed> */
    private function user(Response $response): array
    {
        $user = $response->json();
        if (! is_array($user) || ! is_int($user['id'] ?? null)) {
            throw new GoRestException('Respuesta inesperada de GoRest');
        }

        return $user;
    }

    /** @param  array<string, mixed>  $data */
    private function send(string $method, string $path, array $data = []): Response
    {
        $base = config('services.gorest.url');
        $token = (string) config('services.gorest.token');
        $get = $method === 'GET';
        $this->lastCall = [
            'method' => $method,
            'url' => $base.$path.($get && $data ? '?'.http_build_query($data) : ''),
            'headers' => ['Authorization' => 'Bearer '.$this->mask($token), 'Accept' => 'application/json'],
        ];

        $http = Http::baseUrl($base)->withToken($token)->acceptJson()->connectTimeout(3)->timeout(6);
        if ($get) {
            // Solo los GET se reintentan, y solo ante un fallo de conexión (02 §6).
            $http = $http->retry(2, 200, fn ($e) => $e instanceof ConnectionException, throw: false);
        }

        try {
            $response = $http->send($method, $path, $get ? ['query' => $data] : ['json' => $data]);
        } catch (ConnectionException $e) {
            throw new GoRestException("No pudimos contactar a GoRest ({$e->getMessage()})");
        }

        return $response->successful() ? $response : throw $this->error($response);
    }

    private function error(Response $response): GoRestException
    {
        return match ($response->status()) {
            401 => new GoRestException('El token de GoRest no es válido o no está configurado'),
            403 => new GoRestException('Ese usuario lo creó otra cuenta de GoRest; solo puedes editar los que creaste aquí'),
            404 => new GoRestException('Ese usuario ya no existe en GoRest'),
            422 => new GoRestException('GoRest rechazó los datos', $this->fieldErrors($response)),
            429 => new GoRestException('GoRest limitó las peticiones (429). Espera un momento y reintenta.'),
            default => new GoRestException("GoRest respondió con un error ({$response->status()})"),
        };
    }

    /** @return array<string, string> GoRest manda [{field, message}]. */
    private function fieldErrors(Response $response): array
    {
        $errors = [];
        foreach ((array) $response->json() as $item) {
            if (isset($item['field'], $item['message'])) {
                $errors[$item['field']] = $item['message'];
            }
        }

        return $errors;
    }

    private function mask(string $token): string
    {
        return strlen($token) > 8 ? substr($token, 0, 4).'…'.substr($token, -4) : '(sin configurar)';
    }
}
