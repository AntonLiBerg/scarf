<?php
declare(strict_types=1);

namespace Scarf\Controller;

final class ApiController
{
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
}
