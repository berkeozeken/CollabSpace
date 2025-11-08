<script setup>
const props = defineProps({
  count: { type: Number, default: 0 },
  users: { type: Array, default: () => [] }, // [{id,name}]
})
</script>

<template>
  <div v-if="count > 0" class="text-[11px] text-gray-500">
    <span>Seen by {{ count }}</span>
    <span v-if="users.length"> ({{ users.slice(0,3).map(u => u.name).join(', ') }}<span v-if="count > 3"> +{{ count - 3 }}</span>)</span>
  </div>
</template>
