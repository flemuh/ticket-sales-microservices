<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controller\VendaController;
use App\Database\Connection;
use App\Service\VendaService;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$pdo = Connection::getPDO();
$vendaService = new VendaService($pdo);
$vendaController = new VendaController($vendaService);

if ($uri === '/comprar' && $method === 'POST') {
    $vendaController->comprar();
    exit;
}

http_response_code(404);
echo json_encode(['erro' => 'Rota não encontrada']);