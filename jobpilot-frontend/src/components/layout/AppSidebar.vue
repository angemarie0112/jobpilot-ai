<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { X } from '@lucide/vue'

import {
  LayoutDashboard,
  FileText,
  BriefcaseBusiness,
  ChartNoAxesCombined,
  Mail,
  FileUser,
  ClipboardList,
} from '@lucide/vue'

defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  (event: 'close'): void
}>()

const route = useRoute()

const navigation = [
  {
    name: 'Dashboard',
    path: '/',
    icon: LayoutDashboard,
  },
  {
    name: 'My CVs',
    path: '/cvs',
    icon: FileText,
  },
  {
    name: 'Job Descriptions',
    path: '/jobs',
    icon: BriefcaseBusiness,
  },
  {
    name: 'Match Analysis',
    path: '/analyses',
    icon: ChartNoAxesCombined,
  },
  {
    name: 'Cover Letters',
    path: '/cover-letters',
    icon: Mail,
  },
  {
    name: 'Tailored CVs',
    path: '/tailored-cvs',
    icon: FileUser,
  },
  {
    name: 'Job Applications',
    path: '/job-applications',
    icon: ClipboardList,
  },
]

function closeSidebar() {
  emit('close')
}

function isActive(path: string) {
  return (
    route.path === path ||
    (path !== '/' && route.path.startsWith(`${path}/`))
  )
}
</script>

<template>
  <aside
    id="jobpilot-sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col overflow-y-auto bg-slate-900 p-6 text-white transition-transform duration-300 ease-in-out lg:z-40 lg:translate-x-0"
    :class="isOpen ? 'translate-x-0' : '-translate-x-full'"
    :aria-hidden="!isOpen ? true : undefined"
  >
    <!-- Application branding -->
    <div class="flex items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">
          JobPilot AI
        </h1>

        <p class="mt-2 text-sm text-slate-400">
          Your AI career assistant
        </p>
      </div>

      <!-- Close button (mobile/tablet only) -->
      <button
        type="button"
        aria-label="Close navigation menu"
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-300 transition hover:bg-slate-800 hover:text-white lg:hidden"
        @click="closeSidebar"
      >
        <X :size="22" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="mt-10 space-y-2" aria-label="Main navigation">
      <RouterLink
        v-for="item in navigation"
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition-colors"
        :class="
          isActive(item.path)
            ? 'bg-indigo-600 text-white shadow-sm'
            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
        "
        :aria-current="isActive(item.path) ? 'page' : undefined"
        @click="closeSidebar"
      >
        <component
          :is="item.icon"
          :size="20"
          :stroke-width="1.8"
          class="shrink-0"
        />

        <span>
          {{ item.name }}
        </span>
      </RouterLink>
    </nav>

    <!-- Sidebar footer -->
    <div class="mt-auto border-t border-slate-700 pt-6">
      <p class="text-xs font-medium text-slate-400">
        JobPilot AI
      </p>

      <p class="mt-1 text-xs text-slate-500">
        Your career, powered by AI.
      </p>
    </div>
  </aside>
</template>