<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  FileUser,
  Building2,
  CalendarDays,
  Sparkles,
  FileText,
  Eye,
  Plus,
  LoaderCircle,
  CircleAlert,
  ArrowRight,
  Trash2,
  X,
  CheckCircle2,
} from '@lucide/vue'

// ------------------------------------
// Types
// ------------------------------------

interface JobDescription {
  id: number
  title: string
  company_name: string
  location: string | null
}

interface Analysis {
  job_description?: JobDescription | null
}

interface OriginalCv {
  id: number
  original_filename: string
}

interface TailoredCv {
  id: number
  title: string
  model_used: string | null
  created_at: string
  cv?: OriginalCv | null
  analysis?: Analysis | null
}

interface PaginatedTailoredCvs {
  data: TailoredCv[]
  current_page: number
  last_page: number
  total: number
}

interface TailoredCvsResponse {
  message: string
  tailored_cvs: PaginatedTailoredCvs
}

// ------------------------------------
// State
// ------------------------------------

const router = useRouter()

const tailoredCvs = ref<TailoredCv[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const successMessage = ref<string | null>(null)

const currentPage = ref(1)
const lastPage = ref(1)
const totalCvs = ref(0)

// Delete modal
const showDeleteModal = ref(false)
const selectedCvForDeletion = ref<TailoredCv | null>(null)
const deleting = ref(false)
const deleteError = ref<string | null>(null)

// ------------------------------------
// Fetch saved tailored CVs
// ------------------------------------

async function fetchTailoredCvs(page = 1) {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<TailoredCvsResponse>(
      '/api/tailored-cvs',
      {
        params: { page },
      },
    )

    const result = response.data.tailored_cvs

    tailoredCvs.value = result.data ?? []
    currentPage.value = result.current_page
    lastPage.value = result.last_page
    totalCvs.value = result.total

  } catch (err) {
    tailoredCvs.value = []

    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to load your tailored CVs.'
    } else {
      error.value =
        'An unexpected error occurred while loading your CVs.'
    }
  } finally {
    loading.value = false
  }
}

// ------------------------------------
// Navigation
// ------------------------------------

function goToAnalyses() {
  router.push('/analyses')
}

function viewTailoredCv(id: number) {
  router.push(`/tailored-cvs/${id}`)
}

// ------------------------------------
// Open delete confirmation modal
// ------------------------------------

function openDeleteModal(cv: TailoredCv) {
  selectedCvForDeletion.value = cv
  deleteError.value = null
  successMessage.value = null
  showDeleteModal.value = true
}

// ------------------------------------
// Close delete confirmation modal
// ------------------------------------

function closeDeleteModal() {
  if (deleting.value) return

  showDeleteModal.value = false
  selectedCvForDeletion.value = null
  deleteError.value = null
}

// ------------------------------------
// Delete tailored CV
// ------------------------------------

async function deleteTailoredCv() {
  if (!selectedCvForDeletion.value || deleting.value) {
    return
  }

  deleting.value = true
  deleteError.value = null

  try {
    await api.delete(
      `/api/tailored-cvs/${selectedCvForDeletion.value.id}`
    )

    showDeleteModal.value = false
    selectedCvForDeletion.value = null

    successMessage.value =
      'Tailored CV deleted successfully.'

    // If this was the last CV on the current page,
    // return to the previous page.
    const nextPage =
      tailoredCvs.value.length === 1 && currentPage.value > 1
        ? currentPage.value - 1
        : currentPage.value

    await fetchTailoredCvs(nextPage)

  } catch (err) {
    if (axios.isAxiosError(err)) {
      deleteError.value =
        err.response?.data?.message ||
        'Unable to delete the tailored CV.'
    } else {
      deleteError.value =
        'An unexpected error occurred while deleting the CV.'
    }
  } finally {
    deleting.value = false
  }
}

// ------------------------------------
// Helpers
// ------------------------------------

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

// ------------------------------------
// Initial load
// ------------------------------------

onMounted(() => {
  fetchTailoredCvs()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">

      <!-- Page heading -->
      <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">
            Tailored CVs
          </h1>

          <p class="mt-2 text-slate-500">
            Review and manage CVs personalized for your job applications.
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-700"
          @click="goToAnalyses"
        >
          <Plus :size="18" />
          Tailor a New CV
        </button>
      </section>

      <!-- Success message -->
      <div
        v-if="successMessage"
        role="status"
        class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
      >
        <div class="flex items-center gap-3">
          <CheckCircle2 :size="20" />
          {{ successMessage }}
        </div>

        <button
          type="button"
          aria-label="Dismiss success message"
          @click="successMessage = null"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- Loading -->
      <section
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <LoaderCircle
          :size="28"
          class="mx-auto animate-spin text-indigo-600"
        />

        <p class="mt-4 text-slate-500">
          Loading your tailored CVs...
        </p>
      </section>

      <!-- Error -->
      <section
        v-else-if="error"
        class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center"
      >
        <CircleAlert
          :size="32"
          class="mx-auto text-red-500"
        />

        <p class="mt-3 font-medium text-red-700">
          {{ error }}
        </p>

        <button
          type="button"
          class="mt-4 font-medium text-red-700 underline"
          @click="fetchTailoredCvs(currentPage)"
        >
          Try again
        </button>
      </section>

      <!-- Empty state -->
      <section
        v-else-if="tailoredCvs.length === 0"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <div
          class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50"
        >
          <FileUser :size="32" class="text-indigo-600" />
        </div>

        <h2 class="mt-6 text-xl font-semibold text-slate-900">
          No Tailored CVs Yet
        </h2>

        <p class="mx-auto mt-3 max-w-lg text-slate-500">
          Generate a tailored CV using your existing CV and a job
          match analysis. Your saved CVs will appear here.
        </p>

        <button
          type="button"
          class="mt-8 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white hover:bg-indigo-700"
          @click="goToAnalyses"
        >
          <Sparkles :size="18" />
          Create Your First Tailored CV
          <ArrowRight :size="18" />
        </button>
      </section>

      <!-- Saved tailored CVs -->
      <section
        v-else
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">
            Saved Tailored CVs
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            {{ totalCvs }}
            tailored CV{{ totalCvs === 1 ? '' : 's' }}
            in your account
          </p>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="cv in tailoredCvs"
            :key="cv.id"
            class="px-6 py-5"
          >
            <div class="flex flex-wrap items-start justify-between gap-4">

              <!-- CV information -->
              <div class="flex min-w-0 items-start gap-4">
                <div
                  class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50"
                >
                  <FileUser :size="24" class="text-indigo-600" />
                </div>

                <div class="min-w-0">
                  <h3 class="text-lg font-semibold text-slate-900">
                    {{ cv.title }}
                  </h3>

                  <div
                    v-if="cv.analysis?.job_description"
                    class="mt-2 flex items-center gap-2 text-sm text-slate-600"
                  >
                    <Building2 :size="16" />

                    <span>
                      {{ cv.analysis.job_description.company_name }}
                      ·
                      {{ cv.analysis.job_description.title }}
                    </span>
                  </div>

                  <div
                    v-if="cv.cv"
                    class="mt-2 flex items-center gap-2 text-sm text-slate-500"
                  >
                    <FileText :size="16" />

                    <span>
                      Original CV: {{ cv.cv.original_filename }}
                    </span>
                  </div>

                  <div class="mt-2 flex items-center gap-2 text-sm text-slate-400">
                    <CalendarDays :size="16" />

                    <span>
                      Generated {{ formatDate(cv.created_at) }}
                    </span>
                  </div>

                  <div
                    v-if="cv.model_used"
                    class="mt-2 flex items-center gap-2 text-sm text-slate-400"
                  >
                    <Sparkles :size="16" />

                    <span>
                      Generated with {{ cv.model_used }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex flex-wrap items-center gap-3">

                <!-- View CV -->
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100"
                  @click="viewTailoredCv(cv.id)"
                >
                  <Eye :size="17" />
                  View CV
                </button>

                <!-- Delete CV -->
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100"
                  @click="openDeleteModal(cv)"
                >
                  <Trash2 :size="17" />
                  Delete
                </button>

              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="lastPage > 1"
          class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 px-6 py-4"
        >
          <p class="text-sm text-slate-500">
            Page {{ currentPage }} of {{ lastPage }}
          </p>

          <div class="flex items-center gap-3">
            <button
              type="button"
              :disabled="loading || currentPage <= 1"
              class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
              @click="fetchTailoredCvs(currentPage - 1)"
            >
              Previous
            </button>

            <button
              type="button"
              :disabled="loading || currentPage >= lastPage"
              class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
              @click="fetchTailoredCvs(currentPage + 1)"
            >
              Next
            </button>
          </div>
        </div>

        <div class="border-t border-slate-100 px-6 py-4 text-xs text-slate-400">
          Always review AI-generated CVs for accuracy before submitting
          a job application.
        </div>
      </section>
    </div>

    <!-- ========================================= -->
    <!-- Delete Confirmation Modal                 -->
    <!-- ========================================= -->

    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
      @click.self="closeDeleteModal"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-cv-title"
        class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl"
      >
        <!-- Modal heading -->
        <div class="flex items-start gap-4">

          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50"
          >
            <Trash2 :size="24" class="text-red-600" />
          </div>

          <div>
            <h2
              id="delete-cv-title"
              class="text-xl font-semibold text-slate-900"
            >
              Delete Tailored CV?
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600">
              Are you sure you want to delete
              <strong class="text-slate-900">
                {{ selectedCvForDeletion?.title }}
              </strong>?
            </p>

            <p class="mt-3 text-sm leading-6 text-red-600">
              This action cannot be undone.
              Tailored CVs linked to job applications may not be deletable.
            </p>
          </div>
        </div>

        <!-- Delete error -->
        <div
          v-if="deleteError"
          role="alert"
          class="mt-5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
          <CircleAlert :size="18" class="mt-0.5 shrink-0" />
          <span>{{ deleteError }}</span>
        </div>

        <!-- Modal actions -->
        <div class="mt-8 flex flex-wrap justify-end gap-3">

          <button
            type="button"
            :disabled="deleting"
            class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
            @click="closeDeleteModal"
          >
            Cancel
          </button>

          <button
            type="button"
            :disabled="deleting"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="deleteTailoredCv"
          >
            <LoaderCircle
              v-if="deleting"
              :size="18"
              class="animate-spin"
            />

            <Trash2
              v-else
              :size="18"
            />

            {{ deleting ? 'Deleting...' : 'Delete CV' }}
          </button>

        </div>
      </div>
    </div>

  </DashboardLayout>
</template>