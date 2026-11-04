<?php
namespace App\Models;

use App\Core\Database;

class Stats
{
    /** Dados do calendário e das estatísticas de um mês ("YYYY-MM"). */
    public static function month(int $userId, string $month): array
    {
        $db = Database::get();
        $first = $month . '-01';
        $daysInMonth = (int) date('t', strtotime($first));
        $last = sprintf('%s-%02d', $month, $daysInMonth);
        $today = date('Y-m-d');

        // Quantas ações foram feitas (e quanto CO2) em cada dia do mês
        $st = $db->prepare(
            'SELECT c.date, COUNT(*) AS n, SUM(h.co2_g) AS co2
             FROM checkins c JOIN habits h ON h.id = c.habit_id
             WHERE c.user_id = ? AND c.date BETWEEN ? AND ?
             GROUP BY c.date'
        );
        $st->execute([$userId, $first, $last]);
        $perDay = [];
        foreach ($st->fetchAll() as $r) {
            $perDay[$r['date']] = $r;
        }

        // Para a porcentagem: quantas ações existiam na lista em cada dia
        $st = $db->prepare('SELECT substr(created_at, 1, 10) AS created, removed_at FROM habits WHERE user_id = ?');
        $st->execute([$userId]);
        $habits = $st->fetchAll();

        $days = [];
        $total = 0;
        $activeDays = 0;
        $co2 = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%s-%02d', $month, $d);
            $done = (int) ($perDay[$date]['n'] ?? 0);

            $listed = 0;
            foreach ($habits as $h) {
                if ($h['created'] <= $date && ($h['removed_at'] === null || $h['removed_at'] > $date)) {
                    $listed++;
                }
            }
            $listed = max($listed, $done);

            $days[] = [
                'date' => $date,
                'done' => $done,
                'total' => $listed,
                'pct' => $listed ? (int) round($done / $listed * 100) : 0,
                'future' => $date > $today,
            ];

            $total += $done;
            $activeDays += $done > 0 ? 1 : 0;
            $co2 += (int) ($perDay[$date]['co2'] ?? 0);
        }

        // Ação mais feita no mês
        $st = $db->prepare(
            'SELECT h.title, COUNT(*) AS n
             FROM checkins c JOIN habits h ON h.id = c.habit_id
             WHERE c.user_id = ? AND c.date BETWEEN ? AND ?
             GROUP BY h.id ORDER BY n DESC, h.id LIMIT 1'
        );
        $st->execute([$userId, $first, $last]);
        $top = $st->fetch();

        return [
            'month' => $month,
            'days' => $days,
            'stats' => [
                'total' => $total,
                'activeDays' => $activeDays,
                'average' => $activeDays ? round($total / $activeDays, 1) : 0,
                'top' => $top ? ['title' => $top['title'], 'count' => (int) $top['n']] : null,
                'co2' => $co2,
            ],
        ];
    }
}
