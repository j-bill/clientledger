import { defineStore } from "pinia";
import eventBus from "./eventBus";
import axios from "axios";

export const store = defineStore("store", {
  state: () => ({
    user: null,
    customers: [],
    projects: [],
    invoices: [], // Added invoice state
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
    // ...existing getters...
    isAuthenticated(state) { return !!state.user; },
    getUser(state) { return state.user; },
    isAdmin(state) { return state.user && state.user.role === 'admin'; },
    has2FAEnabled(state) { 
      return state.user?.has_two_factor_enabled === true;
    },
    currencySymbol(state) { return state.settings.currency_symbol || '$'; }
  },
  actions: {
    showSnackbar(message, color = 'success', timeout = 3000) {
      this.snackbar.show = true;
      this.snackbar.message = message;
      this.snackbar.color = color;
      this.snackbar.timeout = timeout;
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
        // Don't show snackbar if it's a 2FA redirect
        if (!error.suppressSnackbar) {
          this.showSnackbar(error.response?.data?.message || "Failed to fetch customers", "error");
        }
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
    async fetchProjects(params = {}) {
      try {
        const response = await axios.get("/api/projects", { params });
        this.projects = response.data;
        return this.projects;
      } catch (error) {
        // Don't show snackbar if it's a 2FA redirect
        if (!error.suppressSnackbar) {
          this.showSnackbar(error.response?.data?.message || "Failed to fetch projects", "error");
        }
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
            // Check if 2FA verification is required
            if (response.data.requires_2fa_verification) {
              // Store email for 2FA challenge
              sessionStorage.setItem('2fa_pending_email', user);
              resolve({ requires_2fa_verification: true, email: user });
              return;
            }

            // Check if 2FA setup is required
            if (response.data.requires_2fa_setup) {
              await this.getAuthUser();
              resolve({ requires_2fa_setup: true });
              return;
            }

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
          console.log('[store.js] getAuthUser response:', response.data);
          this.user = response.data;
          console.log('[store.js] user set in store:', this.user);
          // Initialize users array with the authenticated user
          this.users = [this.user];
          resolve(this.user); // Resolve promise with authenticated user
        } catch (error) {
          console.error('[store.js] getAuthUser error:', error);
          reject(error.response?.data?.message || "Failed to get user info"); // Reject promise with error message
        }
      });
    },
    
    // Update the current authenticated user's data in the store
    updateAuthUser(userData) {
      this.user = userData;
    },
    
    async logout() {
      try {
        await axios.post("/api/logout");
        this.user = null;
        this.users = [];
        // Lazy import router to avoid circular import during module init
        const { default: router } = await import('./router');
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

    // Invoice actions - NEW
    async fetchInvoices() {
      this.showLoading();
      try {
        const response = await axios.get('/api/invoices');
        this.invoices = response.data;
      } catch (error) {
        this.showSnackbar('Error fetching invoices: ' + (error.response?.data?.message || error.message), 'error');
        console.error("Error fetching invoices:", error);
      } finally {
        this.hideLoading();
      }
    },

    async createInvoice(invoice) {
      this.showLoading();
      try {
        const response = await axios.post('/api/invoices', invoice);
        this.invoices.push(response.data); // Add the new invoice to the state
        this.showSnackbar('Invoice created successfully', 'success');
        return response.data; // Return the created invoice
      } catch (error) {
        this.showSnackbar('Error creating invoice: ' + (error.response?.data?.message || error.message), 'error');
        console.error("Error creating invoice:", error);
        return null;
      } finally {
        this.hideLoading();
      }
    },

    async updateInvoice(invoice) {
      this.showLoading();
      try {
        const response = await axios.put(`/api/invoices/${invoice.id}`, invoice);
        const index = this.invoices.findIndex(inv => inv.id === invoice.id);
        if (index !== -1) {
          this.invoices.splice(index, 1, response.data); // Update the invoice in the state
        }
        this.showSnackbar('Invoice updated successfully', 'success');
        return response.data; // Return the updated invoice
      } catch (error) {
        this.showSnackbar('Error updating invoice: ' + (error.response?.data?.message || error.message), 'error');
        console.error("Error updating invoice:", error);
        return null;
      } finally {
        this.hideLoading();
      }
    },

    async deleteInvoice(id) {
      this.showLoading();
      try {
        await axios.delete(`/api/invoices/${id}`);
        this.invoices = this.invoices.filter(inv => inv.id !== id); // Remove the invoice from the state
        this.showSnackbar('Invoice deleted successfully', 'success');
        return true;
      } catch (error) {
        this.showSnackbar('Error deleting invoice: ' + (error.response?.data?.message || error.message), 'error');
        console.error("Error deleting invoice:", error);
        return false;
      } finally {
        this.hideLoading();
      }
    },

    async generateInvoiceFromWorkLogs(payload) {
        this.showLoading();
        try {
            const response = await axios.post('/api/invoices/generate', payload);
            // Optionally fetch invoices again or add the new one to the list
            await this.fetchInvoices(); // Refresh the list to show the new invoice
            this.showSnackbar('Invoice generated successfully', 'success');
            return response.data;
        } catch (error) {
            this.showSnackbar('Error generating invoice: ' + (error.response?.data?.message || error.message), 'error');
            console.error("Error generating invoice:", error);
            return null;
        } finally {
            this.hideLoading();
        }
    },
    
    // Settings actions
    async fetchSettings() {
      try {
        const response = await axios.get('/api/settings');
        // Convert array of {key, value} to object
        const settingsObj = {};
        response.data.forEach(setting => {
          settingsObj[setting.key] = setting.value;
        });
        this.settings = settingsObj;
        
        // Apply language from settings if available
        const language = settingsObj.language || 'en';
        if (window.$i18n && window.$i18n.global) {
          if (window.$i18n.global.locale.value !== language) {
            window.$i18n.global.locale.value = language;
            document.documentElement.lang = language;
          }
        }
        
        return this.settings;
      } catch (error) {
        console.error('Failed to fetch settings:', error);
        // Set default values if fetch fails
        this.settings = {
          currency_symbol: '$'
        };
        return this.settings;
      }
    }
  }
});
