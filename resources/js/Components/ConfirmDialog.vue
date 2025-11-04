<script setup>
import { onMounted, watch, ref } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: '' },
  confirmText: { type: String, default: 'Confirm' },
  cancelText: { type: String, default: 'Cancel' },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])
const okBtn = ref(null)

watch(() => props.modelValue, (open) => {
  if (open) setTimeout(() => okBtn.value?.focus(), 50)
})

function close() { emit('update:modelValue', false) }
function onConfirm() { emit('confirm'); close() }
function onCancel() { emit('cancel'); close() }

function onBackdrop(e) {
  if (e.target === e.currentTarget) onCancel()
}
</script>

<template>
  <teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center" @click="onBackdrop">
      <!-- backdrop -->
      <div class="absolute inset-0 bg-black/40"></div>

      <!-- dialog -->
      <div class="relative z-10 w-[92%] max-w-md rounded-xl border border-gray-200 bg-white p-5 shadow-2xl">
        <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
        <p v-if="message" class="mt-2 text-sm text-gray-600">{{ message }}</p>

        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
            @click="onCancel"
          >
            {{ cancelText }}
          </button>
          <button
            ref="okBtn"
            type="button"
            class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700"
            @click="onConfirm"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </teleport>
</template>
