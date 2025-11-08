<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  teamId: {
    type: [Number, String],
    required: true
  }
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow p-4 mb-6 flex items-center justify-between">
    <div>
      <h2 class="text-lg font-semibold">Team Chat</h2>
      <p class="text-sm text-gray-500">Takım içi mesajlaşma alanına geç.</p>
    </div>
    <Link
      :href="route('teams.chat', { team: teamId })"
      class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700"
    >
      Aç
    </Link>
  </div>
</template>
