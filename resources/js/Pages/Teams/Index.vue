<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  teams: Array,
  ownedTeams: Array,
})

const form = useForm({
  name: '',
})

const submitting = ref(false)

function createTeam() {
  submitting.value = true
  form.post(route('teams.store'), {
    onFinish: () => { submitting.value = false; form.reset('name') },
  })
}
</script>

<template>
  <div class="max-w-4xl mx-auto p-6 space-y-8">
    <h1 class="text-2xl font-bold">Teams</h1>

    <div class="p-4 border rounded-lg">
      <h2 class="font-semibold mb-2">Create Team</h2>
      <form @submit.prevent="createTeam" class="flex gap-2">
        <input v-model="form.name" class="border px-3 py-2 rounded w-full" placeholder="Team name">
        <button :disabled="submitting || !form.name" class="bg-indigo-600 text-white px-4 py-2 rounded disabled:opacity-50">
          Create
        </button>
      </form>
      <div v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
      <div>
        <h3 class="font-semibold mb-2">Owned Teams</h3>
        <ul class="space-y-2">
          <li v-for="t in ownedTeams" :key="t.id" class="border rounded p-3">
            <div class="font-medium">{{ t.name }}</div>
            <div class="text-sm text-gray-500">Owner: You</div>
          </li>
        </ul>
      </div>

      <div>
        <h3 class="font-semibold mb-2">Member Of</h3>
        <ul class="space-y-2">
          <li v-for="t in teams" :key="t.id" class="border rounded p-3">
            <div class="font-medium">{{ t.name }}</div>
            <div class="text-sm text-gray-500">Owner ID: {{ t.owner_id }}</div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
