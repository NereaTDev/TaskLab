<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\AiTaskRefiner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefineTaskWithAi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle(AiTaskRefiner $refiner): void
    {
        // For now we use a fake refinement so the flow works without a real AI key
        $result = $refiner->refine($this->task->description_raw);

        $this->task->update([
            'title'          => $result['title'] ?? $this->task->title,
            'description_ai' => $result['summary'] ?? $this->task->description_ai,
            'requirements'   => $result['requirements'] ?? [],
            'behavior'       => $result['behavior'] ?? null,
            'test_cases'     => $result['test_cases'] ?? [],
            'status'         => 'ready_for_dev',
        ]);
    }
}
