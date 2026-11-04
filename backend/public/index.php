<?php
// Ponto de entrada da API. Rodar com: php -S localhost:8000 -t public public/index.php

use App\Controllers\AuthController;
use App\Controllers\HabitController;
use App\Core\Router;

date_default_timezone_set('America/Sao_Paulo');

// Autoload simples: App\Core\Router -> app/Core/Router.php
spl_autoload_register(function (string $class) {
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (str_starts_with($class, 'App\\') && is_file($file)) {
        require $file;
    }
});

// CORS: o frontend roda em outra porta (localhost:3000)
$allowedOrigin = 'http://localhost:3000';
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

$router = new Router();
$router->post('/api/register', [AuthController::class, 'register']);
$router->post('/api/login', [AuthController::class, 'login']);
$router->post('/api/logout', [AuthController::class, 'logout']);
$router->get('/api/me', [AuthController::class, 'me']);

$router->get('/api/habits', [HabitController::class, 'index']);
$router->post('/api/habits', [HabitController::class, 'store']);
$router->post('/api/habits/toggle', [HabitController::class, 'toggle']);
$router->post('/api/habits/close-day',[HabitController::class, 'closeDay']);
$router->post('/api/habits/delete',[HabitController::class, 'delete']);
$router->get('/api/history', [HabitController::class, 'history']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro interno no servidor.']);
}
