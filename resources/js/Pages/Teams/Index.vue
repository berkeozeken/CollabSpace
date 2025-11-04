<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
  teams: { type: Array, default: () => [] },
  ownedTeams: { type: Array, default: () => [] },
})

const form = useForm({ name: '' })
const submitting = ref(false)

function createTeam() {
  submitting.value = true
  form.post(route('teams.store'), {
    onFinish: () => { submitting.value = false; form.reset('name') },
  })
}

const hasOwned = computed(() => props.ownedTeams.length > 0)
const hasTeams = computed(() => props.teams.length > 0)

function normalizeRole(role) {
  return (role ?? '').toString().toLowerCase()
}
function roleBadge(role) {
  const r = normalizeRole(role)
  if (r === 'owner') return 'bg-emerald-100 text-emerald-800'
  if (r === 'manager') return 'bg-indigo-100 text-indigo-800'
  if (r === 'member') return 'bg-gray-100 text-gray-800'
  return 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <div class="mx-auto max-w-5xl p-6 space-y-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Teams</h1>
    </div>

    <!-- Create -->
    <div class="rounded-lg border bg-white p-4">
      <h2 class="mb-2 font-semibold">Create Team</h2>
      <form @submit.prevent="createTeam" class="flex gap-2">
        <input v-model="form.name" class="w-full rounded border px-3 py-2" placeholder="Team name">
        <button :disabled="submitting || !form.name" class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">
          Create
        </button>
      </form>
      <div v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
      <!-- Owned Teams -->
      <div class="space-y-3">
        <h3 class="font-semibold">Owned Teams</h3>
        <div v-if="!hasOwned" class="rounded border border-dashed p-4 text-sm text-gray-600 bg-white">
          You don't own any team yet.
        </div>
        <ul v-else class="space-y-2">
          <li v-for="t in ownedTeams" :key="t.id" class="rounded border bg-white p-3">
            <div class="flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900">{{ t.name }}</div>
                <div class="text-xs text-gray-500">Members: {{ t.members_count }}</div>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-800">
                  OWNER
                </span>
                <Link :href="route('teams.show', t.id)" class="text-xs text-indigo-600 hover:underline">View</Link>
              </div>
            </div>
          </li>
        </ul>
      </div>

      <!-- Member Of -->
      <div class="space-y-3">
        <h3 class="font-semibold">Member Of</h3>
        <div v-if="!hasTeams" class="rounded border border-dashed p-4 text-sm text-gray-600 bg-white">
          You are not a member of any team yet.
        </div>
        <ul v-else class="space-y-2">
          <li v-for="t in teams" :key="t.id" class="rounded border bg-white p-3">
            <div class="flex items-center justify-between">
              <div>
                <div class="font-medium text-gray-900">{{ t.name }}</div>
                <div class="text-xs text-gray-500">
                  Owner: <span class="font-medium">{{ t.owner?.name ?? '-' }}</span>
                  <span class="mx-2">•</span>
                  Members: {{ t.members_count }}
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="roleBadge(t.your_role)">
                  {{ normalizeRole(t.your_role).toUpperCase() || '-' }}
                </span>
                <Link :href="route('teams.show', t.id)" class="text-xs text-indigo-600 hover:underline">View</Link>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>

  </div>
</template>
