<template>
  <v-container>
    <h1 class="text-h4 mb-4">Projects</h1>
    
    <!-- Action Bar -->
    <v-row class="mb-4">
      <v-col cols="12" class="d-flex justify-space-between">
        <v-btn color="secondary" @click="toggleFilters" prepend-icon="mdi-filter">
          Display Filters
        </v-btn>

        <v-btn v-if="isAdmin" color="primary" @click="openCreateDialog" prepend-icon="mdi-plus">
          New Project
        </v-btn>
      </v-col>
    </v-row>
    
    <!-- Filters -->
    <v-card v-if="showFilters" class="mb-6">
      <v-card-title>Filters</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" sm="6" md="4">
            <v-autocomplete
              v-model="filters.customers"
              :items="customers"
              item-title="name"
              item-value="id"
              label="Customers"
              multiple
              chips
              closable-chips
              clearable
              prepend-icon="mdi-account"
              :search-input.sync="customerSearch"
              @update:search="searchCustomers"
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6" md="4">
            <v-autocomplete
              v-model="filters.freelancers"
              :items="freelancers"
              item-title="name"
              item-value="id"
              label="Freelancers"
              multiple
              chips
              closable-chips
              clearable
              prepend-icon="mdi-account"
              :search-input.sync="freelancerSearch"
              @update:search="searchFreelancers"
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" class="text-right">
            <v-btn color="primary" @click="applyFilters">
              Apply Filters
            </v-btn>
            <v-btn class="ms-2" @click="resetFilters">
              Reset
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
    
    <!-- Projects Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="projects"
        :loading="loading"
        class="elevation-1"
        :search="search"
      >
        <template v-slot:item.deadline="{ item }">
          {{ item.deadline ? formatDate(item.deadline) : 'N/A' }}
        </template>
        <template v-slot:item.hourly_rate="{ item }">
          <span v-if="isAdmin">${{ Number(item.hourly_rate || 0).toFixed(2) }}</span>
          <span v-else>-</span>
        </template>
        <template v-slot:item.users="{ item }">
          <v-chip-group>
            <v-chip
              v-for="user in item.users"
              :key="user.id"
              size="small"
              color="primary"
              variant="outlined"
            >
              {{ user.name }}
            </v-chip>
          </v-chip-group>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="primary" @click="openViewDialog(item)">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <template v-if="isAdmin">
            <v-btn icon variant="text" size="small" color="primary" @click="openEditDialog(item)">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
            <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </template>
        </template>
      </v-data-table>
    </v-card>
    
    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title>Delete Project</v-card-title>
        <v-card-text>
          Are you sure you want to delete this project? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteProject">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Create Project Dialog -->
    <v-dialog v-model="createDialog" max-width="800px">
      <v-card>
        <v-card-title>New Project</v-card-title>
        <v-card-text>
          <project-form ref="createForm" :customers="customers" :freelancers="freelancers" @save="saveProject"></project-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.createForm.submit()">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Edit Project Dialog -->
    <v-dialog v-model="editDialog" max-width="800px">
      <v-card>
        <v-card-title>Edit Project</v-card-title>
        <v-card-text>
          <project-form ref="editForm" :project="currentProject" :customers="customers" :freelancers="freelancers" @save="updateProject"></project-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.editForm.submit()">Update</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Project View Dialog -->
    <v-dialog v-model="viewDialog" max-width="800px">
      <v-card>
        <v-card-title>Project Details</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12">
              <strong>Name:</strong> {{ currentProject?.name }}
            </v-col>
            <v-col cols="12">
              <strong>Customer:</strong> {{ currentProject?.customer?.name }}
            </v-col>
            <v-col cols="12" v-if="isAdmin">
              <strong>Project Rate:</strong> ${{ Number(currentProject?.hourly_rate || 0).toFixed(2) }}
            </v-col>
            <v-col cols="12">
              <strong>Deadline:</strong> {{ formatDate(currentProject?.deadline) }}
            </v-col>
            <v-col cols="12">
              <strong>Description:</strong> {{ currentProject?.description }}
            </v-col>
            <v-col cols="12">
              <strong>Assigned Freelancers:</strong>
              <v-chip-group class="mt-2">
                <v-chip v-for="user in currentProject?.users" :key="user.id" color="primary" variant="outlined">
                  {{ user.name }} ( ${{ Number(user.hourly_rate || 0).toFixed(2) }}/hr )
                </v-chip>
              </v-chip-group>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="viewDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import axios from 'axios';
import ProjectForm from '../components/forms/ProjectForm.vue';
import { mapActions } from 'pinia';
import { store } from '../store';

export default {
  name: 'ProjectsIndex',
  components: {
    ProjectForm
  },
  data() {
    return {
      projects: [],
      customers: [],
      freelancers: [],
      loading: false,
      search: '',
      deleteDialog: false,
      createDialog: false,
      editDialog: false,
      viewDialog: false,
      itemToDelete: null,
      currentProject: null,
      isAdmin: false,
      showFilters: false,
      filters: {
        customers: [],
        freelancers: []
      },
      customerSearch: '',
      freelancerSearch: '',
      
      headers: [
        { title: 'Name', key: 'name' },
        { title: 'Customer', key: 'customer.name' },
        { title: 'Project Rate', key: 'hourly_rate' },
        { title: 'Assigned Freelancers', key: 'users' },
        { title: 'Deadline', key: 'deadline' },
        { title: 'Actions', key: 'actions', sortable: false }
      ]
    };
  },
  
  created() {
    this.fetchProjects();
    this.fetchCustomers();
    this.fetchFreelancers();
    this.checkUserRole();
  },
  
  methods: {
    ...mapActions(store, ['showSnackbar']),
    async fetchProjects() {
      this.loading = true;
      
      try {
        // Filter out null values
        const params = { ...this.filters };
        Object.keys(params).forEach(key => {
          if (params[key] === null) delete params[key];
        });

        const response = await axios.get('/api/projects', { params });
        this.projects = response.data;
      } catch (error) {
        console.error('Error fetching projects:', error);
      } finally {
        this.loading = false;
      }
    },
    
    async fetchCustomers() {
      try {
        const response = await axios.get('/api/customers');
        this.customers = response.data;
      } catch (error) {
        console.error('Error fetching customers:', error);
      }
    },

    async fetchFreelancers() {
      try {
        const response = await axios.get('/api/users');
        this.freelancers = response.data.filter(user => user.role === 'freelancer');
      } catch (error) {
        console.error('Error fetching freelancers:', error);
      }
    },
    
    openCreateDialog() {
      this.createDialog = true;
    },
    
    openEditDialog(item) {
      this.currentProject = { ...item };
      this.editDialog = true;
    },
    
    openViewDialog(item) {
      this.currentProject = { ...item };
      this.viewDialog = true;
    },
    
    async saveProject(project) {
      console.dir(project)
      try {
        const response = await axios.post('/api/projects', project);
        this.projects.unshift(response.data);
        this.createDialog = false;
        this.fetchProjects();
        this.showSnackbar('Project created successfully', 'success');
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to create project';
        this.showSnackbar(message, 'error');
      }
    },
    
    async updateProject(project) {
      console.dir(project)

      try {
        const response = await axios.put(`/api/projects/${project.id}`, project);
        const index = this.projects.findIndex(p => p.id === project.id);
        if (index !== -1) {
          this.projects.splice(index, 1, response.data);
        }
        this.editDialog = false;
        this.showSnackbar('Project updated successfully', 'success');
      } catch (error) {
        const message = error.response?.data?.errors 
          ? Object.values(error.response.data.errors)[0][0]
          : 'Failed to update project';
        this.showSnackbar(message, 'error');
      }
    },
    
    confirmDelete(item) {
      this.itemToDelete = item;
      this.deleteDialog = true;
    },
    
    async deleteProject() {
      try {
        await axios.delete(`/api/projects/${this.itemToDelete.id}`);
        this.projects = this.projects.filter(p => p.id !== this.itemToDelete.id);
        this.deleteDialog = false;
      } catch (error) {
        console.error('Error deleting project:', error);
      }
    },
    
    formatDate(dateStr) {
      let date = new Date(dateStr)

      const year = date.getFullYear();
      const month = date.getMonth() + 1;
      const day = date.getDate();

      return `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    },

    async checkUserRole() {
      try {
        const response = await axios.get('/api/user');
        this.isAdmin = response.data.role === 'admin';
      } catch (error) {
        console.error('Error checking user role:', error);
      }
    },
    
    toggleFilters() {
      this.showFilters = !this.showFilters;
    },

    applyFilters() {
      this.fetchProjects();
    },

    resetFilters() {
      // Reset filters to default
      this.filters = {
        customers: [],
        freelancers: []
      };
      this.fetchProjects();
    },

    searchCustomers(query) {
      this.customerSearch = query;
    },

    searchFreelancers(query) {
      this.freelancerSearch = query;
    }
  }
};
</script>
