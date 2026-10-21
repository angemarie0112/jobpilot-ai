<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'

import AppSidebar from '@/components/layout/AppSidebar.vue'
import AppHeader from '@/components/layout/AppHeader.vue'

const route = useRoute()

const isSidebarOpen = ref(false)

function openSidebar() {
  isSidebarOpen.value = true
}

function closeSidebar() {
  isSidebarOpen.value = false
}

function toggleSidebar() {
  if (isSidebarOpen.value) {
    closeSidebar()
  } else {
    openSidebar()
  }
}

// Close the sidebar when navigating to another page
watch(
  () => route.fullPath,
  () => {
    closeSidebar()
  },
)

// Close sidebar using Escape
function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && isSidebarOpen.value) {
    closeSidebar()
  }
}

// Prevent background scrolling while mobile sidebar is open
watch(isSidebarOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="min-h-screen bg-slate-50">

    <!-- Mobile backdrop -->
    <div
      v-if="isSidebarOpen"
      class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
      aria-hidden="true"
      @click="closeSidebar"
    ></div>

    <!-- Sidebar -->
    <AppSidebar
      :is-open="isSidebarOpen"
      @close="closeSidebar"
    />

    <!-- Main application area -->
    <div class="min-w-0 lg:ml-64">

      <!-- Header -->
      <AppHeader
        :is-sidebar-open="isSidebarOpen"
        @toggle-sidebar="toggleSidebar"
      />

      <!-- Page content -->
      <main class="min-w-0 p-4 sm:p-6 lg:p-8">
        <slot />
      </main>

    </div>
  </div>
</template>