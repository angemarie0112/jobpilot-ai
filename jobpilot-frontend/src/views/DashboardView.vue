<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  FileText,
  BriefcaseBusiness,
  ChartNoAxesCombined,
  Mail,
  FileUser,
  ClipboardList,
  ArrowRight,
  CircleAlert,
  LoaderCircle,
} from '@lucide/vue'

// ------------------------------------
// Types
// ------------------------------------

interface DashboardStats {
  cvs: number
  job_descriptions: number
  analyses: number
  cover_letters: number
  tailored_cvs: number
  job_applications: number
}

interface DashboardResponse {
  stats: Partial<DashboardStats>
}

// ------------------------------------
// Default statistics
// ------------------------------------

const defaultStats: DashboardStats = {
  cvs: 0,
  job_descriptions: 0,
  analyses: 0,
  cover_letters: 0,
  tailored_cvs: 0,
  job_applications: 0,
}

// ------------------------------------
// State
// ------------------------------------

const stats = ref<DashboardStats>({ ...defaultStats })

const loading = ref(true)
const error = ref<string | null>(null)

// ------------------------------------
// Dashboard cards
// ------------------------------------

const cards = [
  {
    title: 'Uploaded CVs',
    key: 'cvs' as const,
    description: 'CVs in your account',
    icon: FileText,
    iconBackground: 'bg-indigo-50',
    iconColor: 'text-indigo-600',
    route: '/cvs',
  },
  {
    title: 'Job Descriptions',
    key: 'job_descriptions' as const,
    description: 'Saved job opportunities',
    icon: BriefcaseBusiness,
    iconBackground: 'bg-blue-50',
    iconColor: 'text-blue-600',
    route: '/jobs',
  },
  {
    title: 'Match Analyses',
    key: 'analyses' as const,
    description: 'Completed CV comparisons',
    icon: ChartNoAxesCombined,
    iconBackground: 'bg-emerald-50',
    iconColor: 'text-emerald-600',
    route: '/analyses',
  },
  {
    title: 'Cover Letters',
    key: 'cover_letters' as const,
    description: 'AI-generated cover letters',
    icon: Mail,
    iconBackground: 'bg-violet-50',
    iconColor: 'text-violet-600',
    route: '/cover-letters',
  },
  {
    title: 'Tailored CVs',
    key: 'tailored_cvs' as const,
    description: 'Personalized CV versions',
    icon: FileUser,
    iconBackground: 'bg-purple-50',
    iconColor: 'text-purple-600',
    route: '/tailored-cvs',
  },
  {
    title: 'Job Applications',
    key: 'job_applications' as const,
    description: 'Tracked job applications',
    icon: ClipboardList,
    iconBackground: 'bg-amber-50',
    iconColor: 'text-amber-600',
    route: '/job-applications',
  },
]

// ------------------------------------
// Normalize statistic values
// ------------------------------------

function normalizeCount(value: unknown): number {
  if (
    typeof value === 'number' &&
    Number.isFinite(value) &&
    value >= 0
  ) {
    return value
  }

  return 0
}

// ------------------------------------
// Fetch dashboard statistics
// ------------------------------------

async function fetchDashboard() {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<DashboardResponse>(
      '/api/dashboard'
    )

    const data = response.data.stats

    if (!data || typeof data !== 'object') {
      throw new Error('Invalid dashboard response')
    }

    stats.value = {
      cvs: normalizeCount(data.cvs),
      job_descriptions: normalizeCount(data.job_descriptions),
      analyses: normalizeCount(data.analyses),
      cover_letters: normalizeCount(data.cover_letters),
      tailored_cvs: normalizeCount(data.tailored_cvs),
      job_applications: normalizeCount(data.job_applications),
    }

  } catch (err) {
    stats.value = { ...defaultStats }

    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to load dashboard statistics.'
    } else {
      error.value =
        'An unexpected error occurred while loading the dashboard.'
    }

  } finally {
    loading.value = false
  }
}

// ------------------------------------
// Initial load
// ------------------------------------

onMounted(fetchDashboard)
</script>

<template>
  <DashboardLayout>
    <div class="min-w-0 space-y-6 sm:space-y-8">

      <!-- Welcome -->
      <section>
        <h1
          class="text-2xl font-bold leading-tight text-slate-900 sm:text-3xl"
        >
          Welcome to JobPilot AI
        </h1>

        <p
          class="mt-2 text-sm leading-6 text-slate-500 sm:text-base"
        >
          Manage your CVs, analyze job opportunities, generate tailored
          application documents, and track your applications.
        </p>
      </section>

      <!-- Loading -->
      <div
        v-if="loading"
        role="status"
        class="flex items-center justify-center gap-3 rounded-2xl border border-slate-200 bg-white p-8 text-sm text-slate-500 sm:p-10"
      >
        <LoaderCircle
          :size="22"
          class="shrink-0 animate-spin text-indigo-600"
        />

        <span>Loading dashboard...</span>
      </div>

      <!-- Error -->
      <div
        v-else-if="error"
        role="alert"
        class="rounded-2xl border border-red-200 bg-red-50 p-5 sm:p-6"
      >
        <div class="flex items-start gap-3 text-red-700">
          <CircleAlert
            :size="20"
            class="mt-0.5 shrink-0"
          />

          <p class="text-sm sm:text-base">
            {{ error }}
          </p>
        </div>

        <button
          type="button"
          class="mt-4 text-sm font-medium text-red-700 underline"
          @click="fetchDashboard"
        >
          Try again
        </button>
      </div>

      <!-- Statistics -->
      <section
        v-else
        class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
      >
        <RouterLink
          v-for="card in cards"
          :key="card.key"
          :to="card.route"
          class="group min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md sm:p-6"
        >
          <div class="flex items-start justify-between gap-3">
            <span
              class="min-w-0 text-sm font-medium leading-5 text-slate-500"
            >
              {{ card.title }}
            </span>

            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
              :class="card.iconBackground"
            >
              <component
                :is="card.icon"
                :size="22"
                :stroke-width="1.8"
                :class="card.iconColor"
              />
            </div>
          </div>

          <!-- Statistic value -->
          <p
            class="mt-5 text-3xl font-bold text-slate-900"
          >
            {{ stats[card.key] }}
          </p>

          <!-- Description -->
          <div
            class="mt-2 flex items-start justify-between gap-3"
          >
            <p
              class="min-w-0 text-sm leading-5 text-slate-500"
            >
              {{ card.description }}
            </p>

            <ArrowRight
              :size="17"
              class="shrink-0 text-slate-400 transition group-hover:translate-x-1 group-hover:text-indigo-600"
            />
          </div>
        </RouterLink>
      </section>

      <!-- Getting started -->
      <section
        class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"
      >
        <h2
          class="text-xl font-semibold text-slate-900"
        >
          Get Started
        </h2>

        <p
          class="mt-2 text-sm text-slate-500 sm:text-base"
        >
          Follow these steps to prepare your next job application.
        </p>

        <div
          class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3"
        >

          <!-- Step 1 -->
          <RouterLink
            to="/cvs"
            class="group min-w-0 rounded-xl bg-indigo-50 p-5 transition hover:bg-indigo-100"
          >
            <span
              class="text-sm font-semibold text-indigo-600"
            >
              STEP 01
            </span>

            <h3
              class="mt-3 font-semibold text-slate-900"
            >
              Upload your CV
            </h3>

            <p
              class="mt-2 text-sm leading-6 text-slate-600"
            >
              Upload your CV so JobPilot AI can analyze your
              experience and skills.
            </p>

            <ArrowRight
              :size="18"
              class="mt-4 text-indigo-600 transition group-hover:translate-x-1"
            />
          </RouterLink>

          <!-- Step 2 -->
          <RouterLink
            to="/jobs"
            class="group min-w-0 rounded-xl bg-indigo-50 p-5 transition hover:bg-indigo-100"
          >
            <span
              class="text-sm font-semibold text-indigo-600"
            >
              STEP 02
            </span>

            <h3
              class="mt-3 font-semibold text-slate-900"
            >
              Add a job description
            </h3>

            <p
              class="mt-2 text-sm leading-6 text-slate-600"
            >
              Save a job opportunity you want to apply for.
            </p>

            <ArrowRight
              :size="18"
              class="mt-4 text-indigo-600 transition group-hover:translate-x-1"
            />
          </RouterLink>

          <!-- Step 3 -->
          <RouterLink
            to="/analyses"
            class="group min-w-0 rounded-xl bg-indigo-50 p-5 transition hover:bg-indigo-100"
          >
            <span
              class="text-sm font-semibold text-indigo-600"
            >
              STEP 03
            </span>

            <h3
              class="mt-3 font-semibold text-slate-900"
            >
              Generate your application
            </h3>

            <p
              class="mt-2 text-sm leading-6 text-slate-600"
            >
              Analyze your CV match, then generate a tailored CV
              and personalized cover letter.
            </p>

            <ArrowRight
              :size="18"
              class="mt-4 text-indigo-600 transition group-hover:translate-x-1"
            />
          </RouterLink>

        </div>
      </section>

    </div>
  </DashboardLayout>
</template>