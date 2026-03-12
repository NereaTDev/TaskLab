<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\TaskImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTaskImages extends Command
{
    protected $signature   = 'tasklab:cleanup-task-images';
    protected $description = 'Elimina imágenes de tareas completadas hace más de 30 días sin actividad';

    public function handle(): int
    {
        $cutoff = now()->subDays(30);

        $tasks = Task::where('status', 'done')
            ->whereNotNull('done_at')
            ->where('done_at', '<', $cutoff)
            ->whereHas('taskImages')
            ->with('taskImages')
            ->get();

        $totalImages = 0;

        foreach ($tasks as $task) {
            foreach ($task->taskImages as $image) {
                Storage::disk('public')->delete($image->storage_path);
                $image->delete();
                $totalImages++;
            }
        }

        $this->info("Limpieza completada: {$totalImages} imagen(es) eliminada(s) de {$tasks->count()} tarea(s).");

        return self::SUCCESS;
    }
}
