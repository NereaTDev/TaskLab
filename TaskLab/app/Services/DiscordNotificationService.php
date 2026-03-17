<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiscordNotificationService
{
    private const API_BASE = 'https://discord.com/api/v10';

    public function notifyTaskAssigned(Task $task): void
    {
        $assignee  = $task->assignee;
        if (! $assignee) {
            return;
        }

        $channelId = $this->openDm($task);
        if (! $channelId) {
            return;
        }

        $this->sendDm($channelId, $this->buildMessage($task, $assignee->name), $task->id, 'assigned');
    }

    public function notifyTaskNeedsReview(Task $task, array $issues): void
    {
        $channelId = $this->openDm($task);
        if (! $channelId) {
            return;
        }

        $title      = $task->title ?? Str::limit($task->description_raw, 60, '…');
        $issueLines = implode("\n", array_map(fn ($i) => "  • {$i}", $issues));

        $message = <<<MSG
        ⚠️ **Tu solicitud necesita más información**

        **Tarea:** {$title}

        No hemos podido añadir esta tarea al flujo de trabajo porque no cumple los criterios mínimos de calidad:

        {$issueLines}

        Por favor, responde con más contexto o reformula tu petición para que podamos procesarla correctamente. ¡Gracias!
        MSG;

        $this->sendDm($channelId, $message, $task->id, 'needs_review');
    }

    public function notifyTaskConflict(Task $task, Task $conflictingTask): void
    {
        $channelId = $this->openDm($task);
        if (! $channelId) {
            return;
        }

        $title           = $task->title ?? Str::limit($task->description_raw, 60, '…');
        $conflictTitle   = $conflictingTask->title ?? "Tarea #{$conflictingTask->id}";
        $conflictStatus  = $conflictingTask->status ?? '—';
        $conflictReporter = $conflictingTask->reporter?->name ?? 'Desconocido';

        $message = <<<MSG
        🔄 **Tu solicitud puede entrar en conflicto con una tarea existente**

        **Tu petición:** {$title}
        **Tarea existente:** {$conflictTitle} (#{$conflictingTask->id})
        **Estado actual:** {$conflictStatus}
        **Solicitada por:** {$conflictReporter}

        Esta tarea parece revertir o contradecir un cambio ya planificado o realizado. Hemos creado tu tarea de todas formas para que puedas revisarla.

        Si fue un error o tienes una razón válida para revertir el cambio, la tarea seguirá adelante. Si no, considera coordinar con el equipo antes de proceder.
        MSG;

        $this->sendDm($channelId, $message, $task->id, 'conflict');
    }

    public function notifyTaskMerged(Task $task, Task $existingTask): void
    {
        $channelId = $this->openDm($task);
        if (! $channelId) {
            return;
        }

        $existingTitle = $existingTask->title ?? "Tarea #{$existingTask->id}";

        $message = <<<MSG
        🔗 **Tu solicitud ha sido fusionada con una tarea existente**

        Hemos detectado que tu petición aporta contexto adicional a una tarea que ya está en el flujo de trabajo:

        **Tarea existente:** {$existingTitle} (#{$existingTask->id})
        **Estado:** {$existingTask->status}

        Tu información ha sido añadida a esa tarea y te hemos registrado como co-solicitante. ¡Gracias por el contexto extra!
        MSG;

        $this->sendDm($channelId, $message, $task->id, 'merged');
    }

    private function openDm(Task $task): ?string
    {
        $token = config('services.discord.bot_token');

        if (! $token || $task->source !== 'discord' || ! $task->external_user_id) {
            return null;
        }

        $response = Http::withToken($token, 'Bot')
            ->post(self::API_BASE . '/users/@me/channels', [
                'recipient_id' => $task->external_user_id,
            ]);

        if (! $response->ok()) {
            Log::warning('DiscordNotificationService: no se pudo abrir DM', [
                'discord_user_id' => $task->external_user_id,
                'status'          => $response->status(),
            ]);
            return null;
        }

        return $response->json('id');
    }

    private function sendDm(string $channelId, string $message, int $taskId, string $type): void
    {
        $response = Http::withToken(config('services.discord.bot_token'), 'Bot')
            ->post(self::API_BASE . "/channels/{$channelId}/messages", [
                'content' => $message,
            ]);

        if ($response->ok()) {
            Log::info("DiscordNotificationService: {$type} enviado", [
                'task_id'    => $taskId,
                'channel_id' => $channelId,
            ]);
        } else {
            Log::warning("DiscordNotificationService: error enviando {$type}", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }
    }

    private function buildMessage(Task $task, string $assigneeName): string
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
        ✅ **Tu solicitud ha sido procesada y asignada**

        **Tarea:** {$title}
        **Asignado a:** {$assigneeName}
        **Prioridad:** {$priority}

        Puedes seguir el progreso en TaskLab. ¡Gracias!
        MSG;
    }
}
