<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiTaskRefiner
{
    /**
     * Refina la descripción bruta de una petición usando OpenAI.
     *
     * @param  string  $rawDescription   Texto original del usuario
     * @param  array   $imageUrls        URLs de imágenes adjuntas
     * @param  string  $categoryTree     Árbol de categorías formateado como texto
     * @param  array   $similarTasks     Tareas existentes similares [{id, title, status, categories}]
     * @param  string  $teamContext      Árbol de equipos (CategoryTypes) con perfiles disponibles
     */
    public function refine(
        string $rawDescription,
        array  $imageUrls    = [],
        string $categoryTree = '',
        array  $similarTasks = [],
        string $teamContext  = '',
        string $codeContext  = '',
    ): array {
        $apiKey = config('services.openai.api_key');
        $model  = config('services.openai.tasklab_model', 'gpt-4.1-mini');

        if (! $apiKey) {
            return $this->fakeRefinement($rawDescription);
        }

        try {
            $categorySection = $categoryTree
                ? "\n\nÁRBOL DE CATEGORÍAS DISPONIBLES EN TASKLAB (úsalas EXACTAMENTE como aparecen aquí):\n{$categoryTree}"
                : '';

            $teamSection = $teamContext
                ? "\n\nEQUIPOS Y PERFILES DISPONIBLES (usa el nombre EXACTO del equipo en el campo \"team\"):\n{$teamContext}"
                : '';

            $codeSection = $codeContext
                ? "\n\n{$codeContext}"
                : '';

            $similarSection = '';
            if (! empty($similarTasks)) {
                $lines = array_map(fn ($t) =>
                    "  - ID:{$t['id']} | \"{$t['title']}\" | Estado:{$t['status']}" .
                    (! empty($t['categories']) ? " | Categorías: " . implode(' > ', $t['categories']) : ''),
                    $similarTasks
                );
                $similarSection = "\n\nTAREAS EXISTENTES RELEVANTES (para detectar duplicados/conflictos):\n" . implode("\n", $lines);
            }

            $systemPrompt = <<<PROMPT
Eres el sistema de validación y refinamiento de tareas de TaskLab. Tu trabajo es analizar peticiones de usuarios y producir:
1. Una tarea bien estructurada para desarrolladores.
2. Una validación de calidad que determina si la tarea puede entrar al flujo de trabajo.
3. Una detección de duplicados o conflictos contra tareas existentes.

SIEMPRE devuelves SOLO un objeto JSON válido, sin texto adicional, con esta estructura EXACTA:
{
  "title": string,
  "summary": string,
  "requirements": string[],
  "behavior": string,
  "test_cases": string[],
  "type": string,
  "priority": string,
  "points": number,
  "primary_url": string,
  "additional_urls": string[],
  "impact": string,
  "parsed_fields": {
    "raw_tipo": string,
    "raw_prioridad": string,
    "raw_resultado_esperado": string,
    "raw_resultado_actual": string
  },
  "categories": [
    { "path": ["NombreExactoTipo", "NombreExactoCategoría", "NombreExactoSubcategoría"] }
  ],
  "team": string | null,
  "required_position": string | null,
  "acceptance_check": {
    "status": "approved" | "needs_review",
    "score": number,
    "issues": string[]
  },
  "duplicate_check": {
    "status": "unique" | "conflict" | "related",
    "related_task_id": null | number,
    "confidence": number,
    "explanation": string
  }
}

═══════════════════════════════════════════
INSTRUCCIONES DE REFINAMIENTO
═══════════════════════════════════════════
- Idioma SIEMPRE español.
- type ∈ {"bug","feature","improvement","question"}.
- priority ∈ {"critical","high","medium","low"}.
- points ∈ [0.5,1,2,4,6,8,10,12,16]. Usa 0 si no puedes estimar.
- title: corto, claro, accionable. Menciona módulo/pantalla + síntoma.
- summary: 2-6 frases técnicas que expliquen el problema sin haber visto el mensaje original.
- requirements: criterios de aceptación concretos y comprobables.
- behavior: dos bloques "Comportamiento actual:" y "Comportamiento esperado:".
- test_cases: escenarios de QA para validar la solución.
- Si hay imágenes adjuntas, analízalas y descríbelas en summary.{$categorySection}

INSTRUCCIONES DE CATEGORIZACIÓN:
- Usa ÚNICAMENTE los nombres exactos del árbol de categorías proporcionado.
- Analiza la petición en profundidad: ¿qué departamento/área/módulo es responsable?
- Recorre el árbol de lo general a lo específico. Si una subcategoría encaja bien, inclúyela. Si no hay subcategoría coherente, quédate en categoría.
- Si no ves ninguna correspondencia clara, devuelve "categories": [].
- Puedes proponer hasta 2 rutas de categorías si la tarea toca múltiples áreas.{$similarSection}

═══════════════════════════════════════════
CRITERIOS DE ACEPTACIÓN (GATE DE CALIDAD)
═══════════════════════════════════════════
Evalúa la petición con estos criterios y devuelve acceptance_check:

CRITERIO 1 — CLARIDAD (obligatorio):
La petición describe claramente QUÉ ocurre o QUÉ se pide. No son válidas frases de 1-3 palabras sin contexto ("arregla el botón", "no va", "necesito ayuda").
Puntuación: 0 = completamente ambiguo, 10 = perfectamente claro.

CRITERIO 2 — RELEVANCIA (obligatorio):
La petición es relevante para la plataforma/producto de la empresa. Mensajes de conversación casual, spam, o solicitudes sin relación con el producto son inválidos.

CRITERIO 3 — ACCIONABILIDAD (obligatorio):
La petición puede convertirse en trabajo concreto para un desarrollador. Preguntas retóricas, opiniones o comentarios sin acción asociada no son válidos.

CRITERIO 4 — CONTEXTO MÍNIMO:
La petición tiene suficiente contexto para empezar a trabajar sin necesidad de ir a preguntar al usuario qué quería decir exactamente. Si falta contexto crítico (¿qué pantalla? ¿qué usuario? ¿cuándo ocurre?) pero la petición es válida, indicarlo en issues pero no bloquear.

REGLA: si algún criterio OBLIGATORIO falla (puntuación < 4), status = "needs_review". En caso contrario, status = "approved".
El campo score es el promedio de los 4 criterios (0-10).
El campo issues lista los problemas encontrados en español, explicando al usuario qué falta para que la tarea sea aceptada.

═══════════════════════════════════════════
INSTRUCCIONES DE DETECCIÓN DE DUPLICADOS
═══════════════════════════════════════════
Compara la petición actual contra las tareas existentes proporcionadas arriba.

"unique": no hay ninguna tarea similar ni contradictoria.

"conflict": existe una tarea que contradice o revierte directamente lo que se pide.
  Ejemplo: hay una tarea "Cambiar botón de verde a azul" y la nueva pide "Cambiar botón de azul a verde".
  En este caso related_task_id = ID de la tarea conflictiva, confidence = 0.0-1.0.

"related": la petición trata sobre el mismo componente/funcionalidad que una tarea existente en estado activo (no done/archived).
  Puede aportar contexto adicional o ser complementaria.
  En este caso related_task_id = ID de la tarea relacionada más relevante, confidence = 0.0-1.0.

Si no hay tareas existentes proporcionadas, devuelve siempre "unique".

═══════════════════════════════════════════
ASIGNACIÓN DE EQUIPO Y PERFIL{$teamSection}

INSTRUCCIONES DE ASIGNACIÓN:
- Analiza la tarea y determina qué equipo debería resolverla usando el árbol de EQUIPOS Y PERFILES anterior.
- "team": nombre EXACTO del equipo (tal como aparece en "Equipo: X"). Si no hay equipos definidos o no encaja en ninguno, pon null.
- "required_position": describe en pocas palabras qué perfil se necesita para resolver esta tarea (ej. "Frontend Developer", "Backend Developer", "Product Manager"). Intenta que coincida con alguno de los perfiles listados en el equipo elegido. Si no puedes determinarlo, pon null.
PROMPT;

            if ($codeSection) {
                $systemPrompt .= $codeSection . "\n\n"
                    . "═══════════════════════════════════════════\n"
                    . "INSTRUCCIONES PARA EL CONTEXTO DE CÓDIGO\n"
                    . "═══════════════════════════════════════════\n"
                    . "Se te proporcionan fragmentos del repositorio del proyecto. Úsalos para:\n"
                    . "- Identificar EXACTAMENTE qué archivo, componente, función o ruta está relacionada con la petición.\n"
                    . "- Deducir la URL o ruta (route) afectada y rellenar \"primary_url\" con ella si puedes determinarla.\n"
                    . "- Enriquecer \"summary\", \"requirements\" y \"behavior\" con referencias concretas al código (componente, función, fichero).\n"
                    . "- Si el código revela la causa raíz del problema, inclúyela en \"behavior\" bajo \"Comportamiento actual:\".\n"
                    . "- No inventes rutas ni componentes que no aparezcan en el código proporcionado.\n";
            }

            $userText = "Descripción de la petición del usuario:\n\n" . $rawDescription;

            if (! empty($imageUrls)) {
                $userText .= "\n\nEl usuario ha adjuntado " . count($imageUrls) . " imagen(es). Analízalas para enriquecer la descripción.";
            }

            $userContent = $this->buildUserContent($userText, $imageUrls);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                'model'           => $model,
                'messages'        => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userContent],
                ],
                'temperature'     => 0.2,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (! $response->ok()) {
                \Log::warning('AiTaskRefiner: OpenAI non-OK', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->fakeRefinement($rawDescription);
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content) || $content === '') {
                return $this->fakeRefinement($rawDescription);
            }

            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                \Log::warning('AiTaskRefiner: invalid JSON', ['content' => $content]);
                return $this->fakeRefinement($rawDescription);
            }

            $fallbackTitle = Str::limit($rawDescription, 60, '…');

            return [
                'title'            => $decoded['title']            ?? $fallbackTitle,
                'summary'          => $decoded['summary']          ?? null,
                'requirements'     => $decoded['requirements']     ?? [],
                'behavior'         => $decoded['behavior']         ?? null,
                'test_cases'       => $decoded['test_cases']       ?? [],
                'type'             => $decoded['type']             ?? 'bug',
                'priority'         => $decoded['priority']         ?? 'medium',
                'points'           => $decoded['points']           ?? null,
                'primary_url'      => $decoded['primary_url']      ?? null,
                'additional_urls'  => $decoded['additional_urls']  ?? [],
                'impact'           => $decoded['impact']           ?? null,
                'categories'        => $decoded['categories']        ?? [],
                'team'              => $decoded['team']              ?? null,
                'required_position' => $decoded['required_position'] ?? null,
                'acceptance_check' => $decoded['acceptance_check'] ?? ['status' => 'approved', 'score' => 7, 'issues' => []],
                'duplicate_check'  => $decoded['duplicate_check']  ?? ['status' => 'unique', 'related_task_id' => null, 'confidence' => 0, 'explanation' => ''],
            ];

        } catch (\Throwable $e) {
            \Log::warning('AiTaskRefiner: exception', ['message' => $e->getMessage()]);
            return $this->fakeRefinement($rawDescription);
        }
    }

    private function buildUserContent(string $text, array $imageUrls): mixed
    {
        if (empty($imageUrls)) {
            return $text;
        }

        $content = [['type' => 'text', 'text' => $text]];
        foreach (array_slice($imageUrls, 0, 4) as $url) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url, 'detail' => 'low']];
        }
        return $content;
    }

    private function fakeRefinement(string $rawDescription): array
    {
        return [
            'title'            => mb_substr($rawDescription, 0, 60) . (mb_strlen($rawDescription) > 60 ? '…' : ''),
            'summary'          => 'Resumen generado automáticamente a partir de la descripción original.',
            'requirements'     => [
                'Clarificar el comportamiento actual y reproducir el problema en entorno de pruebas.',
                'Implementar el comportamiento esperado descrito por el solicitante.',
                'Verificar que no hay regresiones en flujos relacionados.',
            ],
            'behavior'         => "Comportamiento actual: descrito por el usuario en la petición original.\nComportamiento esperado: actualizado según los requisitos refinados.",
            'test_cases'       => [
                'Abrir la página afectada y reproducir el escenario descrito.',
                'Verificar que el nuevo comportamiento coincide con el resultado esperado.',
                'Comprobar casos límite y diferentes dispositivos/navegadores si aplica.',
            ],
            'acceptance_check' => ['status' => 'approved', 'score' => 5, 'issues' => []],
            'duplicate_check'  => ['status' => 'unique', 'related_task_id' => null, 'confidence' => 0, 'explanation' => ''],
        ];
    }
}
