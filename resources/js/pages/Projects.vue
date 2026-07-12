<template>
  <div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
    <!-- Heading + primary actions -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('pages.projects.title') }}</h1>
      <div class="flex flex-wrap items-center gap-2">
        <ui-button variant="outline" :icon="Filter" @click="toggleFilters" />
        <ui-button v-if="isAdmin" variant="primary" data-test="btn-new-project" :icon="Plus" @click="openCreateDialog">
          {{ $t('pages.projects.newProject') }}
        </ui-button>
      </div>
    </div>

    <!-- Search -->
    <div class="mb-4 max-w-sm">
      <ui-input v-model="search" :icon="Search" clearable :placeholder="$t('common.search')" />
    </div>

    <!-- Filters -->
    <ui-card v-if="showFilters" :title="$t('common.filters')" class="mb-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <ui-autocomplete
            :model-value="null"
            :items="availableFilterCustomers"
            item-title="name"
            item-value="id"
            :label="$t('pages.projects.customers')"
            @update:model-value="addCustomerFilter"
          />
          <div v-if="filters.customers.length" class="mt-2 flex flex-wrap gap-1.5">
            <ui-chip v-for="id in filters.customers" :key="id" color="brass">
              {{ customerName(id) }}
              <button type="button" class="-mr-0.5 rounded-full hover:text-bone-100" @click="removeCustomerFilter(id)">
                <X class="h-3 w-3" />
              </button>
            </ui-chip>
          </div>
        </div>
        <div>
          <ui-autocomplete
            :model-value="null"
            :items="availableFilterFreelancers"
            item-title="name"
            item-value="id"
            :label="$t('pages.projects.freelancers')"
            @update:model-value="addFreelancerFilter"
          />
          <div v-if="filters.freelancers.length" class="mt-2 flex flex-wrap gap-1.5">
            <ui-chip v-for="id in filters.freelancers" :key="id" color="brass">
              {{ freelancerName(id) }}
              <button type="button" class="-mr-0.5 rounded-full hover:text-bone-100" @click="removeFreelancerFilter(id)">
                <X class="h-3 w-3" />
              </button>
            </ui-chip>
          </div>
        </div>
        <div class="flex justify-end sm:col-span-2">
          <ui-button variant="ghost" @click="resetFilters">
            {{ $t('common.reset') }}
          </ui-button>
        </div>
      </div>
    </ui-card>

    <!-- Projects Table -->
    <ui-card dense>
      <ui-data-table
        :headers="headers"
        :items="filteredProjects"
        :loading="loading"
        :sort-by="sortBy"
      >
        <template v-slot:item.deadline="{ item }">
          <span class="tnum">{{ item.deadline ? formatDate(item.deadline) : $t('pages.projects.na') }}</span>
        </template>
        <template v-slot:item.hourly_rate="{ item }">
          <span v-if="isAdmin" class="tnum">{{ formatCurrency(item.hourly_rate || 0) }}</span>
          <span v-else>-</span>
        </template>
        <template v-slot:item.users="{ item }">
          <div class="flex flex-wrap gap-1">
            <ui-chip
              v-for="user in item.users"
              :key="user.id"
              color="brass"
            >
              {{ user.name }}
            </ui-chip>
          </div>
        </template>
        <template v-slot:item.actions="{ item }">
          <div v-if="isAdmin" class="flex justify-end gap-1">
            <ui-button variant="ghost" size="sm" :icon="Pencil" @click="openEditDialog(item)" />
            <ui-button variant="danger-ghost" size="sm" :icon="Trash2" @click="confirmDelete(item)" />
          </div>
        </template>
      </ui-data-table>
    </ui-card>

    <!-- Delete Confirmation Dialog -->
    <ui-dialog v-model="deleteDialog" :title="$t('pages.projects.deleteProject')" max-width="500px" persistent>
      <p class="text-sm text-bone-300">{{ $t('pages.projects.deleteConfirmation') }}</p>
      <template #actions>
        <ui-button variant="ghost" @click="deleteDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="danger" @click="deleteProjectRecord">{{ $t('common.delete') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Create Project Dialog -->
    <ui-dialog v-model="createDialog" :title="$t('pages.projects.newProject')" max-width="800px" persistent>
      <project-form ref="createForm" :customers="customers" :freelancers="freelancers" @save="saveProject"></project-form>
      <template #actions>
        <ui-button variant="ghost" @click="createDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" @click="$refs.createForm.submit()">{{ $t('common.save') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Edit Project Dialog -->
    <ui-dialog v-model="editDialog" :title="$t('pages.projects.editProject')" max-width="800px" persistent>
      <project-form ref="editForm" :project="currentProject" :customers="customers" :freelancers="freelancers" @save="handleUpdateProject"></project-form>
      <template #actions>
        <ui-button variant="ghost" @click="editDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" @click="$refs.editForm.submit()">{{ $t('pages.projects.update') }}</ui-button>
      </template>
    </ui-dialog>
  </div>
</template>

<script>
import ProjectForm from '../components/forms/ProjectForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatDate, formatCurrency } from '../utils/formatters';
import { useI18n } from 'vue-i18n';
import { Plus, Search, Filter, Pencil, Trash2, X } from 'lucide-vue-next';

export default {
  name: 'ProjectsIndex',
  components: {
    ProjectForm,
    X
  },
  setup() {
    const { t } = useI18n();
    return { t, Plus, Search, Filter, Pencil, Trash2 };
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
    },

    // Client-side search preserving the old Vuetify custom-filter behavior
    // (matches project name, customer name, assigned users and description)
    filteredProjects() {
      if (!this.search) return this.projects;
      return this.projects.filter((p) => this.customSearch(null, this.search, { raw: p }));
    },

    availableFilterCustomers() {
      return this.customers.filter((c) => !this.filters.customers.includes(c.id));
    },

    availableFilterFreelancers() {
      return this.freelancers.filter((f) => !this.filters.freelancers.includes(f.id));
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

    customerName(id) {
      return this.customers.find((c) => c.id === id)?.name ?? id;
    },

    freelancerName(id) {
      return this.freelancers.find((f) => f.id === id)?.name ?? id;
    },

    addCustomerFilter(id) {
      if (id == null || this.filters.customers.includes(id)) return;
      this.filters.customers.push(id);
      this.applyFilters();
    },

    removeCustomerFilter(id) {
      this.filters.customers = this.filters.customers.filter((v) => v !== id);
      this.applyFilters();
    },

    addFreelancerFilter(id) {
      if (id == null || this.filters.freelancers.includes(id)) return;
      this.filters.freelancers.push(id);
      this.applyFilters();
    },

    removeFreelancerFilter(id) {
      this.filters.freelancers = this.filters.freelancers.filter((v) => v !== id);
      this.applyFilters();
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
