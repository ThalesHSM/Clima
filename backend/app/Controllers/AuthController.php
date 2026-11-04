<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function register(): void
    {
        $in = $this->input();
        $name = trim($in['name'] ?? '');
        $email = strtolower(trim($in['email'] ?? ''));
        $password = $in['password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $this->error('Informe nome, e-mail válido e senha com pelo menos 6 caracteres.', 422);
        }
        if (User::findByEmail($email)) {
            $this->error('Este e-mail já está cadastrado.', 409);
        }

        $id = User::create($name, $email, $password);
        $this->startSession($id, $name);
        $this->json(['name' => $name], 201);
    }

    public function login(): void
    {
        $in = $this->input();
        $user = User::findByEmail(strtolower(trim($in['email'] ?? '')));

        if (!$user || !password_verify($in['password'] ?? '', $user['password'])) {
            $this->error('E-mail ou senha incorretos.', 401);
        }

        $this->startSession((int) $user['id'], $user['name']);
        $this->json(['name' => $user['name']]);
    }

    public function logout(): void
    {
        session_destroy();
        $this->json(['ok' => true]);
    }

    /** Usado pelo frontend para saber se há alguém logado. */
    public function me(): void
    {
        $this->requireLogin();
        $this->json(['name' => $_SESSION['user_name']]);
    }

    private function startSession(int $id, string $name): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
    }
}
