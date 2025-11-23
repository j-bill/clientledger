<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">{{ $t('expenses.title') }}</h1>

    <!-- Search & Actions -->
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="filters.search"
          :label="$t('common.search')"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
          @input="loadExpenses"
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="d-flex justify-end">
        <v-btn color="secondary" @click="toggleFilters" class="mr-2">
          <v-icon>mdi-filter</v-icon>
        </v-btn>
        <v-btn color="success" @click="exportExpenses" class="mr-2" prepend-icon="mdi-download">
          {{ $t('expenses.exportCSV') }}
        </v-btn>
        <v-btn color="primary" @click="openCreateDialog" prepend-icon="mdi-plus">
          {{ $t('expenses.newExpense') }}
        </v-btn>
      </v-col>
    </v-row>

    <!-- Filters -->
    <v-card v-if="showFilters" class="mb-4">
      <v-card-title>{{ $t('common.filters') }}</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" sm="6" md="3">
            <v-text-field
              v-model="filters.start_date"
              :label="$t('expenses.startDate')"
              type="date"
              prepend-icon="mdi-calendar"
              @change="loadExpenses"
            ></v-text-field>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-text-field
              v-model="filters.end_date"
              :label="$t('expenses.endDate')"
              type="date"
              prepend-icon="mdi-calendar"
              @change="loadExpenses"
            ></v-text-field>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="filters.project_id"
              :items="projects"
              item-title="name"
              item-value="id"
              :label="$t('expenses.project')"
              clearable
              prepend-icon="mdi-folder"
              @update:model-value="loadExpenses"
            ></v-select>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="filters.customer_id"
              :items="customers"
              item-title="name"
              item-value="id"
              :label="$t('expenses.customer')"
              clearable
              prepend-icon="mdi-account"
              @update:model-value="loadExpenses"
            ></v-select>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Data Table -->
    <v-data-table-server
      v-model:items-per-page="itemsPerPage"
      :headers="headers"
      :items="expenses"
      :items-length="totalExpenses"
      :loading="loading"
      @update:options="loadExpenses"
    >
      <template v-slot:item.date="{ item }">
        {{ formatDate(item.date) }}
      </template>
      <template v-slot:item.amount="{ item }">
        {{ formatCurrency(item.amount, item.currency) }}
      </template>
      <template v-slot:item.is_tax_deductible="{ item }">
        <v-icon :color="item.is_tax_deductible ? 'success' : 'grey'">
          {{ item.is_tax_deductible ? 'mdi-check' : 'mdi-close' }}
        </v-icon>
      </template>
      <template v-slot:item.receipt_path="{ item }">
        <v-btn
          v-if="item.receipt_path"
          icon="mdi-file-document"
          variant="text"
          color="primary"
          :href="`/storage/${item.receipt_path}`"
          target="_blank"
        ></v-btn>
      </template>
      <template v-slot:item.actions="{ item }">
        <v-icon size="small" class="me-2" @click="editExpense(item)">
          mdi-pencil
        </v-icon>
        <v-icon size="small" @click="deleteExpense(item)">
          mdi-delete
        </v-icon>
      </template>
    </v-data-table-server>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600px">
      <v-card>
        <v-card-title>
          <span class="text-h5">{{ form.id ? $t('expenses.editExpense') : $t('expenses.newExpense') }}</span>
        </v-card-title>

        <v-card-text>
          <v-container>
            <v-row>
              <v-col cols="12">
                <v-text-field
                  v-model="form.description"
                  :label="$t('expenses.description')"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.amount"
                  :label="$t('expenses.amount')"
                  type="number"
                  step="0.01"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.currency"
                  :label="$t('expenses.currency')"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.date"
                  :label="$t('expenses.date')"
                  type="date"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field
                  v-model="form.category"
                  :label="$t('expenses.category')"
                  required
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.customer_id"
                  :items="customers"
                  item-title="name"
                  item-value="id"
                  :label="$t('expenses.customer')"
                  clearable
                  @update:model-value="onCustomerChange"
                ></v-select>
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.project_id"
                  :items="customerProjects"
                  item-title="name"
                  item-value="id"
                  :label="$t('expenses.project')"
                  clearable
                  :disabled="!form.customer_id"
                  :hint="!form.customer_id ? 'Select a customer first' : ''"
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-file-input
                  v-model="form.receipt"
                  :label="$t('expenses.receipt')"
                  accept="image/*,application/pdf"
                  prepend-icon="mdi-camera"
                ></v-file-input>
              </v-col>
              <v-col cols="12">
                <v-checkbox
                  v-model="form.is_tax_deductible"
                  :label="$t('expenses.taxDeductible')"
                ></v-checkbox>
              </v-col>
            </v-row>
          </v-container>
        </v-card-text>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="blue-darken-1" variant="text" @click="closeDialog">
            {{ $t('common.cancel') }}
          </v-btn>
          <v-btn color="blue-darken-1" variant="text" @click="saveExpense">
            {{ $t('common.save') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { store } from '../store';
import { formatDate as formatDateUtil, formatCurrency as formatCurrencyUtil } from '../utils/formatters';

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
