import { createRouter, createWebHistory } from "vue-router";
import { store as createStore } from "./store";

import Home from "./pages/Home.vue";
import Login from "./pages/Login.vue";
import WorkLogs from "./pages/Worklogs.vue";
import Projects from "./pages/Projects.vue";
import Customers from "./pages/Customers.vue";
import Invoices from "./pages/Invoices.vue";
import Users from "./pages/Users.vue";
import NoPermission from "./pages/NoPermission.vue";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
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
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Add navigation guard
router.beforeEach((to, from, next) => {
  const store = createStore();
  
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
