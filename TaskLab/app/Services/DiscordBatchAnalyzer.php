<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordBatchAnalyzer
{
    /**
     * Analiza un lote de mensajes de Discord junto con las tareas recientes del usuario
     * y devuelve las acciones a ejecutar.
     *
     * @param  Collection  $messages  Mensajes del buffer (ordenados por created_at ASC)
     * @param  Collection  $recentTasks  Tareas creadas en las últimas 4 horas por este usuario
     * @return array  Lista de acciones a ejecutar
     */
    public function analyze(Collection $messages, Collection $recentTasks): array
    {
        $apiKey = config('services.openai.api_key');
        $model  = config('services.openai.tasklab_model', 'gpt-4.1-mini');

        if (! $apiKey) {
            return $this->fallbackAnalysis($messages);
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt   = $this->buildUserPrompt($messages, $recentTasks);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model'           => $model,
                'messages'        => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'temperature'     => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

            if (! $response->ok()) {
                Log::warning('DiscordBatchAnalyzer: non-OK response', ['status' => $response->status()]);
                return $this->fallbackAnalysis($messages);
            }

            $content = $response->json('choices.0.message.content');
            $decoded = json_decode($content, true);

            if (! is_array($decoded) || ! isset($decoded['actions'])) {
                Log::warning('DiscordBatchAnalyzer: invalid JSON', ['content' => $content]);
                return $this->fallbackAnalysis($messages);
            }

            return $decoded['actions'];
        } catch (\Throwable $e) {
            Log::warning('DiscordBatchAnalyzer: exception', ['message' => $e->getMessage()]);
            return $this->fallbackAnalysis($messages);
        }
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres un sistema de análisis de mensajes de Discord para una herramienta de gestión de tareas llamada TaskLab.

Tu misión es analizar uno o varios mensajes enviados por el mismo usuario en un canal de Discord y decidir qué acciones tomar sobre las tareas del sistema.

TIPOS DE ACCIONES DISPONIBLES:
- "create": Crear una nueva tarea con el contenido del mensaje.
- "modify": Modificar una tarea existente reciente (últimas 4 horas) con nueva información.
- "delete": Archivar una tarea existente porque el usuario la ha cancelado explícitamente.
- "ignore": No hacer nada porque el mensaje es conversación casual o no es una petición de tarea.

REGLAS ESTRICTAS — LEE CON ATENCIÓN:

1. REGLA DE ORO: Ante la duda, siempre "create". Nunca modifiques ni elimines si no estás seguro.

2. SOBRE "delete":
   - SOLO cuando el usuario dice de forma EXPLÍCITA Y CLARA que quiere cancelar/eliminar/anular algo.
   - Palabras clave: "cancela", "olvida", "borra", "ya no hace falta", "no lo hagas", "olvídate de", "elimina la tarea de", "cancela lo de".
   - Siempre debe haber una referencia clara a qué tarea quiere eliminar.
   - Si hay alguna duda mínima, usa "modify" en lugar de "delete".
   - NUNCA usar "delete" si el usuario solo está corrigiendo o añadiendo información.

3. SOBRE "modify":
   - Solo si el mensaje es claramente una aclaración, corrección o adición de contexto a una tarea RECIENTE (máximo 4 horas).
   - El usuario debe referenciar implícita o explícitamente la tarea anterior.
   - Si el contenido podría ser una petición nueva e independiente, usa "create".

4. SOBRE "create":
   - Úsalo cuando hay una petición nueva que no encaja claramente con ninguna tarea reciente.
   - Si un usuario envía múltiples mensajes sobre temas distintos, crea una tarea por cada tema.
   - Si el usuario envía múltiples mensajes sobre el mismo tema, agrúpalos en una sola tarea.

5. SOBRE "ignore":
   - Saludos, respuestas a otros, mensajes sin petición concreta ("ok", "gracias", "entendido").
   - Preguntas sobre el estado de una tarea ya existente (no crean tarea nueva).

FORMATO DE RESPUESTA — devuelve SOLO este JSON:
{
  "actions": [
    {
      "type": "create|modify|delete|ignore",
      "message_indices": [0, 1],
      "task_id": null,
      "data": {
        "title": "string (solo para create/modify)",
        "description_raw": "string con todo el contexto relevante de los mensajes (solo para create/modify)",
        "type": "bug|feature|improvement|question (solo para create)",
        "priority": "critical|high|medium|low (solo para create)"
      },
      "reason": "Explicación breve de por qué esta acción"
    }
  ]
}

- "message_indices": índices (base 0) de los mensajes que cubren esta acción.
- "task_id": solo para "modify" y "delete", el ID de la tarea existente.
- "data": solo para "create" y "modify".
- Para "ignore" y "delete", "data" puede ser null o no incluirse.
PROMPT;
    }

    private function buildUserPrompt(Collection $messages, Collection $recentTasks): string
    {
        $messagesText = $messages->values()->map(function ($msg, $i) {
            $text = "[Mensaje {$i}] {$msg->message_text}";
            if (! empty($msg->image_urls)) {
                $text .= "\n  → Imágenes adjuntas: " . count($msg->image_urls);
            }
            if (! empty($msg->attachments)) {
                $text .= "\n  → Adjuntos: " . count($msg->attachments);
            }
            return $text;
        })->implode("\n\n");

        $prompt = "MENSAJES RECIBIDOS (en orden cronológico):\n\n{$messagesText}";

        if ($recentTasks->isNotEmpty()) {
            $tasksText = $recentTasks->map(function (Task $task) {
                $status = match ($task->status) {
                    'new'           => 'Backlog',
                    'ready_for_dev' => 'Pendiente',
                    'in_progress'   => 'En progreso',
                    'done'          => 'Completada',
                    'blocked'       => 'En revisión',
                    default         => $task->status,
                };
                return "- ID {$task->id}: \"{$task->title}\" [{$status}] — {$task->description_raw}";
            })->implode("\n");

            $prompt .= "\n\nTAREAS RECIENTES DE ESTE USUARIO (últimas 4 horas):\n{$tasksText}";
        } else {
            $prompt .= "\n\nTAREAS RECIENTES DE ESTE USUARIO: ninguna en las últimas 4 horas.";
        }

        $prompt .= "\n\nAnaliza los mensajes y devuelve las acciones a ejecutar según las reglas del sistema.";

        return $prompt;
    }

    /**
     * Fallback cuando no hay API key: tratar cada mensaje como una tarea nueva.
     */
    private function fallbackAnalysis(Collection $messages): array
    {
        return $messages->values()->map(function ($msg, $i) {
            return [
                'type'            => 'create',
                'message_indices' => [$i],
                'task_id'         => null,
                'data'            => [
                    'title'           => mb_substr($msg->message_text, 0, 60) . (mb_strlen($msg->message_text) > 60 ? '…' : ''),
                    'description_raw' => $msg->message_text,
                    'type'            => 'bug',
                    'priority'        => 'medium',
                ],
                'reason' => 'Fallback: sin API key, creando tarea directamente.',
            ];
        })->all();
    }
}
