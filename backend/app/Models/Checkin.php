<?php
namespace App\Models;

use App\Core\Database;

class Checkin
{
    public static function set(int $userId, int $habitId, string $date, bool $done): void
    {
        $db = Database::get();
        if ($done) {
            $st = $db->prepare('INSERT OR IGNORE INTO checkins (habit_id, user_id, date) VALUES (?, ?, ?)');
        } else {
            $st = $db->prepare('DELETE FROM checkins WHERE habit_id = ? AND user_id = ? AND date = ?');
        }
        $st->execute([$habitId, $userId, $date]);
    }

    public static function isClosed(int $userId, string $date): bool
    {
        $st = Database::get()->prepare('SELECT 1 FROM closed_days WHERE user_id = ? AND date = ?');
        $st->execute([$userId, $date]);
        return (bool) $st->fetch();
    }

    public static function setClosed(int $userId, string $date, bool $closed): void
    {
        $sql = $closed
            ? 'INSERT OR IGNORE INTO closed_days (user_id, date) VALUES (?, ?)'
            : 'DELETE FROM closed_days WHERE user_id = ? AND date = ?';
        Database::get()->prepare($sql)->execute([$userId, $date]);
    }

    /**
     * Dias seguidos finalizados. Se hoje ainda não foi finalizado, a contagem
     * parte de ontem (a sequência só "quebra" quando um dia inteiro passa sem finalizar).
     */
    public static function streak(int $userId, string $today): int
    {
        $st = Database::get()->prepare('SELECT date FROM closed_days WHERE user_id = ? ORDER BY date DESC');
        $st->execute([$userId]);
        $closed = array_flip($st->fetchAll(\PDO::FETCH_COLUMN));

        $day = isset($closed[$today]) ? $today : date('Y-m-d', strtotime($today . ' -1 day'));
        $count = 0;
        while (isset($closed[$day])) {
            $count++;
            $day = date('Y-m-d', strtotime($day . ' -1 day'));
        }
        return $count;
    }

    /** Histórico desde uma data: lista de ['date', 'closed', 'items' => [títulos]], mais recente primeiro. */
    public static function history(int $userId, string $since): array
    {
        $st = Database::get()->prepare(
            'SELECT c.date, h.title
             FROM checkins c JOIN habits h ON h.id = c.habit_id
             WHERE c.user_id = ? AND c.date >= ?
             ORDER BY c.date DESC, h.id'
        );
        $st->execute([$userId, $since]);

        $days = [];
        foreach ($st->fetchAll() as $row) {
            $days[$row['date']][] = $row['title'];
        }

        $result = [];
        foreach ($days as $date => $items) {
            $result[] = [
                'date' => $date,
                'closed' => self::isClosed($userId, $date),
                'items' => $items,
            ];
        }
        return $result;
    }
}
