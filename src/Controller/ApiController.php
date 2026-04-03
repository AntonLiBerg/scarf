<?php
declare(strict_types=1);

namespace Scarf\Controller;

use Scarf\Shared\FactoryGame;
use Scarf\Shared\IGame;

final class ApiController
{
    private IGame $_game;

    public function OnRequest(string $path, string $method): string
    {
        if ($method === 'GET' && ($path === '/' || $path === '/health')) {
            return json_encode($this->Health());
        }
        if ($method === 'GET' && ($path === '/startgame')) {
            return json_encode($this->StartGame());
        }

        if ($method === 'POST' && $path === '/echo') {
            $rawBody = file_get_contents('php://input');
            $data = json_decode($rawBody ?: 'null', true);

            return json_encode($this->Echo($data));
        }

        http_response_code(404);

        return json_encode([
            'error' => 'Not found',
        ]);
    }

    public function Health(): array
    {
        return [
            'status' => 'ok',
            'message' => 'PHP server running',
        ];
    }

    public function Echo(array|null $data): array
    {
        return [
            'received' => $data,
        ];
    }

    public function StartGame(): array
    {
        try {
            $this->_game = FactoryGame::MakeGame();

            return ['gameState' => $this->_game->InitGame()];
        } catch (\RuntimeException $error) {
            http_response_code(500);

            return [
                'error' => $error->getMessage(),
            ];
        }
    }

    public function TrySolution(int $id, array $actions): array
    {
        try {
            $res = $this->_game.TrySolution($id, $actions);

            return ['result' => $res];
        } catch (\RuntimeException $error) {
            http_response_code(500);

            return [
                'error' => $error->getMessage(),
            ];
        }
    }
}
