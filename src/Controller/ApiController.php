<?php
declare(strict_types=1);

namespace Scarf\Controller;

final class ApiController
{
    public function onRequest(string $path, string $method): string
    {
        if ($method === 'GET' && ($path === '/' || $path === '/health')) {
            return json_encode($this->health());
        }

        if ($method === 'POST' && $path === '/echo') {
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody ?: 'null', true);

            return json_encode($this->echo($data));
        }

        http_response_code(404);

        return json_encode([
            'error' => 'Not found',
        ]);
    }

    public function health(): array
    {
        return [
            'status' => 'ok',
            'message' => 'PHP server running',
        ];
    }

    public function echo(array|null $data): array
    {
        return [
            'received' => $data,
        ];
    }
    public function startGame(): array
    {
        
    }
}
