@props(['users' => collect()])

<form
  method="POST"
  class="flex flex-col flex-1 min-h-0"
  :action="modalMode === 'create'
    ? '{{ route('tasks.store') }}'
    : '{{ route('tasks.update', ['task' => 'TASK_ID_PLACEHOLDER']) }}'.replace('TASK_ID_PLACEHOLDER', modalTask?.id ?? '')"
>
@csrf
<template x-if="modalMode !== 'create'">
  @method('PATCH')
</template>

{{--
  Hidden fallbacks para status/type/priority.
  Siempre se envían, incluso cuando los <select> están disabled (modo vista).
  Como van ANTES de los selects, en modo edición el select (que va después) sobreescribe el valor.
--}}
<input type="hidden" name="status"   :value="modalTask ? modalTask.status   : 'new'">
<input type="hidden" name="type"     :value="modalTask ? modalTask.type     : 'bug'">
<input type="hidden" name="priority" :value="modalTask ? modalTask.priority : 'medium'">

{{-- Cabecera: título + metadatos clave --}}
<div class="shrink-0 border-b border-slate-800 bg-tasklab-bg-muted px-6 py-4 flex flex-col gap-3">
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
        :disabled="modalMode === 'view'"
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
        <template x-if="modalTask && modalTask.reporter">
          <span class="inline-flex items-center rounded-full bg-tasklab-bg px-2 py-0.5 text-[11px] border border-slate-700">
            <span class="mr-1">Requester:</span>
            <span x-text="modalTask.reporter.name"></span>
            <template x-if="modalTask.reporter.email">
              <span class="ml-1 text-tasklab-muted/80" x-text="'<' + modalTask.reporter.email + '>'"></span>
            </template>
          </span>
        </template>
      </div>
    </div>

    <!-- Action Buttons -->
     <div class="flex items-center gap-3">  
      {{-- 3-dots dropdown (solo en view/edit, no en create) --}}
      <div x-show="modalMode !== 'create'" class="relative shrink-0" x-data="{ open: false }" @click.outside="open = false">
        <button
          type="button"
          @click="open = !open"
          class="flex h-8 w-8 items-center justify-center rounded-lg text-tasklab-muted hover:text-tasklab-text hover:bg-slate-800 transition-colors"
        >
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M4 10a2 2 0 110-4 2 2 0 010 4zM10 10a2 2 0 110-4 2 2 0 010 4zM16 10a2 2 0 110-4 2 2 0 010 4z"/>
        </svg>
        </button>
        <div
          x-show="open"
          x-transition:enter="transition ease-out duration-100"
          x-transition:enter-start="opacity-0 scale-95"
          x-transition:enter-end="opacity-100 scale-100"
          class="absolute right-0 top-9 z-50 w-44 rounded-xl border border-slate-700 bg-tasklab-bg shadow-xl py-1"
          style="display:none"
        >
          <button
            type="button"
            x-show="modalMode === 'view'"
            @click="open = false; enterEditMode()"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-xs text-tasklab-text hover:bg-slate-800 transition-colors"
          >
            <svg class="h-3.5 w-3.5 text-tasklab-muted shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Editar tarea
          </button>
          <div x-show="modalMode === 'view'" class="my-1 border-t border-slate-800"></div>
          <button
            type="button"
            @click="open = false; $refs.archiveField.value = '1'; $el.closest('form').submit()"
            class="flex w-full items-center gap-2.5 px-3 py-2 text-xs text-red-400 hover:bg-red-500/10 transition-colors"
          >
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12"/>
            </svg>
            Archivar tarea
          </button>
        </div>
      </div>

      {{-- Botones de acción en el header --}}
      <div class="flex items-center gap-2 shrink-0">
        <template x-if="modalMode === 'create'">
          <div class="flex items-center gap-2">
            <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-700 bg-tasklab-bg px-4 py-1.5 text-body text-tasklab-muted hover:text-tasklab-text hover:border-tasklab-accent" @click.prevent="closeTaskModal()">Cancelar</button>
            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-tasklab-accent px-4 py-1.5 text-body font-medium text-slate-950 hover:bg-tasklab-accent-soft">Crear tarea</button>
          </div>
        </template>
        <template x-if="modalMode === 'view'">
          <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-700 bg-tasklab-bg px-4 py-1.5 text-body text-tasklab-muted hover:text-tasklab-text hover:border-tasklab-accent" @click.prevent="closeTaskModal()">Cerrar</button>
        </template>
        <template x-if="modalMode === 'edit'">
          <div class="flex items-center gap-2">
            <button type="button" class="inline-flex items-center justify-center rounded-full border border-slate-700 bg-tasklab-bg px-4 py-1.5 text-body text-tasklab-muted hover:text-tasklab-text hover:border-tasklab-accent" @click.prevent="cancelEditMode()">Cancelar</button>
            <button type="submit" class="inline-flex items-center justify-center rounded-full bg-tasklab-accent px-4 py-1.5 text-body font-medium text-slate-950 hover:bg-tasklab-accent-soft">Guardar</button>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>

{{-- Inputs ocultos de categorías y archivo --}}
<template
  x-for="id in Object.values(categorySelections).flatMap(sel => [sel.value_id, sel.child_value_id]).filter(id => id)"
  :key="id"
>
  <input type="hidden" name="category_values[]" :value="id">
</template>
<input type="hidden" name="archive" x-ref="archiveField" value="">

{{-- Cuerpo en dos columnas: descripciones + metadatos --}}
<div x-data="{ asideOpen: false }" class="px-6 md:pl-6 md:pr-0 py-4 flex gap-4 flex-1 min-h-0 overflow-hidden relative">
  {{-- Columna izquierda: descripciones (flex-col para pinear comentarios abajo) --}}
  <div class="flex flex-col flex-1 md:flex-[2] min-w-0 min-h-0">
  <div class="flex-1 overflow-y-auto pr-2 space-y-3 min-h-0">
    {{-- Descripción de IA (para tareas que no vienen del formulario web) --}}
    <section
      class="rounded-xl p-3 space-y-2"
      x-show="modalTask && modalTask.source !== 'web_form' && modalTask.description_ai"
    >
      <h3 class="text-label font-semibold text-tasklab-text">Descripción de la tarea</h3>
      <p
        class="text-body text-tasklab-muted whitespace-pre-wrap"
        x-text="modalTask.description_ai"
      ></p>
    </section>

    {{-- Criterios de aceptación: solo si hay requirements reales --}}
    <section
      class="rounded-xl p-3 space-y-2"
      x-show="modalTask && modalTask.source !== 'web_form' && modalTask.requirements && modalTask.requirements.length"
    >
      <h3 class="text-label font-semibold text-tasklab-text">Criterios de aceptación</h3>
      <ul class="space-y-2">
        <template x-for="(req, idx) in modalTask.requirements" :key="idx">
          <li class="flex items-center gap-3 px-3 py-1">
            · <p class="text-body text-tasklab-text" x-text="req"></p>
          </li>
        </template>
      </ul>
    </section>

    {{-- Descripción editable (para tareas web_form) --}}
    <section
      class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3"
      x-show="modalTask && modalTask.source === 'web_form'"
    >
      <h3 class="text-label font-semibold text-tasklab-text mb-1">Descripción</h3>
      <textarea
        name="description_raw"
        rows="6"
        class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-3 py-2 text-sm resize-y"
        x-model="modalTask.description_raw"
        :disabled="modalMode === 'view'"
      ></textarea>
    </section>

    {{-- Adjuntos externos (Discord/Teams), imágenes y URLs --}}
    <template x-if="modalTask && (modalTask.primary_url || (modalTask.additional_urls && modalTask.additional_urls.length) || (modalTask.attachments && modalTask.attachments.length) || (modalTask.task_images && modalTask.task_images.length))">
      <section class="rounded-xl p-3 space-y-3">
        <h3 class="text-label font-semibold text-tasklab-text">Adjuntos y URLs</h3>

        <template x-if="modalTask.primary_url">
          <div>
            <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">URL principal</p>
            <a
              :href="modalTask.primary_url"
              target="_blank"
              rel="noopener noreferrer"
              class="flex items-center gap-1.5 w-fit text-body text-tasklab-accent hover:underline break-all"
              x-text="modalTask.primary_url"
            ></a>
          </div>
        </template>

        <template x-if="modalTask.additional_urls && modalTask.additional_urls.length">
          <div>
            <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">URLs adicionales</p>
            <ul class="space-y-1">
              <template x-for="(url, i) in modalTask.additional_urls" :key="i">
                <li>
                  <a
                    :href="url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-1.5 w-fit text-body text-tasklab-accent hover:underline break-all"
                    x-text="url"
                  ></a>
                </li>
              </template>
            </ul>
          </div>
        </template>

        <template x-if="modalTask.attachments && modalTask.attachments.length">
          <div>
            <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-2">Archivos adjuntos</p>
            <div class="flex flex-wrap gap-2">
              <template x-for="(att, i) in modalTask.attachments" :key="i">
                <div>
                  <template x-if="att.type === 'image' || /\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(att.url || '')">
                    <a :href="att.url" target="_blank" rel="noopener noreferrer" class="block group">
                      <img
                        :src="att.url"
                        :alt="att.label || 'Adjunto'"
                        class="h-24 w-auto max-w-[180px] rounded-lg border border-slate-700 object-cover group-hover:border-tasklab-accent transition-colors"
                      />
                      <span class="mt-1 block text-meta text-tasklab-muted truncate max-w-[180px]" x-text="att.label || 'imagen'"></span>
                    </a>
                  </template>
                  <template x-if="!(att.type === 'image' || /\.(png|jpe?g|gif|webp|svg)(\?.*)?$/i.test(att.url || ''))">
                    <a
                      :href="att.url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="flex items-center gap-2 rounded-lg border border-slate-700 bg-tasklab-bg px-3 py-2 text-body text-tasklab-text hover:border-tasklab-accent transition-colors"
                    >
                      <svg class="h-4 w-4 shrink-0 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                      <span class="truncate max-w-[160px]" x-text="att.label || att.url"></span>
                    </a>
                  </template>
                </div>
              </template>
            </div>
          </div>
        </template>
      </section>
    </template>

  </div>{{-- fin zona scrollable izquierda --}}

    {{-- Mobile: botón para abrir las propiedades --}}
    <button
      type="button"
      class="md:hidden shrink-0 mt-3 w-full flex items-center gap-2 rounded-xl border border-slate-800 bg-tasklab-bg-muted px-3 py-2 text-xs text-tasklab-muted hover:border-slate-700 transition-colors"
      @click="asideOpen = true"
    >
      <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
      <span>Propiedades</span>
      <div class="flex items-center gap-1.5 ml-auto">
        <span
          x-show="modalTask?.priority"
          class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium"
          :class="{
            'bg-tasklab-danger/20 text-tasklab-danger border border-tasklab-danger/60': modalTask?.priority === 'critical',
            'bg-tasklab-accent/10 text-tasklab-accent border border-tasklab-accent/40': modalTask?.priority === 'high',
            'bg-tasklab-primary/10 text-tasklab-primary border border-tasklab-primary/40': modalTask?.priority === 'medium',
            'bg-slate-800 text-tasklab-muted border border-slate-700': modalTask?.priority === 'low' || !modalTask?.priority,
          }"
          x-text="({'critical':'Crítica','high':'Alta','medium':'Media','low':'Baja'})[modalTask?.priority] || ''"
        ></span>
        <span x-show="modalTask?.assignee?.name" class="text-tasklab-muted" x-text="modalTask?.assignee?.name"></span>
      </div>
      <svg class="h-4 w-4 shrink-0 text-tasklab-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>

    {{-- Comentarios: fijo al fondo de la columna izquierda --}}
    <section class="shrink-0 border-t border-slate-800 pt-3 mt-3 space-y-3">
      <h3 class="text-label font-semibold text-tasklab-text">Comentarios</h3>
      <div class="flex items-center gap-2">
        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-900 text-[10px] font-semibold text-tasklab-text border border-slate-700">N</span>
        <input
          type="text"
          class="flex-1 rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-3 py-1.5 text-sm"
          placeholder="Añadir un comentario..."
        />
      </div>
    </section>
  </div>{{-- fin flex-col izquierda --}}

  {{-- Columna derecha: panel de propiedades
       · Desktop: columna fija a la derecha
       · Mobile: overlay que cubre el contenido (x-show + md:!block) --}}
  <div
    x-show="asideOpen"
    class="flex flex-col space-y-3 overflow-y-auto
           absolute inset-0 z-10 bg-tasklab-bg p-4
           md:!flex md:static md:inset-auto md:z-auto md:bg-transparent md:p-0 md:w-72 md:shrink-0 md:pr-6"
    style="display:none"
  >
    {{-- Mobile: cabecera del panel --}}
    <div class="md:hidden flex items-center justify-between shrink-0 mb-1">
      <h3 class="text-sm font-semibold text-tasklab-text">Propiedades</h3>
      <button type="button" @click="asideOpen = false" class="p-1 rounded-lg text-tasklab-muted hover:text-tasklab-text hover:bg-slate-800">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

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

    {{-- Categorías + estado/tipo/prioridad --}}
    <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3 text-label text-tasklab-muted">
      <template x-for="type in categoryTypes" :key="type.id">
        <div class="space-y-1.5">
          <p class="text-meta uppercase tracking-wide text-tasklab-muted/80" x-text="type.name"></p>
          {{-- View: texto --}}
          <template x-if="modalMode === 'view'">
            <p class="text-body text-tasklab-text"
               x-text="(type.values.find(v => String(v.id) === String(categorySelections[type.slug]?.value_id))?.name) || '—'">
            </p>
          </template>
          {{-- Edit/Create: select --}}
          <template x-if="modalMode !== 'view'">
            <div class="space-y-1.5">
              <select
                class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                x-model="categorySelections[type.slug].value_id"
                @change="onCategoryRootChange(type.slug)"
              >
                <option value="">Sin asignar</option>
                <template x-for="value in type.values.filter(v => !v.parent_id)" :key="value.id">
                  <option :value="value.id" x-text="value.name"></option>
                </template>
              </select>
              <template x-if="categorySelections[type.slug] && categorySelections[type.slug].children && categorySelections[type.slug].children.length">
                <select
                  class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                  x-model="categorySelections[type.slug].child_value_id"
                >
                  <option value="">Sin subcategoría</option>
                  <template x-for="child in categorySelections[type.slug].children" :key="child.id">
                    <option :value="child.id" x-text="child.name"></option>
                  </template>
                </select>
              </template>
            </div>
          </template>
          {{-- Subcategoría en view mode --}}
          <template x-if="modalMode === 'view' && categorySelections[type.slug]?.child_value_id">
            <p class="text-[11px] text-tasklab-muted"
               x-text="type.values.find(v => String(v.id) === String(categorySelections[type.slug]?.child_value_id))?.name || ''">
            </p>
          </template>
        </div>
      </template>

      <div class="border-t border-slate-800 pt-3 space-y-3">
        {{-- Estado --}}
        <div>
          <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Estado</p>
          <template x-if="modalMode === 'view'">
            <p class="text-body text-tasklab-text"
               x-text="({'new':'Backlog','ready_for_dev':'Pendiente','in_progress':'En progreso','blocked':'En revisión','done':'Completada','archived':'Archivada'})[modalTask?.status] || '—'">
            </p>
          </template>
          <template x-if="modalMode !== 'view'">
            <select name="status" class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm" x-model="modalTask.status">
              <option value="new">Backlog</option>
              <option value="ready_for_dev">Pendiente</option>
              <option value="in_progress">En progreso</option>
              <option value="blocked">En revisión</option>
              <option value="done">Completada</option>
              <option value="archived">Archivada</option>
            </select>
          </template>
        </div>
        {{-- Tipo --}}
        <div>
          <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Tipo</p>
          <template x-if="modalMode === 'view'">
            <p class="text-body text-tasklab-text"
               x-text="({'bug':'Bug','feature':'Evolutiva','improvement':'Mejora','question':'Consulta'})[modalTask?.type] || '—'">
            </p>
          </template>
          <template x-if="modalMode !== 'view'">
            <select name="type" class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm" x-model="modalTask.type">
              <option value="bug">Bug</option>
              <option value="feature">Evolutiva</option>
              <option value="improvement">Mejora</option>
              <option value="question">Consulta</option>
            </select>
          </template>
        </div>
        {{-- Prioridad --}}
        <div>
          <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Prioridad</p>
          <template x-if="modalMode === 'view'">
            <p class="text-body text-tasklab-text"
               x-text="({'critical':'Crítica','high':'Alta','medium':'Media','low':'Baja'})[modalTask?.priority] || '—'">
            </p>
          </template>
          <template x-if="modalMode !== 'view'">
            <select name="priority" class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm" x-model="modalTask.priority">
              <option value="critical">Crítica</option>
              <option value="high">Alta</option>
              <option value="medium">Media</option>
              <option value="low">Baja</option>
            </select>
          </template>
        </div>
      </div>
    </section>

    {{-- Requester / Asignado --}}
    <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3 text-label text-tasklab-muted">
      <div>
        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Requester</p>
        <template x-if="modalMode === 'view'">
          <p class="text-body text-tasklab-text" x-text="modalTask?.reporter?.name || '—'"></p>
        </template>
        <template x-if="modalMode !== 'view'">
          <select name="reporter_id" class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm">
            <option value="">— Sin asignar —</option>
            @foreach($users as $userOption)
              <option value="{{ $userOption->id }}" x-bind:selected="modalTask && modalTask.reporter && modalTask.reporter.id === {{ $userOption->id }}">{{ $userOption->name }}</option>
            @endforeach
          </select>
        </template>
      </div>
      <div>
        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Asignado a</p>
        <template x-if="modalMode === 'view'">
          <p class="text-body text-tasklab-text" x-text="modalTask?.assignee?.name || '—'"></p>
        </template>
        <template x-if="modalMode !== 'view'">
          <select name="assignee_id" class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm">
            <option value="">— Sin asignar —</option>
            @foreach($users as $userOption)
              <option value="{{ $userOption->id }}" x-bind:selected="modalTask && modalTask.assignee && modalTask.assignee.id === {{ $userOption->id }}">{{ $userOption->name }}</option>
            @endforeach
          </select>
        </template>
      </div>
    </section>

    {{-- Estimación / Fechas --}}
    <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3 text-label text-tasklab-muted">
      <div>
        <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Estimación (horas)</p>
        <template x-if="modalMode === 'view'">
          <p class="text-body text-tasklab-text" x-text="modalTask?.points ? modalTask.points + ' h' : '—'"></p>
        </template>
        <template x-if="modalMode !== 'view'">
          <select name="points" class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm">
            <option value="">Sin estimación</option>
            <template x-for="value in [0.5,1,2,4,6,8,10,12,16]" :key="value">
              <option :value="value" x-text="value + ' h'" :selected="modalTask && Number(modalTask.points) === value"></option>
            </template>
          </select>
        </template>
      </div>
      <div class="space-y-2">
        <div>
          <p class="text-meta uppercase tracking-wide text-tasklab-muted/80">Fecha de creación</p>
          <p class="mt-0.5 text-body text-tasklab-text" x-text="modalTask && modalTask.created_at ? new Date(modalTask.created_at).toLocaleDateString('es-ES') : '—'"></p>
        </div>
        <div>
          <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Fecha límite</p>
          <template x-if="modalMode === 'view'">
            <p class="text-body text-tasklab-text" x-text="modalTask?.due_date || '—'"></p>
          </template>
          <template x-if="modalMode !== 'view'">
            <input type="date" name="due_date" class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm" />
          </template>
        </div>
      </div>
    </section>
  </div>
</div>

</form>
