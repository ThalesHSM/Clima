<?php
namespace App\Models;

use App\Core\Database;

class Habit
{
    public const DEFAULTS = [
        'Evitei desperdícios e comprei só o necessário',
        'Separei o lixo para reciclagem',
        'Usei transporte sustentável (a pé, bicicleta ou público)',
        'Desliguei aparelhos da tomada',
        'Tomei um banho curto',
    ];

    public static function create(int $userId, string $title): void
    {
        $st = Database::get()->prepare('INSERT INTO habits (user_id, title, created_at) VALUES (?, ?, ?)');
        $st->execute([$userId, $title, date('Y-m-d H:i:s')]);
    }

    /** Ações ativas do usuário, indicando se foram feitas hoje. */
    public static function listWithToday(int $userId, string $today): array
    {
        $st = Database::get()->prepare(
            'SELECT h.id, h.title,
                    EXISTS (SELECT 1 FROM checkins c WHERE c.habit_id = h.id AND c.date = ?) AS done
             FROM habits h
             WHERE h.user_id = ? AND h.active = 1
             ORDER BY h.id'
        );
        $st->execute([$today, $userId]);

        return array_map(fn($r) => [
            'id' => (int) $r['id'],
            'title' => $r['title'],
            'done' => (bool) $r['done'],
        ], $st->fetchAll());
    }

    public static function rename(int $userId, int $habitId, string $title): void
    {
        $st = Database::get()->prepare('UPDATE habits SET title = ? WHERE id = ? AND user_id = ? AND active = 1');
        $st->execute([$title, $habitId, $userId]);
    }

    /** Remove da lista (o histórico continua guardado). */
    public static function remove(int $userId, int $habitId): void
    {
        $st = Database::get()->prepare('UPDATE habits SET active = 0, removed_at = ? WHERE id = ? AND user_id = ? AND active = 1');
        $st->execute([date('Y-m-d'), $habitId, $userId]);
    }

    public static function belongsTo(int $userId, int $habitId): bool
    {
        $st = Database::get()->prepare('SELECT 1 FROM habits WHERE id = ? AND user_id = ? AND active = 1');
        $st->execute([$habitId, $userId]);
        return (bool) $st->fetch();
    }
}
