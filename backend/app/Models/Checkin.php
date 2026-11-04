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

    /** Histórico desde uma data: lista de ['date' => ..., 'items' => [títulos]], mais recente primeiro. */
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
            $result[] = ['date' => $date, 'items' => $items];
        }
        return $result;
    }
}
