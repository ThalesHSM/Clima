<?php
namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $st = Database::get()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public static function create(string $name, string $email, string $password): int
    {
        $db = Database::get();
        $st = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $st->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $id = (int) $db->lastInsertId();

        // Novo usuário já começa com algumas ações sugeridas
        foreach (Habit::DEFAULTS as $title) {
            Habit::create($id, $title);
        }
        return $id;
    }
}
