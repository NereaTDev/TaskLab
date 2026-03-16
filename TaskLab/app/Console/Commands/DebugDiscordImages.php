<?php

namespace App\Console\Commands;

use App\Models\DiscordMessageBuffer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DebugDiscordImages extends Command
{
    protected $signature   = 'tasklab:debug-images';
    protected $description = 'Diagnóstico completo del flujo de imágenes Discord → Cloudinary';

    public function handle(): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════════');
        $this->info('  DIAGNÓSTICO: Discord → Cloudinary → TaskLab');
        $this->line('═══════════════════════════════════════════════');

        // 1. Cloudinary
        $this->newLine();
        $this->info('① Credenciales Cloudinary');
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey    = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        $this->line('  cloud_name : ' . ($cloudName ?: '❌ VACÍO'));
        $this->line('  api_key    : ' . ($apiKey    ? '✅ ' . substr($apiKey, 0, 6) . '...' : '❌ VACÍO'));
        $this->line('  api_secret : ' . ($apiSecret ? '✅ configurado' : '❌ VACÍO'));

        if ($cloudName && $apiKey && $apiSecret) {
            $this->newLine();
            $this->info('② Test de upload a Cloudinary (imagen de prueba)');
            $testUrl   = 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3f/JPEG_example_flower.jpg/320px-JPEG_example_flower.jpg';
            $timestamp = time();
            $folder    = 'tasklab-test';
            $params    = ['folder' => $folder, 'timestamp' => $timestamp];
            ksort($params);
            $signString = collect($params)->map(fn ($v, $k) => "{$k}={$v}")->implode('&') . $apiSecret;
            $signature  = sha1($signString);

            $response = Http::timeout(30)->asForm()->post(
                "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload",
                ['file' => $testUrl, 'folder' => $folder, 'timestamp' => $timestamp, 'api_key' => $apiKey, 'signature' => $signature]
            );

            if ($response->ok()) {
                $this->line('  ✅ Upload OK → ' . $response->json('secure_url'));
            } else {
                $this->error('  ❌ Upload FALLÓ — status ' . $response->status());
                $this->line('  Respuesta: ' . $response->body());
            }
        } else {
            $this->warn('  ⚠️  Saltando test — faltan credenciales');
        }

        // 2. Discord buffer — últimas entradas
        $this->newLine();
        $this->info('③ Últimos 5 mensajes en discord_message_buffer');
        $messages = DiscordMessageBuffer::latest()->limit(5)->get();

        if ($messages->isEmpty()) {
            $this->warn('  Sin mensajes — ¿ha llegado algún webhook de Discord?');
        } else {
            foreach ($messages as $msg) {
                $imageCount = count($msg->image_urls ?? []);
                $attCount   = count($msg->attachments ?? []);
                $this->line(sprintf(
                    '  [%s] texto: "%s" | attachments: %d | image_urls: %d | processed: %s',
                    $msg->created_at->format('H:i:s'),
                    mb_substr($msg->message_text ?? '[vacío]', 0, 40),
                    $attCount,
                    $imageCount,
                    $msg->processed_at ? '✅' : '⏳ pendiente'
                ));
                if ($imageCount > 0) {
                    foreach ($msg->image_urls as $url) {
                        $this->line('    🖼  ' . $url);
                    }
                }
            }
        }

        // 3. Jobs fallidos
        $this->newLine();
        $this->info('④ Jobs fallidos (últimos 5)');
        try {
            $failed = DB::table('failed_jobs')->latest('failed_at')->limit(5)->get();
            if ($failed->isEmpty()) {
                $this->line('  ✅ Sin jobs fallidos');
            } else {
                foreach ($failed as $job) {
                    $payload   = json_decode($job->payload, true);
                    $jobName   = $payload['displayName'] ?? $job->queue;
                    $this->error(sprintf('  ❌ %s — %s', $jobName, substr($job->exception, 0, 120)));
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  No se pudo leer failed_jobs: ' . $e->getMessage());
        }

        // 4. TaskImages en BD
        $this->newLine();
        $this->info('⑤ Últimas 5 TaskImages guardadas en BD');
        try {
            $images = DB::table('task_images')->latest()->limit(5)->get();
            if ($images->isEmpty()) {
                $this->warn('  Sin imágenes guardadas en task_images');
            } else {
                foreach ($images as $img) {
                    $this->line(sprintf('  task_id=%d | %s', $img->task_id, $img->storage_path));
                }
            }
        } catch (\Throwable $e) {
            $this->warn('  No se pudo leer task_images: ' . $e->getMessage());
        }

        $this->newLine();
        $this->line('═══════════════════════════════════════════════');
        $this->newLine();
    }
}
