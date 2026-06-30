<?php

namespace App\Jobs;

use App\Models\SlackConnection;
use App\Models\SlackMessageBuffer;
use App\Models\Task;
use App\Models\User;
use App\Services\DiscordBatchAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessSlackMessageBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 10;
    public int $timeout = 120;

    private const WINDOW_SECONDS = 30;

    public function __construct(
        public string $slackUserId,
        public string $channelId,
    ) {}

    public function handle(DiscordBatchAnalyzer $analyzer): void
    {
        $lastMessage = SlackMessageBuffer::where('slack_user_id', $this->slackUserId)
            ->where('channel_id', $this->channelId)
            ->whereNull('processed_at')
            ->latest('created_at')
            ->first();

        if (! $lastMessage) {
            return;
        }

        if ($lastMessage->created_at->gt(now()->subSeconds(self::WINDOW_SECONDS))) {
            $this->release(15);
            return;
        }

        $lockKey = "slack_batch_{$this->slackUserId}_{$this->channelId}";
        $lock    = Cache::lock($lockKey, 60);

        if (! $lock->get()) {
            return;
        }

        try {
            $this->processBatch($analyzer);
        } finally {
            $lock->release();
        }
    }

    private function processBatch(DiscordBatchAnalyzer $analyzer): void
    {
        $messages = SlackMessageBuffer::where('slack_user_id', $this->slackUserId)
            ->where('channel_id', $this->channelId)
            ->whereNull('processed_at')
            ->orderBy('created_at')
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        // Tareas recientes (últimas 4 horas) de este usuario de Slack
        $recentTasks = Task::where('source', 'slack')
            ->where('external_user_id', $this->slackUserId)
            ->where('created_at', '>=', now()->subHours(4))
            ->whereNull('archived_at')
            ->orderBy('created_at')
            ->get();

        $actions = $analyzer->analyze($messages, $recentTasks);

        // Resolver reporter (intentamos obtener el nombre real de Slack via API)
        $reporter   = null;
        $connection = SlackConnection::active();

        if ($connection?->bot_token) {
            $userInfo = Http::withToken($connection->bot_token)
                ->get('https://slack.com/api/users.info', ['user' => $this->slackUserId]);

            if ($userInfo->ok() && $userInfo->json('ok')) {
                $slackUser = $userInfo->json('user');
                $realName  = $slackUser['real_name'] ?? $slackUser['name'] ?? null;
                $email     = $slackUser['profile']['email'] ?? null;

                if ($email) {
                    $reporter = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name'      => $realName ?? $email,
                            'password'  => Str::random(32),
                            'user_type' => 'requester',
                        ]
                    );
                }
            }
        }

        if (! $reporter) {
            $syntheticEmail = 'slack+' . $this->slackUserId . '@tasklab.local';
            $first          = $messages->first();

            $reporter = User::firstOrCreate(
                ['email' => $syntheticEmail],
                [
                    'name'      => $first?->from_name ?? ('Slack user ' . $this->slackUserId),
                    'password'  => Str::random(32),
                    'user_type' => 'requester',
                ]
            );
        }

        $validActions = array_filter($actions, function ($action) use ($messages) {
            $type = $action['type'] ?? '';
            if (! in_array($type, ['create', 'modify', 'delete', 'ignore'])) {
                return false;
            }
            if ($type === 'create') {
                $hasText   = ! empty($action['data']['description_raw']);
                $hasImages = collect($action['message_indices'] ?? [])
                    ->map(fn ($i) => $messages->values()->get($i))
                    ->filter()
                    ->some(fn ($m) => ! empty($m->image_urls));
                if (! $hasText && ! $hasImages) {
                    return false;
                }
            }
            if (in_array($type, ['modify', 'delete']) && empty($action['task_id'])) {
                return false;
            }
            return true;
        });

        Log::info("ProcessSlackMessageBatch: {$messages->count()} mensaje(s) → " . count($validActions) . " acción(es)", [
            'user'    => $this->slackUserId,
            'channel' => $this->channelId,
        ]);

        foreach ($validActions as $action) {
            try {
                $this->executeAction($action, $messages, $recentTasks, $reporter);
            } catch (\Throwable $e) {
                Log::error('ProcessSlackMessageBatch: error ejecutando acción', [
                    'action'  => $action,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        SlackMessageBuffer::where('slack_user_id', $this->slackUserId)
            ->where('channel_id', $this->channelId)
            ->whereNull('processed_at')
            ->update(['processed_at' => now()]);
    }

    private function executeAction(array $action, $messages, $recentTasks, ?User $reporter): void
    {
        $type = $action['type'] ?? 'ignore';

        $coveredMessages = collect($action['message_indices'] ?? [])
            ->map(fn ($i) => $messages->values()->get($i))
            ->filter();

        $allImageUrls = $coveredMessages->flatMap(fn ($m) => $m->image_urls ?? [])->unique()->values()->all();
        $firstMessage = $coveredMessages->first();

        switch ($type) {
            case 'create':
                $data = $action['data'] ?? [];

                if (empty($data['description_raw'])) {
                    if (empty($allImageUrls)) {
                        return;
                    }
                    $data['description_raw'] = '[Mensaje con imagen desde Slack — sin texto adjunto]';
                    $data['title']           = $data['title'] ?? 'Imagen recibida desde Slack';
                }

                $task = Task::create([
                    'title'               => $data['title'] ?? null,
                    'description_raw'     => $data['description_raw'],
                    'type'                => $data['type'] ?? 'bug',
                    'status'              => 'processing',
                    'priority'            => $data['priority'] ?? 'medium',
                    'reporter_id'         => $reporter?->id,
                    'source'              => 'slack',
                    'external_user_id'    => $this->slackUserId,
                    'external_channel'    => $firstMessage?->channel_id ?? $this->channelId,
                    'external_message_id' => $coveredMessages->pluck('message_id')->implode(','),
                    'attachments'         => $coveredMessages->flatMap(fn ($m) => $m->attachments ?? [])->values()->all(),
                    'primary_url'         => $this->extractFirstUrl($data['description_raw']),
                ]);

                RefineTaskWithAi::dispatch($task, $allImageUrls);

                if (! empty($allImageUrls)) {
                    DownloadTaskAttachments::dispatch($task, $allImageUrls);
                }

                Log::info("ProcessSlackMessageBatch: tarea #{$task->id} creada");
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

                RefineTaskWithAi::dispatch($task, $allImageUrls);

                if (! empty($allImageUrls)) {
                    DownloadTaskAttachments::dispatch($task, $allImageUrls);
                }
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
                break;

            case 'ignore':
                Log::info('ProcessSlackMessageBatch: mensajes ignorados', ['reason' => $action['reason'] ?? '']);
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
