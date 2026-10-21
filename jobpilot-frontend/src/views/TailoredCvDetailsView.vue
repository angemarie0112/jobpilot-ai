<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  ArrowLeft,
  FileUser,
  Building2,
  CalendarDays,
  Sparkles,
  LoaderCircle,
  CircleAlert,
  CheckCircle2,
  Mail,
  Phone,
  MapPin,
  Link,
  Code2,
  BriefcaseBusiness,
  GraduationCap,
  FolderKanban,
  Award,
  Pencil,
  Save,
  X,
  Plus,
  Trash2,
  Download,
} from '@lucide/vue'

// ------------------------------------
// Types
// ------------------------------------

interface Contact {
  full_name?: string
  email?: string
  phone?: string
  location?: string
  linkedin?: string
  github?: string
}

interface Experience {
  job_title?: string
  company?: string
  location?: string
  start_date?: string
  end_date?: string
  responsibilities: string[]
}

interface Education {
  degree?: string
  institution?: string
  location?: string
  start_date?: string
  end_date?: string
}

interface Project {
  name?: string
  description?: string
  technologies: string[]
}

interface CvContent {
  contact: Contact
  professional_summary: string
  skills: string[]
  experience: Experience[]
  education: Education[]
  projects: Project[]
  certifications: string[]
}

interface JobDescription {
  title: string
  company_name: string
}

interface TailoredCv {
  id: number
  title: string
  content: CvContent
  model_used: string | null
  created_at: string
  analysis?: {
    job_description?: JobDescription | null
  } | null
}

interface TailoredCvResponse {
  message: string
  tailored_cv: TailoredCv
}

// ------------------------------------
// State
// ------------------------------------

const route = useRoute()
const router = useRouter()

const tailoredCv = ref<TailoredCv | null>(null)

const loading = ref(true)
const error = ref<string | null>(null)

const isEditing = ref(false)
const saving = ref(false)

const editError = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const editTitle = ref('')
const editContent = ref<CvContent | null>(null)

const cvId = computed(() => Number(route.params.id))

const content = computed(() => tailoredCv.value?.content)

const contact = computed(() => content.value?.contact ?? {})

const downloadingPdf = ref(false)
const downloadError = ref<string | null>(null)

// ------------------------------------
// Fetch tailored CV
// ------------------------------------

async function fetchTailoredCv() {
  loading.value = true
  error.value = null

  if (!Number.isSafeInteger(cvId.value) || cvId.value < 1) {
    error.value = 'Invalid tailored CV ID.'
    loading.value = false
    return
  }

  try {
    const response = await api.get<TailoredCvResponse>(
      `/api/tailored-cvs/${cvId.value}`,
    )

    tailoredCv.value = response.data.tailored_cv
  } catch (err) {
    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to load this tailored CV.'
    } else {
      error.value =
        'An unexpected error occurred while loading the CV.'
    }
  } finally {
    loading.value = false
  }
}

// ------------------------------------
// Start editing
// ------------------------------------

function startEditing() {
  if (!tailoredCv.value) return

  // Copy the CV title
  editTitle.value = tailoredCv.value.title

  // Create an independent copy of the CV content.
  // JSON serialization removes Vue's reactive proxies.
  editContent.value = JSON.parse(
    JSON.stringify(tailoredCv.value.content),
  ) as CvContent

  // Clear previous errors
  editError.value = null

  // Activate editing mode
  isEditing.value = true
}

// ------------------------------------
// Cancel editing
// ------------------------------------

function cancelEditing() {
  if (saving.value) return

  isEditing.value = false
  editContent.value = null
  editTitle.value = ''
  editError.value = null
}

// ------------------------------------
// Save changes
// ------------------------------------

async function saveTailoredCv() {
  if (
    !tailoredCv.value ||
    !editContent.value ||
    saving.value
  ) {
    return
  }

  if (!editTitle.value.trim()) {
    editError.value = 'Please enter a CV title.'
    return
  }

  saving.value = true
  editError.value = null

  try {
    // Remove empty entries from editable lists.
    const cleanedContent = JSON.parse(JSON.stringify(editContent.value),) as CvContent

    cleanedContent.skills = cleanedContent.skills
      .map((skill) => skill.trim())
      .filter(Boolean)

    cleanedContent.certifications = cleanedContent.certifications
      .map((certification) => certification.trim())
      .filter(Boolean)

    cleanedContent.experience = cleanedContent.experience.map((item) => ({
      ...item,
      responsibilities: item.responsibilities
        .map((responsibility) => responsibility.trim())
        .filter(Boolean),
    }))

    cleanedContent.projects = cleanedContent.projects.map((item) => ({
      ...item,
      technologies: item.technologies
        .map((technology) => technology.trim())
        .filter(Boolean),
    }))

    const response = await api.put<TailoredCvResponse>(
      `/api/tailored-cvs/${tailoredCv.value.id}`,
      {
        title: editTitle.value.trim(),
        content: cleanedContent,
      },
    )

    // Update the preview with the saved database record.
    tailoredCv.value = response.data.tailored_cv

    isEditing.value = false
    editContent.value = null
    editTitle.value = ''

    successMessage.value = 'Your tailored CV was updated successfully.'
  } catch (err) {
    if (axios.isAxiosError(err)) {
      editError.value =
        err.response?.data?.message ||
        'Unable to save your tailored CV.'
    } else {
      editError.value =
        'An unexpected error occurred while saving your CV.'
    }
  } finally {
    saving.value = false
  }
}

// ------------------------------------
// Editable list helpers
// ------------------------------------

function addSkill() {
  editContent.value?.skills.push('')
}

function removeSkill(index: number) {
  editContent.value?.skills.splice(index, 1)
}

function addCertification() {
  editContent.value?.certifications.push('')
}

function removeCertification(index: number) {
  editContent.value?.certifications.splice(index, 1)
}

function addExperience() {
  editContent.value?.experience.push({
    job_title: '',
    company: '',
    location: '',
    start_date: '',
    end_date: '',
    responsibilities: [''],
  })
}

function removeExperience(index: number) {
  editContent.value?.experience.splice(index, 1)
}

function addResponsibility(index: number) {
  editContent.value?.experience[index]?.responsibilities.push('')
}

function removeResponsibility(
  experienceIndex: number,
  responsibilityIndex: number,
) {
  editContent.value?.experience[experienceIndex]?.responsibilities.splice(
    responsibilityIndex,
    1,
  )
}

function addEducation() {
  editContent.value?.education.push({
    degree: '',
    institution: '',
    location: '',
    start_date: '',
    end_date: '',
  })
}

function removeEducation(index: number) {
  editContent.value?.education.splice(index, 1)
}

function addProject() {
  editContent.value?.projects.push({
    name: '',
    description: '',
    technologies: [''],
  })
}

function removeProject(index: number) {
  editContent.value?.projects.splice(index, 1)
}

function addTechnology(index: number) {
  editContent.value?.projects[index]?.technologies.push('')
}

function removeTechnology(
  projectIndex: number,
  technologyIndex: number,
) {
  editContent.value?.projects[projectIndex]?.technologies.splice(
    technologyIndex,
    1,
  )
}

// ------------------------------------
// Helpers
// ------------------------------------

function goBack() {
  router.push('/tailored-cvs')
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function dateRange(start?: string, end?: string) {
  return [start, end].filter(Boolean).join(' – ')
}

// ------------------------------------
// Download tailored CV as PDF
// ------------------------------------

async function downloadTailoredCvPdf() {
  if (!tailoredCv.value || downloadingPdf.value || isEditing.value) {
    return
  }

  downloadingPdf.value = true
  downloadError.value = null

  try {
    const cvId = tailoredCv.value.id

    // Request the generated PDF from Laravel.
    const response = await api.get<Blob>(
      `/api/tailored-cvs/${cvId}/download`,
      {
        responseType: 'blob',
      },
    )

    // Ensure the server returned a PDF.
    const contentType = response.headers['content-type']

    if (
      typeof contentType !== 'string' ||
      !contentType.includes('application/pdf')
    ) {
      throw new Error('The server did not return a PDF file.')
    }

    // Create a temporary browser URL for the PDF.
    const blob = new Blob([response.data], {
      type: 'application/pdf',
    })

    const url = window.URL.createObjectURL(blob)

    // Create a temporary download link.
    const link = document.createElement('a')
    link.href = url
    link.download = `tailored-cv-${cvId}.pdf`

    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    // Release the temporary URL.
    window.setTimeout(() => {
      window.URL.revokeObjectURL(url)
    }, 1000)

  } catch (err) {
    if (axios.isAxiosError(err)) {
      downloadError.value =
        'Unable to download your tailored CV. Please try again.'
    } else {
      downloadError.value =
        'An unexpected error occurred while downloading the PDF.'
    }
  } finally {
    downloadingPdf.value = false
  }
}

onMounted(fetchTailoredCv)
</script>

<template>
  <DashboardLayout>
    <div class="mx-auto max-w-5xl space-y-6">

      <!-- Back navigation -->
      <button
        type="button"
        :disabled="saving"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-indigo-600 disabled:opacity-50"
        @click="goBack"
      >
        <ArrowLeft :size="18" />
        Back to Tailored CVs
      </button>

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
          Loading your tailored CV...
        </p>
      </section>

      <!-- Error -->
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
          @click="fetchTailoredCv"
        >
          Try again
        </button>
      </section>

      <template v-else-if="tailoredCv && content">

        <!-- Page heading -->
        <section class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <div class="mb-3 flex items-center gap-2 text-sm font-medium text-indigo-600">
              <FileUser :size="18" />
              AI-Tailored CV
            </div>

            <h1 class="text-3xl font-bold text-slate-900">
              {{ tailoredCv.title }}
            </h1>

            <div class="mt-4 flex flex-wrap gap-5 text-sm text-slate-500">
              <span
                v-if="tailoredCv.analysis?.job_description"
                class="inline-flex items-center gap-2"
              >
                <Building2 :size="16" />
                {{ tailoredCv.analysis.job_description.company_name }}
              </span>

              <span class="inline-flex items-center gap-2">
                <CalendarDays :size="16" />
                {{ formatDate(tailoredCv.created_at) }}
              </span>

              <span
                v-if="tailoredCv.model_used"
                class="inline-flex items-center gap-2"
              >
                <Sparkles :size="16" />
                {{ tailoredCv.model_used }}
              </span>
            </div>
          </div>

          <!-- Edit button -->
          <div
              v-if="!isEditing"
              class="flex flex-wrap items-center gap-3"
            >
              <!-- Edit CV -->
              <button
                type="button"
                :disabled="downloadingPdf"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-700 disabled:opacity-50"
                @click="startEditing"
              >
                <Pencil :size="18" />
                Edit CV
              </button>

              <!-- Download PDF -->
              <button
                type="button"
                :disabled="downloadingPdf"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                @click="downloadTailoredCvPdf"
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
          </div>
        </section>

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

          <!-- PDF download error -->
          <div
            v-if="downloadError"
            role="alert"
            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700"
          >
            <CircleAlert :size="20" class="shrink-0" />

            <span>{{ downloadError }}</span>

            <button
              type="button"
              class="ml-auto rounded-lg p-1 hover:bg-red-100"
              aria-label="Dismiss download error"
              @click="downloadError = null"
            >
              <X :size="18" />
            </button>
        </div>

        <!-- ================================= -->
        <!-- EDIT MODE                         -->
        <!-- ================================= -->
        <form
          v-if="isEditing && editContent"
          class="space-y-6"
          @submit.prevent="saveTailoredCv"
        >
          <!-- Title -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-slate-900">
              CV Title
            </h2>

            <input
              v-model="editTitle"
              type="text"
              required
              maxlength="255"
              :disabled="saving"
              class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </section>

          <!-- Contact information -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-slate-900">
              Contact Information
            </h2>

            <div class="grid gap-5 md:grid-cols-2">
              <div
                v-for="field in ([
                  { key: 'full_name', label: 'Full Name' },
                  { key: 'email', label: 'Email Address' },
                  { key: 'phone', label: 'Phone Number' },
                  { key: 'location', label: 'Location' },
                  { key: 'linkedin', label: 'LinkedIn' },
                  { key: 'github', label: 'GitHub' },
                ] as const)"
                :key="field.key"
              >
                <label class="mb-2 block text-sm font-medium text-slate-700">
                  {{ field.label }}
                </label>

                <input
                  v-model="editContent.contact[field.key]"
                  :type="field.key === 'email' ? 'email' : 'text'"
                  :disabled="saving"
                  class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                  :placeholder="`Enter ${field.label.toLowerCase()}`"
                />
              </div>
            </div>
          </section>

          <!-- Professional summary -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-slate-900">
              Professional Summary
            </h2>

            <textarea
              v-model="editContent.professional_summary"
              rows="6"
              required
              :disabled="saving"
              class="w-full resize-y rounded-lg border border-slate-200 px-4 py-3 text-sm leading-7 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            />
          </section>

          <!-- Technical skills -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-4">
              <h2 class="text-lg font-semibold text-slate-900">
                Technical Skills
              </h2>

              <button
                type="button"
                :disabled="saving"
                class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                @click="addSkill"
              >
                <Plus :size="17" />
                Add Skill
              </button>
            </div>

            <div class="space-y-3">
              <div
                v-for="(_, index) in editContent.skills"
                :key="index"
                class="flex items-center gap-3"
              >
                <input
                  v-model="editContent.skills[index]"
                  type="text"
                  :disabled="saving"
                  class="min-w-0 flex-1 rounded-lg border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                  placeholder="Enter a skill"
                />

                <button
                  type="button"
                  :disabled="saving"
                  class="rounded-lg p-2 text-red-500 hover:bg-red-50"
                  aria-label="Remove skill"
                  @click="removeSkill(index)"
                >
                  <Trash2 :size="18" />
                </button>
              </div>
            </div>

            <p
              v-if="editContent.skills.length === 0"
              class="text-sm text-slate-400"
            >
              No skills added yet.
            </p>
          </section>

          <!-- Experience -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-4">
              <h2 class="text-lg font-semibold text-slate-900">
                Professional Experience
              </h2>

              <button
                type="button"
                :disabled="saving"
                class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                @click="addExperience"
              >
                <Plus :size="17" />
                Add Experience
              </button>
            </div>

            <div class="space-y-6">
              <div
                v-for="(experience, index) in editContent.experience"
                :key="index"
                class="rounded-xl border border-slate-200 p-5"
              >
                <div class="mb-4 flex items-center justify-between">
                  <h3 class="font-semibold text-slate-900">
                    Experience {{ index + 1 }}
                  </h3>

                  <button
                    type="button"
                    :disabled="saving"
                    class="inline-flex items-center gap-2 text-sm text-red-600 hover:text-red-700"
                    @click="removeExperience(index)"
                  >
                    <Trash2 :size="16" />
                    Remove
                  </button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                  <input
                    v-model="experience.job_title"
                    type="text"
                    placeholder="Job title"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="experience.company"
                    type="text"
                    placeholder="Company"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="experience.location"
                    type="text"
                    placeholder="Location"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="experience.start_date"
                    type="text"
                    placeholder="Start date"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="experience.end_date"
                    type="text"
                    placeholder="End date"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />
                </div>

                <div class="mt-5">
                  <div class="mb-3 flex items-center justify-between">
                    <h4 class="text-sm font-semibold text-slate-700">
                      Responsibilities
                    </h4>

                    <button
                      type="button"
                      :disabled="saving"
                      class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600"
                      @click="addResponsibility(index)"
                    >
                      <Plus :size="16" />
                      Add
                    </button>
                  </div>

                  <div class="space-y-3">
                    <div
                      v-for="(_, responsibilityIndex) in experience.responsibilities"
                      :key="responsibilityIndex"
                      class="flex items-start gap-3"
                    >
                      <textarea
                        v-model="experience.responsibilities[responsibilityIndex]"
                        rows="2"
                        :disabled="saving"
                        class="min-w-0 flex-1 rounded-lg border border-slate-200 px-4 py-3 text-sm"
                        placeholder="Describe a responsibility or achievement"
                      />

                      <button
                        type="button"
                        :disabled="saving"
                        class="rounded-lg p-2 text-red-500 hover:bg-red-50"
                        aria-label="Remove responsibility"
                        @click="removeResponsibility(index, responsibilityIndex)"
                      >
                        <Trash2 :size="18" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Education -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-4">
              <h2 class="text-lg font-semibold text-slate-900">
                Education
              </h2>

              <button
                type="button"
                :disabled="saving"
                class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600"
                @click="addEducation"
              >
                <Plus :size="17" />
                Add Education
              </button>
            </div>

            <div class="space-y-5">
              <div
                v-for="(education, index) in editContent.education"
                :key="index"
                class="rounded-xl border border-slate-200 p-5"
              >
                <div class="mb-4 flex items-center justify-between">
                  <h3 class="font-semibold text-slate-900">
                    Education {{ index + 1 }}
                  </h3>

                  <button
                    type="button"
                    :disabled="saving"
                    class="text-sm text-red-600"
                    @click="removeEducation(index)"
                  >
                    Remove
                  </button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                  <input
                    v-model="education.degree"
                    type="text"
                    placeholder="Degree"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="education.institution"
                    type="text"
                    placeholder="Institution"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="education.location"
                    type="text"
                    placeholder="Location"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="education.start_date"
                    type="text"
                    placeholder="Start date"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <input
                    v-model="education.end_date"
                    type="text"
                    placeholder="End date"
                    :disabled="saving"
                    class="rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />
                </div>
              </div>
            </div>
          </section>

          <!-- Projects -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-4">
              <h2 class="text-lg font-semibold text-slate-900">
                Projects
              </h2>

              <button
                type="button"
                :disabled="saving"
                class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600"
                @click="addProject"
              >
                <Plus :size="17" />
                Add Project
              </button>
            </div>

            <div class="space-y-5">
              <div
                v-for="(project, index) in editContent.projects"
                :key="index"
                class="rounded-xl border border-slate-200 p-5"
              >
                <div class="mb-4 flex items-center justify-between">
                  <h3 class="font-semibold text-slate-900">
                    Project {{ index + 1 }}
                  </h3>

                  <button
                    type="button"
                    :disabled="saving"
                    class="text-sm text-red-600"
                    @click="removeProject(index)"
                  >
                    Remove
                  </button>
                </div>

                <div class="space-y-4">
                  <input
                    v-model="project.name"
                    type="text"
                    placeholder="Project name"
                    :disabled="saving"
                    class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <textarea
                    v-model="project.description"
                    rows="3"
                    placeholder="Project description"
                    :disabled="saving"
                    class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm"
                  />

                  <div>
                    <div class="mb-3 flex items-center justify-between">
                      <h4 class="text-sm font-semibold text-slate-700">
                        Technologies
                      </h4>

                      <button
                        type="button"
                        :disabled="saving"
                        class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600"
                        @click="addTechnology(index)"
                      >
                        <Plus :size="16" />
                        Add
                      </button>
                    </div>

                    <div class="space-y-3">
                      <div
                        v-for="(_, technologyIndex) in project.technologies"
                        :key="technologyIndex"
                        class="flex items-center gap-3"
                      >
                        <input
                          v-model="project.technologies[technologyIndex]"
                          type="text"
                          placeholder="Technology"
                          :disabled="saving"
                          class="min-w-0 flex-1 rounded-lg border border-slate-200 px-4 py-3 text-sm"
                        />

                        <button
                          type="button"
                          :disabled="saving"
                          class="rounded-lg p-2 text-red-500 hover:bg-red-50"
                          aria-label="Remove technology"
                          @click="removeTechnology(index, technologyIndex)"
                        >
                          <Trash2 :size="18" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Certifications -->
          <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-4">
              <h2 class="text-lg font-semibold text-slate-900">
                Certifications
              </h2>

              <button
                type="button"
                :disabled="saving"
                class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600"
                @click="addCertification"
              >
                <Plus :size="17" />
                Add Certification
              </button>
            </div>

            <div class="space-y-3">
              <div
                v-for="(_, index) in editContent.certifications"
                :key="index"
                class="flex items-center gap-3"
              >
                <input
                  v-model="editContent.certifications[index]"
                  type="text"
                  placeholder="Certification name"
                  :disabled="saving"
                  class="min-w-0 flex-1 rounded-lg border border-slate-200 px-4 py-3 text-sm"
                />

                <button
                  type="button"
                  :disabled="saving"
                  class="rounded-lg p-2 text-red-500 hover:bg-red-50"
                  aria-label="Remove certification"
                  @click="removeCertification(index)"
                >
                  <Trash2 :size="18" />
                </button>
              </div>
            </div>
          </section>

          <!-- Save error -->
          <div
            v-if="editError"
            role="alert"
            class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
          >
            {{ editError }}
          </div>

          <!-- Editor actions -->
          <div class="flex flex-wrap justify-end gap-3">
            <button
              type="button"
              :disabled="saving"
              class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-6 py-3 font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
              @click="cancelEditing"
            >
              <X :size="18" />
              Cancel
            </button>

            <button
              type="submit"
              :disabled="saving || !editTitle.trim()"
              class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
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

              {{ saving ? 'Saving Changes...' : 'Save Changes' }}
            </button>
          </div>

          <p class="text-center text-sm text-slate-500">
            Only include accurate information that reflects your real
            experience, education, and qualifications.
          </p>
        </form>

        <!-- ================================= -->
        <!-- PREVIEW MODE                      -->
        <!-- ================================= -->

        <article
          v-else
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >
          <!-- Contact -->
          <header class="border-b border-slate-200 px-8 py-8 md:px-12">
            <h2 class="text-3xl font-bold text-slate-900">
              {{ contact.full_name || 'Name not provided' }}
            </h2>

            <div class="mt-5 flex flex-wrap gap-x-6 gap-y-3 text-sm text-slate-600">
              <span
                v-if="contact.email"
                class="inline-flex items-center gap-2"
              >
                <Mail :size="16" />
                {{ contact.email }}
              </span>

              <span
                v-if="contact.phone"
                class="inline-flex items-center gap-2"
              >
                <Phone :size="16" />
                {{ contact.phone }}
              </span>

              <span
                v-if="contact.location"
                class="inline-flex items-center gap-2"
              >
                <MapPin :size="16" />
                {{ contact.location }}
              </span>

              <span
                v-if="contact.linkedin"
                class="inline-flex items-center gap-2"
              >
                <Link :size="16" />
                {{ contact.linkedin }}
              </span>

              <span
                v-if="contact.github"
                class="inline-flex items-center gap-2"
              >
                <Code2 :size="16" />
                {{ contact.github }}
              </span>
            </div>
          </header>

          <div class="space-y-10 px-8 py-10 md:px-12">

            <!-- Summary -->
            <section v-if="content.professional_summary">
              <h3 class="border-b border-slate-200 pb-3 text-lg font-bold uppercase tracking-wide text-slate-900">
                Professional Summary
              </h3>

              <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">
                {{ content.professional_summary }}
              </p>
            </section>

            <!-- Skills -->
            <section v-if="content.skills?.length">
              <h3 class="border-b border-slate-200 pb-3 text-lg font-bold uppercase tracking-wide text-slate-900">
                Technical Skills
              </h3>

              <div class="mt-5 flex flex-wrap gap-2">
                <span
                  v-for="(skill, index) in content.skills"
                  :key="index"
                  class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-medium text-indigo-700"
                >
                  {{ skill }}
                </span>
              </div>
            </section>

            <!-- Experience -->
            <section v-if="content.experience?.length">
              <h3 class="border-b border-slate-200 pb-3 text-lg font-bold uppercase tracking-wide text-slate-900">
                Professional Experience
              </h3>

              <div class="mt-5 space-y-7">
                <div
                  v-for="(experience, index) in content.experience"
                  :key="index"
                >
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                      <h4 class="font-semibold text-slate-900">
                        {{ experience.job_title || 'Position not specified' }}
                      </h4>

                      <p
                        v-if="experience.company"
                        class="mt-1 flex items-center gap-2 text-sm text-slate-600"
                      >
                        <BriefcaseBusiness :size="15" />
                        {{ experience.company }}
                      </p>

                      <p
                        v-if="experience.location"
                        class="mt-1 text-sm text-slate-500"
                      >
                        {{ experience.location }}
                      </p>
                    </div>

                    <span
                      v-if="dateRange(experience.start_date, experience.end_date)"
                      class="text-sm text-slate-500"
                    >
                      {{ dateRange(experience.start_date, experience.end_date) }}
                    </span>
                  </div>

                  <ul
                    v-if="experience.responsibilities?.length"
                    class="mt-3 list-disc space-y-2 pl-5 text-sm leading-7 text-slate-700"
                  >
                    <li
                      v-for="(responsibility, responsibilityIndex) in experience.responsibilities"
                      :key="responsibilityIndex"
                    >
                      {{ responsibility }}
                    </li>
                  </ul>
                </div>
              </div>
            </section>

            <!-- Education -->
            <section v-if="content.education?.length">
              <h3 class="border-b border-slate-200 pb-3 text-lg font-bold uppercase tracking-wide text-slate-900">
                Education
              </h3>

              <div class="mt-5 space-y-6">
                <div
                  v-for="(education, index) in content.education"
                  :key="index"
                  class="flex flex-wrap items-start justify-between gap-3"
                >
                  <div>
                    <h4 class="flex items-center gap-2 font-semibold text-slate-900">
                      <GraduationCap :size="18" />
                      {{ education.degree || 'Degree not specified' }}
                    </h4>

                    <p
                      v-if="education.institution"
                      class="mt-1 text-sm text-slate-600"
                    >
                      {{ education.institution }}
                    </p>

                    <p
                      v-if="education.location"
                      class="mt-1 text-sm text-slate-500"
                    >
                      {{ education.location }}
                    </p>
                  </div>

                  <span
                    v-if="dateRange(education.start_date, education.end_date)"
                    class="text-sm text-slate-500"
                  >
                    {{ dateRange(education.start_date, education.end_date) }}
                  </span>
                </div>
              </div>
            </section>

            <!-- Projects -->
            <section v-if="content.projects?.length">
              <h3 class="border-b border-slate-200 pb-3 text-lg font-bold uppercase tracking-wide text-slate-900">
                Projects
              </h3>

              <div class="mt-5 space-y-6">
                <div
                  v-for="(project, index) in content.projects"
                  :key="index"
                >
                  <h4 class="flex items-center gap-2 font-semibold text-slate-900">
                    <FolderKanban :size="18" />
                    {{ project.name || 'Unnamed project' }}
                  </h4>

                  <p
                    v-if="project.description"
                    class="mt-2 text-sm leading-7 text-slate-700"
                  >
                    {{ project.description }}
                  </p>

                  <div
                    v-if="project.technologies?.length"
                    class="mt-3 flex flex-wrap gap-2"
                  >
                    <span
                      v-for="(technology, technologyIndex) in project.technologies"
                      :key="technologyIndex"
                      class="rounded-lg bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600"
                    >
                      {{ technology }}
                    </span>
                  </div>
                </div>
              </div>
            </section>

            <!-- Certifications -->
            <section v-if="content.certifications?.length">
              <h3 class="border-b border-slate-200 pb-3 text-lg font-bold uppercase tracking-wide text-slate-900">
                Certifications
              </h3>

              <ul class="mt-5 space-y-3">
                <li
                  v-for="(certification, index) in content.certifications"
                  :key="index"
                  class="flex items-center gap-3 text-sm text-slate-700"
                >
                  <Award :size="18" class="text-indigo-600" />
                  {{ certification }}
                </li>
              </ul>
            </section>
          </div>
        </article>

        <!-- Review reminder -->
        <p
          v-if="!isEditing"
          class="text-center text-sm text-slate-500"
        >
          AI-generated content may contain inaccuracies.
          Review all details before submitting your CV.
        </p>

      </template>
    </div>
  </DashboardLayout>
</template>