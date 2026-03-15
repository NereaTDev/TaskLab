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

        // Algunos conectores (como Pipedream) anidan el mensaje real bajo "message" o
        // exponen los adjuntos en campos alternativos. Aquí intentamos normalizar
        // para que el resto del código pueda trabajar siempre con las mismas claves.
        $messagePayload = $raw['message'] ?? $raw;

        if (! isset($raw['attachments']) && isset($messagePayload['attachments'])) {
            $raw['attachments'] = $messagePayload['attachments'];
        }

        // Normalizar un único image_url suelto a array image_urls
        if (! isset($raw['image_urls']) && isset($raw['image_url']) && $raw['image_url']) {
            $raw['image_urls'] = [$raw['image_url']];
        }

        // Algunos conectores de Discord mandan la imagen en embeds[0].image.url
        if (! isset($raw['attachments']) && isset($messagePayload['embeds']) && is_array($messagePayload['embeds'])) {
            $embedImageUrls = [];
            foreach ($messagePayload['embeds'] as $embed) {
                if (! is_array($embed)) {
                    continue;
                }
                $url = $embed['image']['url'] ?? null;
                if ($url) {
                    $embedImageUrls[] = $url;
                }
            }
            if ($embedImageUrls) {
                $raw['image_urls'] = array_merge($raw['image_urls'] ?? [], $embedImageUrls);
            }
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
