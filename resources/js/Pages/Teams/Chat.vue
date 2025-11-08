<script setup>
import { onMounted, onBeforeUnmount, reactive, ref, nextTick, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import MessageItem from '@/Components/Chat/MessageItem.vue'

const props = defineProps({ team: { type: Object, required: true } })
const page = usePage()
const me = page?.props?.auth?.user || { id: null, name: '' }

const state = reactive({
  loading: true,
  sending: false,
  hasMore: true,
  nextBeforeId: null,
  typingUsers: {}, // { [userId]: name }
})

const messages = ref([]) // [{ id, user:{id,name}, body, created_at, reactions?: [{emoji,count,me,users[]}] }]
const body = ref('')
const scroller = ref(null)
let typingActive = false
let markReadDebounce = null

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

function hasMessage (id) {
  return messages.value.some(m => m.id === id)
}
function normalizeMessage (m) {
  if (!Array.isArray(m.reactions)) m.reactions = []
  if (!m.user) m.user = { id: 0, name: 'User' }
  return m
}
function pushMessage (m, smooth = false) {
  if (!m || hasMessage(m.id)) return
  normalizeMessage(m)
  messages.value.push(m)
  const el = scroller.value
  const nearBottom = el && (el.scrollHeight - el.scrollTop - el.clientHeight) < 120
  if (nearBottom) scrollToBottom(smooth)
}

// ===== API (cursor-based) =====
async function fetchMessages (initial = false) {
  if (!state.hasMore && !initial) return
  try {
    const params = { per_page: 30 }
    if (state.nextBeforeId) params.before_id = state.nextBeforeId

    const { data } = await window.axios.get(
      route('teams.messages.index', { team: props.team.id }),
      { params }
    )
    const items = (data?.data ?? []).map(normalizeMessage)

    if (initial) {
      messages.value = items
      scrollToBottom()
      queueMarkReadVisible()
    } else {
      messages.value = [...items, ...messages.value]
    }

    state.hasMore = !!data?.has_more
    state.nextBeforeId = data?.next_before_id || null
  } finally { state.loading = false }
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
      pushMessage(data.message, true)
      queueMarkReadVisible()
    }
    body.value = ''
    stopTypingIfNeeded()
  } finally {
    state.sending = false
  }
}

// ===== typing outbox =====
function sendTyping (stateVal) {
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
watch(body, (val) => { if (isBlank(val)) stopTypingIfNeeded(); else startTypingIfNeeded() })

// typing indicator (self hariç)
const typingText = computed(() => {
  const others = Object.entries(state.typingUsers)
    .filter(([uid]) => String(uid) !== String(me.id))
    .map(([, name]) => name)
  if (others.length === 0) return ''
  if (others.length === 1) return `${others[0]} is typing...`
  if (others.length === 2) return `${others[0]} and ${others[1]} are typing...`
  return `${others.slice(0, 2).join(', ')} and others are typing...`
})

// ===== realtime (Echo) =====
let channel = null
function subscribe () {
  if (!window?.Echo) return
  unsubscribe()
  channel = window.Echo.private(`team.${props.team.id}`)
    .listen('.MessageCreated', (e) => {
      pushMessage(e, true)
      if (e?.user?.id) delete state.typingUsers[e.user.id]
      queueMarkReadVisible()
    })
    .listen('.MessageUpdated', (e) => {
      const i = messages.value.findIndex(m => m.id === e.id)
      if (i !== -1) {
        messages.value[i].body = e.body
        messages.value[i].updated_at = e.updated_at
        messages.value[i].edited = true
      }
    })
    .listen('.MessageDeleted', (e) => {
      const i = messages.value.findIndex(m => m.id === e.id)
      if (i !== -1) messages.value[i].deleted = true
    })
    .listen('.ReactionToggled', (e) => {
      // e = { message_id, user_id, emoji, direction, count, users, user_ids }
      const msg = messages.value.find(m => m.id === e.message_id)
      if (!msg) return

      let entry = msg.reactions.find(r => r.emoji === e.emoji)
      if (!entry) {
        entry = { emoji: e.emoji, count: 0, me: false, users: [] }
        msg.reactions.push(entry)
      }

      entry.count = e.count

      const ids = Array.isArray(e.user_ids) ? e.user_ids : []
      const names = Array.isArray(e.users) ? e.users : []
      // yerelleştir: benim id'm eşitse "You"
      entry.users = names.map((n, i) => String(ids[i]) === String(me.id) ? 'You' : n)
      entry.me = ids.some(uid => String(uid) === String(me.id))

      if (entry.count === 0) {
        msg.reactions = msg.reactions.filter(r => r.emoji !== e.emoji)
      }
    })
    .listen('.TypingStarted', (e) => {
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

// infinite scroll (yukarı -> eski)
function onScroll (e) {
  const el = e.target
  if (el.scrollTop < 40 && !state.loading && state.hasMore) {
    const prev = el.scrollHeight
    fetchMessages(false).then(() => {
      nextTick(() => { el.scrollTop = el.scrollHeight - prev })
    })
  }
  queueMarkReadVisible()
}

// okundu işaretleme (debounce)
function queueMarkReadVisible () {
  clearTimeout(markReadDebounce)
  markReadDebounce = setTimeout(markReadVisible, 250)
}
function markReadVisible () {
  if (messages.value.length === 0) return
  const lastIds = messages.value.slice(-30).map(m => m.id)
  if (lastIds.length) {
    window.axios.post(
      route('teams.messages.markRead', { team: props.team.id }),
      { message_ids: lastIds }
    ).catch(() => {})
  }
}

// actions
async function editMessage (id, bodyNew) {
  try {
    await window.axios.patch(route('teams.messages.update', { team: props.team.id, message: id }), { body: bodyNew })
    const m = messages.value.find(x => x.id === id)
    if (m) { m.body = bodyNew; m.edited = true }
  } catch {}
}
async function deleteMessage (id) {
  try {
    await window.axios.delete(route('teams.messages.destroy', { team: props.team.id, message: id }))
    const m = messages.value.find(x => x.id === id)
    if (m) m.deleted = true
  } catch {}
}
async function toggleReaction (id, emoji) {
  try {
    const { data } = await window.axios.post(
      route('teams.messages.reactions.toggle', { team: props.team.id, message: id }),
      { emoji }
    )
    const m = messages.value.find(x => x.id === id); if (!m) return
    let entry = m.reactions.find(r => r.emoji === data.emoji)
    if (!entry) {
      entry = { emoji: data.emoji, count: 0, me: false, users: [] }
      m.reactions.push(entry)
    }
    entry.count = data.count

    const ids = Array.isArray(data.user_ids) ? data.user_ids : []
    const names = Array.isArray(data.users) ? data.users : []
    entry.users = names.map((n, i) => String(ids[i]) === String(me.id) ? 'You' : n)
    entry.me = ids.some(uid => String(uid) === String(me.id))

    if (entry.count === 0) {
      m.reactions = m.reactions.filter(r => r.emoji !== data.emoji)
    }
  } catch (e) {
    console.error('toggleReaction failed', e?.response?.data || e)
  }
}

onMounted(async () => {
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

        <MessageItem
          v-for="m in messages"
          :key="m.id"
          :me-id="me.id"
          :message="m"
          :format-time="formatTime"
          @edit="({ id, body }) => editMessage(id, body)"
          @delete="({ id }) => deleteMessage(id)"
          @react="({ id, emoji }) => toggleReaction(id, emoji)"
        />
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
