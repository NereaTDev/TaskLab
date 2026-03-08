<?php

namespace App\Http\Controllers;

use App\Jobs\RefineTaskWithAi;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscordIntegrationController extends Controller
{
    public function store(Request $request, TaskAssignmentService $assignmentService)
    {
        // Autenticación simple por token reutilizando el mismo que para Teams
        $expectedToken = config('services.teams.token');

        if (! $expectedToken || $request->header('X-Teams-Token') !== $expectedToken) {
            abort(403, 'Invalid token');
        }

        // Payload normalizado desde el conector de Discord (Pipedream, n8n, bot propio...)
        $data = $request->validate([
            'message_id'    => ['required', 'string'],
            'message_text'  => ['required', 'string'],
            'message_url'   => ['nullable', 'string'],
            'channel_id'    => ['nullable', 'string'],
            'channel_name'  => ['nullable', 'string'],
            'team_id'       => ['nullable', 'string'],
            'team_name'     => ['nullable', 'string'],
            'from_email'    => ['nullable', 'email'],
            'from_name'     => ['nullable', 'string'],
            'from_teams_id' => ['nullable', 'string'], // aquí guardamos el user id de Discord
            // Adjuntos opcionales normalizados desde Discord / Pipedream
            // attachments: [ { "url": "https://...", "label": "screenshot.png", "type": "image" }, ... ]
            'attachments'   => ['nullable', 'array'],
            'attachments.*' => ['array'],
            'attachments.*.url'   => ['nullable', 'string'],
            'attachments.*.label' => ['nullable', 'string'],
            'attachments.*.type'  => ['nullable', 'string'],
            'image_urls'    => ['nullable', 'array'],
            'image_urls.*'  => ['string'],
        ]);

        // Idempotencia: no duplicar mensajes
        $existing = Task::where('source', 'discord')
            ->where('external_message_id', $data['message_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'already_exists',
                'task_id' => $existing->id,
            ]);
        }

        // Reporter opcional (si algún día mapeamos email de Discord → User)
        $reporter = null;
        if (! empty($data['from_email'])) {
            $reporter = User::firstOrCreate(
                ['email' => $data['from_email']],
                [
                    'name'      => $data['from_name'] ?? $data['from_email'],
                    'password'  => Str::random(32),
                    'user_type' => 'requester',
                ]
            );
        }

        // Construir descripción con contexto de Discord
        $descriptionRaw = $data['message_text'];

        if (! empty($data['message_url'])) {
            $descriptionRaw .= "\n\nDiscord message: " . $data['message_url'];
        }

        if (! empty($data['channel_name']) || ! empty($data['team_name'])) {
            $descriptionRaw .= "\n\nOrigen: " .
                ($data['team_name'] ?? 'Servidor desconocido') . ' / ' .
                ($data['channel_name'] ?? 'Canal desconocido');
        }

        // Normalizar adjuntos/imágenes para que la IA los vea en description_raw
        $attachments = [];
        $imageUrls = $data['image_urls'] ?? [];

        if (! empty($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $att) {
                if (! is_array($att)) {
                    continue;
                }
                $url = $att['url'] ?? null;
                $label = $att['label'] ?? null;
                $type = $att['type'] ?? null;

                if ($url) {
                    $attachments[] = [
                        'url'   => $url,
                        'label' => $label,
                        'type'  => $type,
                    ];

                    $imageUrls[] = $url;
                }
            }
        }

        if (! empty($imageUrls)) {
            $uniqueImageUrls = array_values(array_unique($imageUrls));
            $descriptionRaw .= "\n\nImágenes adjuntas:";
            foreach ($uniqueImageUrls as $imgUrl) {
                $descriptionRaw .= "\n- " . $imgUrl;
            }
        }

        // Extraer URLs del texto del mensaje para primary_url / additional_urls
        $primaryUrl = null;
        $additionalUrls = [];
        $textForUrlExtraction = $data['message_text'] . "\n" . ($data['message_url'] ?? '');

        if (preg_match_all('~https?://\S+~i', $textForUrlExtraction, $matches)) {
            $urls = $matches[0] ?? [];
            if (! empty($urls)) {
                $primaryUrl = $urls[0];
                if (count($urls) > 1) {
                    $additionalUrls = array_values(array_unique(array_slice($urls, 1)));
                }
            }
        }

        // Crear la Task base con source=discord
        $task = Task::create([
            'title'               => null,
            'description_raw'     => $descriptionRaw,
            'type'                => 'bug',
            'status'              => 'new',
            'priority'            => 'medium',
            'reporter_id'         => $reporter?->id,
            'assignee_id'         => null,
            'source'              => 'discord',
            'primary_url'         => $primaryUrl,
            'additional_urls'     => $additionalUrls,
            'external_message_id' => $data['message_id'],
            'external_channel'    => $data['channel_id'] ?? $data['channel_name'] ?? null,
            'external_user_id'    => $data['from_teams_id'] ?? null,
            'external_payload'    => $request->all(),
            'attachments'         => $attachments,
        ]);

        // Lanzar refinamiento + asignación automática
        RefineTaskWithAi::dispatch($task);
        $assignmentService->assign($task);

        return response()->json([
            'status'  => 'ok',
            'task_id' => $task->id,
        ], 201);
    }
}
