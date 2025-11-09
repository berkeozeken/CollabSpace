<!-- resources/js/Pages/Teams/Tasks.vue -->
<script setup>
import { reactive, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import TaskColumn from '@/Components/TaskColumn.vue'

const props = defineProps({
  team: { type: Object, required: true },        // { id, name }
  members: { type: Array, default: () => [] },   // [{id,name,email}]
})

const state = reactive({
  loading: true,
  creating: false,
  items: [],
})

const form = reactive({
  title: '',
  description: '',
  assignee_id: '',
  status: 'todo',
  due_at: '', // datetime-local
})

const byStatus = computed(() => ({
  todo:        state.items.filter(t => t.status === 'todo'),
  in_progress: state.items.filter(t => t.status === 'in_progress'),
  done:        state.items.filter(t => t.status === 'done'),
}))

async function fetchTasks () {
  state.loading = true
  try {
    const { data } = await window.axios.get(route('teams.tasks.index', { team: props.team.id }))
    state.items = Array.isArray(data?.data) ? data.data : []
  } finally {
    state.loading = false
  }
}

function normalizeDue(dueAt) {
  return dueAt ? new Date(dueAt).toISOString() : null
}

async function createTask () {
  if (!form.title.trim() || state.creating) return
  state.creating = true
  try {
    const payload = {
      title: form.title.trim(),
      description: form.description?.trim() || null,
      status: form.status,
      assignee_id: form.assignee_id || null,
      due_at: normalizeDue(form.due_at),
    }
    const { data } = await window.axios.post(route('teams.tasks.store', { team: props.team.id }), payload)
    const task = data?.task
    if (task) state.items.unshift({ ...task, attachments: [] })
    form.title = ''
    form.description = ''
    form.assignee_id = ''
    form.status = 'todo'
    form.due_at = ''
  } finally {
    state.creating = false
  }
}

async function updateTask (taskId, patch) {
  const idx = state.items.findIndex(t => t.id === taskId)
  if (idx === -1) return
  const backup = { ...state.items[idx] }
  state.items[idx] = { ...state.items[idx], ...patch, edited_at: new Date().toISOString() }
  try {
    await window.axios.patch(route('teams.tasks.update', { team: props.team.id, task: taskId }), patch)
  } catch (e) {
    state.items[idx] = backup
  }
}

async function removeTask (taskId) {
  const idx = state.items.findIndex(t => t.id === taskId)
  if (idx === -1) return
  const backup = state.items[idx]
  state.items.splice(idx, 1)
  try {
    await window.axios.delete(route('teams.tasks.destroy', { team: props.team.id, task: taskId }))
  } catch (e) {
    state.items.splice(idx, 0, backup)
  }
}

async function uploadAttachment(taskId, file) {
  if (!file) return
  const fd = new FormData()
  fd.append('file', file)
  const { data } = await window.axios.post(
    route('teams.tasks.attachments.store', { team: props.team.id, task: taskId }),
    fd,
    { headers: { 'Content-Type': 'multipart/form-data' } }
  )
  const att = data?.attachment
  if (!att) return
  const idx = state.items.findIndex(t => t.id === taskId)
  if (idx !== -1) {
    const list = Array.isArray(state.items[idx].attachments) ? state.items[idx].attachments : []
    state.items[idx].attachments = [att, ...list]
  }
}

async function deleteAttachment(attId, taskId) {
  await window.axios.delete(route('attachments.destroy', { attachment: attId }))
  const idx = state.items.findIndex(t => t.id === taskId)
  if (idx !== -1) {
    const list = Array.isArray(state.items[idx].attachments) ? state.items[idx].attachments : []
    state.items[idx].attachments = list.filter(a => a.id !== attId)
  }
}

onMounted(fetchTasks)
</script>

<template>
  <div class="mx-auto max-w-6xl p-6">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">Tasks — {{ team.name }}</h1>
        <p class="text-sm text-gray-500">Organize your work with a simple board. Upload proof files as attachments.</p>
      </div>
      <Link :href="route('teams.show', team.id)" class="text-sm text-indigo-600 hover:underline">← Back to Team</Link>
    </div>

    <!-- Create Task -->
    <div class="rounded-lg border bg-white p-4 mb-6">
      <h2 class="mb-3 font-semibold">Create Task</h2>
      <form class="grid grid-cols-1 gap-3 sm:grid-cols-12" @submit.prevent="createTask">
        <input v-model="form.title" class="sm:col-span-3 rounded border px-3 py-2" placeholder="Title *" maxlength="200" />
        <input v-model="form.description" class="sm:col-span-3 rounded border px-3 py-2" placeholder="Description (optional)" />
        <select v-model="form.assignee_id" class="sm:col-span-2 rounded border px-3 py-2">
          <option value="">Unassigned</option>
          <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }} ({{ m.email }})</option>
        </select>
        <select v-model="form.status" class="sm:col-span-2 rounded border px-3 py-2">
          <option value="todo">To-Do</option>
          <option value="in_progress">In Progress</option>
          <option value="done">Done</option>
        </select>
        <input v-model="form.due_at" type="datetime-local" class="sm:col-span-2 rounded border px-3 py-2" />
        <button :disabled="!form.title || state.creating" class="sm:col-span-12 md:col-span-1 rounded bg-indigo-600 px-3 py-2 text-white disabled:opacity-50">
          {{ state.creating ? 'Adding…' : 'Add' }}
        </button>
      </form>
    </div>

    <!-- Board -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <TaskColumn
        title="To-Do"
        :items="byStatus.todo"
        :members="members"
        @update="updateTask"
        @remove="removeTask"
        @upload="uploadAttachment"
        @delete-att="deleteAttachment"
      />
      <TaskColumn
        title="In Progress"
        :items="byStatus.in_progress"
        :members="members"
        @update="updateTask"
        @remove="removeTask"
        @upload="uploadAttachment"
        @delete-att="deleteAttachment"
      />
      <TaskColumn
        title="Done"
        :items="byStatus.done"
        :members="members"
        @update="updateTask"
        @remove="removeTask"
        @upload="uploadAttachment"
        @delete-att="deleteAttachment"
      />
    </div>
  </div>
</template>
