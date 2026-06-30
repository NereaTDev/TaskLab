<?php

namespace App\Services;

use App\Models\SlackConnection;
use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SlackNotificationService
{
    private const API_BASE = 'https://slack.com/api';

    public function notifyTaskAssigned(Task $task): void
    {
        $assignee = $task->assignee;
        if (! $assignee) {
            return;
        }

        $dmChannel = $this->openDm($task);
        if (! $dmChannel) {
            return;
        }

        $this->sendMessage($dmChannel, $this->buildAssignedMessage($task, $assignee->name), $task->id, 'assigned');
    }

    public function notifyTaskNeedsReview(Task $task, array $issues): void
    {
        $dmChannel = $this->openDm($task);
        if (! $dmChannel) {
            return;
        }

        $title      = $task->title ?? Str::limit($task->description_raw, 60, '…');
        $issueLines = implode("\n", array_map(fn ($i) => "  • {$i}", $issues));

        $text = <<<MSG
        ⚠️ *Tu solicitud necesita más información*

        *Tarea:* {$title}

        No hemos podido añadir esta tarea al flujo de trabajo porque no cumple los criterios mínimos de calidad:

        {$issueLines}

        Por favor, responde con más contexto para que podamos procesarla correctamente.
        MSG;

        $this->sendMessage($dmChannel, $text, $task->id, 'needs_review');
    }

    public function notifyTaskConflict(Task $task, Task $conflictingTask): void
    {
        $dmChannel = $this->openDm($task);
        if (! $dmChannel) {
            return;
        }

        $title          = $task->title ?? Str::limit($task->description_raw, 60, '…');
        $conflictTitle  = $conflictingTask->title ?? "Tarea #{$conflictingTask->id}";

        $text = <<<MSG
        🔄 *Tu solicitud puede entrar en conflicto con una tarea existente*

        *Tu petición:* {$title}
        *Tarea existente:* {$conflictTitle} (#{$conflictingTask->id})
        *Estado actual:* {$conflictingTask->status}

        Hemos creado tu tarea de todas formas para que puedas revisarla.
        MSG;

        $this->sendMessage($dmChannel, $text, $task->id, 'conflict');
    }

    public function notifyTaskMerged(Task $newTask, Task $existingTask): void
    {
        $existingTitle = $existingTask->title ?? "Tarea #{$existingTask->id}";
        $newReporter   = $newTask->reporter?->name ?? 'Alguien';

        $dmNew = $this->openDm($newTask);
        if ($dmNew) {
            $text = <<<MSG
            🔗 *Tu solicitud ha sido fusionada con una tarea existente*

            Hemos detectado que tu petición aporta contexto adicional a:

            *{$existingTitle}* (#{$existingTask->id}) — Estado: {$existingTask->status}

            Tu información ha sido añadida y te hemos registrado como co-solicitante.
            MSG;

            $this->sendMessage($dmNew, $text, $newTask->id, 'merged-new');
        }

        $dmExisting = $this->openDm($existingTask);
        if ($dmExisting) {
            $text = <<<MSG
            💬 *Alguien ha añadido contexto a tu tarea*

            *{$existingTitle}* (#{$existingTask->id})
            *Nuevo contexto aportado por:* {$newReporter}
            MSG;

            $this->sendMessage($dmExisting, $text, $existingTask->id, 'merged-existing');
        }
    }

    private function openDm(Task $task): ?string
    {
        if ($task->source !== 'slack' || ! $task->external_user_id) {
            return null;
        }

        $token = $this->botToken();
        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
            ->post(self::API_BASE . '/conversations.open', [
                'users' => $task->external_user_id,
            ]);

        if (! $response->ok() || ! $response->json('ok')) {
            Log::warning('SlackNotificationService: no se pudo abrir DM', [
                'slack_user_id' => $task->external_user_id,
                'error'         => $response->json('error'),
            ]);
            return null;
        }

        return $response->json('channel.id');
    }

    private function sendMessage(string $channelId, string $text, int $taskId, string $type): void
    {
        $token = $this->botToken();
        if (! $token) {
            return;
        }

        $response = Http::withToken($token)
            ->post(self::API_BASE . '/chat.postMessage', [
                'channel' => $channelId,
                'text'    => $text,
            ]);

        if ($response->ok() && $response->json('ok')) {
            Log::info("SlackNotificationService: {$type} enviado", ['task_id' => $taskId]);
        } else {
            Log::warning("SlackNotificationService: error enviando {$type}", [
                'error' => $response->json('error'),
            ]);
        }
    }

    private function botToken(): ?string
    {
        return \App\Models\SlackConnection::active()?->bot_token
            ?? config('services.slack.bot_token');
    }

    private function buildAssignedMessage(Task $task, string $assigneeName): string
    {
        $title    = $task->title ?? 'Sin título';
        $priority = match ($task->priority) {
            'critical' => '🔴 Crítica',
            'high'     => '🟠 Alta',
            'medium'   => '🟡 Media',
            'low'      => '🟢 Baja',
            default    => $task->priority ?? '—',
        };

        return <<<MSG
        ✅ *Tu solicitud ha sido procesada y asignada*

        *Tarea:* {$title}
        *Asignado a:* {$assigneeName}
        *Prioridad:* {$priority}

        Puedes seguir el progreso en TaskLab.
        MSG;
    }
}
