import { createRouter, createWebHistory } from "vue-router";
import { store as createStore } from "./store";

import Home from "./pages/Home.vue";
import Login from "./pages/Login.vue";
import WorkLogs from "./pages/Worklogs.vue";
import Projects from "./pages/Projects.vue";
import Customers from "./pages/Customers.vue";
import Invoices from "./pages/Invoices.vue";
import Users from "./pages/Users.vue";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
    meta: { requiresAuth: false },
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

  // Customers routes
  {
    path: "/customers",
    component: Customers,
    meta: { requiresAuth: true },
  },

  // Invoice routes
  {
    path: "/invoices",
    component: Invoices,
    meta: { requiresAuth: true },
  },

  // User routes
  {
    path: "/users",
    component: Users,
    meta: { requiresAuth: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Add navigation guard
router.beforeEach((to, from, next) => {
  const store = createStore();
  if (to.meta.requiresAuth !== false && !store.isAuthenticated) {
    next({ name: "Login" });
  } else {
    next();
  }
});

export default router;
