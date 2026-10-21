<script setup lang="ts">
import { onMounted, ref } from 'vue'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  BriefcaseBusiness,
  Plus,
  MapPin,
  Building2,
  CircleAlert,
  CircleCheck,
  CalendarDays,
  Pencil,
  Trash2,
  X,
  Save,
  ExternalLink,
} from '@lucide/vue'

// --------------------------------------------------
// Interfaces
// --------------------------------------------------

interface JobDescription {
  id: number
  title: string
  company_name: string
  location: string | null
  description: string
  source_url: string | null
  created_at: string
}

interface JobForm {
  title: string
  company_name: string
  location: string
  description: string
  source_url: string
}

// --------------------------------------------------
// State
// --------------------------------------------------

const jobs = ref<JobDescription[]>([])

const loading = ref(false)
const error = ref<string | null>(null)

const successMessage = ref<string | null>(null)

// Add/Edit modal
const showJobModal = ref(false)
const editingJob = ref<JobDescription | null>(null)
const saving = ref(false)
const formError = ref<string | null>(null)

// Delete modal
const jobToDelete = ref<JobDescription | null>(null)
const deleting = ref(false)
const deleteError = ref<string | null>(null)

// Job form
const jobForm = ref<JobForm>({
  title: '',
  company_name: '',
  location: '',
  description: '',
  source_url: '',
})

// --------------------------------------------------
// Fetch jobs
// --------------------------------------------------

async function fetchJobs() {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<{
      job_descriptions: JobDescription[]
    }>('/api/job-descriptions')

    jobs.value = response.data.job_descriptions ?? []
  } catch (err) {
    jobs.value = []

    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to retrieve your job descriptions.'
    } else {
      error.value = 'An unexpected error occurred.'
    }
  } finally {
    loading.value = false
  }
}

// --------------------------------------------------
// Add/Edit Job
// --------------------------------------------------

function resetJobForm() {
  jobForm.value = {
    title: '',
    company_name: '',
    location: '',
    description: '',
    source_url: '',
  }
}

function openAddJobModal() {
  editingJob.value = null

  resetJobForm()

  formError.value = null
  successMessage.value = null

  showJobModal.value = true
}

function openEditJobModal(job: JobDescription) {
  editingJob.value = job

  jobForm.value = {
    title: job.title,
    company_name: job.company_name,
    location: job.location ?? '',
    description: job.description,
    source_url: job.source_url ?? '',
  }

  formError.value = null
  successMessage.value = null

  showJobModal.value = true
}

function closeJobModal() {
  if (saving.value) return

  showJobModal.value = false
  editingJob.value = null

  formError.value = null

  resetJobForm()
}

async function saveJob() {
  if (saving.value) return

  saving.value = true
  formError.value = null

  try {
    const payload = {
      title: jobForm.value.title.trim(),
      company_name: jobForm.value.company_name.trim(),
      location: jobForm.value.location.trim() || null,
      description: jobForm.value.description.trim(),
      source_url: jobForm.value.source_url.trim() || null,
    }

    if (editingJob.value) {
      await api.put(
        `/api/job-descriptions/${editingJob.value.id}`,
        payload,
      )

      successMessage.value =
        'Job description updated successfully.'
    } else {
      await api.post('/api/job-descriptions', payload)

      successMessage.value =
        'Job description added successfully.'
    }

    showJobModal.value = false
    editingJob.value = null

    resetJobForm()

    await fetchJobs()
  } catch (err) {
    if (axios.isAxiosError(err)) {
      formError.value =
        err.response?.data?.message ||
        'Unable to save this job description.'
    } else {
      formError.value = 'An unexpected error occurred.'
    }
  } finally {
    saving.value = false
  }
}

// --------------------------------------------------
// Delete Job
// --------------------------------------------------

function openDeleteModal(job: JobDescription) {
  jobToDelete.value = job

  deleteError.value = null
  successMessage.value = null
}

function closeDeleteModal() {
  if (deleting.value) return

  jobToDelete.value = null
  deleteError.value = null
}

async function confirmDeleteJob() {
  if (!jobToDelete.value || deleting.value) return

  deleting.value = true
  deleteError.value = null

  const job = jobToDelete.value

  try {
    await api.delete(`/api/job-descriptions/${job.id}`)

    jobToDelete.value = null

    successMessage.value =
      `${job.title} deleted successfully.`

    await fetchJobs()
  } catch (err) {
    if (axios.isAxiosError(err)) {
      deleteError.value =
        err.response?.data?.message ||
        'Unable to delete this job description.'
    } else {
      deleteError.value = 'An unexpected error occurred.'
    }
  } finally {
    deleting.value = false
  }
}

// --------------------------------------------------
// Helpers
// --------------------------------------------------

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

// --------------------------------------------------
// Lifecycle
// --------------------------------------------------

onMounted(() => {
  fetchJobs()
})
</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">

      <!-- Page heading -->
      <section class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">
            Job Descriptions
          </h1>

          <p class="mt-2 text-slate-500">
            Save and manage job opportunities you want to apply for.
          </p>
        </div>

        <button
          type="button"
          class="flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition-colors hover:bg-indigo-700"
          @click="openAddJobModal"
        >
          <Plus :size="18" />
          Add Job
        </button>
      </section>

      <!-- Success message -->
      <div
        v-if="successMessage"
        role="status"
        class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700"
      >
        <CircleCheck :size="20" />
        {{ successMessage }}
      </div>

      <!-- Loading -->
      <section
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-12 text-center shadow-sm"
      >
        <p class="text-slate-500">
          Loading your job descriptions...
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
          @click="fetchJobs"
        >
          Try again
        </button>
      </section>

      <!-- Empty state -->
      <section
        v-else-if="jobs.length === 0"
        class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      >
        <div class="flex flex-col items-center justify-center py-12 text-center">
          <div
            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50"
          >
            <BriefcaseBusiness
              :size="32"
              :stroke-width="1.6"
              class="text-blue-600"
            />
          </div>

          <h2 class="mt-6 text-xl font-semibold text-slate-900">
            Manage Job Opportunities
          </h2>

          <p class="mt-3 max-w-md text-slate-500">
            Your saved job descriptions will appear here.
            Add opportunities you're interested in and compare
            their requirements with your CV.
          </p>

          <div
            class="mt-8 flex flex-wrap items-center justify-center gap-6 text-sm text-slate-400"
          >
            <div class="flex items-center gap-2">
              <BriefcaseBusiness :size="16" />
              <span>Job title</span>
            </div>

            <div class="flex items-center gap-2">
              <Building2 :size="16" />
              <span>Company</span>
            </div>

            <div class="flex items-center gap-2">
              <MapPin :size="16" />
              <span>Location</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Saved jobs -->
      <section
        v-else
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-6 py-5">
          <h2 class="text-lg font-semibold text-slate-900">
            Saved Job Descriptions
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            {{ jobs.length }}
            job{{ jobs.length === 1 ? '' : 's' }} in your account
          </p>
        </div>

        <div class="divide-y divide-slate-100">
          <div
            v-for="job in jobs"
            :key="job.id"
            class="px-6 py-5"
          >
            <div class="flex flex-wrap items-start justify-between gap-4">

              <!-- Job information -->
              <div class="flex min-w-0 items-start gap-4">
                <div
                  class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-50"
                >
                  <BriefcaseBusiness
                    :size="24"
                    class="text-blue-600"
                  />
                </div>

                <div class="min-w-0">
                  <h3 class="text-lg font-semibold text-slate-900">
                    {{ job.title }}
                  </h3>

                  <div class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                    <Building2 :size="16" />
                    {{ job.company_name }}
                  </div>

                  <div
                    v-if="job.location"
                    class="mt-2 flex items-center gap-2 text-sm text-slate-500"
                  >
                    <MapPin :size="16" />
                    {{ job.location }}
                  </div>

                  <div class="mt-2 flex items-center gap-2 text-sm text-slate-400">
                    <CalendarDays :size="16" />
                    Added {{ formatDate(job.created_at) }}
                  </div>

                  <a
                    v-if="job.source_url"
                    :href="job.source_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                  >
                    View Job Posting
                    <ExternalLink :size="14" />
                  </a>
                </div>
              </div>

              <!-- Action buttons -->
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                  @click="openEditJobModal(job)"
                >
                  <Pencil :size="16" />
                  Edit
                </button>

                <button
                  type="button"
                  class="flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50"
                  @click="openDeleteModal(job)"
                >
                  <Trash2 :size="16" />
                  Delete
                </button>
              </div>
            </div>

            <!-- Description preview -->
            <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">
              {{ job.description }}
            </p>
          </div>
        </div>
      </section>
    </div>

    <!-- ======================================= -->
    <!-- ADD / EDIT JOB MODAL -->
    <!-- ======================================= -->

    <div
      v-if="showJobModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="job-modal-title"
        class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl"
      >
        <!-- Modal header -->
        <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5">
          <div>
            <h2
              id="job-modal-title"
              class="text-xl font-semibold text-slate-900"
            >
              {{ editingJob ? 'Edit Job Description' : 'Add Job Description' }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
              {{
                editingJob
                  ? 'Update the details of this job opportunity.'
                  : 'Save a job opportunity to analyze it against your CV.'
              }}
            </p>
          </div>

          <button
            type="button"
            :disabled="saving"
            class="text-slate-400 hover:text-slate-600 disabled:opacity-50"
            aria-label="Close modal"
            @click="closeJobModal"
          >
            <X :size="22" />
          </button>
        </div>

        <!-- Form -->
        <form
          id="job-form"
          class="space-y-5 overflow-y-auto px-6 py-5"
          @submit.prevent="saveJob"
        >
          <!-- Error -->
          <div
            v-if="formError"
            role="alert"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
          >
            {{ formError }}
          </div>

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
              v-model="jobForm.title"
              type="text"
              required
              maxlength="255"
              placeholder="e.g. Junior AI Engineer"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
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
              v-model="jobForm.company_name"
              type="text"
              required
              maxlength="255"
              placeholder="e.g. Tech Innovations"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <!-- Location -->
          <div>
            <label
              for="job-location"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Location
            </label>

            <input
              id="job-location"
              v-model="jobForm.location"
              type="text"
              maxlength="255"
              placeholder="e.g. Antwerp, Belgium"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>

          <!-- Description -->
          <div>
            <label
              for="job-description"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Job Description <span class="text-red-500">*</span>
            </label>

            <textarea
              id="job-description"
              v-model="jobForm.description"
              required
              rows="7"
              placeholder="Paste the full job description here..."
              class="w-full resize-y rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            ></textarea>

            <p class="mt-2 text-xs text-slate-400">
              Include responsibilities, required skills, and qualifications for a better match analysis.
            </p>
          </div>

          <!-- URL -->
          <div>
            <label
              for="job-url"
              class="mb-2 block text-sm font-medium text-slate-700"
            >
              Job Posting URL
            </label>

            <input
              id="job-url"
              v-model="jobForm.source_url"
              type="url"
              placeholder="https://example.com/careers/job"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </div>
        </form>

        <!-- Modal footer -->
        <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-5">
          <button
            type="button"
            :disabled="saving"
            class="rounded-lg border border-slate-200 px-4 py-2 font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            @click="closeJobModal"
          >
            Cancel
          </button>

          <button
            type="submit"
            form="job-form"
            :disabled="saving"
            class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <Save :size="16" />
            {{
              saving
                ? 'Saving...'
                : editingJob
                  ? 'Save Changes'
                  : 'Save Job'
            }}
          </button>
        </div>
      </div>
    </div>

    <!-- ======================================= -->
    <!-- DELETE JOB CONFIRMATION MODAL -->
    <!-- ======================================= -->

    <div
      v-if="jobToDelete"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="delete-job-title"
        class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
      >
        <!-- Icon -->
        <div
          class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-50"
        >
          <Trash2 :size="24" class="text-red-600" />
        </div>

        <!-- Title -->
        <h2
          id="delete-job-title"
          class="mt-5 text-xl font-semibold text-slate-900"
        >
          Delete Job?
        </h2>

        <!-- Description -->
        <p class="mt-3 text-sm leading-6 text-slate-600">
          Are you sure you want to delete
          <strong>{{ jobToDelete.title }}</strong>?
          This action cannot be undone.
        </p>

        <p class="mt-2 text-sm text-slate-500">
          Jobs linked to existing match analyses cannot be deleted.
        </p>

        <!-- Error -->
        <div
          v-if="deleteError"
          role="alert"
          class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
        >
          {{ deleteError }}
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end gap-3">
          <button
            type="button"
            :disabled="deleting"
            class="rounded-lg border border-slate-200 px-4 py-2 font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
            @click="closeDeleteModal"
          >
            Cancel
          </button>

          <button
            type="button"
            :disabled="deleting"
            class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="confirmDeleteJob"
          >
            <Trash2 :size="16" />
            {{ deleting ? 'Deleting...' : 'Delete Job' }}
          </button>
        </div>
      </div>
    </div>

  </DashboardLayout>
</template>