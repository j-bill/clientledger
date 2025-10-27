<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">{{ $t('pages.projects.title') }}</h1>
    
    <!-- Search & Actions -->
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          :label="$t('common.search')"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="d-flex justify-end">
        <v-btn color="secondary" @click="toggleFilters" class="mr-2">
          <v-icon>mdi-filter</v-icon>
        </v-btn>
        <v-btn v-if="isAdmin" color="primary" data-test="btn-new-project" @click="openCreateDialog" prepend-icon="mdi-plus">
          {{ $t('pages.projects.newProject') }}
        </v-btn>
      </v-col>
    </v-row>
    
    <!-- Filters -->
    <v-card v-if="showFilters" class="mb-4">
      <v-card-title>{{ $t('common.filters') }}</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" sm="6" md="4">
            <v-autocomplete
              v-model="filters.customers"
              :items="customers"
              item-title="name"
              item-value="id"
              :label="$t('pages.projects.customers')"
              multiple
              chips
              closable-chips
              clearable
              prepend-icon="mdi-account"
              :search-input.sync="customerSearch"
              @update:search="searchCustomers"
              @update:modelValue="applyFilters"
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6" md="4">
            <v-autocomplete
              v-model="filters.freelancers"
              :items="freelancers"
              item-title="name"
              item-value="id"
              :label="$t('pages.projects.freelancers')"
              multiple
              chips
              closable-chips
              clearable
              prepend-icon="mdi-account"
              :search-input.sync="freelancerSearch"
              @update:search="searchFreelancers"
              @update:modelValue="applyFilters"
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" class="text-right">
            <v-btn class="ms-2" @click="resetFilters">
              {{ $t('common.reset') }}
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
        :custom-filter="customSearch"
        :sort-by="sortBy"
      >
        <template v-slot:item.deadline="{ item }">
          {{ item.deadline ? formatDate(item.deadline) : $t('pages.projects.na') }}
        </template>
        <template v-slot:item.hourly_rate="{ item }">
          <span v-if="isAdmin">{{ formatCurrency(item.hourly_rate || 0) }}</span>
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
        <v-card-title>{{ $t('pages.projects.deleteProject') }}</v-card-title>
        <v-card-text>
          {{ $t('pages.projects.deleteConfirmation') }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="error" @click="deleteProjectRecord">{{ $t('common.delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Create Project Dialog -->
    <v-dialog v-model="createDialog" max-width="800px">
      <v-card>
        <v-card-title>{{ $t('pages.projects.newProject') }}</v-card-title>
        <v-card-text>
          <project-form ref="createForm" :customers="customers" :freelancers="freelancers" @save="saveProject"></project-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="createDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="primary" @click="$refs.createForm.submit()">{{ $t('common.save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Edit Project Dialog -->
    <v-dialog v-model="editDialog" max-width="800px">
      <v-card>
        <v-card-title>{{ $t('pages.projects.editProject') }}</v-card-title>
        <v-card-text>
          <project-form ref="editForm" :project="currentProject" :customers="customers" :freelancers="freelancers" @save="handleUpdateProject"></project-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="editDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="primary" @click="$refs.editForm.submit()">{{ $t('pages.projects.update') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import ProjectForm from '../components/forms/ProjectForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatDate, formatCurrency } from '../utils/formatters';
import { useI18n } from 'vue-i18n';

export default {
  name: 'ProjectsIndex',
  components: {
    ProjectForm
  },
  setup() {
    const { t } = useI18n();
    return { t };
  },
  data() {
    return {
      loading: false,
      search: '',
      deleteDialog: false,
      createDialog: false,
      editDialog: false,
      itemToDelete: null,
      currentProject: null,
      showFilters: false,
      filters: {
        customers: [],
        freelancers: []
      },
      customerSearch: '',
      freelancerSearch: '',
      
      sortBy: [{ key: 'id', order: 'desc' }]
    };
  },
  
  computed: {
    ...mapState(store, ['projects', 'customers', 'users', 'user', 'currencySymbol', 'settings']),
    
    headers() {
      return [
        { title: 'ID', key: 'id' },
        { title: this.t('forms.project.name'), key: 'name' },
        { title: this.t('pages.projects.customer'), key: 'customer.name' },
        { title: this.t('pages.projects.projectRate'), key: 'hourly_rate' },
        { title: this.t('pages.projects.assignedFreelancers'), key: 'users', sortable: false },
        { title: this.t('pages.projects.deadline'), key: 'deadline' },
        { title: this.t('common.actions'), key: 'actions', sortable: false }
      ];
    },
    
    isAdmin() {
      return this.user?.role === 'admin';
    },
    
    freelancers() {
      // Return all users (including admins) for project assignment
      return this.users;
    }
  },
  
  created() {
    this.loadData();
  },
  
  methods: {
    ...mapActions(store, [
      'showSnackbar',
      'fetchProjects',
      'fetchCustomers', 
      'fetchUsers',
      'createProject',
      'updateProject',
      'deleteProject'
    ]),
    
    async loadData() {
      this.loading = true;
      
      try {
        await Promise.all([
          this.fetchProjects(),
          this.fetchCustomers(),
          this.fetchUsers()
        ]);
      } catch (error) {
        console.error('Error loading data:', error);
      } finally {
        this.loading = false;
      }
    },
    
    customSearch(value, query, item) {
      if (!query) return true;
      
      const searchLower = query.toString().toLowerCase();
      
      // Search in project name
      if (item.raw.name && item.raw.name.toLowerCase().includes(searchLower)) {
        return true;
      }
      
      // Search in customer name
      if (item.raw.customer && item.raw.customer.name && item.raw.customer.name.toLowerCase().includes(searchLower)) {
        return true;
      }
      
      // Search in assigned users
      if (item.raw.users && item.raw.users.length > 0) {
        const userMatch = item.raw.users.some(user => 
          user.name && user.name.toLowerCase().includes(searchLower)
        );
        if (userMatch) return true;
      }
      
      // Search in description
      if (item.raw.description && item.raw.description.toLowerCase().includes(searchLower)) {
        return true;
      }
      
      return false;
    },
    
    openCreateDialog() {
      this.createDialog = true;
    },
    
    openEditDialog(item) {
      this.currentProject = { ...item };
      this.editDialog = true;
    },
    
    async saveProject(project) {
      try {
        await this.createProject(project);
        this.createDialog = false;
      } catch (error) {
        console.error('Error creating project:', error);
      }
    },
    
    async handleUpdateProject(project) {
      try {
        await this.updateProject(project);
        this.editDialog = false;
      } catch (error) {
        console.error('Error updating project:', error);
      }
    },
    
    confirmDelete(item) {
      this.itemToDelete = item;
      this.deleteDialog = true;
    },
    
    async deleteProjectRecord() {
      try {
        await this.deleteProject(this.itemToDelete.id);
        this.deleteDialog = false;
      } catch (error) {
        console.error('Error deleting project:', error);
      }
    },
    
    formatDate(dateStr) {
      return formatDate(dateStr, this.settings);
    },
    
    formatCurrency(amount) {
      return formatCurrency(amount, this.settings);
    },

    async checkUserRole() {
      try {
        const response = await axios.get('/api/user');
        this.isAdmin = response.data.role === 'admin';
        this.currentUserId = response.data.id;
      } catch (error) {
        console.error('Error checking user role:', error);
        this.showSnackbar('Error loading user information', 'error');
      }
    },
    
    toggleFilters() {
      this.showFilters = !this.showFilters;
    },

    applyFilters() {
      const params = {};
      if (this.filters.customers.length > 0) {
        params.customers = this.filters.customers;
      }
      if (this.filters.freelancers.length > 0) {
        params.freelancers = this.filters.freelancers;
      }
      this.fetchProjects(params);
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
