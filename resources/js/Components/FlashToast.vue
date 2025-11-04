<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  success: { type: [String, null], default: null },
  error:   { type: [String, null], default: null },
  timeout: { type: Number, default: 3000 },
})

const open = ref(false)
const msg = ref('')
const kind = ref('success')
let timer = null

function show(text, type = 'success') {
  msg.value = text
  kind.value = type
  open.value = true
  clearTimeout(timer)
  timer = setTimeout(() => (open.value = false), props.timeout)
}

watch(() => props.success, v => v && show(v, 'success'))
watch(() => props.error,   v => v && show(v, 'error'))

onMounted(() => {
  if (props.success) show(props.success, 'success')
  else if (props.error) show(props.error, 'error')
})
</script>

<template>
  <transition name="fade">
    <div
      v-if="open"
      class="fixed right-4 top-4 z-50 flex min-w-[260px] max-w-sm items-start gap-2 rounded-lg border bg-white p-3 shadow-lg"
      :class="kind === 'success' ? 'border-emerald-200' : 'border-red-200'"
    >
      <div
        class="mt-1 h-2.5 w-2.5 rounded-full"
        :class="kind === 'success' ? 'bg-emerald-500' : 'bg-red-500'"
      ></div>
      <div class="text-sm text-gray-800">{{ msg }}</div>
      <button class="ml-auto text-gray-400 hover:text-gray-600" @click="open=false">✕</button>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>
