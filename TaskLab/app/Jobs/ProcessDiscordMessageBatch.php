<?php

namespace App\Jobs;

use App\Models\DiscordMessageBuffer;
use App\Models\Task;
use App\Models\User;
use App\Services\DiscordBatchAnalyzer;
use App\Services\TaskAssignmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessDiscordMessageBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 10;
    public int $timeout = 120;

    // Ventana de silencio: si el último mensaje tiene menos de N segundos, esperamos
    private const WINDOW_SECONDS = 30;

    public function __construct(
        public string $discordUserId,
        public string $channelId,
    ) {}

    public function handle(DiscordBatchAnalyzer $analyzer, TaskAssignmentService $assignmentService): void
    {
        // Comprobamos si el usuario sigue escribiendo (ventana deslizante)
        $lastMessage = DiscordMessageBuffer::where('discord_user_id', $this->discordUserId)
            ->where('channel_id', $this->channelId)
            ->whereNull('processed_at')
            ->latest('created_at')
            ->first();

        if (! $lastMessage) {
            return; // Ya procesado por otra instancia del job
        }

        if ($lastMessage->created_at->gt(now()->subSeconds(self::WINDOW_SECONDS))) {
            // El usuario sigue escribiendo — reintentamos en 15 segundos
            $this->release(15);
            return;
        }

        // Usamos un cache lock para evitar procesamiento doble si dos jobs corren simultáneamente
        $lockKey = "discord_batch_{$this->discordUserId}_{$this->channelId}";

        $lock = Cache::lock($lockKey, 60);
        if (! $lock->get()) {
            return; // Otro job ya está procesando este lote
        }

        try {
            $this->processBatch($analyzer, $assignmentService);
        } finally {
            $lock->release();
        }
    }

    private function processBatch(DiscordBatchAnalyzer $analyzer, TaskAssignmentService $assignmentService): void
    {
        $messages = DiscordMessageBuffer::where('discord_user_id', $this->discordUserId)
            ->where('channel_id', $this->channelId)
            ->whereNull('processed_at')
            ->orderBy('created_at')
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        // Tareas recientes (últimas 4 horas) de este usuario de Discord
        $recentTasks = Task::where('source', 'discord')
            ->where('external_user_id', $this->discordUserId)
            ->where('created_at', '>=', now()->subHours(4))
            ->whereNull('archived_at')
            ->orderBy('created_at')
            ->get();

        // Analizar con IA
        $actions = $analyzer->analyze($messages, $recentTasks);

        // Resolver reporter (buscamos en el primer mensaje que tenga email)
        $reporter = null;
        $firstWithEmail = $messages->first(fn ($m) => ! empty($m->from_email));
        if ($firstWithEmail) {
            $reporter = User::firstOrCreate(
                ['email' => $firstWithEmail->from_email],
                [
                    'name'      => $firstWithEmail->from_name ?? $firstWithEmail->from_email,
                    'password'  => Str::random(32),
                    'user_type' => 'requester',
                ]
            );
        }

        // Validar que el AI no ha devuelto acciones sin contenido real
        $validActions = array_filter($actions, function ($action) {
            $type = $action['type'] ?? '';
            if (! in_array($type, ['create', 'modify', 'delete', 'ignore'])) {
                return false;
            }
            if ($type === 'create' && empty($action['data']['description_raw'])) {
                return false;
            }
            if (in_array($type, ['modify', 'delete']) && empty($action['task_id'])) {
                return false;
            }
            return true;
        });

        Log::info("ProcessDiscordMessageBatch: {$messages->count()} mensaje(s) → " . count($validActions) . " acción(es)", [
            'user'    => $this->discordUserId,
            'channel' => $this->channelId,
            'actions' => array_column(array_values($validActions), 'type'),
        ]);

        foreach ($validActions as $action) {
            try {
                $this->executeAction($action, $messages, $recentTasks, $reporter, $assignmentService);
            } catch (\Throwable $e) {
                Log::error('ProcessDiscordMessageBatch: error executing action', [
                    'action'  => $action,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // Marcar mensajes como procesados
        DiscordMessageBuffer::where('discord_user_id', $this->discordUserId)
            ->where('channel_id', $this->channelId)
            ->whereNull('processed_at')
            ->update(['processed_at' => now()]);
    }

    private function executeAction(
        array $action,
        $messages,
        $recentTasks,
        ?User $reporter,
        TaskAssignmentService $assignmentService,
    ): void {
        $type = $action['type'] ?? 'ignore';

        // Mensajes que cubre esta acción
        $coveredMessages = collect($action['message_indices'] ?? [])
            ->map(fn ($i) => $messages->values()->get($i))
            ->filter();

        // Recopilar imágenes de los mensajes cubiertos
        $allImageUrls = $coveredMessages->flatMap(fn ($m) => $m->image_urls ?? [])->unique()->values()->all();
        $firstMessage = $coveredMessages->first();

        switch ($type) {
            case 'create':
                $data = $action['data'] ?? [];
                if (empty($data['description_raw'])) {
                    return;
                }

                $task = Task::create([
                    'title'               => $data['title'] ?? null,
                    'description_raw'     => $data['description_raw'],
                    'type'                => $data['type'] ?? 'bug',
                    'status'              => 'new',
                    'priority'            => $data['priority'] ?? 'medium',
                    'reporter_id'         => $reporter?->id,
                    'source'              => 'discord',
                    'external_user_id'    => $this->discordUserId,
                    'external_channel'    => $firstMessage?->channel_id ?? $this->channelId,
                    'external_message_id' => $coveredMessages->pluck('message_id')->implode(','),
                    'attachments'         => $coveredMessages->flatMap(fn ($m) => $m->attachments ?? [])->values()->all(),
                    'primary_url'         => $this->extractFirstUrl($data['description_raw']),
                ]);

                RefineTaskWithAi::dispatch($task);
                $assignmentService->assign($task);

                if (! empty($allImageUrls)) {
                    DownloadTaskAttachments::dispatch($task, $allImageUrls);
                }

                Log::info("ProcessDiscordMessageBatch: created task #{$task->id}", ['reason' => $action['reason'] ?? '']);
                break;

            case 'modify':
                $taskId = $action['task_id'] ?? null;
                $data   = $action['data'] ?? [];

                if (! $taskId || empty($data)) {
                    return;
                }

                $task = $recentTasks->firstWhere('id', $taskId);
                if (! $task) {
                    return;
                }

                if (! empty($data['description_raw'])) {
                    $task->description_raw = $data['description_raw'];
                }
                if (! empty($data['title'])) {
                    $task->title = $data['title'];
                }

                $task->save();

                // Re-refinar con el nuevo contexto
                RefineTaskWithAi::dispatch($task);

                if (! empty($allImageUrls)) {
                    DownloadTaskAttachments::dispatch($task, $allImageUrls);
                }

                Log::info("ProcessDiscordMessageBatch: modified task #{$task->id}", ['reason' => $action['reason'] ?? '']);
                break;

            case 'delete':
                $taskId = $action['task_id'] ?? null;
                if (! $taskId) {
                    return;
                }

                $task = $recentTasks->firstWhere('id', $taskId);
                if (! $task) {
                    return;
                }

                $task->archived_at = now();
                $task->status      = 'archived';
                $task->save();

                Log::info("ProcessDiscordMessageBatch: archived task #{$task->id}", ['reason' => $action['reason'] ?? '']);
                break;

            case 'ignore':
                Log::info("ProcessDiscordMessageBatch: ignored messages", ['reason' => $action['reason'] ?? '']);
                break;
        }
    }

    private function extractFirstUrl(string $text): ?string
    {
        if (preg_match('~https?://\S+~i', $text, $matches)) {
            return $matches[0];
        }
        return null;
    }
}
