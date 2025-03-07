<template>
  <v-container>
    <h1 class="text-h4 mb-4">Projects</h1>
    
    <!-- Action Bar -->
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
          @input="fetchProjects"
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" prepend-icon="mdi-plus">
          New Project
        </v-btn>
      </v-col>
    </v-row>
    
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
          {{ item.hourly_rate ? `$${item.hourly_rate}` : 'N/A' }}
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="info" @click="viewProject(item)">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="primary" @click="openEditDialog(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
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
          <project-form ref="createForm" :customers="customers" @save="saveProject"></project-form>
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
          <project-form ref="editForm" :project="currentProject" :customers="customers" @save="updateProject"></project-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.editForm.submit()">Update</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- View Project Dialog -->
    <v-dialog v-model="viewDialog" max-width="800px">
      <v-card v-if="currentProject">
        <v-card-title>{{ currentProject.name }}</v-card-title>
        <v-card-text>
          <v-list>
            <v-list-item>
              <v-list-item-title>Customer:</v-list-item-title>
              <v-list-item-subtitle>{{ currentProject.customer?.name }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Hourly Rate:</v-list-item-title>
              <v-list-item-subtitle>${{ currentProject.hourly_rate || 'N/A' }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Deadline:</v-list-item-title>
              <v-list-item-subtitle>{{ currentProject.deadline ? formatDate(currentProject.deadline) : 'N/A' }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Description:</v-list-item-title>
              <v-list-item-subtitle>{{ currentProject.description || 'No description' }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" @click="viewDialog = false">Close</v-btn>
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
      loading: false,
      search: '',
      deleteDialog: false,
      createDialog: false,
      editDialog: false,
      viewDialog: false,
      itemToDelete: null,
      currentProject: null,
      
      headers: [
        { title: 'Name', key: 'name' },
        { title: 'Customer', key: 'customer.name' },
        { title: 'Hourly Rate', key: 'hourly_rate' },
        { title: 'Deadline', key: 'deadline' },
        { title: 'Actions', key: 'actions', sortable: false }
      ]
    };
  },
  
  created() {
    this.fetchProjects();
    this.fetchCustomers();
  },
  
  methods: {
    ...mapActions(store, ['showSnackbar']),
    async fetchProjects() {
      this.loading = true;
      
      try {
        const response = await axios.get('/api/projects');
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
    
    openCreateDialog() {
      this.createDialog = true;
    },
    
    openEditDialog(item) {
      this.currentProject = { ...item };
      this.editDialog = true;
    },
    
    viewProject(item) {
      this.currentProject = { ...item };
      this.viewDialog = true;
    },
    
    async saveProject(project) {
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
      return new Date(dateStr).toLocaleDateString();
    }
  }
};
</script>
