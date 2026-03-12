<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Scarf\Controller\ApiController;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$controller = new ApiController();

if ($method === 'GET' && ($path === '/' || $path === '/health')) {
    echo json_encode($controller->health());
    exit;
}

if ($method === 'POST' && $path === '/echo') {
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody ?: 'null', true);

    echo json_encode($controller->echo($data));
    exit;
}

http_response_code(404);

echo json_encode([
    'error' => 'Not found',
]);
