<script setup lang="ts">
import { onMounted, ref, useTemplateRef } from 'vue'
import axios from 'axios'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  FileText,
  Upload,
  CalendarDays,
  CircleCheck,
  Clock3,
  CircleAlert,
  RefreshCw,
  Play,
  Trash2,
} from '@lucide/vue'

interface Cv {
  id: number
  original_filename: string
  status: string
  created_at: string
  file_size: number
}

const cvs = ref<Cv[]>([])
const loading = ref(false)
const error = ref<string | null>(null)

const fileInput = useTemplateRef<HTMLInputElement>('fileInput')

const uploading = ref(false)
const uploadError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const processingCvId = ref<number | null>(null)
const deletingCvId = ref<number | null>(null)

// CV selected for deletion
const cvToDelete = ref<Cv | null>(null)

function openFilePicker() {
  fileInput.value?.click()
}

function getErrorMessage(err: unknown, fallback: string): string {
  if (axios.isAxiosError(err)) {
    return err.response?.data?.message || fallback
  }

  return fallback
}

// Upload CV
async function handleFileSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  uploadError.value = null
  successMessage.value = null

  if (
    file.type !== 'application/pdf' &&
    !file.name.toLowerCase().endsWith('.pdf')
  ) {
    uploadError.value = 'Please select a PDF file.'
    input.value = ''
    return
  }

  if (file.size > 10 * 1024 * 1024) {
    uploadError.value = 'The PDF must not exceed 10 MB.'
    input.value = ''
    return
  }

  uploading.value = true

  try {
    const formData = new FormData()
    formData.append('cv', file)

    await api.post('/api/cvs', formData)

    await fetchCvs()

    successMessage.value =
      `${file.name} uploaded successfully. You can now process it for analysis.`
  } catch (err) {
    uploadError.value = getErrorMessage(
      err,
      'Failed to upload CV. Please try again.',
    )
  } finally {
    uploading.value = false
    input.value = ''
  }
}

// Fetch CVs
async function fetchCvs() {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<{ cvs: Cv[] }>('/api/cvs')
    cvs.value = response.data.cvs
  } catch (err) {
    error.value = getErrorMessage(err, 'Unable to retrieve your CVs.')
  } finally {
    loading.value = false
  }
}

// Process CV
async function processCv(cv: Cv) {
  if (processingCvId.value !== null || deletingCvId.value !== null) return

  processingCvId.value = cv.id
  uploadError.value = null
  successMessage.value = null

  try {
    await api.post(`/api/cvs/${cv.id}/process`)

    await fetchCvs()

    successMessage.value =
      `${cv.original_filename} processed successfully and is ready for analysis.`
  } catch (err) {
    uploadError.value = getErrorMessage(
      err,
      `Unable to process ${cv.original_filename}. Please try again.`,
    )

    await fetchCvs()
  } finally {
    processingCvId.value = null
  }
}

// Open delete confirmation dialog
function confirmDelete(cv: Cv) {
  uploadError.value = null
  successMessage.value = null
  cvToDelete.value = cv
}

// Close confirmation dialog
function cancelDelete() {
  if (deletingCvId.value !== null) return

  cvToDelete.value = null
}

// Delete CV
async function deleteCv() {
  if (!cvToDelete.value || deletingCvId.value !== null) return

  const cv = cvToDelete.value

  deletingCvId.value = cv.id
  uploadError.value = null
  successMessage.value = null

  try {
    await api.delete(`/api/cvs/${cv.id}`)

    cvToDelete.value = null

    await fetchCvs()

    successMessage.value = `${cv.original_filename} deleted successfully.`
  } catch (err) {
    uploadError.value = getErrorMessage(
      err,
      `Unable to delete ${cv.original_filename}. Please try again.`,
    )

    cvToDelete.value = null
  } finally {
    deletingCvId.value = null
  }
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function formatFileSize(bytes: number) {
  return `${(bytes / (1024 * 1024)).toFixed(2)} MB`
}

onMounted(() => {
  fetchCvs()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">
      <!-- Page heading -->
      <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">
            My CVs
          </h1>

          <p class="mt-2 text-slate-500">
            Upload and manage your CVs for job matching and AI-powered applications.
          </p>
        </div>

        <button
          type="button"
          :disabled="uploading"
          class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
          @click="openFilePicker"
        >
          <Upload :size="18" />
          {{ uploading ? 'Uploading...' : 'Upload CV' }}
        </button>

        <input
          ref="fileInput"
          type="file"
          accept=".pdf,application/pdf"
          class="hidden"
          @change="handleFileSelected"
        />
      </section>

      <!-- Error message -->
      <div
        v-if="uploadError"
        role="alert"
        class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700"
      >
        <CircleAlert :size="20" class="shrink-0" />
        {{ uploadError }}
      </div>

      <!-- Success message -->
      <div
        v-if="successMessage"
        role="status"
        class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
      >
        <CircleCheck :size="20" class="shrink-0" />
        {{ successMessage }}
      </div>

      <!-- Loading state -->
      <section
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <p class="text-slate-500">Loading your CVs...</p>
      </section>

      <!-- Error state -->
      <section
        v-else-if="error"
        class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center"
      >
        <CircleAlert :size="32" class="mx-auto text-red-500" />

        <p class="mt-3 font-medium text-red-700">
          {{ error }}
        </p>

        <button
          type="button"
          class="mt-4 font-medium text-red-700 underline"
          @click="fetchCvs"
        >
          Try again
        </button>
      </section>

      <!-- Empty state -->
      <section
        v-else-if="cvs.length === 0"
        class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      >
        <div class="flex flex-col items-center justify-center py-12 text-center">
          <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50"
          >
            <FileText
              :size="32"
              :stroke-width="1.6"
              class="text-indigo-600"
            />
          </div>

          <h2 class="mt-6 text-xl font-semibold text-slate-900">
            Manage Your CVs
          </h2>

          <p class="mt-3 max-w-md text-slate-500">
            Your uploaded CVs will appear here. You can upload PDF documents,
            process them for analysis, and manage your existing CVs.
          </p>

          <p class="mt-4 text-sm text-slate-400">
            Supported format: PDF · Maximum file size: 10 MB
          </p>
        </div>
      </section>

      <!-- CV list -->
      <section
        v-else
        class="rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">
            Uploaded CVs
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            {{ cvs.length }} CV{{ cvs.length === 1 ? '' : 's' }} in your account
          </p>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="cv in cvs"
            :key="cv.id"
            class="flex flex-wrap items-center justify-between gap-4 px-6 py-5"
          >
            <!-- CV details -->
            <div class="flex min-w-0 items-center gap-4">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-50"
              >
                <FileText :size="24" class="text-indigo-600" />
              </div>

              <div class="min-w-0">
                <h3 class="truncate font-semibold text-slate-900">
                  {{ cv.original_filename }}
                </h3>

                <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                  <span class="flex items-center gap-1">
                    <CalendarDays :size="14" />
                    {{ formatDate(cv.created_at) }}
                  </span>

                  <span>{{ formatFileSize(cv.file_size) }}</span>
                </div>
              </div>
            </div>

            <!-- CV status and actions -->
            <div class="flex flex-wrap items-center gap-3">
              <!-- Processed -->
              <div
                v-if="cv.status === 'processed'"
                class="flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700"
              >
                <CircleCheck :size="16" />
                Processed
              </div>

              <!-- Uploaded -->
              <template v-else-if="cv.status === 'uploaded'">
                <div
                  class="flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700"
                >
                  <Clock3 :size="16" />
                  Uploaded
                </div>

                <button
                  type="button"
                  :disabled="processingCvId !== null || deletingCvId !== null"
                  class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
                  @click="processCv(cv)"
                >
                  <RefreshCw
                    v-if="processingCvId === cv.id"
                    :size="16"
                    class="animate-spin"
                  />
                  <Play v-else :size="16" />

                  {{ processingCvId === cv.id ? 'Processing...' : 'Process CV' }}
                </button>
              </template>

              <!-- Failed -->
              <template v-else-if="cv.status === 'failed'">
                <div
                  class="flex items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700"
                >
                  <CircleAlert :size="16" />
                  Failed
                </div>

                <button
                  type="button"
                  :disabled="processingCvId !== null || deletingCvId !== null"
                  class="flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                  @click="processCv(cv)"
                >
                  <RefreshCw
                    :size="16"
                    :class="{ 'animate-spin': processingCvId === cv.id }"
                  />

                  {{ processingCvId === cv.id ? 'Processing...' : 'Retry Processing' }}
                </button>
              </template>

              <!-- Other statuses -->
              <div
                v-else
                class="flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600"
              >
                <Clock3 :size="16" />
                {{ cv.status }}
              </div>

              <!-- Delete button -->
              <button
                type="button"
                :disabled="processingCvId !== null || deletingCvId !== null"
                class="flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                :aria-label="`Delete ${cv.original_filename}`"
                @click="confirmDelete(cv)"
              >
                <Trash2 :size="16" />
                Delete
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Delete confirmation dialog -->
    <div
      v-if="cvToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4"
    >
      <div
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="delete-cv-title"
        aria-describedby="delete-cv-description"
        class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
      >
        <div
          class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50"
        >
          <Trash2 :size="24" class="text-red-600" />
        </div>

        <h2
          id="delete-cv-title"
          class="mt-4 text-xl font-semibold text-slate-900"
        >
          Delete CV?
        </h2>

        <p
          id="delete-cv-description"
          class="mt-3 text-sm leading-6 text-slate-600"
        >
          Are you sure you want to delete
          <strong>{{ cvToDelete.original_filename }}</strong>?
          This action cannot be undone.
        </p>

        <p class="mt-2 text-sm text-slate-500">
          CVs linked to existing match analyses cannot be deleted.
        </p>

        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            :disabled="deletingCvId !== null"
            class="rounded-lg border border-slate-200 px-4 py-2 font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            @click="cancelDelete"
          >
            Cancel
          </button>

          <button
            type="button"
            :disabled="deletingCvId !== null"
            class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
            @click="deleteCv"
          >
            <RefreshCw
              v-if="deletingCvId !== null"
              :size="16"
              class="animate-spin"
            />
            <Trash2 v-else :size="16" />

            {{ deletingCvId !== null ? 'Deleting...' : 'Delete CV' }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>