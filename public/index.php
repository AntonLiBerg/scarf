<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Scarf\Controller\ApiController;

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$controller = new ApiController();

echo $controller->OnRequest($path,$method);
exit;
