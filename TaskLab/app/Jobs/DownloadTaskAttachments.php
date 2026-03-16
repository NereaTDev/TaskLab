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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadTaskAttachments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public Task $task,
        public array $imageUrls,
    ) {}

    public function handle(): void
    {
        foreach ($this->imageUrls as $url) {
            $this->processUrl($url);
        }
    }

    private function processUrl(string $url): void
    {
        try {
            // Intentar Cloudinary primero (si está configurado)
            $cloudinaryUrl = $this->uploadToCloudinary($url);

            if ($cloudinaryUrl) {
                $originalName = basename(parse_url($url, PHP_URL_PATH)) ?: 'image.jpg';
                TaskImage::create([
                    'task_id'       => $this->task->id,
                    'original_name' => $originalName,
                    'storage_path'  => $cloudinaryUrl,
                    'mime_type'     => 'image/jpeg',
                    'size'          => 0,
                ]);
                Log::info('DownloadTaskAttachments: stored via Cloudinary', [
                    'task_id' => $this->task->id,
                    'url'     => $cloudinaryUrl,
                ]);
                return;
            }

            // Fallback: descargar y guardar en S3/local
            $this->downloadAndStore($url);

        } catch (\Throwable $e) {
            Log::error('DownloadTaskAttachments: failed', [
                'task_id' => $this->task->id,
                'url'     => $url,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function uploadToCloudinary(string $url): ?string
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey    = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (! $cloudName || ! $apiKey || ! $apiSecret) {
            return null;
        }

        $timestamp = time();
        $folder    = 'tasklab';

        // Parámetros a firmar (orden alfabético, sin file, api_key)
        $params = ['folder' => $folder, 'timestamp' => $timestamp];
        ksort($params);

        $signString = collect($params)
            ->map(fn ($v, $k) => "{$k}={$v}")
            ->implode('&') . $apiSecret;

        $signature = sha1($signString);

        $response = Http::timeout(30)->asForm()->post(
            "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload",
            [
                'file'      => $url,
                'folder'    => $folder,
                'timestamp' => $timestamp,
                'api_key'   => $apiKey,
                'signature' => $signature,
            ]
        );

        if (! $response->ok()) {
            Log::warning('DownloadTaskAttachments: Cloudinary upload failed', [
                'task_id' => $this->task->id,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return null;
        }

        return $response->json('secure_url');
    }

    private function downloadAndStore(string $url): void
    {
        $response = Http::timeout(20)->get($url);

        if (! $response->ok()) {
            Log::warning('DownloadTaskAttachments: URL returned non-OK status', [
                'task_id' => $this->task->id,
                'url'     => $url,
                'status'  => $response->status(),
            ]);
            return;
        }

        $contentType = $response->header('Content-Type') ?? 'image/jpeg';
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
        $path     = 'task-images/' . $filename;

        $disk = config('filesystems.default') === 'local' ? 'public' : config('filesystems.default');
        Storage::disk($disk)->put($path, $response->body());

        $originalName = basename(parse_url($url, PHP_URL_PATH)) ?: $filename;

        TaskImage::create([
            'task_id'       => $this->task->id,
            'original_name' => $originalName,
            'storage_path'  => $path,
            'mime_type'     => $contentType,
            'size'          => strlen($response->body()),
        ]);

        Log::info('DownloadTaskAttachments: stored via S3/local', [
            'task_id' => $this->task->id,
            'path'    => $path,
            'disk'    => $disk,
        ]);
    }
}
