import axios from 'axios'
window.axios = axios

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common['Accept'] = 'application/json'

// Blade'de eklediğimiz meta'dan CSRF token al
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
if (token) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token
}
