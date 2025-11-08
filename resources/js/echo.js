import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

// .env(Vite) üzerinden mod seçimi:
// VITE_ECHO_BACKEND=pusher   → Pusher Cloud (Render prod için ÖNERİLEN)
// VITE_ECHO_BACKEND=local    → Laravel WebSockets (lokalde istersen)
const BACKEND = import.meta.env.VITE_ECHO_BACKEND || 'pusher'

// Ortak key & cluster
const PUSHER_KEY     = import.meta.env.VITE_PUSHER_APP_KEY || ''
const PUSHER_CLUSTER = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu'

// Local WS için opsiyonel host/port (sadece BACKEND=local iken kullanılır)
const WS_HOST   = import.meta.env.VITE_PUSHER_HOST || window.location.hostname
const WS_PORT   = Number(import.meta.env.VITE_PUSHER_PORT || 6001)
const WS_SCHEME = import.meta.env.VITE_PUSHER_SCHEME || 'http'

if (BACKEND === 'pusher') {
  // 🔵 Pusher Cloud (Render prod için)
  window.Echo = new Echo({
    broadcaster: 'pusher',
    key: PUSHER_KEY,
    cluster: PUSHER_CLUSTER,
    forceTLS: true,     // WSS
    // host/port KULLANMA! Cloud endpoint’leri otomatik seçilir.
  })
} else {
  // 🟠 Local WebSockets (beyondcode) — sadece lokal geliştirme için
  window.Echo = new Echo({
    broadcaster: 'pusher',
    key: PUSHER_KEY || 'localkey',
    cluster: PUSHER_CLUSTER || 'mt1',
    wsHost: WS_HOST,
    wsPort: WS_PORT,
    wssPort: WS_PORT,
    forceTLS: WS_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
  })
}
