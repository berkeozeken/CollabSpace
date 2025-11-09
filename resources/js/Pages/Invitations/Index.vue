<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  invitations: Array
})

async function respond(id, action) {
  try {
    await window.axios.post(route(`invitations.${action}`, { invitation: id }))
    window.location.reload()
  } catch (e) {
    console.error(e?.response?.data || e)
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Your Team Invitations</h1>
      <Link :href="route('dashboard')" class="text-sm text-indigo-600 hover:underline">← Back</Link>
    </div>

    <div v-if="!invitations || invitations.length === 0" class="text-gray-500 text-center py-10">
      You don’t have any pending invitations.
    </div>

    <div v-for="inv in invitations" :key="inv.id"
         class="rounded-lg border bg-white p-4 shadow-sm flex justify-between items-center">
      <div>
        <h2 class="font-semibold text-gray-900">{{ inv.team?.name || 'Team' }}</h2>
        <p class="text-sm text-gray-500">
          Invited by: <span class="font-medium">{{ inv.inviter?.name || '—' }}</span>
        </p>
        <p class="text-xs text-gray-400">
          {{ new Date(inv.created_at).toLocaleString() }}
        </p>
      </div>

      <div class="flex gap-2">
        <button
          @click="respond(inv.id, 'accept')"
          class="rounded bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-700">
          Accept
        </button>
        <button
          @click="respond(inv.id, 'decline')"
          class="rounded bg-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-400">
          Decline
        </button>
      </div>
    </div>
  </div>
</template>
