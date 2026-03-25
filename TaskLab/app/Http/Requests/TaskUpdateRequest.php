<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy checked in controller via $this->authorize()
    }

    public function rules(): array
    {
        return [
            'title'             => ['nullable', 'string', 'max:255'],
            'description_raw'   => ['nullable', 'string'],
            'priority'          => ['required', Rule::enum(TaskPriority::class)],
            'area'              => ['nullable', 'in:web,plataforma,frontierz,dashboard_empresas'],
            'status'            => ['required', Rule::enum(TaskStatus::class)],
            'type'              => ['required', Rule::enum(TaskType::class)],
            'estimated_effort'  => ['nullable', 'in:low,medium,high'],
            'category_values'   => ['nullable', 'array'],
            'category_values.*' => ['integer', 'exists:category_values,id'],
            'reporter_id'       => ['nullable', 'exists:users,id'],
            'assignee_id'       => ['nullable', 'exists:users,id'],
        ];
    }
}
