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
    loading: false,
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
    isAdmin(state) {
      return state.user?.role === 'admin';
    }
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
    
    showLoading() {
      this.loading = true;
    },
    
    hideLoading() {
      this.loading = false;
    },
    
    // Customer actions
    async fetchCustomers() {
      try {
        const response = await axios.get("/api/customers");
        this.customers = response.data;
        return this.customers;
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || "Failed to fetch customers", "error");
        throw error;
      }
    },
    
    async createCustomer(customer) {
      try {
        const response = await axios.post('/api/customers', customer);
        this.customers.unshift(response.data);
        this.showSnackbar('Customer created successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to create customer';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async updateCustomer(customer) {
      try {
        const response = await axios.put(`/api/customers/${customer.id}`, customer);
        const index = this.customers.findIndex(c => c.id === customer.id);
        if (index !== -1) {
          this.customers.splice(index, 1, response.data);
        }
        this.showSnackbar('Customer updated successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to update customer';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async deleteCustomer(id) {
      try {
        await axios.delete(`/api/customers/${id}`);
        this.customers = this.customers.filter(c => c.id !== id);
        this.showSnackbar('Customer deleted successfully', 'success');
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Failed to delete customer', 'error');
        throw error;
      }
    },
    
    // Project actions
    async fetchProjects() {
      try {
        const response = await axios.get("/api/projects");
        this.projects = response.data;
        return this.projects;
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || "Failed to fetch projects", "error");
        throw error;
      }
    },
    
    async createProject(project) {
      try {
        const response = await axios.post('/api/projects', project);
        this.projects.unshift(response.data);
        this.showSnackbar('Project created successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to create project';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async updateProject(project) {
      try {
        const response = await axios.put(`/api/projects/${project.id}`, project);
        const index = this.projects.findIndex(p => p.id === project.id);
        if (index !== -1) {
          this.projects.splice(index, 1, response.data);
        }
        this.showSnackbar('Project updated successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to update project';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async deleteProject(id) {
      try {
        await axios.delete(`/api/projects/${id}`);
        this.projects = this.projects.filter(p => p.id !== id);
        this.showSnackbar('Project deleted successfully', 'success');
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Failed to delete project', 'error');
        throw error;
      }
    },
    
    // User actions
    async fetchUsers() {
      try {
        if (this.isAdmin) {
          const response = await axios.get("/api/users");
          this.users = response.data;
        } else {
          // For freelancers, just store their own user object
          this.users = [this.user];
        }
        return this.users;
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || "Failed to fetch users", "error");
        throw error;
      }
    },
    
    async createUser(user) {
      try {
        const response = await axios.post('/api/users', user);
        this.users.unshift(response.data);
        this.showSnackbar('User created successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to create user';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async updateUser(user) {
      try {
        const response = await axios.put(`/api/users/${user.id}`, user);
        const index = this.users.findIndex(u => u.id === user.id);
        if (index !== -1) {
          this.users.splice(index, 1, response.data);
        }
        this.showSnackbar('User updated successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to update user';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async deleteUser(id) {
      try {
        await axios.delete(`/api/users/${id}`);
        this.users = this.users.filter(u => u.id !== id);
        this.showSnackbar('User deleted successfully', 'success');
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Failed to delete user', 'error');
        throw error;
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
          this.showSnackbar(error.response?.data?.message || "Login failed", "error");
          reject(error.response?.data?.message || "Login failed"); // Reject promise with error message
        }
      });
    },
    
    async getAuthUser() {
      return new Promise(async (resolve, reject) => {
        try {
          const response = await axios.get("/api/user");
          this.user = response.data;
          // Initialize users array with the authenticated user
          this.users = [this.user];
          resolve(this.user); // Resolve promise with authenticated user
        } catch (error) {
          reject(error.response?.data?.message || "Failed to get user info"); // Reject promise with error message
        }
      });
    },
    
    async logout() {
      try {
        await axios.post("/api/logout");
        this.user = null;
        this.users = [];
        router.push({ name: "Login" });
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || "Logout failed", "error");
      }
    },
    
    // Worklog actions
    async fetchWorkLogs(params = {}) {
      try {
        const response = await axios.get("/api/worklogs", { params });
        return response.data;
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || "Failed to fetch work logs", "error");
        throw error;
      }
    },
    
    async getWorkLog(id) {
      try {
        const response = await axios.get(`/api/worklogs/${id}`);
        return response.data;
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || "Failed to fetch work log", "error");
        throw error;
      }
    },
    
    async createWorkLog(workLog) {
      try {
        const response = await axios.post('/api/worklogs', workLog);
        this.showSnackbar('Work log created successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to create work log';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async updateWorkLog(workLog) {
      try {
        const response = await axios.put(`/api/worklogs/${workLog.id}`, workLog);
        this.showSnackbar('Work log updated successfully', 'success');
        return response.data;
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to update work log';
        this.showSnackbar(message, 'error');
        throw error;
      }
    },
    
    async deleteWorkLog(id) {
      try {
        await axios.delete(`/api/worklogs/${id}`);
        this.showSnackbar('Work log deleted successfully', 'success');
      } catch (error) {
        this.showSnackbar(error.response?.data?.message || 'Failed to delete work log', 'error');
        throw error;
      }
    },
  }
});
