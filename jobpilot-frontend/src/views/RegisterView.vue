<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import {
  Eye,
  EyeOff,
  BriefcaseBusiness,
  ArrowRight,
  Sparkles,
  CircleAlert,
  LoaderCircle,
} from '@lucide/vue'

// ------------------------------------
// Router and authentication
// ------------------------------------

const router = useRouter()
const authStore = useAuthStore()

// ------------------------------------
// Form state
// ------------------------------------

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const showPassword = ref(false)
const showPasswordConfirmation = ref(false)

const validationError = ref<string | null>(null)

// ------------------------------------
// Validation
// ------------------------------------

const passwordsMatch = computed(() => {
  return password.value === passwordConfirmation.value
})

const passwordIsValid = computed(() => {
  return password.value.length >= 8
})

// ------------------------------------
// Registration
// ------------------------------------

async function handleRegister() {
  validationError.value = null
  authStore.error = null

  if (!name.value.trim()) {
    validationError.value = 'Please enter your full name.'
    return
  }

  if (!email.value.trim()) {
    validationError.value = 'Please enter your email address.'
    return
  }

  if (!passwordIsValid.value) {
    validationError.value =
      'Your password must contain at least 8 characters.'
    return
  }

  if (!passwordsMatch.value) {
    validationError.value = 'Your passwords do not match.'
    return
  }

  if (authStore.loading) return

  const success = await authStore.register(
    name.value.trim(),
    email.value.trim(),
    password.value,
    passwordConfirmation.value,
  )

  if (success) {
    await router.push('/')
  }
}
</script>

<template>
  <main
    class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10"
  >
    <div class="w-full max-w-md">

      <!-- Branding -->
      <div class="mb-8 text-center">
        <div
          class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-600 shadow-lg shadow-indigo-200"
        >
          <BriefcaseBusiness :size="27" class="text-white" />
        </div>

        <h1 class="mt-5 text-3xl font-bold tracking-tight text-slate-900">
          Create your account
        </h1>

        <p class="mt-2 text-slate-500">
          Start your career journey with JobPilot AI.
        </p>
      </div>

      <!-- Registration card -->
      <div
        class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      >

        <!-- Card heading -->
        <div class="mb-6 flex items-center gap-2">
          <Sparkles :size="19" class="text-indigo-600" />

          <h2 class="text-lg font-semibold text-slate-900">
            Get started for free
          </h2>
        </div>

        <form
          class="space-y-5"
          @submit.prevent="handleRegister"
        >

          <!-- Full name -->
          <div>
            <label
              for="name"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Full name
            </label>

            <input
              id="name"
              v-model="name"
              type="text"
              autocomplete="name"
              required
              maxlength="255"
              placeholder="Enter your full name"
              class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <!-- Email -->
          <div>
            <label
              for="email"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Email address
            </label>

            <input
              id="email"
              v-model="email"
              type="email"
              autocomplete="email"
              required
              maxlength="255"
              placeholder="you@example.com"
              class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <!-- Password -->
          <div>
            <label
              for="password"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Password
            </label>

            <div class="relative">
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="new-password"
                required
                minlength="8"
                placeholder="Create a password"
                class="w-full rounded-lg border border-slate-300 px-4 py-3 pr-12 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
              />

              <button
                type="button"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600"
                @click="showPassword = !showPassword"
              >
                <EyeOff
                  v-if="showPassword"
                  :size="19"
                />

                <Eye
                  v-else
                  :size="19"
                />
              </button>
            </div>

            <p class="mt-2 text-xs text-slate-500">
              Must contain at least 8 characters.
            </p>
          </div>

          <!-- Confirm password -->
          <div>
            <label
              for="password-confirmation"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Confirm password
            </label>

            <div class="relative">
              <input
                id="password-confirmation"
                v-model="passwordConfirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                autocomplete="new-password"
                required
                minlength="8"
                placeholder="Confirm your password"
                class="w-full rounded-lg border border-slate-300 px-4 py-3 pr-12 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
              />

              <button
                type="button"
                :aria-label="
                  showPasswordConfirmation
                    ? 'Hide confirmation password'
                    : 'Show confirmation password'
                "
                class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600"
                @click="
                  showPasswordConfirmation = !showPasswordConfirmation
                "
              >
                <EyeOff
                  v-if="showPasswordConfirmation"
                  :size="19"
                />

                <Eye
                  v-else
                  :size="19"
                />
              </button>
            </div>

            <p
              v-if="
                passwordConfirmation.length > 0 &&
                !passwordsMatch
              "
              class="mt-2 text-xs text-red-600"
            >
              Passwords do not match.
            </p>
          </div>

          <!-- Validation or API error -->
          <div
            v-if="validationError || authStore.error"
            role="alert"
            class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          >
            <CircleAlert
              :size="18"
              class="mt-0.5 shrink-0"
            />

            <span>
              {{ validationError || authStore.error }}
            </span>
          </div>

          <!-- Register button -->
          <button
            type="submit"
            :disabled="authStore.loading"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <LoaderCircle
              v-if="authStore.loading"
              :size="18"
              class="animate-spin"
            />

            <template v-if="authStore.loading">
              Creating account...
            </template>

            <template v-else>
              Create Account
              <ArrowRight :size="18" />
            </template>
          </button>

        </form>

        <!-- Login link -->
        <div
          class="mt-6 border-t border-slate-100 pt-6 text-center text-sm text-slate-500"
        >
          Already have an account?

          <RouterLink
            to="/login"
            class="font-semibold text-indigo-600 hover:text-indigo-700"
          >
            Sign in
          </RouterLink>
        </div>
      </div>

      <!-- Footer -->
      <p class="mt-6 text-center text-xs text-slate-400">
        Your career, powered by AI.
      </p>

    </div>
  </main>
</template>