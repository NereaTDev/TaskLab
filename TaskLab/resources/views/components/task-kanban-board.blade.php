@props(['tasks', 'categoryTypes' => collect(), 'users' => collect()])

@php
  $columnConfig = [
    'pending' => [
      'label'    => 'Pendiente',
      'statuses' => ['new', 'in_refinement', 'ready_for_dev'],
      'target'   => 'new',
      'icon'     => 'clock',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-slate-800 border-slate-700',
      'badge'    => 'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40',
    ],
    'in_progress' => [
      'label'    => 'En Progreso',
      'statuses' => ['in_progress'],
      'target'   => 'in_progress',
      'icon'     => 'bolt',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-tasklab-primary/10 border-tasklab-primary/40',
      'badge'    => 'bg-tasklab-primary/20 text-tasklab-primary border border-tasklab-primary/60',
    ],
    'in_review' => [
      'label'    => 'En Revisión',
      'statuses' => ['blocked'],
      'target'   => 'blocked',
      'icon'     => 'eye',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-violet-900/30 border-violet-600/70',
      'badge'    => 'bg-violet-900/40 text-violet-100 border border-violet-700/70',
    ],
    'done' => [
      'label'    => 'Completada',
      'statuses' => ['done'],
      'target'   => 'done',
      'icon'     => 'check',
      'bg'       => 'bg-slate-900',
      'header'   => 'bg-tasklab-success/15 border-tasklab-success/40',
      'badge'    => 'bg-tasklab-success/20 text-tasklab-success border border-tasklab-success/60',
    ],
  ];

  $priorityColors = [
    'critical' => 'bg-tasklab-danger/20 text-tasklab-danger border border-tasklab-danger/60',
    'high'     => 'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40',
    'medium'   => 'bg-tasklab-primary/10 text-tasklab-primary border border-tasklab-primary/40',
    'low'      => 'bg-tasklab-bg-muted text-tasklab-muted border border-slate-800',
  ];

  $typeLabels = [
    'bug'         => 'Bug',
    'feature'     => 'Evolutiva',
    'improvement' => 'Mejora',
    'question'    => 'Consulta',
  ];
@endphp

<div
  class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
  x-data="taskBoard(
    '{{ route('tasks.updateStatus', ['task' => 'TASK_ID_PLACEHOLDER']) }}',
    @js($tasks->values()),
    @js($categoryTypes->map(fn($t) => [
        'id'     => $t->id,
        'name'   => $t->name,
        'slug'   => $t->slug,
        'values' => $t->values->map(fn($v) => [
            'id'               => $v->id,
            'name'             => $v->name,
            'parent_id'        => $v->parent_id,
            'category_type_id' => $v->category_type_id,
        ]),
    ]))
  )"
>
  @foreach($columnConfig as $key => $col)
    @php
      $count = $tasks->whereIn('status', $col['statuses'])->count();
    @endphp
    <div
      class="rounded-xl border border-slate-800 {{ $col['bg'] }} flex flex-col min-h-[400px]"
      @dragover.prevent
      @drop.prevent="moveTaskToStatus('{{ $col['target'] }}')"
    >
      {{-- Encabezado de columna --}}
      <div class="flex items-center justify-between px-3 py-2.5 rounded-t-xl border-b {{ $col['header'] }}">
        <div class="flex items-center gap-2">
          @if($col['icon'] === 'clock')
            <svg class="h-4 w-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          @elseif($col['icon'] === 'bolt')
            <svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          @elseif($col['icon'] === 'eye')
            <svg class="h-4 w-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          @else
            <svg class="h-4 w-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          @endif
          <span class="text-sm font-semibold text-tasklab-text">{{ $col['label'] }}</span>
        </div>
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $col['badge'] }}">{{ $count }}</span>
        @if(in_array($key, ['pending', 'in_progress']))
          <a href="{{ route('tasks.create') }}" class="p-1 rounded-md text-tasklab-accent hover:bg-tasklab-accent/10 hover:text-tasklab-accent" title="Añadir tarea">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          </a>
        @endif
      </div>

      <div class="flex-1 p-2 space-y-2 overflow-y-auto" data-column-body="{{ $col['target'] }}">
        <template x-for="task in columnTasks(@js($col['statuses']))" :key="task.id">
          <div
            class="block rounded-lg border border-slate-800 bg-tasklab-bg-muted p-3 shadow-card hover:border-tasklab-accent transition-shadow cursor-move"
            draggable="true"
            :data-task-id="task.id"
            @dragstart="draggedTaskId = task.id"
            @dragend="draggedTaskId = null"
            @click.stop="openTaskModal(task)"
          >
            <h3 class="text-sm font-medium text-tasklab-text line-clamp-2" x-text="task.title || ('Sin título #' + task.id)"></h3>
            <div class="mt-2 flex flex-wrap gap-1">
              <span
                class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                :class="{
                  'bg-tasklab-danger/20 text-tasklab-danger border border-tasklab-danger/60': task.priority === 'critical',
                  'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40': task.priority === 'high',
                  'bg-tasklab-primary/10 text-tasklab-primary border border-tasklab-primary/40': task.priority === 'medium',
                  'bg-tasklab-bg-muted text-tasklab-muted border border-slate-800': !['critical','high','medium'].includes(task.priority),
                }"
              >
                <span x-text="task.priority === 'critical' ? 'Crítica' : (task.priority ? task.priority.charAt(0).toUpperCase() + task.priority.slice(1) : 'Media')"></span>
              </span>
              <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-tasklab-bg text-tasklab-muted border border-slate-800">
                <span x-text="
                  task.type === 'bug' ? 'Bug' :
                  task.type === 'feature' ? 'Evolutiva' :
                  task.type === 'improvement' ? 'Mejora' :
                  task.type === 'question' ? 'Consulta' :
                  (task.type ? task.type : '')
                "></span>
              </span>
            </div>
            <div class="mt-2 flex items-center gap-3 text-[11px] text-tasklab-muted">
              <span class="flex items-center gap-1">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span x-text="task.created_at ? new Date(task.created_at).toLocaleDateString('es-ES') : ''"></span>
              </span>
            </div>
            <div class="mt-1.5 flex items-center gap-1 text-[11px] text-tasklab-muted">
              <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <span x-text="task.assignee && task.assignee.name ? task.assignee.name : 'Sin asignar'"></span>
            </div>
          </div>
        </template>

        <template x-if="columnTasks(@js($col['statuses'])).length === 0">
          <div class="flex flex-col items-center justify-center py-12 text-tasklab-muted">
            @if($col['icon'] === 'clock')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($col['icon'] === 'bolt')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            @elseif($col['icon'] === 'eye')
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            @else
              <svg class="h-10 w-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
            @endif
            <p class="text-label font-medium text-tasklab-muted">Sin tareas</p>
          </div>
        </template>
      </div>
    </div>
  @endforeach

  {{-- Modal detalle / edición de tarea --}}
  <div
    x-cloak
    x-show="isTaskModalOpen"
    class="fixed inset-0 z-40 flex items-center justify-center bg-black/60"
    @keydown.escape.window="closeTaskModal()"
  >
    <template x-if="modalTask">
      <div
        class="w-full max-w-4xl rounded-2xl border border-slate-800 bg-tasklab-bg shadow-2xl flex flex-col overflow-hidden"
        @click.outside="closeTaskModal()"
      >
        <form
          method="POST"
          :action="'{{ route('tasks.update', ['task' => 'TASK_ID_PLACEHOLDER']) }}'.replace('TASK_ID_PLACEHOLDER', modalTask.id)"
        >
        @csrf
        @method('PATCH')

        {{-- Cabecera: título + metadatos clave --}}
        <div class="border-b border-slate-800 bg-tasklab-bg-muted px-6 py-4 flex flex-col gap-3">
          <div class="flex items-start gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-tasklab-text border border-slate-700">
              <span x-text="modalTask && modalTask.title ? modalTask.title.substring(0,2).toUpperCase() : 'TS'"></span>
            </span>
            <div class="flex-1 min-w-0">
              <input
                type="text"
                name="title"
                class="w-full bg-transparent border-none text-body font-semibold text-tasklab-text focus:ring-0 focus:outline-none p-0"
                placeholder="Título de la tarea"
                x-model="modalTask.title"
              />
              <div class="mt-2 flex flex-wrap items-center gap-2 text-meta text-tasklab-muted">
                <span class="inline-flex items-center rounded-full bg-tasklab-bg px-2 py-0.5 text-[11px] border border-slate-700">
                  <span class="mr-1">Tipo:</span>
                  <span x-text="modalTask ? modalTask.type : ''"></span>
                </span>
                <span class="inline-flex items-center rounded-full bg-tasklab-bg px-2 py-0.5 text-[11px] border border-slate-700">
                  <span class="mr-1">ID:</span>
                  <span x-text="modalTask ? modalTask.id : ''"></span>
                </span>
              </div>
            </div>
          </div>


        </div>

        {{-- Cuerpo en dos columnas: descripciones + metadatos --}}
        <div class="px-6 py-4 grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 h-[70vh] overflow-hidden items-stretch">
          {{-- Columna izquierda: descripciones --}}
          <div class="lg:col-span-2 space-y-3 overflow-y-auto pr-2 min-h-0">
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3">
              <h3 class="text-label font-semibold text-tasklab-text mb-1">Descripción refinada (solo lectura)</h3>
              <p class="text-body text-tasklab-muted whitespace-pre-wrap" x-text="modalTask && modalTask.description_ai ? modalTask.description_ai : 'Refinamiento pendiente o no disponible.'"></p>
            </section>
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3">
              <h3 class="text-label font-semibold text-tasklab-text mb-1">Descripción original (editable)</h3>
              <textarea
                name="description_raw"
                rows="6"
                class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-3 py-2 text-sm resize-y"
                x-text="modalTask && modalTask.description_raw ? modalTask.description_raw : ''"
              ></textarea>
            </section>
            {{-- Comments (solo maquetación básica) --}}
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3">
              <h3 class="text-label font-semibold text-tasklab-text">Comentarios</h3>
              <div class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-[10px] font-semibold text-tasklab-text border border-slate-700">
                  N
                </span>
                <input
                  type="text"
                  class="flex-1 rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-3 py-1.5 text-sm"
                  placeholder="Añadir un comentario..."
                />
              </div>
            </section>
          </div>

          {{-- Columna derecha: panel de propiedades estilo Shortcut --}}
          <div class="space-y-3 overflow-y-auto pl-2 min-h-0">
            {{-- Task ID + Permalink --}}
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-2 text-label text-tasklab-muted">
              <div class="flex items-center justify-between gap-2">
                <span class="text-meta uppercase tracking-wide text-tasklab-muted/80">Task ID</span>
                <span class="text-body font-semibold text-tasklab-text" x-text="modalTask ? modalTask.id : ''"></span>
              </div>
              <div class="mt-2">
                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Permalink</p>
                <input
                  type="text"
                  readonly
                  class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-[11px] text-tasklab-text px-2 py-1"
                  :value="modalTask ? '{{ url('/tasks') }}/' + modalTask.id : ''"
                />
              </div>
            </section>

            {{-- Sección grande de filtros: tipos dinámicos + estado/tipo/prioridad --}}
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3 text-label text-tasklab-muted">
              {{-- Tipos dinámicos (CategoryTypes) --}}
              @foreach ($categoryTypes as $type)
                <div class="space-y-2">
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">
                    {{ $type->name }}
                  </p>
                  <select
                    class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                    x-model="categorySelections['{{ $type->slug }}'].value_id"
                    @change="onCategoryRootChange('{{ $type->slug }}')"
                  >
                    <option value="">Sin asignar</option>
                    @foreach($type->values->whereNull('parent_id') as $value)
                      <option value="{{ $value->id }}">{{ $value->name }}</option>
                    @endforeach
                  </select>
                  <template
                    x-if="categorySelections['{{ $type->slug }}'].children && categorySelections['{{ $type->slug }}'].children.length"
                  >
                    <select
                      class="mt-1 w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                      x-model="categorySelections['{{ $type->slug }}'].child_value_id"
                    >
                      <option value="">Sin subcategoría</option>
                      <template
                        x-for="child in categorySelections['{{ $type->slug }}'].children"
                        :key="child.id"
                      >
                        <option :value="child.id" x-text="child.name"></option>
                      </template>
                    </select>
                  </template>
                </div>
              @endforeach

              <div class="border-t border-slate-800 pt-3 space-y-3">
                <div>
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Estado</p>
                  <select
                    name="status"
                    class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                    x-model="modalTask.status"
                  >
                    <option value="new">Nueva</option>
                    <option value="in_refinement">En refinamiento</option>
                    <option value="ready_for_dev">Lista para dev</option>
                    <option value="in_progress">En progreso</option>
                    <option value="blocked">Bloqueada</option>
                    <option value="done">Completada</option>
                  </select>
                </div>
                <div>
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Tipo</p>
                  <select
                    name="type"
                    class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                    x-model="modalTask.type"
                  >
                    <option value="bug">Bug</option>
                    <option value="feature">Evolutiva</option>
                    <option value="improvement">Mejora</option>
                    <option value="question">Consulta</option>
                  </select>
                </div>
                <div>
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Prioridad</p>
                  <select
                    name="priority"
                    class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                    x-model="modalTask.priority"
                  >
                    <option value="critical">Crítica</option>
                    <option value="high">Alta</option>
                    <option value="medium">Media</option>
                    <option value="low">Baja</option>
                  </select>
                </div>
              </div>
            </section>

            {{-- Requester / Asignado (selectores de usuarios) --}}
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3 text-label text-tasklab-muted">
              <div>
                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Requester</p>
                <select
                  name="reporter_id"
                  class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                >
                  @foreach($users as $userOption)
                    <option
                      value="{{ $userOption->id }}"
                      x-bind:selected="modalTask && modalTask.reporter && modalTask.reporter.id === {{ $userOption->id }}"
                    >
                      {{ $userOption->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div>
                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Asignado a</p>
                <select
                  name="assignee_id"
                  class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                >
                  @foreach($users as $userOption)
                    <option
                      value="{{ $userOption->id }}"
                      x-bind:selected="modalTask && modalTask.assignee && modalTask.assignee.id === {{ $userOption->id }}"
                    >
                      {{ $userOption->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </section>

            {{-- Estimación / Fechas --}}
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3 text-label text-tasklab-muted">
              <div>
                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Estimación (puntos)</p>
                <input
                  type="number"
                  name="points"
                  min="0"
                  class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                  :value="modalTask && modalTask.points ? modalTask.points : ''"
                />
              </div>
              <div>
                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Esfuerzo estimado</p>
                <select
                  name="estimated_effort"
                  class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                  x-model="modalTask.estimated_effort"
                >
                  <option value="">Sin especificar</option>
                  <option value="low">Bajo</option>
                  <option value="medium">Medio</option>
                  <option value="high">Alto</option>
                </select>
              </div>
              <div class="grid grid-cols-1 gap-2">
                <div>
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Fecha de creación</p>
                  <p class="mt-0.5 text-body text-tasklab-text" x-text="modalTask && modalTask.created_at ? new Date(modalTask.created_at).toLocaleDateString('es-ES') : '—'"></p>
                </div>
                <div>
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Fecha límite</p>
                  <input
                    type="date"
                    name="due_date"
                    class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                  />
                </div>
              </div>
            </section>


          </div>
        </div>

        <div class="px-6 py-3 flex justify-end gap-2 border-t border-slate-800 bg-slate-900/80">
          {{-- IDs de categorías seleccionadas (raíz + subcategoría) --}}
          <template
            x-for="id in Object.values(categorySelections)
              .flatMap(sel => [sel.value_id, sel.child_value_id])
              .filter(id => id)"
            :key="id"
          >
            <input type="hidden" name="category_values[]" :value="id">
          </template>

          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full border border-slate-700 bg-tasklab-bg px-4 py-1.5 text-body text-tasklab-muted hover:text-tasklab-text hover:border-tasklab-accent"
            @click.prevent="closeTaskModal()"
          >
            Cancelar
          </button>
          <button
            type="submit"
            class="inline-flex items-center justify-center rounded-full bg-tasklab-accent px-4 py-1.5 text-body font-medium text-slate-950 hover:bg-tasklab-accent-soft"
          >
            Guardar cambios
          </button>
        </div>
      </form>
    </div>
  </template>
  </div>
</div>
