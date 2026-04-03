<?php
declare(strict_types=1);

namespace Scarf\Controller;

use Scarf\Shared\FactoryGame;
use Scarf\Shared\IGame;

final class ApiController
{
    private const ERROR_KEY = 'error';
    private const INPUT_STREAM = 'php://input';
    private const STATUS_INTERNAL_SERVER_ERROR = 500;

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
            $rawBody = file_get_contents(self::INPUT_STREAM);
            $data = json_decode($rawBody ?: 'null', true);

            return json_encode($this->Echo($data));
        }

        if ($method === 'POST' && $path === '/trysolution') {
            $rawBody = file_get_contents(self::INPUT_STREAM);
            $data = json_decode($rawBody ?: 'null', true);

            if (!is_array($data) || !isset($data['id']) || !is_array($data['actions'])) {
                http_response_code(400);

                return json_encode([
                    self::ERROR_KEY => 'Invalid request',
                ]);
            }

            return json_encode($this->TrySolution((int) $data['id'], $data['actions']));
        }

        http_response_code(404);

        return json_encode([
            self::ERROR_KEY => 'Not found',
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
            http_response_code(self::STATUS_INTERNAL_SERVER_ERROR);

            return [
                self::ERROR_KEY => $error->getMessage(),
            ];
        }
    }

    public function TrySolution(int $id, array $actions): array
    {
        try {
            if (!isset($this->_game)) {
                $this->_game = FactoryGame::MakeGame();
            }

            return $this->_game->TrySolution($id, $actions);
        } catch (\RuntimeException $error) {
            http_response_code(self::STATUS_INTERNAL_SERVER_ERROR);

            return [
                self::ERROR_KEY => $error->getMessage(),
            ];
        }
    }
}
