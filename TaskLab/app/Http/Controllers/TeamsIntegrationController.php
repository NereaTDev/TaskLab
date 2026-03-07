<?php

namespace App\Http\Controllers;

use App\Jobs\RefineTaskWithAi;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamsIntegrationController extends Controller
{
    public function store(Request $request, TaskAssignmentService $assignmentService)
    {
        // Autenticación simple por token
        $expectedToken = config('services.teams.token');

        if (! $expectedToken || $request->header('X-Teams-Token') !== $expectedToken) {
            abort(403, 'Invalid token');
        }

        // Validar el payload básico que esperamos desde Teams / Power Automate
        $data = $request->validate([
            'message_id'       => ['required', 'string'],
            'message_text'     => ['required', 'string'],
            'message_url'      => ['nullable', 'string'],
            'channel_id'       => ['nullable', 'string'],
            'channel_name'     => ['nullable', 'string'],
            'team_id'          => ['nullable', 'string'],
            'team_name'        => ['nullable', 'string'],
            'from_email'       => ['nullable', 'email'],
            'from_name'        => ['nullable', 'string'],
            'from_teams_id'    => ['nullable', 'string'],
            'area'             => ['nullable', 'in:web,plataforma,frontierz,dashboard_empresas'],
            'estimated_effort' => ['nullable', 'in:low,medium,high'],
        ]);

        // Idempotencia: si ya tenemos ese mensaje de Teams no creamos otra task
        $existing = Task::where('source', 'teams')
            ->where('external_message_id', $data['message_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'already_exists',
                'task_id' => $existing->id,
            ]);
        }

        // Buscar o crear el usuario que reporta por email (si viene)
        $reporter = null;
        if (! empty($data['from_email'])) {
            $reporter = User::firstOrCreate(
                ['email' => $data['from_email']],
                [
                    'name'      => $data['from_name'] ?? $data['from_email'],
                    'password'  => Str::random(32),
                    'user_type' => 'requester',
                ]
            );
        }

        // Construir la descripción bruta con algo de contexto del mensaje de Teams
        $descriptionRaw = $data['message_text'];

        if (! empty($data['message_url'])) {
            $descriptionRaw .= "\n\nTeams message: " . $data['message_url'];
        }

        if (! empty($data['channel_name']) || ! empty($data['team_name'])) {
            $descriptionRaw .= "\n\nOrigen: " .
                ($data['team_name'] ?? 'Equipo desconocido') . ' / ' .
                ($data['channel_name'] ?? 'Canal desconocido');
        }

        // Crear la Task base, reutilizando el mismo esquema que el formulario web
        $task = Task::create([
            'title'               => null,
            'description_raw'     => $descriptionRaw,
            'type'                => 'bug',
            'status'              => 'new',
            'priority'            => 'medium',
            'reporter_id'         => $reporter?->id,
            'assignee_id'         => null,
            'source'              => 'teams',
            'area'                => $data['area'] ?? null,
            'estimated_effort'    => $data['estimated_effort'] ?? 'medium',
            'external_message_id' => $data['message_id'],
            'external_channel'    => $data['channel_id'] ?? $data['channel_name'] ?? null,
            'external_user_id'    => $data['from_teams_id'] ?? null,
            'external_payload'    => $request->all(),
        ]);

        // Lanzar el job de refinamiento con "IA" y la asignación automática
        RefineTaskWithAi::dispatch($task);
        $assignmentService->assign($task);

        return response()->json([
            'status'  => 'ok',
            'task_id' => $task->id,
        ], 201);
    }
}
