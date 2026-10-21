<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Eye, EyeOff, BriefcaseBusiness, ArrowRight, Sparkles } from '@lucide/vue'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)

async function handleLogin() {
  if (!email.value.trim() || !password.value) {
    return
  }

  const success = await authStore.login(email.value.trim(), password.value)

  if (success) {
    await router.push('/')
  }
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
    <div class="w-full max-w-md">
      <!-- Branding -->
      <div class="mb-8 text-center">
        <div
          class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 shadow-lg shadow-indigo-200"
        >
          <BriefcaseBusiness :size="27" class="text-white" />
        </div>

        <h1 class="mt-5 text-3xl font-bold tracking-tight text-slate-900">
          Welcome back
        </h1>

        <p class="mt-2 text-slate-500">
          Sign in to continue your journey with JobPilot AI.
        </p>
      </div>

      <!-- Login card -->
      <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6 flex items-center gap-2">
          <Sparkles :size="19" class="text-indigo-600" />

          <h2 class="text-lg font-semibold text-slate-900">
            Sign in to your account
          </h2>
        </div>

        <form class="space-y-5" @submit.prevent="handleLogin">
          <!-- Email -->
          <div>
            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
              Email address
            </label>

            <input
              id="email"
              v-model="email"
              type="email"
              autocomplete="email"
              required
              placeholder="you@example.com"
              class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
              Password
            </label>

            <div class="relative">
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="Enter your password"
                class="w-full rounded-lg border border-slate-300 px-4 py-3 pr-12 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
              />

              <button
                type="button"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" :size="19" />
                <Eye v-else :size="19" />
              </button>
            </div>
          </div>

          <!-- Error message -->
          <div
            v-if="authStore.error"
            role="alert"
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          >
            {{ authStore.error }}
          </div>

          <!-- Login button -->
          <button
            type="submit"
            :disabled="authStore.loading"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <span>{{ authStore.loading ? 'Signing in...' : 'Sign In' }}</span>
            <ArrowRight v-if="!authStore.loading" :size="18" />
          </button>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-6 text-center text-sm text-slate-500">
          Don't have an account?

          <RouterLink
            to="/register"
            class="font-semibold text-indigo-600 hover:text-indigo-700"
          >
            Create an account
          </RouterLink>
        </div>
      </div>

      <p class="mt-6 text-center text-xs text-slate-400">
        Your career, powered by AI.
      </p>
    </div>
  </main>
</template>