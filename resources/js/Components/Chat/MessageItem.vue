<script setup>
import { ref, computed } from 'vue'
import EmojiQuickBar from '@/Components/Chat/EmojiQuickBar.vue'

const props = defineProps({
  meId: { type: [Number, String], required: true },
  message: { type: Object, required: true }, // reactions: [{emoji,count,me,users?:string[]}]
  formatTime: { type: Function, required: true }
})
const emit = defineEmits(['edit', 'delete', 'react'])

const isMine = computed(() => String(props.message.user?.id) === String(props.meId))
const editing = ref(false)
const editBody = ref(props.message.body || '')

function startEdit(){ if(!isMine.value||props.message.deleted) return; editing.value=true; editBody.value=props.message.body||'' }
function cancelEdit(){ editing.value=false }
function saveEdit(){ if(!editing.value) return; const body=(editBody.value||'').trim(); if(!body||body===props.message.body){ editing.value=false; return } emit('edit',{id:props.message.id,body}); editing.value=false }
function doDelete(){ if(!isMine.value||props.message.deleted) return; emit('delete',{id:props.message.id}) }
function react(emoji){ if(props.message.deleted) return; emit('react',{id:props.message.id,emoji}) }

// Tooltip metni: varsa users listesiyle göster
function reactionTitle(r){
  const users = Array.isArray(r.users) ? r.users.slice() : []
  // uzun listeyi kısalt
  const MAX = 6
  let label = ''
  if(users.length){
    const shown = users.slice(0, MAX).join(', ')
    label = users.length > MAX ? `${shown}, +${users.length - MAX} more` : shown
  } else {
    // fallback
    if(r.me && (r.count||0)>1) return `You + ${(r.count||0)-1}`
    if(r.me && (r.count||0)===1) return 'You'
    return (r.count||0)===1 ? '1 person' : `${r.count||0} people`
  }
  return label
}
</script>

<template>
  <div class="group relative">
    <div class="flex items-start gap-3">
      <div class="h-8 w-8 flex items-center justify-center rounded-full"
           :class="isMine ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-white'">
        {{ (message.user?.name || '?').slice(0,1).toUpperCase() }}
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium text-gray-900 truncate">{{ message.user?.name || 'User' }}</span>
          <span class="text-xs text-gray-400 shrink-0">{{ formatTime(message.created_at) }}</span>

          <div class="ml-auto flex items-center gap-1 z-[5]">
            <EmojiQuickBar @pick="react" />
            <button v-if="isMine && !message.deleted" class="text-xs px-2 py-1 rounded border bg-white hover:bg-gray-50" @click="startEdit">Edit</button>
            <button v-if="isMine && !message.deleted" class="text-xs px-2 py-1 rounded border bg-white hover:bg-red-50 text-red-600" @click="doDelete">Delete</button>
          </div>
        </div>

        <div v-if="!editing" class="mt-0.5 whitespace-pre-wrap break-words"
             :class="message.deleted ? 'italic text-gray-400' : 'text-gray-800'">
          <template v-if="message.deleted">Message deleted</template>
          <template v-else>{{ message.body }}</template>
          <span v-if="message.edited && !message.deleted" class="ml-2 text-[10px] text-gray-400">(edited)</span>
        </div>

        <div v-else class="mt-1 flex gap-2">
          <textarea v-model="editBody" rows="2" class="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
          <div class="flex flex-col gap-1">
            <button class="px-3 py-1 rounded bg-indigo-600 text-white text-sm" @click="saveEdit">Save</button>
            <button class="px-3 py-1 rounded border text-sm" @click="cancelEdit">Cancel</button>
          </div>
        </div>

        <div v-if="(message.reactions || []).length" class="mt-2 flex flex-wrap gap-1">
          <button
            v-for="r in message.reactions"
            :key="r.emoji"
            :title="reactionTitle(r)"
            @click.stop="react(r.emoji)"
            class="text-sm px-2 py-0.5 rounded-full border shadow-sm bg-white hover:bg-indigo-50 transition flex items-center gap-1"
            :class="r.me ? 'border-indigo-300 text-indigo-700' : 'border-gray-200 text-gray-700'"
          >
            <span class="text-base leading-none">{{ r.emoji }}</span>
            <span class="text-[11px] leading-none">{{ r.count || 0 }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(#emoji-quickbar),
:deep(#emoji-grid){ z-index: 1000; }
</style>
