{{-- Global task modal: mounted in the layout, available in every view --}}
<div
  x-data="taskModal(@js($categoryTypes->map(fn($t) => [
      'id'     => $t->id,
      'name'   => $t->name,
      'slug'   => $t->slug,
      'values' => $t->values->map(fn($v) => [
          'id'               => $v->id,
          'name'             => $v->name,
          'parent_id'        => $v->parent_id,
          'category_type_id' => $v->category_type_id,
      ]),
  ])))"
>
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
        <x-task-modal.form :users="$users" />
      </div>
    </template>
  </div>
</div>
