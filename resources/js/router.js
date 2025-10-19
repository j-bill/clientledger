import { createRouter, createWebHistory } from "vue-router";
import { store as createStore } from "./store";

import Home from "./pages/Home.vue";
import Login from "./pages/Login.vue";
import WorkLogs from "./pages/Worklogs.vue";
import Projects from "./pages/Projects.vue";
import Customers from "./pages/Customers.vue";
import Invoices from "./pages/Invoices.vue";
import Users from "./pages/Users.vue";
import Profile from "./pages/Profile.vue";
import Settings from "./pages/Settings.vue";
import Privacy from "./pages/Privacy.vue";
import Imprint from "./pages/Imprint.vue";
import NoPermission from "./pages/NoPermission.vue";
import TwoFactorSetup from "./pages/TwoFactorSetup.vue";
import TwoFactorChallenge from "./pages/TwoFactorChallenge.vue";
import NotFound from "./pages/NotFound.vue";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { requiresAuth: false },
  },
  {
    path: "/privacy",
    name: "Privacy",
    component: Privacy,
    meta: { requiresAuth: false },
  },
  {
    path: "/imprint",
    name: "Imprint",
    component: Imprint,
    meta: { requiresAuth: false },
  },
  {
    path: "/2fa/setup",
    name: "TwoFactorSetup",
    component: TwoFactorSetup,
    meta: { requiresAuth: true, skip2FACheck: true },
  },
  {
    path: "/2fa/challenge",
    name: "TwoFactorChallenge",
    component: TwoFactorChallenge,
    meta: { requiresAuth: false },
  },
  {
    path: "/no-permission",
    name: "NoPermission",
    component: NoPermission,
    meta: { requiresAuth: true },
  },
  {
    path: "/",
    component: Home,
    meta: { requiresAuth: true },
  },
  // Profile route
  {
    path: "/profile",
    component: Profile,
    meta: { requiresAuth: true },
  },
  // Settings route
  {
    path: "/settings",
    component: Settings,
    meta: { requiresAuth: true },
  },
  // WorkLog routes
  {
    path: "/work-logs",
    component: WorkLogs,
    meta: { requiresAuth: true },
  },

  // Project routes
  {
    path: "/projects",
    component: Projects,
    meta: { requiresAuth: true },
  },

  // Customers routes (admin only)
  {
    path: "/customers",
    component: Customers,
    meta: { requiresAuth: true, requiresAdmin: true },
  },

  // Invoice routes (admin only)
  {
    path: "/invoices",
    component: Invoices,
    meta: { requiresAuth: true, requiresAdmin: true },
  },

  // User routes (admin only)
  {
    path: "/users",
    component: Users,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  
  // 404 Not Found - must be last
  {
    path: "/:pathMatch(.*)*",
    name: "NotFound",
    component: NotFound,
    meta: { requiresAuth: false },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Add navigation guard
router.beforeEach((to, from, next) => {
  const store = createStore();
  
  // Allow access to 2FA setup if pending flag is set (recovery code flow)
  if (to.name === "TwoFactorSetup" && sessionStorage.getItem('2fa_setup_pending')) {
    next();
    return;
  }
  
  // Check if user is authenticated
  if (to.meta.requiresAuth !== false && !store.isAuthenticated) {
    next({ name: "Login" });
    return;
  }

  // Redirect to login if already authenticated and trying to access login page
  if (to.name === "Login" && store.isAuthenticated) {
    next({ path: "/" });
    return;
  }

  // Check admin access
  if (to.meta.requiresAdmin && store.user?.role !== 'admin') {
    next({ name: "NoPermission" });
    return;
  }

  next();
});

export default router;
