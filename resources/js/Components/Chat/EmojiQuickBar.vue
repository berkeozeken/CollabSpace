<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, nextTick } from 'vue'
const emit = defineEmits(['pick'])

const QUICK = ['👍','❤️','😂','😮','😢','🙏']
const GRID = [
  '👍','❤️','😂','😮','😢','🙏','🔥','👏','🎉','😅','😎','🤔','🤯','🥳','👌','🙌','💯','✨','🤝','🤩','😴','🤫','🤌',
  '👀','🧠','✅','❗','❓','🙂','😉','😊','😍','😘','😗','😚','😙','😋','😜','🤪','😝','😛','🤤','😭','😠',
  '😡','🤬','🫡','🫶','✌️','🤟','👊','👏'
]

// panel ölçüleri
const PANEL_W = 280
const PANEL_H = 220

const openBar  = ref(false)
const openGrid = ref(false)

const triggerRef = ref(null)
const barPos  = reactive({ top: 0, left: 0 })
const gridPos = reactive({ top: 0, left: 0, width: PANEL_W, height: PANEL_H })

const clamp = (v, min, max) => Math.min(Math.max(v, min), max)

function computeBarPosition () {
  const r = triggerRef.value?.getBoundingClientRect()
  if (!r) return
  const gap = 10
  const vw = window.innerWidth

  // ÖNCE SOLA açmayı dene; yer yoksa sağa kay
  let left = r.left - 160 /* quick bar genişliği tahmini */ - gap
  if (left < 8) left = Math.min(r.right + gap, vw - 160 - 8)
  barPos.left = left
  barPos.top  = r.top - 8
}

function computeGridPosition () {
  const vw = window.innerWidth, vh = window.innerHeight, m = 8
  const btn = triggerRef.value; if (!btn) return
  btn.scrollIntoView({ block: 'nearest', inline: 'nearest' })
  const r = btn.getBoundingClientRect()
  const gap = 10

  // ÖNCE SOL: paneli butonun soluna hizala; sığmazsa sağ tarafa koy
  let left = r.left - PANEL_W - gap
  if (left < m) {
    // sola sığmadı → sağa dene
    left = Math.min(r.right + gap, vw - PANEL_W - m)
  }

  // Dikey hizalama (orta) + clamp
  let top = r.top + r.height / 2 - PANEL_H / 2
  top = clamp(top, m, vh - m - PANEL_H)

  gridPos.left   = left
  gridPos.top    = top
  gridPos.width  = PANEL_W
  gridPos.height = PANEL_H
}

function toggleBar(){
  if (openGrid.value) openGrid.value = false
  openBar.value = !openBar.value
  if (openBar.value) nextTick(computeBarPosition)
}
function openFullGrid(){
  openGrid.value = true
  openBar.value  = false
  nextTick(computeGridPosition)
}
function choose(e){
  emit('pick', e)
  openBar.value = false
  openGrid.value = false
}

function onClickOutside(e){
  const t = triggerRef.value
  const bar  = document.getElementById('emoji-quickbar')
  const grid = document.getElementById('emoji-grid')
  if (t?.contains(e.target) || bar?.contains(e.target) || grid?.contains(e.target)) return
  openBar.value=false; openGrid.value=false
}
function onKey(e){ if((openBar.value||openGrid.value) && e.key==='Escape'){ openBar.value=false; openGrid.value=false } }
function onScrollOrResize(){
  if(openBar.value) computeBarPosition()
  if(openGrid.value) computeGridPosition()
}

onMounted(()=>{
  document.addEventListener('click', onClickOutside, true)
  document.addEventListener('keydown', onKey, true)
  window.addEventListener('scroll', onScrollOrResize, true)
  window.addEventListener('resize', onScrollOrResize, true)
})
onBeforeUnmount(()=>{
  document.removeEventListener('click', onClickOutside, true)
  document.removeEventListener('keydown', onKey, true)
  window.removeEventListener('scroll', onScrollOrResize, true)
  window.removeEventListener('resize', onScrollOrResize, true)
})
</script>

<template>
  <div class="relative inline-block">
    <!-- Trigger -->
    <button
      ref="triggerRef"
      type="button"
      class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full border bg-white hover:bg-gray-50
             shadow-sm transition hover:shadow-md"
      aria-label="Add reaction"
      @click.stop="toggleBar"
    >
      😊 <span class="hidden sm:inline">React</span>
    </button>

    <!-- QUICK BAR (sol öncelikli) -->
    <teleport to="body">
      <transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 -translate-x-1 scale-95"
        enter-to-class="opacity-100 translate-x-0 scale-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-x-0 scale-100"
        leave-to-class="opacity-0 -translate-x-1 scale-95"
      >
        <div
          v-if="openBar"
          id="emoji-quickbar"
          class="fixed z-[1000] rounded-full border bg-white/85 backdrop-blur-md shadow-xl ring-1 ring-black/5
                 px-2 py-1 flex items-center gap-1"
          :style="{ top: barPos.top + 'px', left: barPos.left + 'px' }"
        >
          <button
            v-for="e in QUICK"
            :key="e"
            type="button"
            class="h-8 w-8 text-[18px] flex items-center justify-center rounded-full
                   hover:bg-gray-100 active:scale-95 transition"
            @click.stop="choose(e)"
            :title="e"
          >{{ e }}</button>

          <span class="mx-1 h-5 w-px bg-gray-200"></span>

          <button
            type="button"
            class="h-8 w-8 text-[16px] flex items-center justify-center rounded-full border
                   bg-white hover:bg-gray-50 active:scale-95 transition"
            title="All reactions"
            @click.stop="openFullGrid"
          >+</button>
        </div>
      </transition>
    </teleport>

    <!-- ALL REACTIONS panel (sol öncelikli; sabit yükseklik, içeride scroll) -->
    <teleport to="body">
      <transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 -translate-x-1"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 -translate-x-1"
      >
        <div
          v-if="openGrid"
          id="emoji-grid"
          class="fixed z-[1000] rounded-2xl border bg-white/90 backdrop-blur-md shadow-2xl ring-1 ring-black/5 p-2 overflow-hidden"
          :style="{ top: gridPos.top + 'px', left: gridPos.left + 'px', width: gridPos.width + 'px' }"
        >
          <div class="px-2 py-1 text-[11px] uppercase tracking-wide text-gray-400">ALL REACTIONS</div>

          <div
            class="grid grid-cols-5 gap-1.5 p-2 overflow-y-auto overscroll-contain pr-1"
            :style="{ height: gridPos.height + 'px' }"
          >
            <button
              v-for="e in GRID"
              :key="e"
              type="button"
              class="h-8 w-8 text-[18px] flex items-center justify-center
                     rounded-lg hover:bg-gray-100 active:scale-95 transition"
              @click.stop="choose(e)"
            >{{ e }}</button>
          </div>

          <div class="px-2 pb-2 pt-1 text-[11px] text-gray-400 text-right">
            <span class="hidden sm:inline">Press <kbd class="px-1 py-0.5 border rounded text-[10px]">Esc</kbd> to close</span>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<style scoped>
.overscroll-contain { overscroll-behavior: contain; }
/* güvenlik: overlay’ler her şeyin üstünde kalsın */
:deep(#emoji-quickbar),
:deep(#emoji-grid) { z-index: 1000; }
</style>
