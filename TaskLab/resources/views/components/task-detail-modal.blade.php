@props(['categoryTypes' => collect(), 'users' => collect()])

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
        :action="modalMode === 'create'
          ? '{{ route('tasks.store') }}'
          : '{{ route('tasks.update', ['task' => 'TASK_ID_PLACEHOLDER']) }}'.replace('TASK_ID_PLACEHOLDER', modalTask?.id ?? '')"
      >
      @csrf
      <template x-if="modalMode !== 'create'">
        @method('PATCH')
      </template>

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
              <template x-if="modalTask && !modalTask.reporter">
                <span class="inline-flex items-center rounded-full bg-tasklab-bg px-2 py-0.5 text-[11px] border border-slate-700">
                  <span class="mr-1">Requester:</span>
                  <span>TaskLab</span>
                </span>
              </template>
            </div>
          </div>
        </div>
      </div>

      {{-- Cuerpo en dos columnas: descripciones + metadatos --}}
      <div class="px-6 py-4 grid grid-cols-1 lg:grid-cols-3 gap-4 flex-1 h-[70vh] overflow-hidden items-stretch">
        {{-- Columna izquierda: descripciones --}}
        <div class="lg:col-span-2 space-y-3 overflow-y-auto pr-2 min-h-0">
          {{-- Descripción principal de la tarea (IA)
               Solo se muestra por defecto para tareas que NO vienen del formulario web (por ejemplo, Discord/Teams).
          --}}
          <section
            class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-2"
            x-show="modalTask && modalTask.source !== 'web_form'"
          >
            <h3 class="text-label font-semibold text-tasklab-text">Descripción de la tarea</h3>
            <p
              class="text-body text-tasklab-muted whitespace-pre-wrap"
              x-text="modalTask && modalTask.description_ai
                ? modalTask.description_ai
                : 'Refinamiento pendiente o no disponible. Revisa la descripción original y completa los detalles necesarios.'"
            ></p>
          </section>

          {{-- Criterios de aceptación (sólo tiene sentido cuando usamos la descripción de IA) --}}
          <section
            class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-2"
            x-show="modalTask && modalTask.source !== 'web_form'"
          >
            <h3 class="text-label font-semibold text-tasklab-text">Criterios de aceptación</h3>
            <template x-if="modalTask && modalTask.requirements && modalTask.requirements.length">
              <ul class="space-y-2">
                <template x-for="(req, idx) in modalTask.requirements" :key="idx">
                  <li class="rounded-lg border border-slate-800 bg-tasklab-bg px-3 py-2">
                    <p class="text-body text-tasklab-text" x-text="req"></p>
                  </li>
                </template>
              </ul>
            </template>
            <template x-if="!modalTask || !modalTask.requirements || !modalTask.requirements.length">
              <p class="text-body text-tasklab-muted">
                La IA todavía no ha definido criterios de aceptación. Asegúrate de que la tarea tenga un resultado claro y comprobable
                antes de marcarla como lista.
              </p>
            </template>
          </section>

          {{-- Descripción original (editable)
               Para tareas creadas desde TaskLab (source = web_form) queremos trabajar con la descripción manual,
               sin mostrar el bloque de IA.
          --}}
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
          <template x-if="modalTask && (modalTask.primary_url || (modalTask.additional_urls && modalTask.additional_urls.length) || (modalTask.attachments && modalTask.attachments.length))">
            <section class="rounded-xl border border-slate-800 bg-tasklab-bg-muted p-3 space-y-3">
              <h3 class="text-label font-semibold text-tasklab-text">Adjuntos y URLs</h3>

              {{-- URL principal --}}
              <template x-if="modalTask.primary_url">
                <div>
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">URL principal</p>
                  <a
                    :href="modalTask.primary_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center gap-1.5 text-body text-tasklab-accent hover:underline break-all"
                    x-text="modalTask.primary_url"
                  ></a>
                </div>
              </template>

              {{-- URLs adicionales --}}
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
                          class="flex items-center gap-1.5 text-body text-tasklab-accent hover:underline break-all"
                          x-text="url"
                        ></a>
                      </li>
                    </template>
                  </ul>
                </div>
              </template>

              {{-- Adjuntos / imágenes --}}
              <template x-if="modalTask.attachments && modalTask.attachments.length">
                <div>
                  <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-2">Archivos adjuntos</p>
                  <div class="flex flex-wrap gap-2">
                    <template x-for="(att, i) in modalTask.attachments" :key="i">
                      <div>
                        {{-- Imagen: mostrar miniatura --}}
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
                        {{-- Otros archivos: enlace con icono --}}
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
            <div class="mt-2">
              <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">URL principal</p>
              <input
                type="url"
                name="primary_url"
                placeholder="https://..."
                class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-[11px] text-tasklab-text px-2 py-1 focus:border-tasklab-accent focus:outline-none"
                x-model="modalTask.primary_url"
                :disabled="modalMode === 'view'"
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
                  :disabled="modalMode === 'view'"
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
                    :disabled="modalMode === 'view'"
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
                  :disabled="modalMode === 'view'"
                >
                  <option value="new">Backlog</option>
                  <option value="ready_for_dev">Pendiente</option>
                  <option value="in_progress">En progreso</option>
                  <option value="blocked">En revisión</option>
                  <option value="done">Completada</option>
                  <option value="archived">Archivada</option>
                </select>
              </div>
              <div>
                <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Tipo</p>
                <select
                  name="type"
                  class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                  x-model="modalTask.type"
                  :disabled="modalMode === 'view'"
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
                  :disabled="modalMode === 'view'"
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
                :disabled="modalMode === 'view'"
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
                :disabled="modalMode === 'view'"
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
              <p class="text-meta uppercase tracking-wide text-tasklab-muted/80 mb-1">Estimación (horas)</p>
              <select
                name="points"
                class="w-full rounded-lg border border-slate-700 bg-tasklab-bg text-body text-tasklab-text px-2 py-1.5 text-sm"
                :disabled="modalMode === 'view'"
              >
                <option value="">Sin estimación</option>
                <template x-for="value in [0.5,1,2,4,6,8,10,12,16]" :key="value">
                  <option :value="value" x-text="value + ' h'" :selected="modalTask && Number(modalTask.points) === value"></option>
                </template>
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

      <div class="px-6 py-3 flex justify-between gap-2 border-t border-slate-800 bg-slate-900/80">
        {{-- IDs de categorías seleccionadas (raíz + subcategoría) --}}
        <template
          x-for="id in Object.values(categorySelections)
            .flatMap(sel => [sel.value_id, sel.child_value_id])
            .filter(id => id)"
          :key="id"
        >
          <input type="hidden" name="category_values[]" :value="id">
        </template>

        <div class="flex items-center gap-2" x-show="modalMode !== 'create'">
          <input type="hidden" name="archive" x-ref="archiveField" value="">
          <button
            type="button"
            class="inline-flex items-center justify-center rounded-full border border-red-800 bg-transparent px-4 py-1.5 text-body text-red-400 hover:bg-red-900/40 hover:border-red-500"
            @click.prevent="$refs.archiveField.value = '1'; $el.closest('form').submit()"
          >
            Archivar tarea
          </button>
        </div>

        <div class="flex items-center gap-2">
          {{-- Modo crear --}}
          <template x-if="modalMode === 'create'">
            <div class="flex items-center gap-2">
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
                Crear tarea
              </button>
            </div>
          </template>

          {{-- Modo ver --}}
          <template x-if="modalMode === 'view'">
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-full border border-slate-700 bg-tasklab-bg px-4 py-1.5 text-body text-tasklab-muted hover:text-tasklab-text hover:border-tasklab-accent"
                @click.prevent="closeTaskModal()"
              >
                Cerrar
              </button>
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-full bg-tasklab-accent px-4 py-1.5 text-body font-medium text-slate-950 hover:bg-tasklab-accent-soft"
                @click.prevent="enterEditMode()"
              >
                Editar
              </button>
            </div>
          </template>

          {{-- Modo editar --}}
          <template x-if="modalMode === 'edit'">
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-full border border-slate-700 bg-tasklab-bg px-4 py-1.5 text-body text-tasklab-muted hover:text-tasklab-text hover:border-tasklab-accent"
                @click.prevent="cancelEditMode()"
              >
                Cancelar edición
              </button>
              <button
                type="submit"
                class="inline-flex items-center justify-center rounded-full bg-tasklab-accent px-4 py-1.5 text-body font-medium text-slate-950 hover:bg-tasklab-accent-soft"
              >
                Guardar cambios
              </button>
            </div>
          </template>
        </div>
      </div>
    </form>
  </div>
</template>
</div>
