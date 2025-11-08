<script setup>
import { Link } from '@inertiajs/vue3'
import { computed, reactive, ref } from 'vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
  team: Object,
  members: Array,
  can: Object,
  yourRole: String,
})

// LOCAL STATE
const members = ref(props.members.map(m => ({ ...m })))
const state = reactive({ busy: {} })

// Confirm modal
const confirm = reactive({
  open: false,
  title: '',
  message: '',
  confirmText: 'Confirm',
  onOk: null,
})

const canInvite     = computed(() => !!props.can?.invite)
const canChangeRole = computed(() => !!props.can?.changeRole)
const canRemove     = computed(() => !!props.can?.remove)
const canUpdate     = computed(() => !!props.can?.update)
const canDelete     = computed(() => !!props.can?.delete)
const canTransfer   = computed(() => !!props.can?.transfer)

const eligibleOwners = computed(() => members.value.filter(m => !m.is_owner))

const upper = (v) => (v ?? '').toString().toUpperCase()
function roleBadge(role) {
  const r = upper(role)
  if (r === 'OWNER')   return 'bg-emerald-100 text-emerald-800'
  if (r === 'MANAGER') return 'bg-indigo-100 text-indigo-800'
  if (r === 'MEMBER')  return 'bg-gray-100 text-gray-800'
  return 'bg-gray-100 text-gray-800'
}

// Invite / Rename / Transfer
const inviteEmail = ref('')
function invite() {
  if (!inviteEmail.value) return
  window.axios.post(route('teams.members.store', props.team.id), { email: inviteEmail.value })
    .then(res => {
      const added = res?.data?.user
      if (added && !members.value.some(x => x.id === added.id)) {
        members.value.push({ ...added, role: added.role ?? 'member', is_owner: false })
      }
      inviteEmail.value = ''
    })
}

const teamName = ref(props.team.name)
function updateName() {
  window.axios.patch(route('teams.update', props.team.id), { name: teamName.value })
}

const newOwnerId = ref('')
function transferOwnership() {
  if (!newOwnerId.value) return
  openConfirm({
    title: 'Transfer Ownership',
    message: 'Are you sure you want to transfer ownership?',
    confirmText: 'Transfer',
    onOk: () => {
      window.axios.post(route('teams.transfer', props.team.id), { new_owner_id: newOwnerId.value })
        .then(() => window.location.href = route('teams.index'))
    }
  })
}

// Confirm helper
function openConfirm({ title, message, confirmText = 'Confirm', onOk }) {
  confirm.title = title
  confirm.message = message
  confirm.confirmText = confirmText
  confirm.onOk = onOk
  confirm.open = true
}

// Remove (optimistic)
function removeMember(member) {
  if (member.is_owner) return
  openConfirm({
    title: 'Remove Member',
    message: `Remove '${member.name}' from the team?`,
    confirmText: 'Remove',
    onOk: () => {
      const idx = members.value.findIndex(x => x.id === member.id)
      if (idx === -1) return
      const backup = members.value[idx]
      state.busy[member.id] = true
      members.value.splice(idx, 1)
      window.axios.delete(route('teams.members.destroy', { team: props.team.id, user: member.id }))
        .catch(() => { members.value.splice(idx, 0, backup) })
        .finally(() => { state.busy[member.id] = false })
    }
  })
}

// Promote/Demote (optimistic) — küçük harf gönderiyoruz (manager/member)
function toggleRole(member) {
  if (member.is_owner) return
  const currentUpper = upper(member.role)      // ekrana göre
  const newRoleLower = (currentUpper === 'MANAGER') ? 'member' : 'manager'
  const actionText   = (newRoleLower === 'manager') ? 'Promote to Manager' : 'Demote to Member'

  openConfirm({
    title: actionText,
    message: `${member.name} → ${upper(newRoleLower)}. Proceed?`,
    confirmText: (newRoleLower === 'manager') ? 'Promote' : 'Demote',
    onOk: () => {
      state.busy[member.id] = true
      const prev = member.role
      // optimistic update
      member.role = newRoleLower

      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

      // POST + method spoof → 422/CSRF sorunlarını by-pass
      window.axios.post(
        route('teams.members.role', { team: props.team.id, user: member.id }),
        { role: newRoleLower, _method: 'PATCH', _token: csrf }
      )
      .then(() => {
        // success -> optimistic zaten uygulandı
      })
      .catch((err) => {
        member.role = prev
        console.error('Role change failed', err?.response?.data || err)
      })
      .finally(() => { state.busy[member.id] = false })
    }
  })
}

// --- DELETE TEAM ---
const deleteForm = ref(null)
function confirmDeleteTeam() {
  openConfirm({
    title: 'Delete Team',
    message: 'This will permanently delete the team.',
    confirmText: 'Delete',
    onOk: () => { deleteForm.value && deleteForm.value.submit() }
  })
}
</script>

<template>
  <div class="mx-auto max-w-5xl p-6 space-y-8">
    <ConfirmDialog
      v-model="confirm.open"
      :title="confirm.title"
      :message="confirm.message"
      :confirm-text="confirm.confirmText"
      @confirm="confirm.onOk && confirm.onOk()"
    />

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold">{{ team.name }}</h1>
        <p class="text-sm text-gray-500">
          Owner: <span class="font-medium">{{ team.owner.name }}</span> ({{ team.owner.email }})
        </p>
      </div>

      <!-- Header actions: Back + Chat -->
      <div class="flex items-center gap-3">
        <Link :href="route('teams.index')" class="text-indigo-600 hover:underline text-sm">
          ← Back to Teams
        </Link>
        <Link
          :href="route('teams.chat', { team: team.id })"
          class="inline-flex items-center rounded-xl bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
        >
          Open Chat
        </Link>
      </div>
    </div>

    <!-- Rename -->
    <div v-if="canUpdate" class="rounded-lg border bg-white p-4">
      <h2 class="mb-2 font-semibold">Rename Team</h2>
      <form @submit.prevent="updateName" class="flex gap-2">
        <input v-model="teamName" class="w-full rounded border px-3 py-2" placeholder="Team name">
        <button class="rounded bg-gray-800 px-4 py-2 text-white">Save</button>
      </form>
    </div>

    <!-- Invite -->
    <div v-if="canInvite" class="rounded-lg border bg-white p-4">
      <h2 class="mb-2 font-semibold">Invite Member (by email)</h2>
      <form @submit.prevent="invite" class="flex gap-2">
        <input v-model="inviteEmail" type="email" class="w-full rounded border px-3 py-2" placeholder="user@example.com">
        <button :disabled="!inviteEmail" class="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Invite</button>
      </form>
    </div>

    <!-- Transfer Ownership -->
    <div v-if="canTransfer" class="rounded-lg border bg-white p-4">
      <h2 class="mb-2 font-semibold">Transfer Ownership</h2>
      <div class="flex flex-col gap-2 sm:flex-row">
        <select v-model="newOwnerId" class="rounded border px-3 py-2">
          <option disabled value="">Select new owner</option>
          <option v-for="m in eligibleOwners" :key="m.id" :value="m.id">
            {{ m.name }} ({{ m.email }})
          </option>
        </select>
        <button
          :disabled="!newOwnerId"
          class="rounded bg-amber-600 px-4 py-2 text-white disabled:opacity-50"
          @click="transferOwnership"
        >
          Transfer
        </button>
      </div>
    </div>

    <!-- Members Table -->
    <div class="overflow-hidden rounded-lg border bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="m in members" :key="m.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-900">{{ m.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-700">{{ m.email }}</td>
            <td class="px-6 py-4">
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="roleBadge(m.role)">
                {{ upper(m.role) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  v-if="canChangeRole && !m.is_owner"
                  :disabled="state.busy[m.id]"
                  @click="toggleRole(m)"
                  class="inline-flex items-center rounded border px-2.5 py-1 text-xs transition"
                  :class="upper(m.role) === 'MANAGER'
                    ? 'border-gray-300 text-gray-700 hover:bg-gray-50'
                    : 'border-indigo-600 text-indigo-700 hover:bg-indigo-50'"
                >
                  {{ upper(m.role) === 'MANAGER' ? 'Demote to Member' : 'Promote to Manager' }}
                </button>

                <button
                  v-if="canRemove && !m.is_owner"
                  :disabled="state.busy[m.id]"
                  @click="removeMember(m)"
                  class="inline-flex items-center rounded border border-red-600 px-2.5 py-1 text-xs text-red-700 hover:bg-red-50 transition"
                >
                  Remove
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Danger Zone -->
    <div v-if="canDelete" class="rounded-lg border border-red-200 bg-white p-4">
      <h2 class="mb-2 font-semibold text-red-700">Danger Zone</h2>
      <form ref="deleteForm" :action="route('teams.destroy', team.id)" method="post">
        <input type="hidden" name="_method" value="DELETE" />
        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
        <button
          type="button"
          class="rounded bg-red-600 px-4 py-2 text-white"
          @click="confirmDeleteTeam"
        >
          Delete Team
        </button>
      </form>
    </div>
  </div>
</template>
