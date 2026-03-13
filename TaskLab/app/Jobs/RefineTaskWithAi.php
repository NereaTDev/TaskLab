<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\CategoryType;
use App\Models\CategoryValue;
use App\Services\AiTaskRefiner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

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

        // Asignación automática de categorías dinámicas (tipo → categoría → subcategoría)
        // a partir de la propuesta de la IA. No inventamos nada: sólo mapeamos contra
        // CategoryType/CategoryValue existentes y descartamos rutas que no encajen.
        $rawCategories = $result['categories'] ?? [];
        $categoryValueIds = $this->mapCategoriesToValues($rawCategories);

        if (! empty($categoryValueIds)) {
            $this->task->categoryValues()->sync($categoryValueIds);
        }
    }

    /**
     * Mapea rutas de categorías devueltas por la IA a IDs reales de CategoryValue.
     * Cada item de $categories debe tener la forma:
     * [ 'path' => ['Tipo', 'Categoria', 'Subcategoria'] ].
     */
    protected function mapCategoriesToValues(array $categories): array
    {
        if (empty($categories)) {
            return [];
        }

        // Cargar todos los tipos y valores con jerarquía en memoria
        $types = CategoryType::with(['values.children'])->get();

        if ($types->isEmpty()) {
            return [];
        }

        $matchedIds = [];

        foreach ($categories as $cat) {
            $path = $cat['path'] ?? null;
            if (! is_array($path) || empty($path)) {
                continue;
            }

            // Normalizamos texto para comparaciones más suaves
            $path = array_map(function ($segment) {
                return trim(mb_strtolower($segment));
            }, $path);

            // 1) Resolver CategoryType (primer elemento del path)
            $typeName = $path[0] ?? null;
            if (! $typeName) {
                continue;
            }

            $type = $this->bestMatchType($types, $typeName);
            if (! $type) {
                continue; // tipo desconocido
            }

            // 2) Resolver valores hijos dentro de ese tipo, siguiendo el path
            $currentLevel = $type->values->whereNull('parent_id');
            $parent = null;

            // Recorremos path[1], path[2]... como categoría, subcategoría...
            for ($i = 1; $i < count($path); $i++) {
                $segment = $path[$i];
                if ($segment === '') {
                    continue;
                }

                $value = $this->bestMatchValue($currentLevel, $segment);
                if (! $value) {
                    // Si no encontramos una coincidencia razonable en este nivel,
                    // dejamos de seguir esta ruta (mejor no asignar nada que inventar).
                    $parent = null;
                    break;
                }

                $parent = $value;
                $currentLevel = $value->children;
            }

            if ($parent) {
                $matchedIds[] = $parent->id;
            }
        }

        return array_values(array_unique($matchedIds));
    }

    protected function bestMatchType($types, string $needle): ?CategoryType
    {
        $needleNorm = Str::slug($needle);
        $best = null;
        $bestScore = 0;

        foreach ($types as $type) {
            $nameNorm = Str::slug($type->name);
            $slugNorm = Str::slug($type->slug ?? '');

            $scores = [
                similar_text($needleNorm, $nameNorm, $p1),
                similar_text($needleNorm, $slugNorm, $p2),
            ];

            $score = max($p1, $p2);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $type;
            }
        }

        // Umbral de similitud mínimo (porcentaje) para aceptar un tipo
        return $bestScore >= 60 ? $best : null;
    }

    protected function bestMatchValue($values, string $needle): ?CategoryValue
    {
        $needleNorm = Str::slug($needle);
        $best = null;
        $bestScore = 0;

        foreach ($values as $value) {
            $nameNorm = Str::slug($value->name);
            $slugNorm = Str::slug($value->slug ?? '');

            $scores = [
                similar_text($needleNorm, $nameNorm, $p1),
                similar_text($needleNorm, $slugNorm, $p2),
            ];

            $score = max($p1, $p2);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $value;
            }
        }

        // Umbral de similitud mínimo (porcentaje) para aceptar una categoría
        return $bestScore >= 60 ? $best : null;
    }
}

