<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Checkin;
use App\Models\Habit;

class HabitController extends Controller
{
    /** Lista de hoje (checkboxes já vêm limpos em dias novos). */
    public function index(): void
    {
        $userId = $this->requireLogin();
        $today = date('Y-m-d');
        $this->json([
            'today' => $today,
            'closed' => Checkin::isClosed($userId, $today),
            'habits' => Habit::listWithToday($userId, $today),
        ]);
    }

    public function store(): void
    {
        $userId = $this->requireLogin();
        $title = trim($this->input()['title'] ?? '');

        if ($title === '' || mb_strlen($title) > 120) {
            $this->error('Digite uma ação com até 120 caracteres.', 422);
        }

        Habit::create($userId, $title);
        $this->json(['ok' => true], 201);
    }

    public function toggle(): void
    {
        $userId = $this->requireLogin();
        $in = $this->input();
        $habitId = (int) ($in['id'] ?? 0);

        if (!Habit::belongsTo($userId, $habitId)) {
            $this->error('Ação não encontrada.', 404);
        }
        if (Checkin::isClosed($userId, date('Y-m-d'))) {
            $this->error('O dia já foi finalizado. Reabra para editar.', 409);
        }

        Checkin::set($userId, $habitId, date('Y-m-d'), (bool) ($in['done'] ?? false));
        $this->json(['ok' => true]);
    }

    /** Finaliza (closed = true) ou reabre (closed = false) o dia de hoje. */
    public function closeDay(): void
    {
        $userId = $this->requireLogin();
        $closed = (bool) ($this->input()['closed'] ?? true);
        Checkin::setClosed($userId, date('Y-m-d'), $closed);
        $this->json(['closed' => $closed]);
    }

    public function delete(): void
    {
        $userId = $this->requireLogin();
        Habit::remove($userId, (int) ($this->input()['id'] ?? 0));
        $this->json(['ok' => true]);
    }

    /** Últimos 30 dias. */
    public function history(): void
    {
        $userId = $this->requireLogin();
        $since = date('Y-m-d', strtotime('-30 days'));
        $this->json(['days' => Checkin::history($userId, $since)]);
    }
}
