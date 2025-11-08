<script setup>
import { onMounted, onBeforeUnmount, reactive, ref, nextTick, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({
  team: { type: Object, required: true }, // { id, name }
})

const page = usePage()
const me = page?.props?.auth?.user || { id: null, name: '' }

const state = reactive({
  loading: true,
  sending: false,
  hasMore: true,
  nextBeforeId: null,     // server'dan cursor
  typingUsers: {},        // { [userId]: userName } (self hariç tutulacak)
})

const messages = ref([])  // { id, user:{id,name}, body, created_at }
const body = ref('')
const scroller = ref(null)
let typingActive = false

function scrollToBottom (smooth = false) {
  nextTick(() => {
    const el = scroller.value
    if (!el) return
    if (smooth) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' })
    else el.scrollTop = el.scrollHeight
  })
}
function formatTime (iso) {
  try { return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) } catch { return '' }
}
const isBlank = v => !v || !v.trim()

// ---- tiny helper: duplicate guard
function hasMessage(id) {
  return messages.value.some(m => m.id === id)
}
function pushMessage(m, smooth = false) {
  if (!m || hasMessage(m.id)) return
  messages.value.push(m)
  const el = scroller.value
  const nearBottom = el && (el.scrollHeight - el.scrollTop - el.clientHeight) < 120
  if (nearBottom) scrollToBottom(smooth)
}

// ---------- API (cursor-based)
async function fetchMessages(initial = false) {
  if (!state.hasMore && !initial) return
  try {
    const params = { per_page: 30 }
    if (state.nextBeforeId) params.before_id = state.nextBeforeId

    const { data } = await window.axios.get(
      route('teams.messages.index', { team: props.team.id }),
      { params }
    )

    const items = data?.data ?? []
    if (initial) {
      messages.value = items
      scrollToBottom()
    } else {
      // daha eski mesajları ÜSTE ekle (chronologic list)
      messages.value = [...items, ...messages.value]
    }

    state.hasMore      = !!data?.has_more
    state.nextBeforeId = data?.next_before_id || null
  } finally {
    state.loading = false
  }
}

async function sendMessage () {
  const text = body.value.trim()
  if (!text || state.sending) return
  state.sending = true
  try {
    const { data } = await window.axios.post(
      route('teams.messages.store', { team: props.team.id }),
      { body: text }
    )
    if (data?.message) {
      pushMessage(data.message, true) // duplicate guard
    }
    body.value = ''
    stopTypingIfNeeded() // send sonrası typing’i kesin
  } catch (e) {
    console.error('sendMessage failed', e?.response?.data || e)
  } finally {
    state.sending = false
  }
}

// ---- typing outbox
function sendTyping(stateVal) {
  window.axios.post(route('teams.typing', { team: props.team.id }), { state: stateVal }).catch(() => {})
}
function startTypingIfNeeded () {
  if (!typingActive) {
    sendTyping('start')
    typingActive = true
  }
}
function stopTypingIfNeeded () {
  if (typingActive) {
    sendTyping('stop')
    typingActive = false
  }
}
watch(body, (val) => {
  if (isBlank(val)) stopTypingIfNeeded()
  else startTypingIfNeeded()
})

// ---- typing indicator metni (self hariç)
const typingText = computed(() => {
  const others = Object.entries(state.typingUsers)
    .filter(([uid]) => String(uid) !== String(me.id))
    .map(([, name]) => name)
  if (others.length === 0) return ''
  if (others.length === 1) return `${others[0]} is typing...`
  if (others.length === 2) return `${others[0]} and ${others[1]} are typing...`
  return `${others.slice(0, 2).join(', ')} and others are typing...`
})

// ---- realtime (Echo)
let channel = null
function subscribe () {
  if (!window?.Echo) return
  unsubscribe()
  channel = window.Echo.private(`team.${props.team.id}`)
    .listen('.MessageCreated', (e) => {
      // e = { id, team_id, user:{id,name}, body, created_at }
      pushMessage(e, true)
      // mesaj gelince gönderenin typing’i sil
      if (e?.user?.id) delete state.typingUsers[e.user.id]
    })
    .listen('.TypingStarted', (e) => {
      // e = { team_id, user_id, user_name }
      if (String(e.user_id) === String(me.id)) return
      state.typingUsers[e.user_id] = e.user_name || `User #${e.user_id}`
    })
    .listen('.TypingStopped', (e) => {
      if (String(e.user_id) === String(me.id)) return
      delete state.typingUsers[e.user_id]
    })
}
function unsubscribe () {
  try { if (channel && window?.Echo) window.Echo.leave(`private-team.${props.team.id}`) } catch {}
  channel = null
}

// ---- infinite scroll (yukarı -> eski)
function onScroll (e) {
  const el = e.target
  if (el.scrollTop < 40 && !state.loading && state.hasMore) {
    const prev = el.scrollHeight
    fetchMessages(false).then(() => {
      nextTick(() => el.scrollTop = el.scrollHeight - prev)
    })
  }
}

onMounted(async () => {
  // İlk açılışta SON 30’u getir
  state.nextBeforeId = null
  await fetchMessages(true)
  subscribe()
})
onBeforeUnmount(() => {
  unsubscribe()
  stopTypingIfNeeded()
})
</script>

<template>
  <div class="mx-auto max-w-4xl p-4 sm:p-6">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold">
          Team Chat — <span class="text-indigo-600">{{ team.name }}</span>
        </h1>
        <p class="text-xs text-gray-500">Private channel: <code>team.{{ team.id }}</code></p>
      </div>
      <Link :href="route('teams.show', team.id)" class="text-sm text-indigo-600 hover:underline">← Back to Team</Link>
    </div>

    <div class="rounded-lg border bg-white">
      <div
        ref="scroller"
        class="h-[60vh] overflow-y-auto p-4 space-y-3"
        @scroll="onScroll"
      >
        <div v-if="state.loading" class="text-center text-sm text-gray-500 py-10">
          Loading messages…
        </div>

        <div v-for="m in messages" :key="m.id" class="flex items-start gap-3">
          <div class="h-8 w-8 flex items-center justify-center rounded-full bg-indigo-600 text-white text-sm">
            {{ (m.user?.name || '?').slice(0,1).toUpperCase() }}
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="font-medium text-gray-900">{{ m.user?.name || 'User' }}</span>
              <span class="text-xs text-gray-400">{{ formatTime(m.created_at) }}</span>
            </div>
            <div class="whitespace-pre-wrap text-gray-800">
              {{ m.body }}
            </div>
          </div>
        </div>
      </div>

      <div v-if="typingText" class="px-4 py-1 text-xs text-gray-500 italic border-t bg-gray-50">
        {{ typingText }}
      </div>

      <form class="border-t p-3 sm:p-4 flex gap-2" @submit.prevent="sendMessage">
        <input
          v-model="body"
          type="text"
          placeholder="Write a message…"
          class="flex-1 rounded border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
        <button
          :disabled="state.sending || isBlank(body)"
          class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50"
        >
          {{ state.sending ? 'Sending…' : 'Send' }}
        </button>
      </form>
    </div>
  </div>
</template>
