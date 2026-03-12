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

        $data = $request->validate([
            'message_id'    => ['required', 'string'],
            'message_text'  => ['nullable', 'string'],
            'message_url'   => ['nullable', 'string'],
            'channel_id'    => ['nullable', 'string'],
            'channel_name'  => ['nullable', 'string'],
            'team_id'       => ['nullable', 'string'],
            'team_name'     => ['nullable', 'string'],
            'from_email'    => ['nullable', 'email'],
            'from_name'     => ['nullable', 'string'],
            'from_teams_id' => ['nullable', 'string'],
            'attachments'   => ['nullable', 'array'],
            'attachments.*' => ['array'],
            'attachments.*.url'   => ['nullable', 'string'],
            'attachments.*.label' => ['nullable', 'string'],
            'attachments.*.type'  => ['nullable', 'string'],
            'image_urls'    => ['nullable', 'array'],
            'image_urls.*'  => ['string'],
        ]);

        // Idempotencia: si el mensaje ya está en el buffer, ignorar
        if (DiscordMessageBuffer::where('message_id', $data['message_id'])->exists()) {
            return response()->json(['status' => 'already_buffered']);
        }

        // Normalizar adjuntos e imágenes
        $attachments  = [];
        $imageUrls    = $data['image_urls'] ?? [];

        foreach ($data['attachments'] ?? [] as $att) {
            if (! is_array($att) || empty($att['url'])) {
                continue;
            }
            $attachments[] = [
                'url'   => $att['url'],
                'label' => $att['label'] ?? null,
                'type'  => $att['type'] ?? null,
            ];
            $imageUrls[] = $att['url'];
        }

        $uniqueImageUrls = array_values(array_unique(array_filter($imageUrls)));

        // Guardar en el buffer
        $discordUserId = $data['from_teams_id'] ?? $data['from_email'] ?? 'unknown';
        $channelId     = $data['channel_id'] ?? $data['channel_name'] ?? 'unknown';

        DiscordMessageBuffer::create([
            'discord_user_id' => $discordUserId,
            'channel_id'      => $channelId,
            'message_id'      => $data['message_id'],
            'message_text'    => $data['message_text'] ?? '',
            'message_url'     => $data['message_url'] ?? null,
            'from_name'       => $data['from_name'] ?? null,
            'from_email'      => $data['from_email'] ?? null,
            'team_name'       => $data['team_name'] ?? null,
            'channel_name'    => $data['channel_name'] ?? null,
            'attachments'     => $attachments ?: null,
            'image_urls'      => $uniqueImageUrls ?: null,
        ]);

        // Lanzar job con 30 segundos de retraso (ventana deslizante)
        ProcessDiscordMessageBatch::dispatch($discordUserId, $channelId)->delay(now()->addSeconds(30));

        return response()->json(['status' => 'buffered']);
    }
}
