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
        $result = $refiner->refine($this->task->description_raw);

        // Normalizar puntos a los valores permitidos (0.5,1,2,4,6,8,10,12,16)
        $points = $result['points'] ?? null;
        if (is_numeric($points)) {
            $points = (float) $points;
            $allowed = [0.5, 1, 2, 4, 6, 8, 10, 12, 16];
            $closest = null;
            $minDiff = null;
            foreach ($allowed as $v) {
                $diff = abs($v - $points);
                if ($minDiff === null || $diff < $minDiff) {
                    $minDiff = $diff;
                    $closest = $v;
                }
            }
            $points = $closest;
        } else {
            $points = null;
        }

        $update = [
            'title'          => $result['title'] ?? $this->task->title,
            'description_ai' => $result['summary'] ?? $this->task->description_ai,
            'requirements'   => $result['requirements'] ?? [],
            'behavior'       => $result['behavior'] ?? null,
            'test_cases'     => $result['test_cases'] ?? [],
            'status'         => 'ready_for_dev',
        ];

        if (! empty($result['type'])) {
            $update['type'] = $result['type'];
        }

        if (! empty($result['priority'])) {
            $update['priority'] = $result['priority'];
        }

        if ($points !== null) {
            $update['points'] = $points;
        }

        if (! empty($result['primary_url'])) {
            $update['primary_url'] = $result['primary_url'];
        }

        if (! empty($result['additional_urls']) && is_array($result['additional_urls'])) {
            $update['additional_urls'] = $result['additional_urls'];
        }

        if (! empty($result['impact'])) {
            $update['impact'] = $result['impact'];
        }

        $this->task->update($update);
    }
}
