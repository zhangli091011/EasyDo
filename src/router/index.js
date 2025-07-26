import { createRouter, createWebHistory } from 'vue-router'
import AuthView from '../views/Auth.vue'
import HomeView from '../views/Home.vue'
import SettingsView from '../views/Settings.vue'
import UserProfile from '../views/UserProfile.vue'
import TaskHistory from '../views/TaskHistory.vue'

const routes = [
  {
    path: '/',
    name: 'auth',
    component: AuthView
  },
  {
    path: '/home',
    name: 'home',
    component: HomeView
  },
  {
    path: '/settings',
    name: 'settings',
    component: SettingsView
  },
  {
    path: '/profile',
    name: 'profile',
    component: UserProfile
  },
  {
    path: '/task-history',
    name: 'taskHistory',
    component: TaskHistory
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

// Navigation guard for authentication
router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('user')
  
  if (to.name !== 'auth' && !isAuthenticated) {
    next({ name: 'auth' })
  } else {
    next()
  }
})

export default router 