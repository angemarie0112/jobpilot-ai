<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import axios from 'axios'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import api from '@/services/api'

import {
  ArrowLeft,
  ChartNoAxesCombined,
  CheckCircle2,
  CircleAlert,
  FileText,
  BriefcaseBusiness,
  Lightbulb,
  Sparkles,
  LoaderCircle,
  FilePenLine,
} from '@lucide/vue'

// ------------------------------------
// Types
// ------------------------------------

interface Analysis {
  id: number
  status: string
  match_score: number | null
  matching_skills: string[] | null
  missing_skills: string[] | null
  recommendations: string[] | null
  summary: string | null

  cv: {
    id: number
    original_filename: string
  } | null

  job_description: {
    id: number
    title: string
    company_name: string
    location: string | null
  } | null
}

interface CoverLetter {
  id: number
  analysis_id: number
  title: string
  content: string
  model_used: string
  created_at: string
}

interface GenerateCoverLetterResponse {
  message: string
  cover_letter: CoverLetter
}

interface TailoredCv {
  id: number
  analysis_id: number
  cv_id: number
  title: string
  model_used: string | null
  content: {
    contact: Record<string, string>
    professional_summary: string
    skills: string[]
    experience: unknown[]
    education: unknown[]
    projects: unknown[]
    certifications: string[]
  }
  created_at: string
}

interface GenerateTailoredCvResponse {
  message: string
  tailored_cv: TailoredCv
}

// ------------------------------------
// Router
// ------------------------------------

const route = useRoute()
const router = useRouter()

// ------------------------------------
// Analysis state
// ------------------------------------

const analysis = ref<Analysis | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

// ------------------------------------
// Cover letter state
// ------------------------------------

const generatingCoverLetter = ref(false)
const coverLetterError = ref<string | null>(null)

// ------------------------------------
// Tailored CV state
// ------------------------------------

const generatingTailoredCv = ref(false)
const tailoredCvError = ref<string | null>(null)
const generatedTailoredCv = ref<TailoredCv | null>(null)

// ------------------------------------
// Computed properties
// ------------------------------------

const matchingSkills = computed(
  () => analysis.value?.matching_skills ?? [],
)

const missingSkills = computed(
  () => analysis.value?.missing_skills ?? [],
)

const recommendations = computed(
  () => analysis.value?.recommendations ?? [],
)

// ------------------------------------
// Fetch analysis details
// ------------------------------------

async function fetchAnalysis() {
  loading.value = true
  error.value = null

  try {
    const response = await api.get<{
      analysis: Analysis
    }>(`/api/analyses/${route.params.id}`)

    analysis.value = response.data.analysis

  } catch (err) {
    analysis.value = null

    if (axios.isAxiosError(err)) {
      error.value =
        err.response?.data?.message ||
        'Unable to retrieve the analysis.'
    } else {
      error.value = 'An unexpected error occurred.'
    }

  } finally {
    loading.value = false
  }
}

// ------------------------------------
// Generate cover letter
// ------------------------------------

async function generateCoverLetter() {
  if (!analysis.value) return
  if (generatingCoverLetter.value || generatingTailoredCv.value) return

  coverLetterError.value = null
  generatingCoverLetter.value = true

  try {
    const response = await api.post<GenerateCoverLetterResponse>(
      '/api/cover-letters',
      {
        analysis_id: analysis.value.id,
      },
      {
        timeout: 150000,
      },
    )

    const generatedCoverLetter = response.data.cover_letter

    if (!generatedCoverLetter?.id) {
      throw new Error(
        'The cover letter was generated but no ID was returned.',
      )
    }

    await router.push({
      path: '/cover-letters',
      query: {
        generated: String(generatedCoverLetter.id),
      },
    })

  } catch (err) {
    if (axios.isAxiosError(err)) {
      if (err.code === 'ECONNABORTED') {
        coverLetterError.value =
          'Cover letter generation took too long. Please check your saved cover letters before trying again.'
      } else {
        coverLetterError.value =
          err.response?.data?.message ||
          'Unable to generate your cover letter. Please try again.'
      }
    } else {
      coverLetterError.value =
        err instanceof Error
          ? err.message
          : 'An unexpected error occurred.'
    }

  } finally {
    generatingCoverLetter.value = false
  }
}

// ------------------------------------
// Generate tailored CV
// ------------------------------------

async function generateTailoredCv() {
  if (!analysis.value) return
  if (generatingTailoredCv.value || generatingCoverLetter.value) return

  tailoredCvError.value = null
  generatedTailoredCv.value = null
  generatingTailoredCv.value = true

  try {
    const response = await api.post<GenerateTailoredCvResponse>(
      '/api/tailored-cvs',
      {
        analysis_id: analysis.value.id,
      },
      {
        timeout: 190000,
      },
    )

    const tailoredCv = response.data.tailored_cv

    if (!tailoredCv?.id) {
      throw new Error(
        'The tailored CV was generated but no ID was returned.',
      )
    }

    generatedTailoredCv.value = tailoredCv

  } catch (err) {
    if (axios.isAxiosError(err)) {
      if (err.code === 'ECONNABORTED') {
        tailoredCvError.value =
          'CV generation took too long. Please check your saved CVs before trying again.'
      } else {
        tailoredCvError.value =
          err.response?.data?.message ||
          'Unable to generate your tailored CV. Please try again.'
      }
    } else {
      tailoredCvError.value =
        err instanceof Error
          ? err.message
          : 'An unexpected error occurred.'
    }

  } finally {
    generatingTailoredCv.value = false
  }
}

// ------------------------------------
// Initial load
// ------------------------------------

onMounted(fetchAnalysis)
</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">

      <!-- Back navigation -->
      <RouterLink
        to="/analyses"
        class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-indigo-600"
      >
        <ArrowLeft :size="18" />
        Back to Match Analysis
      </RouterLink>

      <!-- Loading -->
      <section
        v-if="loading"
        class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm"
      >
        <div class="flex items-center justify-center gap-3">
          <LoaderCircle
            :size="20"
            class="animate-spin text-indigo-600"
          />

          <p class="text-slate-500">
            Loading analysis details...
          </p>
        </div>
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

        <p class="mt-3 text-red-700">
          {{ error }}
        </p>

        <button
          type="button"
          class="mt-4 font-medium text-red-700 underline"
          @click="fetchAnalysis"
        >
          Try again
        </button>
      </section>

      <template v-else-if="analysis">

        <!-- ===================================== -->
        <!-- Page heading                          -->
        <!-- ===================================== -->

        <section class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-slate-900">
              Analysis Results
            </h1>

            <p class="mt-2 text-slate-500">
              Review your CV compatibility and improvement opportunities.
            </p>
          </div>

          <!-- Action buttons -->
          <div class="flex flex-wrap items-center gap-3">

            <!-- Tailor CV -->
            <button
              type="button"
              :disabled="
                generatingTailoredCv ||
                generatingCoverLetter ||
                analysis.status !== 'completed'
              "
              class="flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
              @click="generateTailoredCv"
            >
              <LoaderCircle
                v-if="generatingTailoredCv"
                :size="18"
                class="animate-spin"
              />

              <FilePenLine
                v-else
                :size="18"
              />

              {{
                generatingTailoredCv
                  ? 'Tailoring Your CV...'
                  : 'Tailor My CV'
              }}
            </button>

            <!-- Generate Cover Letter -->
            <button
              type="button"
              :disabled="
                generatingCoverLetter ||
                generatingTailoredCv ||
                analysis.status !== 'completed'
              "
              class="flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
              @click="generateCoverLetter"
            >
              <LoaderCircle
                v-if="generatingCoverLetter"
                :size="18"
                class="animate-spin"
              />

              <Sparkles
                v-else
                :size="18"
              />

              {{
                generatingCoverLetter
                  ? 'Generating Cover Letter...'
                  : 'Generate Cover Letter'
              }}
            </button>

          </div>
        </section>

        <!-- Cover letter error -->
        <div
          v-if="coverLetterError"
          role="alert"
          class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
        >
          <CircleAlert
            :size="20"
            class="mt-0.5 shrink-0"
          />

          <span>{{ coverLetterError }}</span>
        </div>

        <!-- Tailored CV error -->
        <div
          v-if="tailoredCvError"
          role="alert"
          class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"
        >
          <CircleAlert
            :size="20"
            class="mt-0.5 shrink-0"
          />

          <span>{{ tailoredCvError }}</span>
        </div>

        <!-- Cover letter generation information -->
        <div
          v-if="generatingCoverLetter"
          role="status"
          class="flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm text-indigo-700"
        >
          <LoaderCircle
            :size="20"
            class="shrink-0 animate-spin"
          />

          <div>
            <p class="font-semibold">
              Gemini AI is generating your cover letter...
            </p>

            <p class="mt-1">
              This may take a minute. Please keep this page open.
            </p>
          </div>
        </div>

        <!-- Tailored CV generation information -->
        <div
          v-if="generatingTailoredCv"
          role="status"
          class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700"
        >
          <LoaderCircle
            :size="20"
            class="shrink-0 animate-spin"
          />

          <div>
            <p class="font-semibold">
              Gemini AI is tailoring your CV...
            </p>

            <p class="mt-1">
              We're adapting your CV to the job requirements.
              This may take a minute. Please keep this page open.
            </p>
          </div>
        </div>

        <!-- Tailored CV success -->
        <div
          v-if="generatedTailoredCv"
          role="status"
          class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-5"
        >
          <div class="flex items-start gap-3">
            <CheckCircle2
              :size="24"
              class="mt-0.5 shrink-0 text-emerald-600"
            />

            <div>
              <h3 class="font-semibold text-emerald-900">
                Your tailored CV is ready!
              </h3>

              <p class="mt-2 text-sm text-emerald-800">
                {{ generatedTailoredCv.title }}
              </p>

              <p class="mt-1 text-sm text-emerald-700">
                Saved successfully. CV ID: {{ generatedTailoredCv.id }}
              </p>

              <p class="mt-3 text-sm text-emerald-700">
                Next, you'll be able to review, edit, and download
                your tailored CV as a PDF.
              </p>
            </div>
          </div>
        </div>

        <!-- ===================================== -->
        <!-- Match overview                        -->
        <!-- ===================================== -->

        <section
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          <div class="flex flex-wrap items-center justify-between gap-6">

            <div>
              <h2 class="text-xl font-semibold text-slate-900">
                {{ analysis.job_description?.title ?? 'Job description' }}
              </h2>

              <p class="mt-1 text-slate-500">
                {{ analysis.job_description?.company_name ?? 'Unknown company' }}
              </p>

              <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-500">

                <span class="flex items-center gap-2">
                  <FileText :size="16" />
                  {{ analysis.cv?.original_filename ?? 'CV unavailable' }}
                </span>

                <span class="flex items-center gap-2">
                  <BriefcaseBusiness :size="16" />
                  {{
                    analysis.job_description?.location ??
                    'Location not specified'
                  }}
                </span>

              </div>
            </div>

            <!-- Match score -->
            <div class="rounded-2xl bg-amber-50 px-8 py-5 text-center">
              <div class="flex items-center justify-center gap-2">

                <ChartNoAxesCombined
                  :size="22"
                  class="text-amber-600"
                />

                <span class="text-4xl font-bold text-amber-700">
                  {{
                    analysis.match_score === null
                      ? '—'
                      : `${analysis.match_score}%`
                  }}
                </span>

              </div>

              <p class="mt-2 text-sm font-medium text-amber-700">
                Match Score
              </p>
            </div>

          </div>

          <!-- Progress bar -->
          <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-100">
            <div
              class="h-full rounded-full bg-indigo-600 transition-all"
              :style="{
                width: `${
                  Math.min(
                    100,
                    Math.max(0, analysis.match_score ?? 0),
                  )
                }%`,
              }"
            />
          </div>

          <!-- Summary -->
          <p
            v-if="analysis.summary"
            class="mt-5 text-sm text-slate-600"
          >
            {{ analysis.summary }}
          </p>

          <p class="mt-3 text-xs text-slate-400">
            This score estimates alignment with documented skills.
            It does not predict hiring outcomes.
          </p>
        </section>

        <!-- ===================================== -->
        <!-- Skills                                -->
        <!-- ===================================== -->

        <div class="grid gap-6 lg:grid-cols-2">

          <!-- Matching skills -->
          <section
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
          >
            <div class="flex items-center gap-3">
              <CheckCircle2
                :size="24"
                class="text-emerald-600"
              />

              <h2 class="text-lg font-semibold text-slate-900">
                Matching Skills
              </h2>
            </div>

            <p class="mt-2 text-sm text-slate-500">
              Skills found in both your CV and the job requirements.
            </p>

            <div
              v-if="matchingSkills.length"
              class="mt-5 flex flex-wrap gap-2"
            >
              <span
                v-for="skill in matchingSkills"
                :key="skill"
                class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700"
              >
                {{ skill }}
              </span>
            </div>

            <p
              v-else
              class="mt-5 text-sm text-slate-400"
            >
              No matching skills identified.
            </p>
          </section>

          <!-- Skills not evidenced -->
          <section
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
          >
            <div class="flex items-center gap-3">
              <CircleAlert
                :size="24"
                class="text-amber-600"
              />

              <h2 class="text-lg font-semibold text-slate-900">
                Skills Not Evidenced
              </h2>
            </div>

            <p class="mt-2 text-sm text-slate-500">
              Job requirements not identified in your uploaded CV.
            </p>

            <div
              v-if="missingSkills.length"
              class="mt-5 flex flex-wrap gap-2"
            >
              <span
                v-for="skill in missingSkills"
                :key="skill"
                class="rounded-full bg-amber-50 px-3 py-1.5 text-sm font-medium text-amber-700"
              >
                {{ skill }}
              </span>
            </div>

            <p
              v-else
              class="mt-5 text-sm text-slate-400"
            >
              No additional skills identified.
            </p>
          </section>

        </div>

        <!-- ===================================== -->
        <!-- Recommendations                      -->
        <!-- ===================================== -->

        <section
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          <div class="flex items-center gap-3">

            <Lightbulb
              :size="24"
              class="text-indigo-600"
            />

            <h2 class="text-lg font-semibold text-slate-900">
              Recommendations
            </h2>

          </div>

          <p class="mt-2 text-sm text-slate-500">
            Suggestions to help improve your application.
          </p>

          <div
            v-if="recommendations.length"
            class="mt-6 space-y-4"
          >
            <div
              v-for="(recommendation, index) in recommendations"
              :key="index"
              class="flex gap-4 rounded-xl bg-slate-50 p-4"
            >

              <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-sm font-semibold text-indigo-700"
              >
                {{ index + 1 }}
              </span>

              <p class="text-sm leading-6 text-slate-700">
                {{ recommendation }}
              </p>

            </div>
          </div>

          <p
            v-else
            class="mt-5 text-sm text-slate-400"
          >
            No recommendations available for this analysis.
          </p>

        </section>

      </template>
    </div>
  </DashboardLayout>
</template>