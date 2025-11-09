<!-- resources/js/Components/TaskColumn.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: String,
  items: { type: Array, default: () => [] },
  members: { type: Array, default: () => [] },
})

const emit = defineEmits(['update','remove','upload','delete-att'])

const memberOptions = computed(() =>
  props.members.map(m => ({ value: m.id, label: `${m.name} (${m.email})` }))
)

function onStatusChange (id, e) { emit('update', id, { status: e.target.value }) }
function onAssigneeChange (id, e) {
  const v = e.target.value
  emit('update', id, { assignee_id: v || null })
}
function onDueChange (id, e) {
  const v = e.target.value
  const iso = v ? new Date(v).toISOString() : null
  emit('update', id, { due_at: iso })
}
function onRemove (id) { emit('remove', id) }
function onFile (id, e) { const f = e.target.files?.[0]; if (f) emit('upload', id, f); e.target.value = '' }
function delAtt (attId, taskId) { emit('delete-att', attId, taskId) }
function fmtDate (iso) {
  if (!iso) return '—'
  try { const d = new Date(iso); return d.toLocaleString() } catch { return iso }
}
</script>

<template>
  <div class="rounded-lg border bg-white">
    <div class="border-b px-4 py-2 font-semibold">{{ title }}</div>
    <div class="divide-y">
      <div v-if="!items || items.length===0" class="px-4 py-6 text-sm text-gray-400">No tasks</div>
      <div v-for="t in items" :key="t.id" class="px-4 py-3">
        <div class="flex items-start justify-between">
          <div class="min-w-0">
            <div class="font-medium text-gray-900 truncate">{{ t.title }}</div>
            <div v-if="t.description" class="text-sm text-gray-600 mt-0.5 break-words">{{ t.description }}</div>
            <div class="mt-2 text-xs text-gray-500 flex flex-wrap items-center gap-3">
              <div><span class="text-gray-400">Created by:</span> <span class="font-medium">{{ t.creator ? t.creator.name : '—' }}</span></div>
              <div v-if="t.edited_at" class="text-amber-600">(edited)</div>
              <div><span class="text-gray-400">Due:</span> {{ fmtDate(t.due_at) }}</div>
            </div>
          </div>
          <button class="text-red-600 text-xs hover:underline shrink-0" @click="onRemove(t.id)">Delete</button>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Assignee</label>
            <select :value="t.assignee?.id || ''" @change="onAssigneeChange(t.id, $event)" class="w-full rounded border px-2 py-1 text-sm">
              <option value="">Unassigned</option>
              <option v-for="opt in memberOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select :value="t.status" @change="onStatusChange(t.id, $event)" class="w-full rounded border px-2 py-1 text-sm">
              <option value="todo">To-Do</option>
              <option value="in_progress">In Progress</option>
              <option value="done">Done</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Deadline</label>
            <input :value="t.due_at ? new Date(t.due_at).toISOString().slice(0,16) : ''"
                   type="datetime-local"
                   @change="onDueChange(t.id, $event)"
                   class="w-full rounded border px-2 py-1 text-sm" />
          </div>
        </div>

        <div class="mt-3">
          <label class="block text-xs text-gray-500 mb-1">Attachments</label>
          <div class="flex items-center gap-3 mb-2">
            <input type="file" @change="onFile(t.id, $event)" class="text-sm" />
          </div>
          <ul class="space-y-1">
            <li v-for="a in (t.attachments || [])" :key="a.id" class="text-sm flex items-center justify-between">
              <div class="truncate">
                <a :href="a.url" class="text-indigo-600 hover:underline" :download="a.original_name">{{ a.original_name }}</a>
                <span class="text-gray-400 text-xs"> • {{ (a.size/1024).toFixed(1) }} KB</span>
              </div>
              <div class="flex items-center gap-2">
                <a :href="route('attachments.download', { attachment: a.id })" class="text-xs text-gray-700 hover:underline">Download</a>
                <button class="text-xs text-red-600 hover:underline" @click="delAtt(a.id, t.id)">Delete</button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
