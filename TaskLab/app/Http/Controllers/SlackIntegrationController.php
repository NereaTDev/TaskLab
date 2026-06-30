<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSlackMessageBatch;
use App\Models\SlackConnection;
use App\Models\SlackMessageBuffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlackIntegrationController extends Controller
{
    public function events(Request $request)
    {
        // El signing secret es app-level (mismo para todos los workspaces)
        $signingSecret = config('services.slack.signing_secret');

        // Verificar la firma antes de hacer cualquier otra cosa
        if (! $this->verifySignature($request, $signingSecret)) {
            abort(403, 'Invalid Slack signature');
        }

        $payload     = $request->all();
        $workspaceId = $payload['team_id'] ?? null;

        // Buscar la conexión del workspace que envía el evento
        $connection = $workspaceId
            ? SlackConnection::where('workspace_id', $workspaceId)->where('active', true)->first()
            : SlackConnection::active();

        if (! $connection) {
            // Responder 200 para que Slack no reintente, pero no procesar
            return response()->json(['ok' => true]);
        }

        $type = $payload['type'] ?? null;

        // Slack URL verification challenge (se hace al configurar los eventos por primera vez)
        if ($type === 'url_verification') {
            return response()->json(['challenge' => $payload['challenge']]);
        }

        if ($type !== 'event_callback') {
            return response()->json(['ok' => true]);
        }

        $event = $payload['event'] ?? [];
        $eventType = $event['type'] ?? null;

        // Solo procesamos mensajes normales de canal (no bot messages, no edits)
        if ($eventType !== 'message') {
            return response()->json(['ok' => true]);
        }

        // Ignorar mensajes de bots y subtipos especiales (edits, deletes, etc.)
        if (! empty($event['bot_id']) || ! empty($event['subtype'])) {
            return response()->json(['ok' => true]);
        }

        $slackUserId = $event['user']    ?? null;
        $channelId   = $event['channel'] ?? null;
        $messageId   = $event['ts']      ?? null;   // ts es el ID único en Slack
        $messageText = $event['text']    ?? '';

        if (! $slackUserId || ! $channelId || ! $messageId) {
            return response()->json(['ok' => true]);
        }

        // Filtrar por canales si está configurado
        $allowedChannels = $connection->channel_ids ?? [];
        if (! empty($allowedChannels) && ! in_array($channelId, $allowedChannels)) {
            return response()->json(['ok' => true]);
        }

        // Idempotencia
        if (SlackMessageBuffer::where('message_id', $messageId)->exists()) {
            return response()->json(['ok' => true]);
        }

        // Extraer imágenes de los ficheros adjuntos
        $imageUrls   = [];
        $attachments = [];

        foreach ($event['files'] ?? [] as $file) {
            $url = $file['url_private'] ?? $file['permalink'] ?? null;
            if (! $url) {
                continue;
            }
            $mimeType = $file['mimetype'] ?? '';
            $isImage  = str_starts_with($mimeType, 'image/');

            $attachments[] = [
                'url'   => $url,
                'label' => $file['name'] ?? null,
                'type'  => $isImage ? 'image' : ($mimeType ?: 'file'),
            ];

            if ($isImage) {
                $imageUrls[] = $url;
            }
        }

        // URLs inline en el texto (Slack las envuelve en <URL>)
        $cleanText = preg_replace('/<(https?:\/\/[^|>]+)[^>]*>/', '$1', $messageText);
        if (preg_match_all('/https?:\/\/\S+/i', $cleanText, $matches)) {
            foreach ($matches[0] as $url) {
                if (preg_match('/\.(png|jpe?g|gif|webp)$/i', $url)) {
                    $imageUrls[] = $url;
                }
            }
        }

        // Nombre de usuario — Slack no lo incluye por defecto en el evento, lo tendremos al procesar
        $workspaceId = $payload['team_id'] ?? null;

        Log::info('SlackIntegrationController: mensaje recibido', [
            'user'    => $slackUserId,
            'channel' => $channelId,
            'ts'      => $messageId,
            'text'    => substr($messageText, 0, 100),
        ]);

        SlackMessageBuffer::create([
            'slack_user_id' => $slackUserId,
            'channel_id'    => $channelId,
            'message_id'    => $messageId,
            'message_text'  => $cleanText,
            'message_url'   => "https://slack.com/archives/{$channelId}/p" . str_replace('.', '', $messageId),
            'workspace_id'  => $workspaceId,
            'attachments'   => $attachments ?: null,
            'image_urls'    => array_values(array_unique($imageUrls)) ?: null,
        ]);

        ProcessSlackMessageBatch::dispatch($slackUserId, $channelId)
            ->delay(now()->addSeconds(30));

        return response()->json(['ok' => true]);
    }

    private function verifySignature(Request $request, ?string $signingSecret): bool
    {
        if (! $signingSecret) {
            return false;
        }

        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        if (! $timestamp || ! $signature) {
            return false;
        }

        // Rechazar requests con más de 5 minutos de antigüedad (replay attack protection)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $baseString = "v0:{$timestamp}:" . $request->getContent();
        $computed   = 'v0=' . hash_hmac('sha256', $baseString, $signingSecret);

        return hash_equals($computed, $signature);
    }
}
