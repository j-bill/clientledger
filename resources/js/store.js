import { defineStore } from "pinia";
import eventBus from "./eventBus";
import router from "./router";
import axios from "axios";

export const store = defineStore("store", {
  state: () => ({
    user: null,
    customers: [],
    projects: [],
    users: [],
    settings: {},
    snackbar: {
      show: false,
      message: '',
      color: 'success',
      timeout: 3000
    }
  }),
  getters: {
    isAuthenticated(state) {
      return !!state.user;
    },
    getUser(state) {
      return state.user;
    },
  },
  actions: {
    showSnackbar(message, color = 'success', timeout = 3000) {
      color = color === 'error' ? 'red' : color;
      color = color === "danger" ? "red" : color;

      this.snackbar = {
        show: true,
        message,
        color,
        timeout
      };
    },
    async fetchCustomers() {
      try {
        const response = await axios.get("/api/customers");
        this.customers = response.data.customers;
      } catch (error) {
        eventBus.emit("snackbar", {
          message: error.response.data.message,
          color: "red",
        });
      }
    },
    async fetchProjects() {
      try {
        const response = await axios.get("/api/projects");
        this.projects = response.data.projects;
      } catch (error) {
        eventBus.emit("snackbar", {
          message: error.response.data.message,
          color: "red",
        });
      }
    },
    async fetchUsers() {
      try {
        const response = await axios.get("/api/users");
        this.users = response.data.users;
      } catch (error) {
        eventBus.emit("snackbar", {
          message: error.response.data.message,
          color: "red",
        });
      }
    },
    async login(user, password) {
      return new Promise(async (resolve, reject) => {
        try {
          const response = await axios.post("/api/login", {
            email: user,
            password: password,
          });

          if (response.status === 200) {
            await this.getAuthUser();
            resolve(); // Resolve promise if login is successful
          } else {
            reject("Login was unsuccessful."); // Reject promise if response status is not 200
          }
        } catch (error) {
          eventBus.emit("snackbar", {
            message: error.response.data.message,
            color: "red",
          });
          reject(error.response.data.message); // Reject promise with error message
        }
      });
    },
    async getAuthUser() {
      return new Promise(async (resolve, reject) => {
        try {
          const response = await axios.get("/api/user");
          this.user = response.data;
          resolve(this.user); // Resolve promise with authenticated user
        } catch (error) {
          reject(error.response.data.message); // Reject promise with error message
        }
      });
    },
    async logout() {
      try {
        await axios.post("/api/logout");
        this.user = null;
        router.push({ name: "Login" });
      } catch (error) {
        eventBus.emit("snackbar", {
          message: error.response.data.message,
          color: "red",
        });
      }
    },
  }
});
