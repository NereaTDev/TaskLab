<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware; Policy checked in controller
    }

    public function rules(): array
    {
        return [
            'type'             => ['required', Rule::enum(TaskType::class)],
            'url'              => ['nullable', 'string', 'max:255', 'url'],
            'priority'         => ['required', Rule::enum(TaskPriority::class)],
            'description'      => ['required', 'string', 'min:10'],
            'area'             => ['nullable', 'in:web,plataforma,frontierz,dashboard_empresas'],
            'estimated_effort' => ['nullable', 'in:low,medium,high'],
        ];
    }
}
