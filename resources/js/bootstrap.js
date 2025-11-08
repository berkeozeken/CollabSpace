import axios from 'axios'
window.axios = axios

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common['Accept'] = 'application/json'

// 🔐 CSRF header (Blade'deki meta'dan)
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (token) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token
}

// Cookie'lerin güvenle taşınması (same-origin)
window.axios.defaults.withCredentials = true

// 🔑 Pusher/Echo socket id'yi tüm isteklerde header'a ekle (sender'a broadcast gelmesin)
window.axios.interceptors.request.use((config) => {
  const sid = window.Echo?.socketId?.()
  if (sid) config.headers['X-Socket-Id'] = sid
  return config
})
