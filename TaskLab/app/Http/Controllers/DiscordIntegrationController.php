<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDiscordMessageBatch;
use App\Models\DiscordMessageBuffer;
use Illuminate\Http\Request;

class DiscordIntegrationController extends Controller
{
    public function store(Request $request)
    {
        $expectedToken = config('services.teams.token');

        if (! $expectedToken || $request->header('X-Teams-Token') !== $expectedToken) {
            abort(403, 'Invalid token');
        }

        // Normalizar campos: aceptamos tanto nombres propios (message_id, message_text...)
        // como los nativos del payload de Discord (id, content, author...) directamente desde Pipedream.
        $raw = $request->all();

        \Log::info('DiscordIntegrationController: inbound payload', [
            'raw_keys'    => array_keys($raw),
            'attachments' => $raw['attachments'] ?? 'not_set',
            'embeds'      => isset($raw['embeds']) ? count($raw['embeds']) . ' embeds' : 'not_set',
            'image_urls'  => $raw['image_urls'] ?? 'not_set',
            'raw'         => $raw,
        ]);

        // Algunos conectores (como Pipedream) anidan el mensaje real bajo "message".
        $messagePayload = $raw['message'] ?? $raw;

        // Usar empty() en lugar de !isset() para que arrays vacíos también sean reemplazados
        if (empty($raw['attachments']) && ! empty($messagePayload['attachments'])) {
            $raw['attachments'] = $messagePayload['attachments'];
        }

        // Normalizar un único image_url suelto a array image_urls
        if (empty($raw['image_urls']) && ! empty($raw['image_url'])) {
            $raw['image_urls'] = [$raw['image_url']];
        }

        // Extraer imágenes de embeds (Discord manda embeds cuando pegas una URL de imagen)
        // Se ejecuta incluso si attachments es [] vacío — ese es el bug que había antes
        $embedImageUrls = [];
        foreach ($messagePayload['embeds'] ?? [] as $embed) {
            if (! is_array($embed)) {
                continue;
            }
            // embed.image.url — imagen principal del embed
            if (! empty($embed['image']['url'])) {
                $embedImageUrls[] = $embed['image']['url'];
            }
            // embed.thumbnail.url — thumbnail del embed
            if (! empty($embed['thumbnail']['url'])) {
                $embedImageUrls[] = $embed['thumbnail']['url'];
            }
        }
        if (! empty($embedImageUrls)) {
            $raw['image_urls'] = array_merge($raw['image_urls'] ?? [], $embedImageUrls);
        }

        $messageId   = $raw['message_id']    ?? $messagePayload['id']                 ?? null;
        $messageText = $raw['message_text']  ?? $messagePayload['content']            ?? '';
        $messageUrl  = $raw['message_url']   ?? $raw['jump_url']                      ?? null;
        $channelId   = $raw['channel_id']                                             ?? null;
        $channelName = $raw['channel_name']                                           ?? null;
        $teamName    = $raw['team_name']     ?? $raw['guild']['name']                 ?? null;
        $fromEmail   = $raw['from_email']                                             ?? null;
        $fromName    = $raw['from_name']     ?? $messagePayload['author']['username'] ?? null;
        $fromUserId  = $raw['from_teams_id'] ?? $messagePayload['author']['id']      ?? null;

        if (empty($messageId)) {
            return response()->json(['error' => 'message_id is required'], 422);
        }

        // Idempotencia
        if (DiscordMessageBuffer::where('message_id', $messageId)->exists()) {
            return response()->json(['status' => 'already_buffered']);
        }

        // Normalizar adjuntos — acepta tanto nuestro formato {url,label,type}
        // como el formato nativo de Discord {url, filename, content_type}
        $attachments = [];
        $imageUrls   = is_array($raw['image_urls'] ?? null) ? $raw['image_urls'] : [];

        foreach ($raw['attachments'] ?? [] as $att) {
            if (! is_array($att) || empty($att['url'])) {
                continue;
            }

            $contentType = $att['content_type'] ?? $att['type'] ?? '';
            $isImage     = str_starts_with($contentType, 'image/')
                || preg_match('/\.(png|jpe?g|gif|webp)$/i', $att['url']);

            $attachments[] = [
                'url'   => $att['url'],
                'label' => $att['label'] ?? $att['filename'] ?? null,
                'type'  => $isImage ? 'image' : ($contentType ?: 'file'),
            ];

            if ($isImage) {
                $imageUrls[] = $att['url'];
            }
        }

        $uniqueImageUrls = array_values(array_unique(array_filter($imageUrls)));

        $discordUserId = $fromUserId ?? $fromEmail ?? 'unknown';
        $resolvedChannelId = $channelId ?? $channelName ?? 'unknown';

        DiscordMessageBuffer::create([
            'discord_user_id' => $discordUserId,
            'channel_id'      => $resolvedChannelId,
            'message_id'      => $messageId,
            'message_text'    => $messageText,
            'message_url'     => $messageUrl,
            'from_name'       => $fromName,
            'from_email'      => $fromEmail,
            'team_name'       => $teamName,
            'channel_name'    => $channelName,
            'attachments'     => $attachments ?: null,
            'image_urls'      => $uniqueImageUrls ?: null,
        ]);

        ProcessDiscordMessageBatch::dispatch($discordUserId, $resolvedChannelId)
            ->delay(now()->addSeconds(30));

        return response()->json(['status' => 'buffered']);
    }
}
