<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiTaskRefiner
{
    /**
     * Refina la descripción bruta de una petición usando un modelo de IA real
     * (OpenAI) cuando hay API key configurada. Si no, vuelve al modo "fake".
     */
    public function refine(string $rawDescription): array
    {
        $apiKey = config('services.openai.api_key');
        $model  = config('services.openai.tasklab_model', 'gpt-4.1-mini');

        if (! $apiKey) {
            // Sin API key, usamos el refinamiento fake para no romper el flujo.
            return $this->fakeRefinement($rawDescription);
        }

        try {
            $systemPrompt = <<<'PROMPT'
Eres un asistente técnico que convierte descripciones en texto libre de peticiones (bugs, features, mejoras, dudas) en tareas claras para desarrolladores.

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
    "raw_area": string,
    "raw_prioridad": string,
    "raw_resultado_esperado": string,
    "raw_resultado_actual": string
  }
}

Instrucciones clave:
- Idioma SIEMPRE español.
- No inventes datos importantes; si falta algo, dilo explícitamente.
- type ∈ {"bug","feature","improvement","question"}.
- priority ∈ {"critical","high","medium","low"}.
- points debe ser una estimación de horas de trabajo de un desarrollador estándar y tomar un valor de este conjunto: [0.5,1,2,4,6,8,10,12,16]. Si no puedes estimar, usa 0.
- primary_url debe ser la URL principal más relevante, si aparece de forma clara.
- additional_urls debe contener otras URLs relevantes mencionadas en el texto.
- Si el texto contiene campos tipo "TIPO:", "AREA:", "PRIORIDAD:", etc., respétalos salvo incoherencias graves, y cópialos también en parsed_fields.*.
- title debe ser corto y claro.
- summary debe explicar el contexto y el problema/petición en pocas frases.
- requirements debe listar criterios de aceptación claros.
- behavior debe tener dos bloques: comportamiento actual y comportamiento esperado.
- test_cases debe listar escenarios de prueba.
PROMPT;

            $userPrompt = "Descripción bruta de la petición (texto tal cual del usuario):\n\n" . $rawDescription;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'temperature'     => 0.2,
                // Pedimos JSON estricto en la respuesta
                'response_format' => [
                    'type' => 'json_object',
                ],
            ]);

            if (! $response->ok()) {
                \Log::warning('AiTaskRefiner OpenAI non-OK response', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return $this->fakeRefinement($rawDescription);
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content) || $content === '') {
                \Log::warning('AiTaskRefiner OpenAI empty content', ['raw' => $data]);

                return $this->fakeRefinement($rawDescription);
            }

            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                \Log::warning('AiTaskRefiner could not decode JSON content', ['content' => $content]);

                return $this->fakeRefinement($rawDescription);
            }

            // Mapear campos con valores por defecto seguros
            $fallbackTitle = Str::limit($rawDescription, 60, '…');

            return [
                'title'           => $decoded['title']           ?? $fallbackTitle,
                'summary'         => $decoded['summary']         ?? null,
                'requirements'    => $decoded['requirements']    ?? [],
                'behavior'        => $decoded['behavior']        ?? null,
                'test_cases'      => $decoded['test_cases']      ?? [],
                'type'            => $decoded['type']            ?? 'bug',
                'priority'        => $decoded['priority']        ?? 'medium',
                'points'          => $decoded['points']          ?? null,
                'primary_url'     => $decoded['primary_url']     ?? null,
                'additional_urls' => $decoded['additional_urls'] ?? [],
                'impact'          => $decoded['impact']          ?? null,
            ];
        } catch (\Throwable $e) {
            \Log::warning('AiTaskRefiner OpenAI exception', [
                'message' => $e->getMessage(),
            ]);

            return $this->fakeRefinement($rawDescription);
        }
    }

    /**
     * Refinamiento "fake" para cuando no hay API key o la llamada falla.
     */
    private function fakeRefinement(string $rawDescription): array
    {
        return [
            'title' => mb_substr($rawDescription, 0, 60) . (mb_strlen($rawDescription) > 60 ? '…' : ''),
            'summary' => 'Auto-generated summary of the request based on the user description.',
            'requirements' => [
                'Clarify the current behavior and reproduce the issue in a test environment.',
                'Implement the expected behavior described by the requester.',
                'Ensure no regressions on related pages or flows.',
            ],
            'behavior' => "Current: behavior described by the user in the raw description.\nDesired: updated behavior matching the refined requirements.",
            'test_cases' => [
                'Open the affected page and reproduce the scenario described by the user.',
                'Verify that the new behavior matches the expected outcome.',
                'Check edge cases and different devices/browsers if applicable.',
            ],
        ];
    }
}
