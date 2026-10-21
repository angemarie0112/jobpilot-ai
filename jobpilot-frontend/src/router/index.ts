import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// ------------------------------------
// Page imports
// ------------------------------------

import DashboardView from '@/views/DashboardView.vue'
import CvsView from '@/views/CvsView.vue'
import JobsView from '@/views/JobsView.vue'
import AnalysesView from '@/views/AnalysesView.vue'
import CoverLettersView from '@/views/CoverLettersView.vue'
import TailoredCvsView from '@/views/TailoredCvsView.vue'
import JobApplicationsView from '@/views/JobApplicationsView.vue'
import LoginView from '@/views/LoginView.vue'
import RegisterView from '@/views/RegisterView.vue'

// ------------------------------------
// Router configuration
// ------------------------------------

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  routes: [
    // ------------------------------------
    // Public routes
    // ------------------------------------

    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: {
        guestOnly: true,
      },
    },

    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: {
        guestOnly: true,
      },
    },

    // ------------------------------------
    // Dashboard
    // ------------------------------------

    {
      path: '/',
      name: 'dashboard',
      component: DashboardView,
      meta: {
        requiresAuth: true,
      },
    },

    // ------------------------------------
    // Original CVs
    // ------------------------------------

    {
      path: '/cvs',
      name: 'cvs',
      component: CvsView,
      meta: {
        requiresAuth: true,
      },
    },

    // ------------------------------------
    // Job descriptions
    // ------------------------------------

    {
      path: '/jobs',
      name: 'jobs',
      component: JobsView,
      meta: {
        requiresAuth: true,
      },
    },

    // ------------------------------------
    // Match analysis
    // ------------------------------------

    {
      path: '/analyses',
      name: 'analyses',
      component: AnalysesView,
      meta: {
        requiresAuth: true,
      },
    },

    {
      path: '/analyses/:id',
      name: 'analysis-details',
      component: () => import('@/views/AnalysisDetailsView.vue'),
      props: true,
      meta: {
        requiresAuth: true,
      },
    },

    // ------------------------------------
    // Cover letters
    // ------------------------------------

    {
      path: '/cover-letters',
      name: 'cover-letters',
      component: CoverLettersView,
      meta: {
        requiresAuth: true,
      },
    },

    // ------------------------------------
    // Tailored CVs
    // ------------------------------------

    {
      path: '/tailored-cvs',
      name: 'tailored-cvs',
      component: TailoredCvsView,
      meta: {
        requiresAuth: true,
      },
    },

    {
      path: '/tailored-cvs/:id',
      name: 'tailored-cv-details',
      component: () => import('@/views/TailoredCvDetailsView.vue'),
      props: true,
      meta: {
        requiresAuth: true,
      },
    },

    // ------------------------------------
    // Job application tracking
    // ------------------------------------

    {
      path: '/job-applications',
      name: 'job-applications',
      component: JobApplicationsView,
      meta: {
        requiresAuth: true,
      },
    },
  ],
})

// ------------------------------------
// Navigation guard
// ------------------------------------

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Restore authentication if it hasn't been checked yet
  if (!authStore.initialized) {
    await authStore.fetchUser()
  }

  // Redirect unauthenticated users to login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return {
      name: 'login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  // Prevent authenticated users from visiting guest-only pages
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return {
      name: 'dashboard',
    }
  }
})

export default router