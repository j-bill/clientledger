<template>
  <div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
    <!-- Heading + primary actions -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('expenses.title') }}</h1>
      <div class="flex flex-wrap items-center gap-2">
        <ui-button variant="outline" :icon="Filter" @click="toggleFilters" />
        <ui-button variant="outline" :icon="Download" @click="exportExpenses">
          {{ $t('expenses.exportCSV') }}
        </ui-button>
        <ui-button variant="primary" :icon="Plus" @click="openCreateDialog">
          {{ $t('expenses.newExpense') }}
        </ui-button>
      </div>
    </div>

    <!-- Search -->
    <div class="mb-4 max-w-sm">
      <ui-input
        v-model="filters.search"
        :icon="Search"
        clearable
        :placeholder="$t('common.search')"
        @update:model-value="loadExpenses"
      />
    </div>

    <!-- Filters -->
    <ui-card v-if="showFilters" :title="$t('common.filters')" class="mb-4">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
        <ui-input
          v-model="filters.start_date"
          :label="$t('expenses.startDate')"
          type="date"
          :icon="Calendar"
          @update:model-value="loadExpenses"
        />
        <ui-input
          v-model="filters.end_date"
          :label="$t('expenses.endDate')"
          type="date"
          :icon="Calendar"
          @update:model-value="loadExpenses"
        />
        <ui-select
          v-model="filters.project_id"
          :items="projects"
          item-title="name"
          item-value="id"
          :label="$t('expenses.project')"
          clearable
          @update:model-value="loadExpenses"
        />
        <ui-select
          v-model="filters.customer_id"
          :items="customers"
          item-title="name"
          item-value="id"
          :label="$t('expenses.customer')"
          clearable
          @update:model-value="loadExpenses"
        />
      </div>
    </ui-card>

    <!-- Data Table -->
    <ui-card dense>
      <ui-data-table
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="expenses"
        :server-items-length="totalExpenses"
        :loading="loading"
        @update:options="loadExpenses"
      >
        <template v-slot:item.date="{ item }">
          <span class="tnum">{{ formatDate(item.date) }}</span>
        </template>
        <template v-slot:item.amount="{ item }">
          <span class="tnum">{{ formatCurrency(item.amount, item.currency) }}</span>
        </template>
        <template v-slot:item.is_tax_deductible="{ item }">
          <Check v-if="item.is_tax_deductible" class="h-4 w-4 text-sage-400" />
          <X v-else class="h-4 w-4 text-bone-700" />
        </template>
        <template v-slot:item.receipt_path="{ item }">
          <a
            v-if="item.receipt_path"
            :href="`/storage/${item.receipt_path}`"
            target="_blank"
            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-brass-400 transition-colors hover:bg-ink-850 hover:text-brass-300"
          >
            <FileText class="h-4 w-4" />
          </a>
        </template>
        <template v-slot:item.actions="{ item }">
          <div class="flex justify-end gap-1">
            <ui-button variant="ghost" size="sm" :icon="Pencil" @click="editExpense(item)" />
            <ui-button variant="danger-ghost" size="sm" :icon="Trash2" @click="deleteExpense(item)" />
          </div>
        </template>
      </ui-data-table>
    </ui-card>

    <!-- Create/Edit Dialog -->
    <ui-dialog v-model="dialog" :title="form.id ? $t('expenses.editExpense') : $t('expenses.newExpense')" max-width="600px">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <ui-input
            v-model="form.description"
            :label="$t('expenses.description')"
          />
        </div>
        <ui-input
          v-model="form.amount"
          :label="$t('expenses.amount')"
          type="number"
        />
        <ui-input
          v-model="form.currency"
          :label="$t('expenses.currency')"
        />
        <ui-input
          v-model="form.date"
          :label="$t('expenses.date')"
          type="date"
        />
        <ui-input
          v-model="form.category"
          :label="$t('expenses.category')"
        />
        <ui-select
          v-model="form.customer_id"
          :items="customers"
          item-title="name"
          item-value="id"
          :label="$t('expenses.customer')"
          clearable
          @update:model-value="onCustomerChange"
        />
        <ui-select
          v-model="form.project_id"
          :items="customerProjects"
          item-title="name"
          item-value="id"
          :label="$t('expenses.project')"
          clearable
          :disabled="!form.customer_id"
          :hint="!form.customer_id ? 'Select a customer first' : ''"
        />
        <div class="sm:col-span-2">
          <ui-file-input
            v-model="form.receipt"
            :label="$t('expenses.receipt')"
            accept="image/*,application/pdf"
          />
        </div>
        <div class="sm:col-span-2">
          <ui-checkbox
            v-model="form.is_tax_deductible"
            :label="$t('expenses.taxDeductible')"
          />
        </div>
      </div>

      <template #actions>
        <ui-button variant="ghost" @click="closeDialog">
          {{ $t('common.cancel') }}
        </ui-button>
        <ui-button variant="primary" @click="saveExpense">
          {{ $t('common.save') }}
        </ui-button>
      </template>
    </ui-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { store } from '../store';
import { formatDate as formatDateUtil, formatCurrency as formatCurrencyUtil } from '../utils/formatters';
import { Plus, Search, Filter, Download, Calendar, Pencil, Trash2, Check, X, FileText } from 'lucide-vue-next';

const { t } = useI18n();
const appStore = store();

const expenses = ref([]);
const totalExpenses = ref(0);
const itemsPerPage = ref(10);
const loading = ref(false);
const showFilters = ref(false);
const dialog = ref(false);
const projects = ref([]);
const customers = ref([]);
const customerProjects = ref([]);

const filters = reactive({
  search: '',
  start_date: '',
  end_date: '',
  project_id: null,
  customer_id: null,
});

const form = reactive({
  id: null,
  description: '',
  amount: '',
  currency: 'EUR',
  date: new Date().toISOString().substr(0, 10),
  category: '',
  project_id: null,
  customer_id: null,
  receipt: null,
  is_tax_deductible: true,
});

const headers = computed(() => [
  { title: t('expenses.date'), key: 'date', align: 'start' },
  { title: t('expenses.description'), key: 'description', align: 'start' },
  { title: t('expenses.category'), key: 'category', align: 'start' },
  { title: t('expenses.amount'), key: 'amount', align: 'end' },
  { title: t('expenses.project'), key: 'project.name', align: 'start' },
  { title: t('expenses.customer'), key: 'customer.name', align: 'start' },
  { title: t('expenses.taxDeductible'), key: 'is_tax_deductible', align: 'center' },
  { title: t('expenses.receipt'), key: 'receipt_path', align: 'center', sortable: false },
  { title: t('common.actions'), key: 'actions', align: 'end', sortable: false },
]);

const loadExpenses = async ({ page, itemsPerPage, sortBy } = {}) => {
  loading.value = true;
  try {
    const params = {
      page: page || 1,
      per_page: itemsPerPage || 10,
      ...filters,
    };

    if (sortBy && sortBy.length) {
        params.sort_by = sortBy[0].key;
        params.sort_dir = sortBy[0].order;
    }

    const response = await axios.get('/api/expenses', { params });
    expenses.value = response.data.data;
    totalExpenses.value = response.data.total;
  } catch (error) {
    console.error('Error loading expenses:', error);
  } finally {
    loading.value = false;
  }
};

const loadProjects = async () => {
  try {
    const response = await axios.get('/api/projects');
    projects.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading projects:', error);
  }
};

const onCustomerChange = async () => {
  // Clear project selection when customer changes
  form.project_id = null;
  customerProjects.value = [];

  if (!form.customer_id) {
    return;
  }

  try {
    const response = await axios.get('/api/invoices/customer-projects', {
      params: { customer_id: form.customer_id }
    });
    customerProjects.value = response.data || [];
  } catch (error) {
    console.error('Error loading customer projects:', error);
  }
};

const loadCustomers = async () => {
  try {
    const response = await axios.get('/api/customers');
    customers.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error loading customers:', error);
  }
};

const exportExpenses = async () => {
  try {
    const params = { ...filters };
    const response = await axios.get('/api/expenses/export', {
      params,
      responseType: 'blob',
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `expenses_export_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error('Error exporting expenses:', error);
  }
};

const toggleFilters = () => {
  showFilters.value = !showFilters.value;
};

const openCreateDialog = () => {
  resetForm();
  customerProjects.value = [];
  dialog.value = true;
};

const editExpense = (item) => {
  form.id = item.id;
  form.description = item.description;
  form.amount = item.amount;
  form.currency = item.currency;
  form.date = item.date;
  form.category = item.category;
  form.project_id = item.project_id;
  form.customer_id = item.customer_id;
  form.is_tax_deductible = !!item.is_tax_deductible;
  form.receipt = null; // Don't preload file
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
  resetForm();
};

const resetForm = () => {
  form.id = null;
  form.description = '';
  form.amount = '';
  form.currency = 'EUR';
  form.date = new Date().toISOString().substr(0, 10);
  form.category = '';
  form.project_id = null;
  form.customer_id = null;
  form.receipt = null;
  form.is_tax_deductible = true;
};

const saveExpense = async () => {
  try {
    const formData = new FormData();
    formData.append('description', form.description);
    formData.append('amount', form.amount);
    formData.append('currency', form.currency);
    formData.append('date', form.date);
    formData.append('category', form.category);
    if (form.project_id) formData.append('project_id', form.project_id);
    if (form.customer_id) formData.append('customer_id', form.customer_id);
    if (form.receipt) formData.append('receipt', form.receipt); // Vuetify file input returns array or file?
    formData.append('is_tax_deductible', form.is_tax_deductible ? '1' : '0');

    if (form.id) {
      formData.append('_method', 'PUT'); // Method spoofing for Laravel
      await axios.post(`/api/expenses/${form.id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
      });
    } else {
      await axios.post('/api/expenses', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
      });
    }
    closeDialog();
    loadExpenses();
  } catch (error) {
    console.error('Error saving expense:', error);
  }
};

const deleteExpense = async (item) => {
  if (confirm(t('expenses.deleteConfirmation'))) {
    try {
      await axios.delete(`/api/expenses/${item.id}`);
      loadExpenses();
    } catch (error) {
      console.error('Error deleting expense:', error);
    }
  }
};

const formatCurrency = (value, currency) => {
  return formatCurrencyUtil(value, appStore.settings);
};

const formatDate = (dateStr) => {
  return formatDateUtil(dateStr, appStore.settings);
};

onMounted(() => {
  loadProjects();
  loadCustomers();
});
</script>
