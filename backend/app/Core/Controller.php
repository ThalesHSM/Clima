<?php
namespace App\Core;

class Controller
{
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function error(string $msg, int $status = 400): void
    {
        $this->json(['error' => $msg], $status);
    }

    /** Corpo da requisição (JSON) como array. */
    protected function input(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    protected function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    protected function requireLogin(): int
    {
        if (!$this->userId()) {
            $this->error('Não autenticado.', 401);
        }
        return $this->userId();
    }
}
