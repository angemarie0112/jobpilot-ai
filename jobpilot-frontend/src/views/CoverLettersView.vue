<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  Mail,
  Plus,
  FileText,
  ChartNoAxesCombined,
  Sparkles,
  CircleAlert,
  CheckCircle2,
  CalendarDays,
  Building2,
  Eye,
  Copy,
  X,
  LoaderCircle,
  ArrowRight,
  Pencil,
  Save,
  Trash2,
  Download,
} from '@lucide/vue'

// ------------------------------------
// Types
// ------------------------------------

interface CoverLetterJob {
  id: number
  title: string
  company_name: string
}

interface CoverLetterAnalysis {
  id: number
  match_score: number | null
  job_description: CoverLetterJob | null
}

interface CoverLetter {
  id: number
  analysis_id: number
  title: string
  content: string
  model_used: string
  created_at: string
  analysis: CoverLetterAnalysis | null
}

interface CoverLettersResponse {
  message: string
  cover_letters: {
    current_page: number
    data: CoverLetter[]
    last_page: number
    total: number
  }
}

interface CoverLetterResponse {
  message: string
  cover_letter: CoverLetter
}

// ------------------------------------
// Router
// ------------------------------------

const route = useRoute()
const router = useRouter()

// ------------------------------------
// State
// ------------------------------------

const coverLetters = ref<CoverLetter[]>([])

const loading = ref(true)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const totalLetters = ref(0)

// Letter modal
const showLetterModal = ref(false)
const selectedLetter = ref<CoverLetter | null>(null)

const loadingLetter = ref(false)
const letterError = ref<string | null>(null)

// Copy functionality
const copying = ref(false)
const copied = ref(false)

// ------------------------------------
// Editing state
// ------------------------------------

const isEditing = ref(false)
const saving = ref(false)

const editTitle = ref('')
const editContent = ref('')

const editError = ref<string | null>(null)

// ------------------------------------
// Deleting state
// ------------------------------------

const deletingLetterId = ref<number | null>(null)

const showDeleteModal = ref(false)
const letterToDelete = ref<CoverLetter | null>(null)

// ------------------------------------
// PDF download state
// ------------------------------------

const downloadingPdf = ref(false)

// ------------------------------------
// Computed
// ------------------------------------

const hasLetters = computed(() => coverLetters.value.length > 0)

// ------------------------------------
// Fetch saved cover letters
// ------------------------------------

async function fetchCoverLetters(page = 1) {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<CoverLettersResponse>(
      '/api/cover-letters',
      {
        params: { page },
      },
    )

    coverLetters.value = response.data.cover_letters.data ?? []

    currentPage.value = response.data.cover_letters.current_page
    lastPage.value = response.data.cover_letters.last_page
    totalLetters.value = response.data.cover_letters.total

  } catch (err) {
    coverLetters.value = []

    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to retrieve your cover letters.'
    } else {
      error.value =
        'An unexpected error occurred while loading cover letters.'
    }
  } finally {
    loading.value = false
  }
}

// ------------------------------------
// Open a saved cover letter
// ------------------------------------

async function viewCoverLetter(id: number) {
  showLetterModal.value = true
  selectedLetter.value = null

  loadingLetter.value = true
  letterError.value = null

  copied.value = false

  isEditing.value = false
  saving.value = false
  editError.value = null

  try {
    const response = await api.get<CoverLetterResponse>(
      `/api/cover-letters/${id}`,
    )

    selectedLetter.value = response.data.cover_letter

  } catch (err) {
    if (axios.isAxiosError(err)) {
      letterError.value =
        err.response?.data?.message ||
        'Unable to retrieve this cover letter.'
    } else {
      letterError.value =
        'An unexpected error occurred while loading the letter.'
    }
  } finally {
    loadingLetter.value = false
  }
}

// ------------------------------------
// Close letter modal
// ------------------------------------

function closeLetterModal() {
  if (
    loadingLetter.value ||
    saving.value ||
    downloadingPdf.value
  ) {
    return
  }

  showLetterModal.value = false
  selectedLetter.value = null

  letterError.value = null
  editError.value = null

  isEditing.value = false
  copied.value = false

  editTitle.value = ''
  editContent.value = ''
}

// ------------------------------------
// Start editing
// ------------------------------------

function startEditing() {
  if (
    !selectedLetter.value ||
    saving.value ||
    downloadingPdf.value
  ) {
    return
  }

  editTitle.value = selectedLetter.value.title
  editContent.value = selectedLetter.value.content

  editError.value = null
  isEditing.value = true
}

// ------------------------------------
// Cancel editing
// ------------------------------------

function cancelEditing() {
  if (saving.value) return

  isEditing.value = false
  editError.value = null

  editTitle.value = ''
  editContent.value = ''
}

// ------------------------------------
// Save edited cover letter
// ------------------------------------

async function saveCoverLetter() {
  if (!selectedLetter.value || saving.value) return

  editError.value = null

  const title = editTitle.value.trim()
  const content = editContent.value.trim()

  if (!title) {
    editError.value = 'Please enter a cover letter title.'
    return
  }

  if (!content) {
    editError.value = 'Cover letter content cannot be empty.'
    return
  }

  saving.value = true

  try {
    const response = await api.put<CoverLetterResponse>(
      `/api/cover-letters/${selectedLetter.value.id}`,
      {
        title,
        content,
      },
    )

    selectedLetter.value = {
      ...selectedLetter.value,
      ...response.data.cover_letter,
    }

    isEditing.value = false

    editTitle.value = ''
    editContent.value = ''

    successMessage.value =
      'Your cover letter has been updated successfully.'

    await fetchCoverLetters(currentPage.value)

  } catch (err) {
    if (axios.isAxiosError(err)) {
      const responseData = err.response?.data

      editError.value =
        responseData?.errors?.title?.[0] ||
        responseData?.errors?.content?.[0] ||
        responseData?.message ||
        'Unable to save your changes.'
    } else {
      editError.value =
        'An unexpected error occurred while saving your cover letter.'
    }
  } finally {
    saving.value = false
  }
}

// ------------------------------------
// Copy letter to clipboard
// ------------------------------------

async function copyLetter() {
  if (
    !selectedLetter.value ||
    copying.value ||
    isEditing.value
  ) {
    return
  }

  copying.value = true
  copied.value = false
  letterError.value = null

  try {
    await navigator.clipboard.writeText(
      selectedLetter.value.content,
    )

    copied.value = true

    window.setTimeout(() => {
      copied.value = false
    }, 2500)

  } catch {
    letterError.value =
      'Unable to copy the letter. Please select and copy the text manually.'
  } finally {
    copying.value = false
  }
}

// ------------------------------------
// Download cover letter as PDF
// ------------------------------------

async function downloadCoverLetterPdf() {
  if (
    !selectedLetter.value ||
    downloadingPdf.value ||
    isEditing.value
  ) {
    return
  }

  downloadingPdf.value = true
  letterError.value = null

  try {
    const letterId = selectedLetter.value.id

    // Request the PDF from Laravel.
    const response = await api.get<Blob>(
      `/api/cover-letters/${letterId}/download`,
      {
        responseType: 'blob',
      },
    )

    // Verify that Laravel returned a PDF.
    const contentType = response.headers['content-type']

    if (
      typeof contentType === 'string' &&
      !contentType.includes('application/pdf')
    ) {
      throw new Error('The server did not return a PDF file.')
    }

    // Create a browser-compatible PDF blob.
    const blob = new Blob([response.data], {
      type: 'application/pdf',
    })

    // Generate a temporary download URL.
    const url = window.URL.createObjectURL(blob)

    // Create a temporary download link.
    const link = document.createElement('a')

    link.href = url
    link.download = `cover-letter-${letterId}.pdf`

    document.body.appendChild(link)

    // Trigger the download.
    link.click()

    // Remove the temporary link.
    document.body.removeChild(link)

    // Release the temporary URL.
    window.setTimeout(() => {
      window.URL.revokeObjectURL(url)
    }, 1000)

  } catch (err) {
    if (axios.isAxiosError(err)) {
      letterError.value =
        'Unable to download your cover letter. Please try again.'
    } else {
      letterError.value =
        'An unexpected error occurred while downloading the PDF.'
    }
  } finally {
    downloadingPdf.value = false
  }
}

// ------------------------------------
// Open delete confirmation
// ------------------------------------

function confirmDelete(letter: CoverLetter) {
  if (deletingLetterId.value !== null) return

  letterToDelete.value = letter
  showDeleteModal.value = true
}

// ------------------------------------
// Cancel deletion
// ------------------------------------

function cancelDelete() {
  if (deletingLetterId.value !== null) return

  showDeleteModal.value = false
  letterToDelete.value = null
}

// ------------------------------------
// Delete cover letter
// ------------------------------------

async function deleteCoverLetter() {
  if (
    !letterToDelete.value ||
    deletingLetterId.value !== null
  ) {
    return
  }

  const letterId = letterToDelete.value.id

  deletingLetterId.value = letterId
  error.value = null
  successMessage.value = null

  try {
    await api.delete(`/api/cover-letters/${letterId}`)

    // Close the confirmation modal.
    showDeleteModal.value = false
    letterToDelete.value = null

    // Close the letter modal if this letter was open.
    if (selectedLetter.value?.id === letterId) {
      closeLetterModal()
    }

    // If the last letter on a page was deleted,
    // return to the previous page.
    const targetPage =
      coverLetters.value.length === 1 && currentPage.value > 1
        ? currentPage.value - 1
        : currentPage.value

    // Refresh the saved letters.
    await fetchCoverLetters(targetPage)

    if (!error.value) {
      successMessage.value =
        'Cover letter deleted successfully.'
    }

  } catch (err) {
    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to delete the cover letter.'
    } else {
      error.value =
        'An unexpected error occurred while deleting the cover letter.'
    }

    // Close confirmation after a failed request.
    showDeleteModal.value = false
    letterToDelete.value = null

  } finally {
    deletingLetterId.value = null
  }
}

// ------------------------------------
// Navigate to Match Analysis
// ------------------------------------

function goToAnalyses() {
  router.push('/analyses')
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

function scoreColor(score: number | null | undefined) {
  if (score === null || score === undefined) {
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

onMounted(async () => {
  const generatedId = route.query.generated

  if (
    typeof generatedId === 'string' &&
    /^\d+$/.test(generatedId)
  ) {
    successMessage.value =
      'Your AI cover letter has been generated and saved successfully.'
  }

  await fetchCoverLetters()

  if (generatedId) {
    await router.replace({
      path: '/cover-letters',
    })
  }
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">

      <!-- ========================================= -->
      <!-- Page heading                            -->
      <!-- ========================================= -->

      <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">
            Cover Letters
          </h1>

          <p class="mt-2 text-slate-500">
            Generate, review, edit, and manage personalized cover letters
            tailored to your job applications.
          </p>
        </div>

        <button
          type="button"
          class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition-colors hover:bg-indigo-700"
          @click="goToAnalyses"
        >
          <Plus :size="18" />
          Generate Cover Letter
        </button>
      </section>

      <!-- ========================================= -->
      <!-- Success message                         -->
      <!-- ========================================= -->

      <div
        v-if="successMessage"
        role="status"
        class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
      >
        <CheckCircle2 :size="20" class="shrink-0" />

        <span>
          {{ successMessage }}
        </span>

        <button
          type="button"
          class="ml-auto rounded-lg p-1 hover:bg-emerald-100"
          aria-label="Dismiss message"
          @click="successMessage = null"
        >
          <X :size="18" />
        </button>
      </div>

      <!-- ========================================= -->
      <!-- Loading state                           -->
      <!-- ========================================= -->

      <section
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <LoaderCircle
          :size="28"
          class="mx-auto animate-spin text-indigo-600"
        />

        <p class="mt-4 text-slate-500">
          Loading your cover letters...
        </p>
      </section>

      <!-- ========================================= -->
      <!-- Error state                             -->
      <!-- ========================================= -->

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
          @click="fetchCoverLetters(currentPage)"
        >
          Try again
        </button>
      </section>

      <!-- ========================================= -->
      <!-- Empty state                             -->
      <!-- ========================================= -->

      <section
        v-else-if="!hasLetters"
        class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      >
        <div class="flex flex-col items-center justify-center py-12 text-center">

          <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-violet-50"
          >
            <Mail
              :size="32"
              :stroke-width="1.6"
              class="text-violet-600"
            />
          </div>

          <h2 class="mt-6 text-xl font-semibold text-slate-900">
            Your AI Cover Letters
          </h2>

          <p class="mt-3 max-w-lg text-slate-500">
            Generate professional cover letters based on your CV
            and the job requirements. Your saved cover letters
            will appear here.
          </p>

          <div class="mt-8 grid w-full max-w-3xl gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-slate-200 p-5 text-center">
              <div
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-indigo-50"
              >
                <FileText :size="22" class="text-indigo-600" />
              </div>

              <h3 class="mt-4 font-semibold text-slate-900">
                Upload Your CV
              </h3>

              <p class="mt-2 text-sm text-slate-500">
                Start with a processed CV containing your
                experience and skills.
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 p-5 text-center">
              <div
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50"
              >
                <ChartNoAxesCombined
                  :size="22"
                  class="text-emerald-600"
                />
              </div>

              <h3 class="mt-4 font-semibold text-slate-900">
                Analyze the Match
              </h3>

              <p class="mt-2 text-sm text-slate-500">
                Compare your CV with the job description
                to identify relevant qualifications.
              </p>
            </div>

            <div class="rounded-xl border border-slate-200 p-5 text-center">
              <div
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-lg bg-violet-50"
              >
                <Sparkles :size="22" class="text-violet-600" />
              </div>

              <h3 class="mt-4 font-semibold text-slate-900">
                Generate with AI
              </h3>

              <p class="mt-2 text-sm text-slate-500">
                Create a personalized cover letter using
                Google Gemini.
              </p>
            </div>
          </div>

          <RouterLink
            to="/analyses"
            class="mt-8 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-medium text-white transition hover:bg-indigo-700"
          >
            <Sparkles :size="18" />
            Create Your First Cover Letter
            <ArrowRight :size="18" />
          </RouterLink>

          <p class="mt-8 text-sm text-slate-400">
            Always review AI-generated cover letters
            before submitting an application.
          </p>
        </div>
      </section>

      <!-- ========================================= -->
      <!-- Saved cover letters                     -->
      <!-- ========================================= -->

      <section
        v-else
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">
            Saved Cover Letters
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            {{ totalLetters }}
            cover letter{{ totalLetters === 1 ? '' : 's' }}
            in your account
          </p>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="letter in coverLetters"
            :key="letter.id"
            class="px-6 py-5"
          >
            <div class="flex flex-wrap items-start justify-between gap-4">

              <!-- Letter information -->
              <div class="flex min-w-0 items-start gap-4">

                <div
                  class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-violet-50"
                >
                  <Mail :size="24" class="text-violet-600" />
                </div>

                <div class="min-w-0">
                  <h3 class="text-lg font-semibold text-slate-900">
                    {{ letter.title }}
                  </h3>

                  <div
                    v-if="letter.analysis?.job_description"
                    class="mt-2 flex items-center gap-2 text-sm text-slate-600"
                  >
                    <Building2 :size="16" />

                    <span>
                      {{ letter.analysis.job_description.company_name }}
                    </span>
                  </div>

                  <div class="mt-2 flex items-center gap-2 text-sm text-slate-400">
                    <CalendarDays :size="16" />

                    <span>
                      Generated {{ formatDate(letter.created_at) }}
                    </span>
                  </div>

                  <div class="mt-2 flex items-center gap-2 text-sm text-slate-400">
                    <Sparkles :size="16" />

                    <span>
                      Generated with {{ letter.model_used }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex flex-wrap items-center gap-3">

                <div
                  v-if="letter.analysis"
                  class="rounded-xl px-4 py-3 text-center"
                  :class="scoreColor(letter.analysis.match_score)"
                >
                  <p class="text-xl font-bold">
                    {{
                      letter.analysis.match_score === null
                        ? '—'
                        : `${letter.analysis.match_score}%`
                    }}
                  </p>

                  <p class="mt-1 text-xs font-medium">
                    Match Score
                  </p>
                </div>

                <!-- View button -->
                <button
                  type="button"
                  :disabled="deletingLetterId !== null"
                  class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-50"
                  @click="viewCoverLetter(letter.id)"
                >
                  <Eye :size="17" />
                  View Letter
                </button>

                <!-- Delete button -->
                <button
                  type="button"
                  :disabled="deletingLetterId !== null"
                  class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
                  @click="confirmDelete(letter)"
                >
                  <LoaderCircle
                    v-if="deletingLetterId === letter.id"
                    :size="17"
                    class="animate-spin"
                  />

                  <Trash2
                    v-else
                    :size="17"
                  />

                  {{
                    deletingLetterId === letter.id
                      ? 'Deleting...'
                      : 'Delete'
                  }}
                </button>
              </div>
            </div>

            <p class="mt-4 line-clamp-2 whitespace-pre-line text-sm leading-6 text-slate-500">
              {{ letter.content }}
            </p>
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
              class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              @click="fetchCoverLetters(currentPage - 1)"
            >
              Previous
            </button>

            <button
              type="button"
              :disabled="loading || currentPage >= lastPage"
              class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              @click="fetchCoverLetters(currentPage + 1)"
            >
              Next
            </button>
          </div>
        </div>

        <div class="border-t border-slate-100 px-6 py-4 text-xs text-slate-400">
          Always review AI-generated cover letters before submitting an application.
        </div>
      </section>

    </div>

    <!-- ========================================= -->
    <!-- Cover Letter Modal                       -->
    <!-- ========================================= -->

    <div
      v-if="showLetterModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
      @click.self="closeLetterModal"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="letter-modal-title"
        class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
      >

        <!-- Modal header -->
        <div class="flex shrink-0 items-start justify-between border-b border-slate-100 px-6 py-5">
          <div class="min-w-0">
            <h2
              id="letter-modal-title"
              class="text-xl font-semibold text-slate-900"
            >
              {{ isEditing ? 'Edit Cover Letter' : (selectedLetter?.title ?? 'Cover Letter') }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
              {{
                isEditing
                  ? 'Personalize your cover letter before submitting your application.'
                  : 'Review your generated cover letter.'
              }}
            </p>
          </div>

          <button
            type="button"
            :disabled="saving || downloadingPdf"
            class="ml-4 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-50"
            aria-label="Close cover letter"
            @click="closeLetterModal"
          >
            <X :size="20" />
          </button>
        </div>

        <!-- Modal content -->
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-6">

          <!-- Loading -->
          <div
            v-if="loadingLetter"
            class="flex flex-col items-center justify-center py-12 text-slate-500"
          >
            <LoaderCircle
              :size="28"
              class="animate-spin text-indigo-600"
            />

            <p class="mt-4">
              Loading cover letter...
            </p>
          </div>

          <!-- Loading error -->
          <div
            v-else-if="letterError && !selectedLetter"
            role="alert"
            class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
          >
            {{ letterError }}
          </div>

          <!-- Letter -->
          <div v-else-if="selectedLetter">

            <!-- ================================= -->
            <!-- Edit mode                         -->
            <!-- ================================= -->

            <form
              v-if="isEditing"
              id="edit-cover-letter-form"
              class="space-y-5"
              @submit.prevent="saveCoverLetter"
            >
              <!-- Title -->
              <div>
                <label
                  for="edit-letter-title"
                  class="mb-2 block text-sm font-medium text-slate-700"
                >
                  Cover Letter Title
                </label>

                <input
                  id="edit-letter-title"
                  v-model="editTitle"
                  type="text"
                  maxlength="255"
                  required
                  :disabled="saving"
                  class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50"
                  placeholder="Enter a title"
                />
              </div>

              <!-- Content -->
              <div>
                <label
                  for="edit-letter-content"
                  class="mb-2 block text-sm font-medium text-slate-700"
                >
                  Cover Letter Content
                </label>

                <textarea
                  id="edit-letter-content"
                  v-model="editContent"
                  rows="16"
                  required
                  :disabled="saving"
                  class="w-full resize-y rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm leading-7 text-slate-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:bg-slate-50"
                  placeholder="Edit your cover letter..."
                />
              </div>

              <!-- Error -->
              <div
                v-if="editError"
                role="alert"
                class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
              >
                <CircleAlert :size="18" class="mt-0.5 shrink-0" />
                <span>{{ editError }}</span>
              </div>

              <!-- Reminder -->
              <div class="rounded-xl bg-indigo-50 px-4 py-3 text-sm leading-6 text-indigo-700">
                Make sure the information in your cover letter accurately
                reflects your experience and qualifications.
              </div>
            </form>

            <!-- ================================= -->
            <!-- View mode                         -->
            <!-- ================================= -->

            <div v-else>

              <div class="mb-6 flex flex-wrap items-center gap-4 border-b border-slate-100 pb-5 text-sm text-slate-500">

                <span class="flex items-center gap-2">
                  <CalendarDays :size="16" />
                  {{ formatDate(selectedLetter.created_at) }}
                </span>

                <span class="flex items-center gap-2">
                  <Sparkles :size="16" />
                  {{ selectedLetter.model_used }}
                </span>
              </div>

              <div
                class="whitespace-pre-wrap break-words text-sm leading-8 text-slate-800"
              >
                {{ selectedLetter.content }}
              </div>

              <p class="mt-8 border-t border-slate-100 pt-4 text-xs text-slate-400">
                AI-generated content may contain inaccuracies.
                Review and personalize this letter before submitting it.
              </p>

            </div>
          </div>

          <div
            v-if="letterError && selectedLetter && !isEditing"
            role="alert"
            class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
          >
            {{ letterError }}
          </div>
        </div>

        <!-- ===================================== -->
        <!-- Modal footer                          -->
        <!-- ===================================== -->

        <div class="flex shrink-0 flex-wrap justify-end gap-3 border-t border-slate-100 px-6 py-5">

          <!-- Edit mode buttons -->
          <template v-if="isEditing">

            <button
              type="button"
              :disabled="saving"
              class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
              @click="cancelEditing"
            >
              Cancel
            </button>

            <button
              type="submit"
              form="edit-cover-letter-form"
              :disabled="saving || !editTitle.trim() || !editContent.trim()"
              class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <LoaderCircle
                v-if="saving"
                :size="18"
                class="animate-spin"
              />

              <Save
                v-else
                :size="18"
              />

              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>

          </template>

          <!-- View mode buttons -->
          <template v-else>

            <!-- Close -->
            <button
              type="button"
              :disabled="loadingLetter || downloadingPdf"
              class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
              @click="closeLetterModal"
            >
              Close
            </button>

            <!-- Edit Letter -->
            <button
              type="button"
              :disabled="loadingLetter || !selectedLetter || downloadingPdf"
              class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-5 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-50"
              @click="startEditing"
            >
              <Pencil :size="18" />
              Edit Letter
            </button>

            <!-- Copy Letter -->
            <button
              type="button"
              :disabled="loadingLetter || !selectedLetter || copying || downloadingPdf"
              class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
              @click="copyLetter"
            >
              <CheckCircle2
                v-if="copied"
                :size="18"
              />

              <Copy
                v-else
                :size="18"
              />

              {{ copied ? 'Copied!' : 'Copy Letter' }}
            </button>

            <!-- Download PDF -->
            <button
              type="button"
              :disabled="loadingLetter || !selectedLetter || downloadingPdf"
              class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
              @click="downloadCoverLetterPdf"
            >
              <LoaderCircle
                v-if="downloadingPdf"
                :size="18"
                class="animate-spin"
              />

              <Download
                v-else
                :size="18"
              />

              {{ downloadingPdf ? 'Downloading...' : 'Download PDF' }}
            </button>

          </template>

        </div>
      </div>
    </div>

    <!-- ========================================= -->
    <!-- Delete Confirmation Modal                -->
    <!-- ========================================= -->

    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 p-4"
      @click.self="cancelDelete"
    >
      <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="delete-modal-title"
        aria-describedby="delete-modal-description"
        class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
      >
        <div
          class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50"
        >
          <Trash2 :size="24" class="text-red-600" />
        </div>

        <h2
          id="delete-modal-title"
          class="mt-5 text-xl font-semibold text-slate-900"
        >
          Delete Cover Letter?
        </h2>

        <p
          id="delete-modal-description"
          class="mt-3 text-sm leading-6 text-slate-600"
        >
          Are you sure you want to delete
          <span class="font-semibold text-slate-900">
            {{ letterToDelete?.title }}
          </span>?
        </p>

        <p class="mt-2 text-sm font-medium text-red-600">
          This action cannot be undone.
        </p>

        <div class="mt-7 flex justify-end gap-3">
          <button
            type="button"
            :disabled="deletingLetterId !== null"
            class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            @click="cancelDelete"
          >
            Cancel
          </button>

          <button
            type="button"
            :disabled="deletingLetterId !== null"
            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="deleteCoverLetter"
          >
            <LoaderCircle
              v-if="deletingLetterId !== null"
              :size="18"
              class="animate-spin"
            />

            <Trash2
              v-else
              :size="18"
            />

            {{
              deletingLetterId !== null
                ? 'Deleting...'
                : 'Delete Letter'
            }}
          </button>
        </div>
      </div>
    </div>

  </DashboardLayout>
</template>