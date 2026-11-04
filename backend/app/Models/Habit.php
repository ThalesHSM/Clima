<?php
namespace App\Models;

use App\Core\Database;

class Habit
{
    /**
     * Ações sugeridas => CO2 evitado estimado, em gramas, por vez que é feita (0 = sem estimativa).
     * Só entram valores que têm fonte (veja o bloco "Como estimamos" em frontend/history.html):
     *  - transporte: ~5 km de carro evitados x 0,167 kg CO2e/km (UK DEFRA 2023)  ≈ 830 g
     *  - dia sem carne: 5,63 - 3,81 kg CO2e/dia (Scarborough et al., 2014)       ≈ 1820 g
     * Para as demais não encontramos um valor confiável, então ficam em 0 e não entram na soma.
     */
    public const CATALOG = [
        'Evitei desperdícios e comprei só o necessário' => 0,
        'Separei o lixo para reciclagem' => 0,
        'Usei transporte sustentável (a pé, bicicleta ou público)' => 830,
        'Desliguei aparelhos da tomada' => 0,
        'Tomei um banho curto' => 0,
        'Plantei ou cuidei de uma árvore' => 0,
        'Conversei com alguém sobre o clima' => 0,
        'Usei sacola retornável' => 0,
        'Fiz um dia sem carne' => 1820,
    ];

    /** Lista inicial de quem cria a conta. */
    public const DEFAULTS = [
        'Evitei desperdícios e comprei só o necessário',
        'Separei o lixo para reciclagem',
        'Usei transporte sustentável (a pé, bicicleta ou público)',
        'Desliguei aparelhos da tomada',
        'Tomei um banho curto',
    ];

    public static function create(int $userId, string $title): void
    {
        $st = Database::get()->prepare(
            'INSERT INTO habits (user_id, title, co2_g, created_at) VALUES (?, ?, ?, ?)'
        );
        $st->execute([$userId, $title, self::CATALOG[$title] ?? 0, date('Y-m-d H:i:s')]);
    }

    public static function existsActive(int $userId, string $title): bool
    {
        $st = Database::get()->prepare('SELECT 1 FROM habits WHERE user_id = ? AND active = 1 AND title = ? COLLATE NOCASE');
        $st->execute([$userId, $title]);
        return (bool) $st->fetch();
    }

    /** Ações ativas do usuário, indicando se foram feitas hoje. */
    public static function listWithToday(int $userId, string $today): array
    {
        $st = Database::get()->prepare(
            'SELECT h.id, h.title, h.co2_g,
                    EXISTS (SELECT 1 FROM checkins c WHERE c.habit_id = h.id AND c.date = ?) AS done
             FROM habits h
             WHERE h.user_id = ? AND h.active = 1
             ORDER BY h.id'
        );
        $st->execute([$today, $userId]);

        return array_map(fn($r) => [
            'id' => (int) $r['id'],
            'title' => $r['title'],
            'co2' => (int) $r['co2_g'],
            'done' => (bool) $r['done'],
        ], $st->fetchAll());
    }

    /** Total de CO2 evitado (gramas) de todas as marcações do usuário. */
    public static function co2Total(int $userId): int
    {
        $st = Database::get()->prepare(
            'SELECT COALESCE(SUM(h.co2_g), 0) FROM checkins c JOIN habits h ON h.id = c.habit_id WHERE c.user_id = ?'
        );
        $st->execute([$userId]);
        return (int) $st->fetchColumn();
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
