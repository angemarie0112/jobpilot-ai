<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { LogOut, Menu } from '@lucide/vue'
import { useAuthStore } from '@/stores/auth'

defineProps<{
  isSidebarOpen: boolean
}>()

const emit = defineEmits<{
  (event: 'toggle-sidebar'): void
}>()

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Current page title
const pageTitle = computed(() => {
  const titles: Record<string, string> = {
    '/': 'Dashboard',
    '/cvs': 'My CVs',
    '/jobs': 'Job Descriptions',
    '/analyses': 'Match Analysis',
    '/cover-letters': 'Cover Letters',
    '/tailored-cvs': 'Tailored CVs',
    '/job-applications': 'Job Applications',
  }

  if (titles[route.path]) {
    return titles[route.path]
  }

  if (route.path.startsWith('/analyses/')) {
    return 'Analysis Details'
  }

  if (route.path.startsWith('/tailored-cvs/')) {
    return 'Tailored CV Details'
  }

  return 'JobPilot AI'
})

// Authenticated user
const userName = computed(() => authStore.user?.name || 'User')

const userInitial = computed(() =>
  userName.value.charAt(0).toUpperCase(),
)

// Logout
async function handleLogout() {
  const success = await authStore.logout()

  if (success) {
    await router.push({ name: 'login' })
  }
}
</script>

<template>
  <header
    class="flex h-16 min-w-0 items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 sm:px-6 lg:h-20 lg:px-8"
  >
    <!-- Left: Mobile menu + page title -->
    <div class="flex min-w-0 items-center gap-3">
      <button
        type="button"
        aria-label="Open navigation menu"
        aria-controls="jobpilot-sidebar"
        :aria-expanded="isSidebarOpen"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 lg:hidden"
        @click="emit('toggle-sidebar')"
      >
        <Menu :size="23" />
      </button>

      <h2
        class="truncate text-base font-semibold text-slate-800 sm:text-lg lg:text-xl"
      >
        {{ pageTitle }}
      </h2>
    </div>

    <!-- Right: User + logout -->
    <div class="flex shrink-0 items-center gap-2 sm:gap-4 lg:gap-5">
      <!-- User -->
      <div class="flex items-center gap-3">
        <div
          class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-600 sm:h-10 sm:w-10"
          :title="userName"
        >
          {{ userInitial }}
        </div>

        <span class="hidden font-medium text-slate-700 md:inline">
          {{ userName }}
        </span>
      </div>

      <!-- Separator -->
      <div class="hidden h-8 w-px bg-slate-200 sm:block"></div>

      <!-- Logout -->
      <button
        type="button"
        :disabled="authStore.loading"
        aria-label="Log out"
        class="flex h-10 items-center gap-2 rounded-lg px-2 text-sm font-medium text-slate-600 transition hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-50 sm:px-3"
        @click="handleLogout"
      >
        <LogOut :size="18" />

        <span class="hidden sm:inline">
          {{ authStore.loading ? 'Signing out...' : 'Logout' }}
        </span>
      </button>
    </div>
  </header>
</template>