<?php
namespace App\Core;

use App\Models\Habit;
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
            self::migrate(self::$pdo);
        }
        return self::$pdo;
    }

    /** Adiciona colunas novas em bancos criados por versões anteriores. */
    private static function migrate(PDO $pdo): void
    {
        $cols = array_column($pdo->query('PRAGMA table_info(habits)')->fetchAll(), 'name');

        if (!in_array('co2_g', $cols, true)) {
            $pdo->exec('ALTER TABLE habits ADD COLUMN co2_g INTEGER NOT NULL DEFAULT 0');
            $st = $pdo->prepare('UPDATE habits SET co2_g = ? WHERE title = ?');
            foreach (Habit::CATALOG as $title => $co2) {
                if ($co2 > 0) {
                    $st->execute([$co2, $title]);
                }
            }
        }

        if (!in_array('removed_at', $cols, true)) {
            $pdo->exec('ALTER TABLE habits ADD COLUMN removed_at TEXT');
            $pdo->prepare('UPDATE habits SET removed_at = ? WHERE active = 0')->execute([date('Y-m-d')]);
        }
    }
}
