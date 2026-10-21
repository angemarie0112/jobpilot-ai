<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  ChartNoAxesCombined,
  Plus,
  FileText,
  BriefcaseBusiness,
  CheckCircle2,
  CircleAlert,
  CalendarDays,
  X,
  LoaderCircle,
  Eye,
  Trash2,
} from '@lucide/vue'

// ------------------------------------
// Types
// ------------------------------------

interface AnalysisCv {
  id: number
  original_filename: string
  status: string
}

interface AnalysisJob {
  id: number
  title: string
  company_name: string
  location: string | null
}

interface Analysis {
  id: number
  status: string
  match_score: number | null
  summary: string | null
  created_at: string
  cv: AnalysisCv | null
  job_description: AnalysisJob | null
}

interface AnalysesResponse {
  message: string
  analyses: {
    current_page: number
    data: Analysis[]
    last_page: number
    total: number
  }
}

interface Cv {
  id: number
  original_filename: string
  status: string
}

interface JobDescription {
  id: number
  title: string
  company_name: string
}

interface CvsResponse {
  cvs: Cv[]
}

interface JobsResponse {
  job_descriptions: JobDescription[]
}

// ------------------------------------
// State
// ------------------------------------

const analyses = ref<Analysis[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const actionError = ref<string | null>(null)

// New analysis modal
const showModal = ref(false)

// Dropdown data
const availableCvs = ref<Cv[]>([])
const availableJobs = ref<JobDescription[]>([])

const loadingOptions = ref(false)
const optionsError = ref<string | null>(null)

// Form
const selectedCvId = ref<number | null>(null)
const selectedJobId = ref<number | null>(null)

const analyzing = ref(false)
const formError = ref<string | null>(null)

// Delete confirmation
const analysisToDelete = ref<Analysis | null>(null)
const deleting = ref(false)

// ------------------------------------
// Fetch previous analyses
// ------------------------------------

async function fetchAnalyses() {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<AnalysesResponse>(
      '/api/analyses'
    )

    analyses.value = response.data.analyses.data ?? []

  } catch (err) {
    analyses.value = []

    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to retrieve your match analyses.'
    } else {
      error.value =
        'An unexpected error occurred while loading analyses.'
    }
  } finally {
    loading.value = false
  }
}

// ------------------------------------
// Open New Analysis modal
// ------------------------------------

async function openAnalysisModal() {
  showModal.value = true

  successMessage.value = null
  actionError.value = null
  formError.value = null
  optionsError.value = null

  selectedCvId.value = null
  selectedJobId.value = null

  availableCvs.value = []
  availableJobs.value = []

  loadingOptions.value = true

  try {
    const [cvsResponse, jobsResponse] = await Promise.all([
      api.get<CvsResponse>('/api/cvs'),
      api.get<JobsResponse>('/api/job-descriptions'),
    ])

    availableCvs.value = (cvsResponse.data.cvs ?? []).filter(
      (cv) => cv.status === 'processed'
    )

    availableJobs.value =
      jobsResponse.data.job_descriptions ?? []

  } catch (err) {
    if (axios.isAxiosError(err)) {
      optionsError.value =
        err.response?.data?.message ||
        'Unable to load CVs and job descriptions.'
    } else {
      optionsError.value =
        'An unexpected error occurred while loading options.'
    }
  } finally {
    loadingOptions.value = false
  }
}

// ------------------------------------
// Close New Analysis modal
// ------------------------------------

function closeAnalysisModal() {
  if (analyzing.value) return

  showModal.value = false
  formError.value = null
  optionsError.value = null
}

// ------------------------------------
// Create analysis
// ------------------------------------

async function createAnalysis() {
  formError.value = null

  if (!selectedCvId.value) {
    formError.value = 'Please select a processed CV.'
    return
  }

  if (!selectedJobId.value) {
    formError.value = 'Please select a job description.'
    return
  }

  if (analyzing.value) return

  analyzing.value = true

  try {
    await api.post('/api/analyses', {
      cv_id: selectedCvId.value,
      job_description_id: selectedJobId.value,
    })

    showModal.value = false

    successMessage.value =
      'Match analysis completed successfully.'

    await fetchAnalyses()

  } catch (err) {
    if (axios.isAxiosError(err)) {
      formError.value =
        err.response?.data?.message ||
        'Unable to complete the match analysis.'
    } else {
      formError.value =
        'An unexpected error occurred during analysis.'
    }
  } finally {
    analyzing.value = false
  }
}

// ------------------------------------
// Delete analysis
// ------------------------------------

function confirmDelete(analysis: Analysis) {
  successMessage.value = null
  actionError.value = null

  analysisToDelete.value = analysis
}

function cancelDelete() {
  if (deleting.value) return

  analysisToDelete.value = null
}

async function deleteAnalysis() {
  const analysis = analysisToDelete.value

  if (!analysis || deleting.value) return

  deleting.value = true
  actionError.value = null
  successMessage.value = null

  try {
    await api.delete(`/api/analyses/${analysis.id}`)

    analysisToDelete.value = null

    successMessage.value =
      'Match analysis deleted successfully.'

    await fetchAnalyses()

  } catch (err) {
    if (axios.isAxiosError(err)) {
      const status = err.response?.status

      if (status === 409) {
        actionError.value =
          err.response?.data?.message ||
          'This analysis is linked to other records and cannot be deleted.'
      } else if (status === 404) {
        actionError.value =
          'This analysis could not be found. Refresh the page and try again.'
      } else {
        actionError.value =
          err.response?.data?.message ||
          'Unable to delete the match analysis.'
      }
    } else {
      actionError.value =
        'An unexpected error occurred while deleting the analysis.'
    }

    analysisToDelete.value = null
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

function scoreColor(score: number | null) {
  if (score === null) {
    return 'bg-slate-100 text-slate-600'
  }

  if (score >= 75) {
    return 'bg-emerald-50 text-emerald-700'
  }

  if (score >= 50) {
    return 'bg-amber-50 text-amber-700'
  }

  return 'bg-red-50 text-red-700'
}

// ------------------------------------
// Initial load
// ------------------------------------

onMounted(() => {
  fetchAnalyses()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">

      <!-- Page heading -->
      <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">
            Match Analysis
          </h1>

          <p class="mt-2 text-slate-500">
            Compare your CV with job requirements and discover opportunities
            to improve your application.
          </p>
        </div>

        <button
          type="button"
          class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition-colors hover:bg-indigo-700"
          @click="openAnalysisModal"
        >
          <Plus :size="18" />
          New Analysis
        </button>
      </section>

      <!-- Success message -->
      <div
        v-if="successMessage"
        role="status"
        class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
      >
        <CheckCircle2 :size="20" />
        <span>{{ successMessage }}</span>

        <button
          type="button"
          class="ml-auto rounded p-1 hover:bg-emerald-100"
          aria-label="Dismiss success message"
          @click="successMessage = null"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- Action error -->
      <div
        v-if="actionError"
        role="alert"
        class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700"
      >
        <CircleAlert :size="20" class="shrink-0" />

        <span class="flex-1">
          {{ actionError }}
        </span>

        <button
          type="button"
          class="rounded p-1 hover:bg-red-100"
          aria-label="Dismiss error message"
          @click="actionError = null"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- Loading state -->
      <section
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <p class="text-slate-500">
          Loading your match analyses...
        </p>
      </section>

      <!-- Error state -->
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
          @click="fetchAnalyses"
        >
          Try again
        </button>
      </section>

      <!-- Empty state -->
      <section
        v-else-if="analyses.length === 0"
        class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      >
        <div class="flex flex-col items-center justify-center py-12 text-center">

          <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50"
          >
            <ChartNoAxesCombined
              :size="32"
              :stroke-width="1.6"
              class="text-emerald-600"
            />
          </div>

          <h2 class="mt-6 text-xl font-semibold text-slate-900">
            Analyze Your Job Compatibility
          </h2>

          <p class="mt-3 max-w-lg text-slate-500">
            Select an uploaded CV and a saved job description to evaluate
            how well your documented skills align with the job requirements.
          </p>

          <div class="mt-8 grid w-full max-w-3xl gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-slate-200 p-5 text-center">
              <div
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-50"
              >
                <FileText :size="22" class="text-indigo-600" />
              </div>

              <h3 class="mt-4 font-semibold text-slate-900">
                Select CV
              </h3>

              <p class="mt-2 text-sm text-slate-500">
                Choose a processed CV from your uploaded documents.
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 p-5 text-center">
              <div
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50"
              >
                <BriefcaseBusiness :size="22" class="text-blue-600" />
              </div>

              <h3 class="mt-4 font-semibold text-slate-900">
                Select Job
              </h3>

              <p class="mt-2 text-sm text-slate-500">
                Choose the job description you want to compare.
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 p-5 text-center">
              <div
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50"
              >
                <CheckCircle2 :size="22" class="text-emerald-600" />
              </div>

              <h3 class="mt-4 font-semibold text-slate-900">
                View Results
              </h3>

              <p class="mt-2 text-sm text-slate-500">
                Review your match score, skills, and recommendations.
              </p>
            </div>
          </div>

          <p class="mt-8 text-sm text-slate-400">
            Match scores are estimates based on documented skills,
            not predictions of hiring outcomes.
          </p>
        </div>
      </section>

      <!-- Previous analyses -->
      <section
        v-else
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >

        <div class="border-b border-slate-100 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">
            Previous Match Analyses
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Your saved CV and job compatibility results
          </p>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="analysis in analyses"
            :key="analysis.id"
            class="px-6 py-5"
          >

            <div class="flex flex-wrap items-start justify-between gap-4">

              <!-- Analysis information -->
              <div class="flex min-w-0 items-start gap-4">

                <div
                  class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50"
                >
                  <ChartNoAxesCombined
                    :size="24"
                    class="text-emerald-600"
                  />
                </div>

                <div class="min-w-0">

                  <h3 class="text-lg font-semibold text-slate-900">
                    {{ analysis.job_description?.title || 'Unknown Job' }}
                  </h3>

                  <p class="mt-1 text-sm text-slate-600">
                    {{ analysis.job_description?.company_name || 'Unknown Company' }}
                  </p>

                  <div class="mt-3 flex items-center gap-2 text-sm text-slate-500">
                    <FileText :size="16" />

                    <span>
                      {{ analysis.cv?.original_filename || 'Unknown CV' }}
                    </span>
                  </div>

                  <div class="mt-2 flex items-center gap-2 text-sm text-slate-400">
                    <CalendarDays :size="16" />

                    <span>
                      Analyzed {{ formatDate(analysis.created_at) }}
                    </span>
                  </div>

                </div>
              </div>

              <!-- Match score and action buttons -->
              <div class="flex flex-col items-end gap-3">

                <div
                  class="rounded-xl px-4 py-3 text-center"
                  :class="scoreColor(analysis.match_score)"
                >
                  <p class="text-2xl font-bold">
                    {{ analysis.match_score === null ? '—' : `${analysis.match_score}%` }}
                  </p>

                  <p class="mt-1 text-xs font-medium">
                    Match Score
                  </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">

                  <!-- View Details -->
                  <RouterLink
                    :to="{
                      name: 'analysis-details',
                      params: { id: analysis.id },
                    }"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 transition-colors hover:bg-indigo-100"
                  >
                    <Eye :size="16" />
                    View Details
                  </RouterLink>

                  <!-- Delete -->
                  <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 transition-colors hover:bg-red-100"
                    @click="confirmDelete(analysis)"
                  >
                    <Trash2 :size="16" />
                    Delete
                  </button>

                </div>
              </div>

            </div>

            <p
              v-if="analysis.summary"
              class="mt-4 text-sm leading-6 text-slate-500"
            >
              {{ analysis.summary }}
            </p>

          </div>
        </div>

        <div class="border-t border-slate-100 px-6 py-4 text-xs text-slate-400">
          Match scores are estimates based on documented skills,
          not predictions of hiring outcomes.
        </div>
      </section>

    </div>

    <!-- ========================================= -->
    <!-- New Analysis Modal                        -->
    <!-- ========================================= -->

    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
      @click.self="closeAnalysisModal"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="analysis-modal-title"
        class="w-full max-w-xl rounded-2xl bg-white shadow-xl"
      >

        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <h2
              id="analysis-modal-title"
              class="text-xl font-semibold text-slate-900"
            >
              New Match Analysis
            </h2>

            <p class="mt-1 text-sm text-slate-500">
              Compare a processed CV with a saved job description.
            </p>
          </div>

          <button
            type="button"
            :disabled="analyzing"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-50"
            aria-label="Close modal"
            @click="closeAnalysisModal"
          >
            <X :size="20" />
          </button>
        </div>

        <div class="space-y-5 px-6 py-6">

          <div
            v-if="loadingOptions"
            class="flex items-center justify-center gap-3 py-10 text-slate-500"
          >
            <LoaderCircle :size="20" class="animate-spin" />
            Loading CVs and job descriptions...
          </div>

          <div
            v-else-if="optionsError"
            role="alert"
            class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
          >
            {{ optionsError }}
          </div>

          <form
            v-else
            id="new-analysis-form"
            class="space-y-5"
            @submit.prevent="createAnalysis"
          >

            <div>
              <label
                for="analysis-cv"
                class="mb-2 block text-sm font-medium text-slate-700"
              >
                Select CV <span class="text-red-500">*</span>
              </label>

              <select
                id="analysis-cv"
                v-model.number="selectedCvId"
                :disabled="analyzing || availableCvs.length === 0"
                required
                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50"
              >
                <option :value="null" disabled>
                  Choose a processed CV
                </option>

                <option
                  v-for="cv in availableCvs"
                  :key="cv.id"
                  :value="cv.id"
                >
                  {{ cv.original_filename }}
                </option>
              </select>

              <p
                v-if="availableCvs.length === 0"
                class="mt-2 text-sm text-amber-700"
              >
                No processed CVs available. Upload and process a CV first.
              </p>
            </div>

            <div>
              <label
                for="analysis-job"
                class="mb-2 block text-sm font-medium text-slate-700"
              >
                Select Job Description <span class="text-red-500">*</span>
              </label>

              <select
                id="analysis-job"
                v-model.number="selectedJobId"
                :disabled="analyzing || availableJobs.length === 0"
                required
                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50"
              >
                <option :value="null" disabled>
                  Choose a saved job
                </option>

                <option
                  v-for="job in availableJobs"
                  :key="job.id"
                  :value="job.id"
                >
                  {{ job.title }} — {{ job.company_name }}
                </option>
              </select>

              <p
                v-if="availableJobs.length === 0"
                class="mt-2 text-sm text-amber-700"
              >
                No job descriptions available. Add a job description first.
              </p>
            </div>

            <div
              v-if="formError"
              role="alert"
              class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
              <CircleAlert :size="18" class="mt-0.5 shrink-0" />
              <span>{{ formError }}</span>
            </div>

            <div class="rounded-xl bg-indigo-50 px-4 py-3 text-sm leading-6 text-indigo-700">
              JobPilot AI will compare the skills documented in your CV
              with the selected job requirements and generate a match score
              and recommendations.
            </div>

          </form>
        </div>

        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 px-6 py-5">

          <button
            type="button"
            :disabled="analyzing"
            class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
            @click="closeAnalysisModal"
          >
            Cancel
          </button>

          <button
            type="submit"
            form="new-analysis-form"
            :disabled="
              loadingOptions ||
              !!optionsError ||
              analyzing ||
              !selectedCvId ||
              !selectedJobId
            "
            class="flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <LoaderCircle
              v-if="analyzing"
              :size="18"
              class="animate-spin"
            />

            <ChartNoAxesCombined
              v-else
              :size="18"
            />

            {{ analyzing ? 'Analyzing...' : 'Analyze Match' }}
          </button>

        </div>
      </div>
    </div>

    <!-- ========================================= -->
    <!-- Delete Confirmation Modal                 -->
    <!-- ========================================= -->

    <div
      v-if="analysisToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
      @click.self="cancelDelete"
    >
      <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="delete-analysis-title"
        aria-describedby="delete-analysis-description"
        class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
      >
        <div class="flex items-start gap-4">

          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-red-50">
            <Trash2 :size="24" class="text-red-600" />
          </div>

          <div>
            <h2
              id="delete-analysis-title"
              class="text-xl font-semibold text-slate-900"
            >
              Delete Match Analysis?
            </h2>

            <p
              id="delete-analysis-description"
              class="mt-3 text-sm leading-6 text-slate-600"
            >
              Are you sure you want to delete the analysis for
              <strong class="text-slate-900">
                {{ analysisToDelete.job_description?.title || 'this job' }}
              </strong>?
            </p>

            <p class="mt-3 text-sm leading-6 text-red-600">
              This action cannot be undone. Analyses linked to other
              documents or job applications may not be deletable.
            </p>
          </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">

          <button
            type="button"
            :disabled="deleting"
            class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            @click="cancelDelete"
          >
            Cancel
          </button>

          <button
            type="button"
            :disabled="deleting"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="deleteAnalysis"
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

            {{ deleting ? 'Deleting...' : 'Delete Analysis' }}
          </button>

        </div>
      </div>
    </div>

  </DashboardLayout>
</template>