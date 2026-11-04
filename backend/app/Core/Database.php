<?php
namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function get(): PDO
    {
        if (self::$pdo === null) {
            $root = dirname(__DIR__, 2);
            self::$pdo = new PDO('sqlite:' . $root . '/data/clima.sqlite');
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Cria as tabelas na primeira execução
            self::$pdo->exec(file_get_contents($root . '/database/schema.sql'));
        }
        return self::$pdo;
    }
}
