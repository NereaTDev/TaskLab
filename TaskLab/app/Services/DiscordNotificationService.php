<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotificationService
{
    private const API_BASE = 'https://discord.com/api/v10';

    public function notifyTaskAssigned(Task $task): void
    {
        $token = config('services.discord.bot_token');

        if (! $token || $task->source !== 'discord' || ! $task->external_user_id) {
            return;
        }

        $assignee = $task->assignee;
        if (! $assignee) {
            return;
        }

        // 1. Abrir (o recuperar) el canal DM con el usuario de Discord
        $dmResponse = Http::withToken($token, 'Bot')
            ->post(self::API_BASE . '/users/@me/channels', [
                'recipient_id' => $task->external_user_id,
            ]);

        if (! $dmResponse->ok()) {
            Log::warning('DiscordNotificationService: no se pudo abrir el DM', [
                'discord_user_id' => $task->external_user_id,
                'status'          => $dmResponse->status(),
                'body'            => $dmResponse->body(),
            ]);
            return;
        }

        $channelId = $dmResponse->json('id');

        // 2. Enviar el mensaje
        $message = $this->buildMessage($task, $assignee->name);

        $msgResponse = Http::withToken($token, 'Bot')
            ->post(self::API_BASE . "/channels/{$channelId}/messages", [
                'content' => $message,
            ]);

        if (! $msgResponse->ok()) {
            Log::warning('DiscordNotificationService: no se pudo enviar el mensaje', [
                'channel_id' => $channelId,
                'status'     => $msgResponse->status(),
                'body'       => $msgResponse->body(),
            ]);
            return;
        }

        Log::info('DiscordNotificationService: notificación enviada', [
            'task_id'         => $task->id,
            'discord_user_id' => $task->external_user_id,
            'assignee'        => $assignee->name,
        ]);
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
