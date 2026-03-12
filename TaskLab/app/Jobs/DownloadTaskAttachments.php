<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadTaskAttachments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public Task $task,
        public array $imageUrls,
    ) {}

    public function handle(): void
    {
        foreach ($this->imageUrls as $url) {
            $this->downloadAndStore($url);
        }
    }

    private function downloadAndStore(string $url): void
    {
        try {
            $response = Http::timeout(20)->get($url);

            if (! $response->ok()) {
                return;
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            // Solo procesamos imágenes
            if (! str_starts_with($contentType, 'image/')) {
                return;
            }

            $extension = match (true) {
                str_contains($contentType, 'png')  => 'png',
                str_contains($contentType, 'gif')  => 'gif',
                str_contains($contentType, 'webp') => 'webp',
                default                             => 'jpg',
            };

            $filename = 'task_' . $this->task->id . '_' . Str::random(12) . '.' . $extension;
            $path = 'task-images/' . $filename;

            Storage::disk('public')->put($path, $response->body());

            // Nombre legible: extraer del final de la URL si es posible
            $originalName = basename(parse_url($url, PHP_URL_PATH)) ?: $filename;

            TaskImage::create([
                'task_id'       => $this->task->id,
                'original_name' => $originalName,
                'storage_path'  => $path,
                'mime_type'     => $contentType,
                'size'          => strlen($response->body()),
            ]);
        } catch (\Throwable) {
            // Si falla la descarga no rompemos el flujo
        }
    }
}
