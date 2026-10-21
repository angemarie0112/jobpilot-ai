import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

import './assets/main.css'

async function bootstrap() {
  const app = createApp(App)

  // Initialize Pinia
  const pinia = createPinia()
  app.use(pinia)

  // Initialize authentication
  const authStore = useAuthStore(pinia)

  // Check whether the user already has an active Laravel session
  await authStore.fetchUser()

  // Initialize Vue Router
  app.use(router)

  // Mount the application
  await router.isReady()
  app.mount('#app')
}

bootstrap()