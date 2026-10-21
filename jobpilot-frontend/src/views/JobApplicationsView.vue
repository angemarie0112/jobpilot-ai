<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  BriefcaseBusiness,
  Building2,
  MapPin,
  CalendarDays,
  LoaderCircle,
  CircleAlert,
  CheckCircle2,
  Plus,
  X,
  Save,
  Link2,
  FileText,
  Pencil,
  Trash2,
  ChartNoAxesColumn,
  FileCheck2,
  Mail,
} from '@lucide/vue'

// ------------------------------------
// Types
// ------------------------------------

type ApplicationStatus =
  | 'saved'
  | 'applied'
  | 'screening'
  | 'interview'
  | 'offer'
  | 'rejected'
  | 'withdrawn'

interface JobApplication {
  id: number
  job_description_id: number | null
  analysis_id: number | null
  cv_id: number | null
  tailored_cv_id: number | null
  cover_letter_id: number | null
  job_title: string
  company_name: string
  job_url: string | null
  location: string | null
  status: ApplicationStatus
  applied_at: string | null
  next_follow_up_at: string | null
  notes: string | null
  created_at: string

  analysis?: {
    id: number
    match_score: number | null
  } | null

  cv?: {
    id: number
    original_filename: string
  } | null

  tailored_cv?: {
    id: number
    title: string
  } | null

  cover_letter?: {
    id: number
    title: string
  } | null
}

interface JobDescription {
  id: number
  title: string
  company_name: string
  location: string | null
}

interface Analysis {
  id: number
  cv_id: number
  job_description_id: number
  match_score: number | null
  status: string

  cv?: {
    id: number
    original_filename: string
    status: string
  } | null
}

interface TailoredCv {
  id: number
  analysis_id: number
  cv_id: number
  title: string
}

interface CoverLetter {
  id: number
  analysis_id: number
  title: string
}

interface PaginatedResponse<T> {
  current_page: number
  last_page: number
  data: T[]
}

interface JobDescriptionsResponse {
  message: string
  job_descriptions: JobDescription[]
}

interface AnalysesResponse {
  message: string
  analyses: PaginatedResponse<Analysis>
}

interface TailoredCvsResponse {
  message: string
  tailored_cvs: PaginatedResponse<TailoredCv>
}

interface CoverLettersResponse {
  message: string
  cover_letters: PaginatedResponse<CoverLetter>
}

interface JobApplicationsResponse {
  message: string
  job_applications: JobApplication[]
}

interface ApplicationResponse {
  message: string
  job_application: JobApplication
}

interface ApplicationForm {
  job_description_id: number | null
  analysis_id: number | null
  cv_id: number | null
  tailored_cv_id: number | null
  cover_letter_id: number | null
  job_title: string
  company_name: string
  job_url: string
  location: string
  status: ApplicationStatus
  applied_at: string
  next_follow_up_at: string
  notes: string
}

// ------------------------------------
// State
// ------------------------------------

const applications = ref<JobApplication[]>([])
const jobDescriptions = ref<JobDescription[]>([])
const analyses = ref<Analysis[]>([])
const tailoredCvs = ref<TailoredCv[]>([])
const coverLetters = ref<CoverLetter[]>([])

const loading = ref(true)
const loadingJobDescriptions = ref(false)
const loadingAnalyses = ref(false)
const loadingTailoredCvs = ref(false)
const loadingCoverLetters = ref(false)

const error = ref<string | null>(null)
const jobDescriptionsError = ref<string | null>(null)
const analysesError = ref<string | null>(null)
const tailoredCvsError = ref<string | null>(null)
const coverLettersError = ref<string | null>(null)

const showForm = ref(false)
const saving = ref(false)
const deletingId = ref<number | null>(null)

const editingApplicationId = ref<number | null>(null)

const formError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const validationErrors = ref<Record<string, string[]>>({})

const form = reactive<ApplicationForm>({
  job_description_id: null,
  analysis_id: null,
  cv_id: null,
  tailored_cv_id: null,
  cover_letter_id: null,
  job_title: '',
  company_name: '',
  job_url: '',
  location: '',
  status: 'saved',
  applied_at: '',
  next_follow_up_at: '',
  notes: '',
})

// ------------------------------------
// Computed
// ------------------------------------

const availableAnalyses = computed(() => {
  if (form.job_description_id === null) {
    return []
  }

  return analyses.value.filter(
    (analysis) =>
      analysis.job_description_id === form.job_description_id &&
      analysis.status === 'completed',
  )
})

const selectedAnalysis = computed(() => {
  if (form.analysis_id === null) return null

  return (
    availableAnalyses.value.find(
      (analysis) => analysis.id === form.analysis_id,
    ) ?? null
  )
})

// Only show tailored CVs generated from
// the currently selected match analysis.
const availableTailoredCvs = computed(() => {
  if (form.analysis_id === null) {
    return []
  }

  return tailoredCvs.value.filter(
    (tailoredCv) =>
      tailoredCv.analysis_id === form.analysis_id &&
      tailoredCv.cv_id === form.cv_id,
  )
})

// Only show cover letters generated from
// the currently selected match analysis.
const availableCoverLetters = computed(() => {
  if (form.analysis_id === null) {
    return []
  }

  return coverLetters.value.filter(
    (coverLetter) =>
      coverLetter.analysis_id === form.analysis_id,
  )
})

// ------------------------------------
// Fetch applications
// ------------------------------------

async function fetchApplications() {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<JobApplicationsResponse>(
      '/api/job-applications',
    )

    applications.value = response.data.job_applications
  } catch (err) {
    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to load your job applications.'
    } else {
      error.value = 'An unexpected error occurred.'
    }
  } finally {
    loading.value = false
  }
}

// ------------------------------------
// Fetch saved job descriptions
// ------------------------------------

async function fetchJobDescriptions() {
  loadingJobDescriptions.value = true
  jobDescriptionsError.value = null

  try {
    const response = await api.get<JobDescriptionsResponse>(
      '/api/job-descriptions',
    )

    jobDescriptions.value = response.data.job_descriptions
  } catch {
    jobDescriptionsError.value =
      'Unable to load your saved job descriptions.'
  } finally {
    loadingJobDescriptions.value = false
  }
}

// ------------------------------------
// Fetch all analyses (paginated)
// ------------------------------------

async function fetchAnalyses() {
  loadingAnalyses.value = true
  analysesError.value = null

  try {
    const allAnalyses: Analysis[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await api.get<AnalysesResponse>(
        '/api/analyses',
        { params: { page } },
      )

      const pagination = response.data.analyses

      allAnalyses.push(...pagination.data)

      lastPage = pagination.last_page
      page++
    } while (page <= lastPage)

    analyses.value = allAnalyses
  } catch (err) {
    analysesError.value = 'Unable to load your match analyses.'
    console.error('Error fetching analyses:', err)
  } finally {
    loadingAnalyses.value = false
  }
}

// ------------------------------------
// Fetch all tailored CVs (paginated)
// ------------------------------------

async function fetchTailoredCvs() {
  loadingTailoredCvs.value = true
  tailoredCvsError.value = null

  try {
    const allTailoredCvs: TailoredCv[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await api.get<TailoredCvsResponse>(
        '/api/tailored-cvs',
        { params: { page } },
      )

      const pagination = response.data.tailored_cvs

      allTailoredCvs.push(...pagination.data)

      lastPage = pagination.last_page
      page++
    } while (page <= lastPage)

    tailoredCvs.value = allTailoredCvs
  } catch (err) {
    tailoredCvsError.value =
      'Unable to load your tailored CVs.'

    console.error('Error fetching tailored CVs:', err)
  } finally {
    loadingTailoredCvs.value = false
  }
}

// ------------------------------------
// Fetch all cover letters (paginated)
// ------------------------------------

async function fetchCoverLetters() {
  loadingCoverLetters.value = true
  coverLettersError.value = null

  try {
    const allCoverLetters: CoverLetter[] = []
    let page = 1
    let lastPage = 1

    do {
      const response = await api.get<CoverLettersResponse>(
        '/api/cover-letters',
        { params: { page } },
      )

      const pagination = response.data.cover_letters

      allCoverLetters.push(...pagination.data)

      lastPage = pagination.last_page
      page++
    } while (page <= lastPage)

    coverLetters.value = allCoverLetters
  } catch (err) {
    coverLettersError.value =
      'Unable to load your cover letters.'

    console.error('Error fetching cover letters:', err)
  } finally {
    loadingCoverLetters.value = false
  }
}

// ------------------------------------
// Select saved job description
// ------------------------------------

function selectJobDescription() {
  // Changing the job description invalidates
  // the previously selected analysis and documents.
  form.analysis_id = null
  form.cv_id = null
  form.tailored_cv_id = null
  form.cover_letter_id = null

  if (form.job_description_id === null) {
    return
  }

  const selectedJob = jobDescriptions.value.find(
    (job) => job.id === form.job_description_id,
  )

  if (!selectedJob) return

  form.job_title = selectedJob.title
  form.company_name = selectedJob.company_name
  form.location = selectedJob.location ?? ''
}

// ------------------------------------
// Select match analysis
// ------------------------------------

function selectAnalysis() {
  // Generated documents belong to a specific analysis.
  // Clear them whenever the analysis changes.
  form.tailored_cv_id = null
  form.cover_letter_id = null

  if (form.analysis_id === null) {
    form.cv_id = null
    return
  }

  const analysis = availableAnalyses.value.find(
    (item) => item.id === form.analysis_id,
  )

  if (!analysis) {
    form.analysis_id = null
    form.cv_id = null
    return
  }

  form.cv_id = analysis.cv_id
}

// ------------------------------------
// Delete application
// ------------------------------------

async function deleteApplication(application: JobApplication) {
  if (deletingId.value !== null) return

  const confirmed = window.confirm(
    `Are you sure you want to delete your application for "${application.job_title}" at "${application.company_name}"?\n\nThis action cannot be undone.`,
  )

  if (!confirmed) return

  deletingId.value = application.id
  error.value = null
  successMessage.value = null

  try {
    await api.delete(`/api/job-applications/${application.id}`)

    applications.value = applications.value.filter(
      (item) => item.id !== application.id,
    )

    successMessage.value =
      'Job application deleted successfully.'
  } catch (err) {
    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to delete this application.'
    } else {
      error.value =
        'An unexpected error occurred while deleting.'
    }
  } finally {
    deletingId.value = null
  }
}

// ------------------------------------
// Form helpers
// ------------------------------------

function resetForm() {
  form.job_description_id = null
  form.analysis_id = null
  form.cv_id = null
  form.tailored_cv_id = null
  form.cover_letter_id = null
  form.job_title = ''
  form.company_name = ''
  form.job_url = ''
  form.location = ''
  form.status = 'saved'
  form.applied_at = ''
  form.next_follow_up_at = ''
  form.notes = ''

  editingApplicationId.value = null
  formError.value = null
  validationErrors.value = {}
}

function openForm() {
  resetForm()

  successMessage.value = null
  showForm.value = true

  // Refresh generated documents in case
  // new ones were created on another page.
  fetchTailoredCvs()
  fetchCoverLetters()

  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function toDateInput(value: string | null): string {
  if (!value) return ''

  const match = value.match(/^\d{4}-\d{2}-\d{2}/)

  return match?.[0] ?? ''
}

// ------------------------------------
// Edit application
// ------------------------------------

function editApplication(application: JobApplication) {
  if (saving.value) return

  resetForm()

  editingApplicationId.value = application.id

  form.job_description_id = application.job_description_id
  form.analysis_id = application.analysis_id
  form.cv_id = application.cv_id
  form.tailored_cv_id = application.tailored_cv_id
  form.cover_letter_id = application.cover_letter_id

  form.job_title = application.job_title
  form.company_name = application.company_name
  form.job_url = application.job_url ?? ''
  form.location = application.location ?? ''
  form.status = application.status
  form.applied_at = toDateInput(application.applied_at)
  form.next_follow_up_at = toDateInput(
    application.next_follow_up_at,
  )
  form.notes = application.notes ?? ''

  successMessage.value = null
  showForm.value = true

  fetchTailoredCvs()
  fetchCoverLetters()

  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function closeForm() {
  if (saving.value) return

  showForm.value = false
  resetForm()
}

function fieldError(field: string): string | null {
  return validationErrors.value[field]?.[0] ?? null
}

// ------------------------------------
// Save application
// ------------------------------------

async function saveApplication() {
  if (saving.value) return

  formError.value = null
  validationErrors.value = {}

  if (!form.job_title.trim()) {
    formError.value = 'Please enter the job title.'
    return
  }

  if (!form.company_name.trim()) {
    formError.value = 'Please enter the company name.'
    return
  }

  if (form.analysis_id !== null) {
    const analysis = availableAnalyses.value.find(
      (item) => item.id === form.analysis_id,
    )

    if (!analysis) {
      formError.value =
        'Please select a valid match analysis for this job description.'
      return
    }

    form.cv_id = analysis.cv_id
  }

  // Validate selected tailored CV.
  if (form.tailored_cv_id !== null) {
    const tailoredCv = availableTailoredCvs.value.find(
      (item) => item.id === form.tailored_cv_id,
    )

    if (!tailoredCv) {
      formError.value =
        'Please select a tailored CV generated from the selected match analysis.'
      return
    }
  }

  // Validate selected cover letter.
  if (form.cover_letter_id !== null) {
    const coverLetter = availableCoverLetters.value.find(
      (item) => item.id === form.cover_letter_id,
    )

    if (!coverLetter) {
      formError.value =
        'Please select a cover letter generated from the selected match analysis.'
      return
    }
  }

  saving.value = true

  try {
    const payload = {
      job_description_id: form.job_description_id,
      analysis_id: form.analysis_id,
      cv_id: form.cv_id,
      tailored_cv_id: form.tailored_cv_id,
      cover_letter_id: form.cover_letter_id,
      job_title: form.job_title.trim(),
      company_name: form.company_name.trim(),
      job_url: form.job_url.trim() || null,
      location: form.location.trim() || null,
      status: form.status,
      applied_at: form.applied_at || null,
      next_follow_up_at: form.next_follow_up_at || null,
      notes: form.notes.trim() || null,
    }

    if (editingApplicationId.value !== null) {
      const applicationId = editingApplicationId.value

      const response = await api.put<ApplicationResponse>(
        `/api/job-applications/${applicationId}`,
        payload,
      )

      const index = applications.value.findIndex(
        (application) => application.id === applicationId,
      )

      if (index !== -1) {
        applications.value[index] = response.data.job_application
      }

      successMessage.value =
        'Job application updated successfully.'
    } else {
      const response = await api.post<ApplicationResponse>(
        '/api/job-applications',
        payload,
      )

      applications.value.unshift(
        response.data.job_application,
      )

      successMessage.value =
        'Job application saved successfully.'
    }

    await fetchApplications()

    showForm.value = false
    resetForm()
  } catch (err) {
    if (axios.isAxiosError(err)) {
      if (err.response?.status === 422) {
        const responseData = err.response.data as {
          message?: string
          errors?: Record<string, string[]>
        }

        validationErrors.value =
          responseData.errors ?? {}

        formError.value =
          responseData.message ||
          'Please correct the highlighted fields.'
      } else {
        formError.value =
          'Unable to save your application. Please try again.'
      }
    } else {
      formError.value =
        'An unexpected error occurred while saving.'
    }
  } finally {
    saving.value = false
  }
}

// ------------------------------------
// Helpers
// ------------------------------------

function formatDate(date: string | null) {
  if (!date) return 'Not applied yet'

  const parsed = new Date(date)

  if (Number.isNaN(parsed.getTime())) {
    return 'Invalid date'
  }

  return parsed.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function statusLabel(status: ApplicationStatus) {
  const labels: Record<ApplicationStatus, string> = {
    saved: 'Saved',
    applied: 'Applied',
    screening: 'Screening',
    interview: 'Interview',
    offer: 'Offer',
    rejected: 'Rejected',
    withdrawn: 'Withdrawn',
  }

  return labels[status]
}

function statusClass(status: ApplicationStatus) {
  const classes: Record<ApplicationStatus, string> = {
    saved: 'bg-slate-100 text-slate-700',
    applied: 'bg-blue-100 text-blue-700',
    screening: 'bg-purple-100 text-purple-700',
    interview: 'bg-amber-100 text-amber-700',
    offer: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-red-100 text-red-700',
    withdrawn: 'bg-gray-100 text-gray-600',
  }

  return classes[status]
}

function matchScoreClass(score: number | null) {
  if (score === null) {
    return 'bg-slate-100 text-slate-600'
  }

  if (score >= 75) {
    return 'bg-emerald-100 text-emerald-700'
  }

  if (score >= 50) {
    return 'bg-amber-100 text-amber-700'
  }

  return 'bg-red-100 text-red-700'
}

// ------------------------------------
// Initial page load
// ------------------------------------

onMounted(() => {
  fetchApplications()
  fetchJobDescriptions()
  fetchAnalyses()
  fetchTailoredCvs()
  fetchCoverLetters()
})
</script>

<template>
  <DashboardLayout>
    <div class="mx-auto max-w-6xl space-y-6">

      <!-- Page heading -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <div
            class="mb-2 flex items-center gap-2 text-sm font-medium text-indigo-600"
          >
            <BriefcaseBusiness :size="18" />
            Application Tracker
          </div>

          <h1 class="text-3xl font-bold text-slate-900">
            Job Applications
          </h1>

          <p class="mt-2 text-sm text-slate-500">
            Track your applications and stay organized throughout your job search.
          </p>
        </div>

        <button
          v-if="!showForm"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-700"
          @click="openForm"
        >
          <Plus :size="18" />
          Add Application
        </button>
      </div>

      <!-- Success message -->
      <div
        v-if="successMessage"
        role="status"
        class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
      >
        <CheckCircle2 :size="20" />

        {{ successMessage }}

        <button
          type="button"
          class="ml-auto rounded-lg p-1 hover:bg-emerald-100"
          aria-label="Dismiss message"
          @click="successMessage = null"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- ================================= -->
      <!-- ADD / EDIT APPLICATION FORM       -->
      <!-- ================================= -->

      <form
        v-if="showForm"
        class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:p-8"
        @submit.prevent="saveApplication"
      >
        <!-- Form heading -->
        <div class="flex items-center justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-slate-900">
              {{
                editingApplicationId !== null
                  ? 'Edit Job Application'
                  : 'Add Job Application'
              }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
              {{
                editingApplicationId !== null
                  ? 'Update your application details and recruitment progress.'
                  : 'Enter the details of the opportunity you want to track.'
              }}
            </p>
          </div>

          <button
            type="button"
            :disabled="saving"
            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 disabled:opacity-50"
            aria-label="Close form"
            @click="closeForm"
          >
            <X :size="20" />
          </button>
        </div>

        <!-- Saved job description -->
        <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-5">
          <label
            for="saved-job-description"
            class="mb-2 block text-sm font-semibold text-slate-800"
          >
            Select Saved Job Description
            <span class="font-normal text-slate-500">(Optional)</span>
          </label>

          <select
            id="saved-job-description"
            v-model.number="form.job_description_id"
            :disabled="saving || loadingJobDescriptions"
            class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            @change="selectJobDescription"
          >
            <option :value="null">
              Enter application details manually
            </option>

            <option
              v-for="job in jobDescriptions"
              :key="job.id"
              :value="job.id"
            >
              {{ job.title }} — {{ job.company_name }}
            </option>
          </select>

          <p class="mt-2 text-xs text-slate-500">
            Selecting a saved job description automatically fills in the job
            title, company name, and location. You can still edit these fields.
          </p>

          <p
            v-if="loadingJobDescriptions"
            class="mt-2 text-xs text-indigo-600"
          >
            Loading saved job descriptions...
          </p>

          <p
            v-if="jobDescriptionsError"
            class="mt-2 text-xs text-red-600"
          >
            {{ jobDescriptionsError }}
          </p>

          <p
            v-if="fieldError('job_description_id')"
            class="mt-2 text-xs text-red-600"
          >
            {{ fieldError('job_description_id') }}
          </p>
        </div>

        <!-- Match analysis -->
        <div class="rounded-xl border border-purple-100 bg-purple-50/50 p-5">
          <div class="mb-2 flex items-center gap-2">
            <ChartNoAxesColumn :size="18" class="text-purple-600" />

            <label
              for="match-analysis"
              class="text-sm font-semibold text-slate-800"
            >
              Link Match Analysis
              <span class="font-normal text-slate-500">(Optional)</span>
            </label>
          </div>

          <select
            id="match-analysis"
            v-model.number="form.analysis_id"
            :disabled="
              saving ||
              loadingAnalyses ||
              form.job_description_id === null
            "
            class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-100 disabled:cursor-not-allowed disabled:bg-slate-100"
            @change="selectAnalysis"
          >
            <option :value="null">
              {{
                form.job_description_id === null
                  ? 'Select a saved job description first'
                  : 'No match analysis selected'
              }}
            </option>

            <option
              v-for="analysis in availableAnalyses"
              :key="analysis.id"
              :value="analysis.id"
            >
              {{
                analysis.match_score === null
                  ? 'Score unavailable'
                  : `${analysis.match_score}% Match`
              }}
              —
              {{ analysis.cv?.original_filename ?? `CV #${analysis.cv_id}` }}
            </option>
          </select>

          <p
            v-if="loadingAnalyses"
            class="mt-2 text-xs text-purple-600"
          >
            Loading match analyses...
          </p>

          <p
            v-else-if="analysesError"
            class="mt-2 text-xs text-red-600"
          >
            {{ analysesError }}
          </p>

          <p
            v-else-if="
              form.job_description_id !== null &&
              availableAnalyses.length === 0
            "
            class="mt-2 text-xs text-slate-500"
          >
            No completed match analyses found for this job description.
            You can still save the application without linking an analysis.
          </p>

          <p
            v-if="fieldError('analysis_id')"
            class="mt-2 text-xs text-red-600"
          >
            {{ fieldError('analysis_id') }}
          </p>

          <p
            v-if="fieldError('cv_id')"
            class="mt-2 text-xs text-red-600"
          >
            {{ fieldError('cv_id') }}
          </p>

          <!-- Selected analysis details -->
          <div
            v-if="selectedAnalysis"
            class="mt-4 rounded-lg border border-purple-100 bg-white p-4"
          >
            <div class="flex flex-wrap items-center gap-3">
              <span
                class="rounded-full px-3 py-1 text-sm font-semibold"
                :class="matchScoreClass(selectedAnalysis.match_score)"
              >
                {{
                  selectedAnalysis.match_score === null
                    ? 'Score unavailable'
                    : `${selectedAnalysis.match_score}% Match`
                }}
              </span>

              <span class="text-sm text-slate-600">
                {{ selectedAnalysis.cv?.original_filename ?? `CV #${selectedAnalysis.cv_id}` }}
              </span>
            </div>

            <p class="mt-2 text-xs text-slate-500">
              The original CV used for this analysis will be linked automatically.
            </p>
          </div>
        </div>

        <!-- ================================= -->
        <!-- GENERATED APPLICATION DOCUMENTS   -->
        <!-- ================================= -->

        <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-5">
          <div class="mb-4">
            <h3 class="text-sm font-semibold text-slate-800">
              Generated Application Documents
            </h3>

            <p class="mt-1 text-xs text-slate-500">
              Link a tailored CV and cover letter generated from your selected
              match analysis. Both documents are optional.
            </p>
          </div>

          <div class="grid gap-5 md:grid-cols-2">

            <!-- Tailored CV -->
            <div>
              <div class="mb-2 flex items-center gap-2">
                <FileCheck2 :size="18" class="text-emerald-600" />

                <label
                  for="tailored-cv"
                  class="text-sm font-semibold text-slate-800"
                >
                  Tailored CV
                  <span class="font-normal text-slate-500">(Optional)</span>
                </label>
              </div>

              <select
                id="tailored-cv"
                v-model.number="form.tailored_cv_id"
                :disabled="
                  saving ||
                  loadingTailoredCvs ||
                  form.analysis_id === null
                "
                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:bg-slate-100"
              >
                <option :value="null">
                  {{
                    form.analysis_id === null
                      ? 'Select a match analysis first'
                      : 'No tailored CV selected'
                  }}
                </option>

                <option
                  v-for="tailoredCv in availableTailoredCvs"
                  :key="tailoredCv.id"
                  :value="tailoredCv.id"
                >
                  {{ tailoredCv.title }}
                </option>
              </select>

              <p
                v-if="loadingTailoredCvs"
                class="mt-2 text-xs text-emerald-600"
              >
                Loading tailored CVs...
              </p>

              <p
                v-else-if="tailoredCvsError"
                class="mt-2 text-xs text-red-600"
              >
                {{ tailoredCvsError }}
              </p>

              <p
                v-else-if="
                  form.analysis_id !== null &&
                  availableTailoredCvs.length === 0
                "
                class="mt-2 text-xs text-slate-500"
              >
                No tailored CV found for this match analysis.
                You can still save the application without one.
              </p>

              <p
                v-if="fieldError('tailored_cv_id')"
                class="mt-2 text-xs text-red-600"
              >
                {{ fieldError('tailored_cv_id') }}
              </p>
            </div>

            <!-- Cover Letter -->
            <div>
              <div class="mb-2 flex items-center gap-2">
                <Mail :size="18" class="text-emerald-600" />

                <label
                  for="cover-letter"
                  class="text-sm font-semibold text-slate-800"
                >
                  Cover Letter
                  <span class="font-normal text-slate-500">(Optional)</span>
                </label>
              </div>

              <select
                id="cover-letter"
                v-model.number="form.cover_letter_id"
                :disabled="
                  saving ||
                  loadingCoverLetters ||
                  form.analysis_id === null
                "
                class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 disabled:cursor-not-allowed disabled:bg-slate-100"
              >
                <option :value="null">
                  {{
                    form.analysis_id === null
                      ? 'Select a match analysis first'
                      : 'No cover letter selected'
                  }}
                </option>

                <option
                  v-for="coverLetter in availableCoverLetters"
                  :key="coverLetter.id"
                  :value="coverLetter.id"
                >
                  {{ coverLetter.title }}
                </option>
              </select>

              <p
                v-if="loadingCoverLetters"
                class="mt-2 text-xs text-emerald-600"
              >
                Loading cover letters...
              </p>

              <p
                v-else-if="coverLettersError"
                class="mt-2 text-xs text-red-600"
              >
                {{ coverLettersError }}
              </p>

              <p
                v-else-if="
                  form.analysis_id !== null &&
                  availableCoverLetters.length === 0
                "
                class="mt-2 text-xs text-slate-500"
              >
                No cover letter found for this match analysis.
                You can still save the application without one.
              </p>

              <p
                v-if="fieldError('cover_letter_id')"
                class="mt-2 text-xs text-red-600"
              >
                {{ fieldError('cover_letter_id') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Job information -->
        <div class="grid gap-5 md:grid-cols-2">

          <!-- Job title -->
          <div>
            <label
              for="job-title"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Job Title <span class="text-red-500">*</span>
            </label>

            <input
              id="job-title"
              v-model="form.job_title"
              type="text"
              required
              maxlength="255"
              :disabled="saving"
              placeholder="e.g. Junior AI Engineer"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />

            <p
              v-if="fieldError('job_title')"
              class="mt-1 text-xs text-red-600"
            >
              {{ fieldError('job_title') }}
            </p>
          </div>

          <!-- Company -->
          <div>
            <label
              for="company-name"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Company Name <span class="text-red-500">*</span>
            </label>

            <input
              id="company-name"
              v-model="form.company_name"
              type="text"
              required
              maxlength="255"
              :disabled="saving"
              placeholder="e.g. Tech Innovations"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />

            <p
              v-if="fieldError('company_name')"
              class="mt-1 text-xs text-red-600"
            >
              {{ fieldError('company_name') }}
            </p>
          </div>

          <!-- Location -->
          <div>
            <label
              for="job-location"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Location
            </label>

            <div class="relative">
              <MapPin
                :size="17"
                class="pointer-events-none absolute left-3 top-3.5 text-slate-400"
              />

              <input
                id="job-location"
                v-model="form.location"
                type="text"
                maxlength="255"
                :disabled="saving"
                placeholder="e.g. Antwerp, Belgium"
                class="w-full rounded-lg border border-slate-200 py-3 pl-10 pr-4 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
              />
            </div>

            <p
              v-if="fieldError('location')"
              class="mt-1 text-xs text-red-600"
            >
              {{ fieldError('location') }}
            </p>
          </div>

          <!-- Job URL -->
          <div>
            <label
              for="job-url"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Job Posting URL
            </label>

            <div class="relative">
              <Link2
                :size="17"
                class="pointer-events-none absolute left-3 top-3.5 text-slate-400"
              />

              <input
                id="job-url"
                v-model="form.job_url"
                type="url"
                maxlength="2048"
                :disabled="saving"
                placeholder="https://..."
                class="w-full rounded-lg border border-slate-200 py-3 pl-10 pr-4 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
              />
            </div>

            <p
              v-if="fieldError('job_url')"
              class="mt-1 text-xs text-red-600"
            >
              {{ fieldError('job_url') }}
            </p>
          </div>

          <!-- Application status -->
          <div>
            <label
              for="application-status"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Application Status
            </label>

            <select
              id="application-status"
              v-model="form.status"
              :disabled="saving"
              class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            >
              <option value="saved">Saved</option>
              <option value="applied">Applied</option>
              <option value="screening">Screening</option>
              <option value="interview">Interview</option>
              <option value="offer">Offer</option>
              <option value="rejected">Rejected</option>
              <option value="withdrawn">Withdrawn</option>
            </select>

            <p
              v-if="fieldError('status')"
              class="mt-1 text-xs text-red-600"
            >
              {{ fieldError('status') }}
            </p>
          </div>

          <!-- Applied date -->
          <div>
            <label
              for="applied-at"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Date Applied
            </label>

            <input
              id="applied-at"
              v-model="form.applied_at"
              type="date"
              :disabled="saving"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />

            <p
              v-if="fieldError('applied_at')"
              class="mt-1 text-xs text-red-600"
            >
              {{ fieldError('applied_at') }}
            </p>
          </div>

          <!-- Follow-up date -->
          <div>
            <label
              for="follow-up-at"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Next Follow-up Date
            </label>

            <input
              id="follow-up-at"
              v-model="form.next_follow_up_at"
              type="date"
              :disabled="saving"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />

            <p
              v-if="fieldError('next_follow_up_at')"
              class="mt-1 text-xs text-red-600"
            >
              {{ fieldError('next_follow_up_at') }}
            </p>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label
            for="application-notes"
            class="mb-2 block text-sm font-medium text-slate-700"
          >
            Notes
          </label>

          <div class="relative">
            <FileText
              :size="17"
              class="absolute left-3 top-3.5 text-slate-400"
            />

            <textarea
              id="application-notes"
              v-model="form.notes"
              rows="4"
              maxlength="10000"
              :disabled="saving"
              placeholder="Add notes about your application, interviews, contacts, or next steps..."
              class="w-full resize-y rounded-lg border border-slate-200 py-3 pl-10 pr-4 text-sm leading-6 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <p
            v-if="fieldError('notes')"
            class="mt-1 text-xs text-red-600"
          >
            {{ fieldError('notes') }}
          </p>
        </div>

        <!-- Form error -->
        <div
          v-if="formError"
          role="alert"
          class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
        >
          <CircleAlert :size="19" class="shrink-0" />
          {{ formError }}
        </div>

        <!-- Form actions -->
        <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
          <button
            type="button"
            :disabled="saving"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
            @click="closeForm"
          >
            <X :size="18" />
            Cancel
          </button>

          <button
            type="submit"
            :disabled="
              saving ||
              !form.job_title.trim() ||
              !form.company_name.trim()
            "
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <LoaderCircle
              v-if="saving"
              :size="18"
              class="animate-spin"
            />

            <Save v-else :size="18" />

            {{
              saving
                ? 'Saving Changes...'
                : editingApplicationId !== null
                  ? 'Save Changes'
                  : 'Save Application'
            }}
          </button>
        </div>
      </form>

      <!-- ================================= -->
      <!-- APPLICATIONS LIST                 -->
      <!-- ================================= -->

      <section
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <LoaderCircle
          :size="28"
          class="mx-auto animate-spin text-indigo-600"
        />

        <p class="mt-4 text-slate-500">
          Loading your applications...
        </p>
      </section>

      <section
        v-else-if="error"
        class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center"
      >
        <CircleAlert :size="30" class="mx-auto text-red-500" />

        <p class="mt-3 text-sm font-medium text-red-700">
          {{ error }}
        </p>

        <button
          type="button"
          class="mt-4 text-sm font-medium text-red-700 underline"
          @click="fetchApplications"
        >
          Try again
        </button>
      </section>

      <section
        v-else-if="applications.length === 0"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <BriefcaseBusiness
          :size="40"
          class="mx-auto text-slate-400"
        />

        <h2 class="mt-4 text-lg font-semibold text-slate-900">
          No job applications yet
        </h2>

        <p class="mt-2 text-sm text-slate-500">
          Click Add Application to start tracking your opportunities.
        </p>
      </section>

      <!-- Applications list -->
      <section v-else class="space-y-4">
        <div
          v-for="application in applications"
          :key="application.id"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">
                {{ application.job_title }}
              </h2>

              <div class="mt-3 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-500">
                <span class="inline-flex items-center gap-2">
                  <Building2 :size="16" />
                  {{ application.company_name }}
                </span>

                <span
                  v-if="application.location"
                  class="inline-flex items-center gap-2"
                >
                  <MapPin :size="16" />
                  {{ application.location }}
                </span>

                <span class="inline-flex items-center gap-2">
                  <CalendarDays :size="16" />
                  {{ formatDate(application.applied_at) }}
                </span>
              </div>

              <!-- Match analysis information -->
              <div
                v-if="application.analysis"
                class="mt-4 flex flex-wrap items-center gap-3"
              >
                <span
                  class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold"
                  :class="matchScoreClass(application.analysis.match_score)"
                >
                  <ChartNoAxesColumn :size="15" />

                  {{
                    application.analysis.match_score === null
                      ? 'Score unavailable'
                      : `${application.analysis.match_score}% Match`
                  }}
                </span>

                <span
                  v-if="application.cv"
                  class="inline-flex items-center gap-2 text-xs text-slate-600"
                >
                  <FileText :size="15" />
                  {{ application.cv.original_filename }}
                </span>
              </div>

              <!-- Linked generated documents -->
              <div
                v-if="application.tailored_cv || application.cover_letter"
                class="mt-3 flex flex-wrap items-center gap-3"
              >
                <span
                  v-if="application.tailored_cv"
                  class="inline-flex items-center gap-2 rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700"
                >
                  <FileCheck2 :size="15" />
                  Tailored CV: {{ application.tailored_cv.title }}
                </span>

                <span
                  v-if="application.cover_letter"
                  class="inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700"
                >
                  <Mail :size="15" />
                  Cover Letter: {{ application.cover_letter.title }}
                </span>
              </div>

              <p
                v-if="application.next_follow_up_at"
                class="mt-3 flex items-center gap-2 text-xs font-medium text-amber-700"
              >
                <CalendarDays :size="15" />
                Follow up:
                {{ formatDate(application.next_follow_up_at) }}
              </p>

              <a
                v-if="application.job_url"
                :href="application.job_url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700"
              >
                <Link2 :size="15" />
                View Job Posting
              </a>

              <p
                v-if="application.notes"
                class="mt-3 max-w-2xl whitespace-pre-line text-sm text-slate-600"
              >
                {{ application.notes }}
              </p>
            </div>

            <!-- Status and actions -->
            <div class="flex items-center gap-3">
              <span
                class="rounded-full px-3 py-1 text-xs font-semibold"
                :class="statusClass(application.status)"
              >
                {{ statusLabel(application.status) }}
              </span>

              <button
                type="button"
                :disabled="saving"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 disabled:opacity-50"
                @click="editApplication(application)"
              >
                <Pencil :size="16" />
                Edit
              </button>

              <button
                type="button"
                :disabled="deletingId !== null"
                class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                @click="deleteApplication(application)"
              >
                <LoaderCircle
                  v-if="deletingId === application.id"
                  :size="16"
                  class="animate-spin"
                />

                <Trash2 v-else :size="16" />

                {{
                  deletingId === application.id
                    ? 'Deleting...'
                    : 'Delete'
                }}
              </button>
            </div>
          </div>
        </div>
      </section>

      <p
        v-if="!loading && !error && applications.length > 0"
        class="text-center text-sm text-slate-500"
      >
        {{ applications.length }}
        {{
          applications.length === 1
            ? 'application'
            : 'applications'
        }}
        tracked
      </p>

    </div>
  </DashboardLayout>
</template>