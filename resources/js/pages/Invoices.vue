<template>
  <div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
    <!-- Heading + primary actions -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('invoices.title') }}</h1>
      <div class="flex flex-wrap items-center gap-2">
        <ui-button variant="ghost" :icon="Filter" @click="toggleFilters" />
        <ui-button variant="ghost" data-test="btn-generate" :icon="FilePlus" @click="openGenerateDialog">
          {{ $t('invoices.generateFromWorkLogs') }}
        </ui-button>
        <ui-button variant="primary" data-test="btn-new" :icon="Plus" @click="openCreateDialog">
          {{ $t('invoices.newInvoice') }}
        </ui-button>
      </div>
    </div>

    <!-- Search -->
    <div class="mb-4 max-w-sm">
      <ui-input v-model="search" :icon="Search" clearable :placeholder="$t('common.search')" />
    </div>

    <!-- Filters -->
    <ui-card v-if="showFilters" :title="$t('common.filters')" class="mb-4">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <ui-select
          v-model="selectedCustomerId"
          :items="customers"
          item-title="name"
          item-value="id"
          :label="$t('customers.customer')"
          clearable
          @update:model-value="applyFilters"
        />
        <ui-select
          v-model="selectedStatus"
          :items="statuses"
          :label="$t('invoices.status')"
          clearable
          @update:model-value="applyFilters"
        />
      </div>
      <div class="mt-4 flex justify-end">
        <ui-button variant="ghost" @click="resetFilters">{{ $t('common.reset') }}</ui-button>
      </div>
    </ui-card>

    <ui-card dense>
      <ui-data-table
        :headers="headers"
        :items="invoices"
        :loading="loading"
        :search="search"
        :sort-by="sortBy"
        :row-props="getRowProps"
      >
        <template v-slot:item.invoice_number="{ item }">
          <span class="tnum">{{ item.invoice_number || '-' }}</span>
        </template>
        <template v-slot:item.issue_date="{ item }">
          <span class="tnum">{{ formatDate(item.issue_date) }}</span>
        </template>
        <template v-slot:item.due_date="{ item }">
          <span class="tnum">{{ formatDate(item.due_date) }}</span>
        </template>
        <template v-slot:item.status="{ item }">
          <ui-chip :color="getStatusColor(item.status)" :text="item.status" />
        </template>
        <template v-slot:item.total_amount="{ item }">
          <span class="tnum">{{ formatCurrency(item.total_amount) }}</span>
        </template>
        <template v-slot:item.actions="{ item }">
          <div class="flex justify-end gap-1">
            <ui-button variant="ghost" size="sm" :icon="FileText" :disabled="!item.pdf_path" :title="item.pdf_path ? $t('invoices.viewPdf') : $t('common.notAvailable')" @click="viewPdf(item)" />
            <ui-button variant="ghost" size="sm" :icon="Download" :disabled="!item.pdf_path" :title="item.pdf_path ? $t('invoices.downloadPdf') : $t('common.notAvailable')" @click="downloadPdf(item)" />
            <ui-button variant="ghost" size="sm" :icon="FilePlus" :disabled="!!item.pdf_path" :title="item.pdf_path ? $t('common.warning') : $t('invoices.generatePdf')" @click="generatePdfDialog(item)" />
            <ui-button variant="ghost" size="sm" :icon="Upload" :disabled="!!item.pdf_path" :title="item.pdf_path ? $t('common.warning') : $t('invoices.uploadPdf')" @click="openUploadDialog(item)" />
            <ui-button variant="ghost" size="sm" :icon="Pencil" :title="$t('common.edit')" @click="openEditDialog(item)" />
            <ui-button variant="danger-ghost" size="sm" :icon="Trash2" :title="$t('common.delete')" @click="confirmDelete(item)" />
          </div>
        </template>
      </ui-data-table>
    </ui-card>

    <!-- Create Invoice Dialog -->
    <ui-dialog v-model="createDialog" :title="$t('invoices.newInvoice')" max-width="800px" persistent>
      <invoice-form ref="createForm" @save="handleInvoiceSave"></invoice-form>
      <template #actions>
        <ui-button variant="ghost" data-test="btn-cancel-create" @click="createDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" data-test="btn-save-create" @click="$refs.createForm.submit()">{{ $t('common.save') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Generate from Work Logs Dialog -->
    <ui-dialog v-model="generateDialog" title="Generate Invoice from Work Logs" max-width="1200px" persistent>
      <!-- Customer Selection -->
      <div class="mb-4">
        <ui-autocomplete
          v-model="generateForm.customer_id"
          data-test="gen-customer"
          :items="customers"
          item-title="name"
          item-value="id"
          label="Customer"
          :rules="[v => !!v || 'Customer is required']"
          @update:model-value="onGenerateCustomerChange"
        />
      </div>

      <!-- Filters Section -->
      <ui-card v-if="generateForm.customer_id" title="Filter Work Logs" class="mb-4" flat>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <ui-select
            v-model="workLogFilters.project_id"
            :items="customerProjects"
            item-title="name"
            item-value="id"
            label="Project"
            clearable
            @update:model-value="loadFilteredWorkLogs"
          />
          <ui-input
            v-model="workLogFilters.start_date"
            type="date"
            label="Start Date"
            clearable
            @update:model-value="loadFilteredWorkLogs"
          />
          <ui-input
            v-model="workLogFilters.end_date"
            type="date"
            label="End Date"
            clearable
            @update:model-value="loadFilteredWorkLogs"
          />
        </div>
        <div class="mt-4 flex justify-end">
          <ui-button variant="ghost" :icon="FilterX" @click="resetWorkLogFilters">
            Reset Filters
          </ui-button>
        </div>
      </ui-card>

      <!-- Work Logs Selection -->
      <ui-card v-if="generateForm.customer_id" class="mb-4" flat>
        <template #title>Available Work Logs ({{ filteredWorkLogs.length }})</template>
        <template #actions>
          <ui-button
            variant="brass-ghost"
            size="sm"
            :disabled="filteredWorkLogs.length === 0"
            @click="selectAllWorkLogs"
          >
            Select All
          </ui-button>
          <ui-button
            variant="ghost"
            size="sm"
            :disabled="generateForm.work_log_ids.length === 0"
            @click="clearWorkLogSelection"
          >
            Clear Selection
          </ui-button>
        </template>
        <ui-alert v-if="filteredWorkLogs.length === 0" type="info">
          No unbilled work logs found for the selected criteria.
        </ui-alert>
        <ul v-else class="divide-y divide-ink-700/60">
          <li
            v-for="log in filteredWorkLogs"
            :key="log.id"
            class="flex cursor-pointer items-start gap-3 px-2 py-3 hover:bg-ink-850"
            :class="{ 'bg-slate-900/40': generateForm.work_log_ids.includes(log.id) }"
            @click="toggleWorkLog(log.id)"
          >
            <div class="pt-0.5" @click.stop>
              <ui-checkbox
                :model-value="generateForm.work_log_ids.includes(log.id)"
                @update:model-value="toggleWorkLog(log.id)"
              />
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-sm text-bone-100">
                <span class="tnum">{{ formatDate(log.date) }}</span> - {{ log.project?.name || 'Unknown Project' }}
              </div>
              <div class="mt-0.5 text-xs text-bone-500">
                {{ log.user?.name || 'Unknown User' }} &bull;
                <span class="tnum">{{ formatNumber(log.hours_worked || 0, 2) }}h</span> &bull;
                <span class="tnum">{{ formatCurrency(log.billing_rate * (log.hours_worked || 0)) }}</span>
                <br>
                <span>{{ log.description || 'No description' }}</span>
              </div>
            </div>
            <ui-chip color="brass" :text="formatCurrency(log.billing_rate * (log.hours_worked || 0))" />
          </li>
        </ul>
      </ui-card>

      <!-- Additional Items -->
      <ui-card v-if="generateForm.customer_id" class="mb-4" flat>
        <template #title>{{ $t('forms.invoice.items') }}</template>
        <template #actions>
          <ui-button variant="brass-ghost" size="sm" :icon="Plus" data-test="gen-add-item" @click="addGenerateItem">
            {{ $t('forms.invoice.addItem') }}
          </ui-button>
        </template>
        <p v-if="generateForm.items.length === 0" class="text-xs text-bone-500">
          {{ $t('forms.invoice.itemsHint') }}
        </p>
        <div
          v-for="(item, index) in generateForm.items"
          :key="index"
          class="mb-2 flex items-start gap-2"
          :data-test="`gen-item-${index}`"
        >
          <div class="min-w-0 flex-1">
            <ui-input v-model="item.description" :label="$t('forms.invoice.itemDescription')" />
          </div>
          <div class="w-24 shrink-0">
            <ui-input v-model="item.quantity" :label="$t('forms.invoice.quantity')" type="number" step="0.01" min="0" />
          </div>
          <div class="w-32 shrink-0">
            <ui-input v-model="item.unit_price" :label="$t('forms.invoice.unitPrice')" type="number" step="0.01" />
          </div>
          <ui-button
            variant="danger-ghost"
            size="sm"
            class="mt-6 shrink-0"
            :icon="Trash2"
            :title="$t('common.delete')"
            @click="removeGenerateItem(index)"
          />
        </div>
      </ui-card>

      <!-- Invoice Details -->
      <div v-if="generateForm.customer_id" class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <ui-input
          v-model="generateForm.due_date"
          data-test="gen-due-date"
          type="date"
          label="Due Date"
          :rules="[v => !!v || 'Due date is required']"
        />
        <ui-select
          v-model="generateForm.status"
          data-test="gen-status"
          :items="['draft','sent','paid','overdue','cancelled']"
          label="Status"
          :rules="[v => !!v || 'Status is required']"
        />
      </div>

      <!-- Selected Work Logs Summary -->
      <div v-if="generateForm.work_log_ids.length > 0 || generateForm.items.length > 0" class="mt-4 rounded-lg border border-brass-500/40 bg-brass-900/30 p-4">
        <div class="mb-3 flex items-center justify-between text-sm text-bone-100">
          <div>
            <strong class="tnum">{{ generateForm.work_log_ids.length }}</strong> work log{{ generateForm.work_log_ids.length !== 1 ? 's' : '' }} selected
            <template v-if="generateForm.items.length > 0">
              + <strong class="tnum">{{ generateForm.items.length }}</strong> item{{ generateForm.items.length !== 1 ? 's' : '' }}
            </template>
          </div>
          <div>
            <strong>Subtotal: <span class="tnum">{{ currencySymbol }}{{ calculateGrandSubtotal().toFixed(2) }}</span></strong>
          </div>
        </div>
        <hr class="my-2 border-ink-700/60" />
        <div class="text-xs text-bone-500">
          Tax Rate ({{ settings.tax_rate }}%): <span class="tnum">{{ currencySymbol }}{{ (calculateGrandSubtotal() * (settings.tax_rate / 100)).toFixed(2) }}</span>
        </div>
        <div class="mt-2 text-lg font-semibold text-bone-100">
          Total: <span class="tnum">{{ currencySymbol }}{{ (calculateGrandSubtotal() * (1 + (settings.tax_rate / 100))).toFixed(2) }}</span>
        </div>
      </div>

      <template #actions>
        <ui-button variant="ghost" data-test="btn-cancel-generate" @click="generateDialog = false">Cancel</ui-button>
        <ui-button
          variant="primary"
          data-test="btn-generate-confirm"
          :disabled="!generateForm.customer_id || generateForm.work_log_ids.length === 0 || !generateForm.due_date || !generateForm.status"
          @click="generateInvoice"
        >
          Generate Invoice (<span class="tnum">{{ currencySymbol }}{{ (calculateGrandSubtotal() * (1 + (settings.tax_rate / 100))).toFixed(2) }}</span>)
        </ui-button>
      </template>
    </ui-dialog>

    <!-- Edit Invoice Dialog -->
    <ui-dialog v-model="editDialog" title="Edit Invoice" max-width="800px" persistent>
      <invoice-form ref="editForm" :invoice="currentInvoice" @save="handleInvoiceSave"></invoice-form>
      <template #actions>
        <ui-button variant="ghost" @click="editDialog = false">Cancel</ui-button>
        <ui-button variant="primary" @click="$refs.editForm.submit()">Save</ui-button>
      </template>
    </ui-dialog>

    <!-- Upload PDF Dialog -->
    <ui-dialog v-model="uploadDialog" title="Upload Invoice PDF" max-width="600px" persistent>
      <ui-alert type="info" class="mb-4">
        Upload an existing invoice PDF. This is useful when migrating from another system.
        Once uploaded, the PDF cannot be changed or regenerated.
      </ui-alert>
      <ui-file-input
        v-model="pdfFile"
        label="Select PDF file"
        accept="application/pdf"
        :rules="[v => !!v || 'PDF file is required', v => !v || v.type === 'application/pdf' || 'File must be a PDF']"
      />
      <template #actions>
        <ui-button variant="ghost" @click="uploadDialog = false">Cancel</ui-button>
        <ui-button variant="primary" :loading="uploading" :disabled="!pdfFile" @click="uploadPdf">Upload</ui-button>
      </template>
    </ui-dialog>

    <ui-dialog v-model="generatePdfConfirmDialog" :title="$t('invoices.generatePdf')" max-width="500px" persistent>
      <p class="text-sm text-bone-300">{{ $t('invoices.generatePdfConfirm') }}</p>
      <template #actions>
        <ui-button variant="ghost" @click="generatePdfConfirmDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" :loading="generatingPdf" @click="generatePdfNow">{{ $t('invoices.generatePdf') }}</ui-button>
      </template>
    </ui-dialog>

    <ui-dialog v-model="deleteDialog" title="Delete Invoice" max-width="500px" persistent>
      <p class="text-sm text-bone-300">Are you sure you want to delete this invoice? This action cannot be undone.</p>
      <template #actions>
        <ui-button variant="ghost" @click="deleteDialog = false">Cancel</ui-button>
        <ui-button variant="danger" @click="deleteInvoice">Delete</ui-button>
      </template>
    </ui-dialog>
  </div>
</template>

<script>
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import axios from 'axios';
import InvoiceForm from '../components/forms/InvoiceForm.vue';
import { formatDate, formatNumber, formatCurrency } from '../utils/formatters';
import { Plus, Search, Filter, FilterX, FilePlus, FileText, Download, Upload, Pencil, Trash2 } from 'lucide-vue-next';

export default {
  name: 'InvoicesIndex',
  components: { InvoiceForm },
  setup() {
    return { Plus, Search, Filter, FilterX, FilePlus, FileText, Download, Upload, Pencil, Trash2 };
  },
  data() {
    return {
      invoices: [],
      allInvoices: [], // Store all invoices for filtering
      loading: false,
      search: '',
      deleteDialog: false,
      itemToDelete: null,
      createDialog: false,
      editDialog: false,
      uploadDialog: false,
      generatePdfConfirmDialog: false,
      pdfFile: null,
      uploading: false,
      generatingPdf: false,
      currentInvoice: null,
      generateDialog: false,
      customers: [],
      unbilledLogs: [],
      filteredWorkLogs: [],
      customerProjects: [],
      workLogFilters: {
        project_id: null,
        start_date: null,
        end_date: null
      },
      generateForm: {
        customer_id: null,
        work_log_ids: [],
        items: [],
        due_date: new Date().toISOString().slice(0, 10),
        status: 'draft'
      },
      showFilters: false,
      selectedCustomerId: null,
      selectedStatus: null,
      sortBy: [{ key: 'id', order: 'desc' }]
    };
  },

  computed: {
    ...mapState(store, ['currencySymbol', 'settings']),

    headers() {
      return [
        { title: 'ID', key: 'id' },
        { title: this.$t('invoices.invoiceNumber'), key: 'invoice_number' },
        { title: this.$t('customers.customer'), key: 'customer.name' },
        { title: this.$t('invoices.issueDate'), key: 'issue_date' },
        { title: this.$t('invoices.dueDate'), key: 'due_date' },
        { title: this.$t('invoices.total'), key: 'total_amount' },
        { title: this.$t('invoices.status'), key: 'status' },
        { title: this.$t('common.actions'), key: 'actions', sortable: false }
      ];
    },

    statuses() {
      return ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'];
    }
  },

  created() {
    this.fetchInvoices();
  },

  methods: {
    ...mapActions(store, ['showSnackbar']),
    formatDate,
    async fetchInvoices() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/invoices');
        // Sort by ID descending (newest first)
        this.allInvoices = data.sort((a, b) => b.id - a.id);
        this.applyFilters();

        // Fetch customers for filter dropdown
        if (this.customers.length === 0) {
          const customersResponse = await axios.get('/api/customers');
          this.customers = customersResponse.data;
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to fetch invoices. Please try again.';
        this.showSnackbar(message, 'error');
      } finally {
        this.loading = false;
      }
    },

    toggleFilters() {
      this.showFilters = !this.showFilters;
    },

    resetFilters() {
      this.selectedCustomerId = null;
      this.selectedStatus = null;
      this.applyFilters();
    },

    applyFilters() {
      let filtered = [...this.allInvoices];

      // Filter by customer
      if (this.selectedCustomerId) {
        filtered = filtered.filter(invoice => invoice.customer_id === this.selectedCustomerId);
      }

      // Filter by status
      if (this.selectedStatus) {
        filtered = filtered.filter(invoice =>
          invoice.status.toLowerCase() === this.selectedStatus.toLowerCase()
        );
      }

      // Sort by ID descending (newest first)
      filtered.sort((a, b) => b.id - a.id);

      this.invoices = filtered;
    },

    confirmDelete(item) {
      this.itemToDelete = item;
      this.deleteDialog = true;
    },

    async deleteInvoice() {
      try {
        await axios.delete(`/api/invoices/${this.itemToDelete.id}`);
        this.allInvoices = this.allInvoices.filter(i => i.id !== this.itemToDelete.id);
        this.applyFilters();
        this.deleteDialog = false;
        this.showSnackbar('Invoice deleted successfully', 'success');
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to delete invoice. Please try again.';
        this.showSnackbar(message, 'error');
      }
    },

    formatDate(dateStr) {
      return formatDate(dateStr, this.settings);
    },

    formatCurrency(amount) {
      return formatCurrency(amount, this.settings);
    },

    formatNumber(value, decimals = 2) {
      return formatNumber(value, decimals, this.settings);
    },

    getStatusColor(status) {
      const colors = {
        'paid': 'success',
        'sent': 'info',
        'draft': 'neutral',
        'overdue': 'error',
        'cancelled': 'warning'
      };
      return colors[status.toLowerCase()] || 'neutral';
    },
    openCreateDialog() {
      // Clear any stale selection from a previous edit/upload, otherwise
      // handleInvoiceSave would treat the new invoice as an update
      this.currentInvoice = null;
      this.createDialog = true;
    },
    openEditDialog(item) {
      this.currentInvoice = { ...item };
      this.editDialog = true;
    },
    openUploadDialog(item) {
      this.currentInvoice = { ...item };
      this.pdfFile = null;
      this.uploadDialog = true;
    },
    async uploadPdf() {
      if (!this.pdfFile) {
        this.showSnackbar('Please select a PDF file', 'error');
        return;
      }

      this.uploading = true;
      try {
        const formData = new FormData();
        formData.append('pdf', this.pdfFile);

        await axios.post(`/api/invoices/${this.currentInvoice.id}/upload-pdf`, formData);

        this.showSnackbar('Invoice PDF uploaded successfully', 'success');
        this.uploadDialog = false;
        this.pdfFile = null;
        await this.fetchInvoices();
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to upload PDF. Please try again.';
        this.showSnackbar(message, 'error');
      } finally {
        this.uploading = false;
      }
    },
    handleInvoiceSave(savedInvoice) {
      // The form's submit() already persisted the invoice through the store
      // (which also shows the snackbar). The @save event carries the saved
      // record, so only sync the local list here — re-posting would create
      // the invoice twice.
      if (this.currentInvoice?.id) {
        const idx = this.allInvoices.findIndex(i => i.id === savedInvoice.id);
        if (idx !== -1) this.allInvoices.splice(idx, 1, savedInvoice);
        this.editDialog = false;
        this.currentInvoice = null;
      } else {
        this.allInvoices.unshift(savedInvoice);
        this.createDialog = false;
      }
      this.applyFilters();
    },
    async openGenerateDialog() {
      // Reset the form with proper dates
      const today = new Date().toISOString().slice(0, 10);
      this.generateForm = {
        customer_id: null,
        work_log_ids: [],
        items: [],
        due_date: today,
        status: 'draft'
      };
      this.unbilledLogs = [];
      this.filteredWorkLogs = [];
      this.customerProjects = [];
      this.resetWorkLogFilters();

      this.generateDialog = true;

      try {
        const { data } = await axios.get('/api/customers');
        this.customers = data;
      } catch (e) {
        this.showSnackbar('Failed to load customers', 'error');
      }
    },
    async onGenerateCustomerChange() {
      // Reset everything when customer changes
      this.unbilledLogs = [];
      this.filteredWorkLogs = [];
      this.customerProjects = [];
      this.generateForm.work_log_ids = [];
      this.resetWorkLogFilters();

      if (!this.generateForm.customer_id) return;

      try {
        // Load customer projects
        const projectsResponse = await axios.get('/api/invoices/customer-projects', {
          params: { customer_id: this.generateForm.customer_id }
        });
        this.customerProjects = projectsResponse.data || [];

        // Load work logs
        await this.loadFilteredWorkLogs();
      } catch (e) {
        this.showSnackbar('Failed to load customer data', 'error');
      }
    },

    async loadFilteredWorkLogs() {
      if (!this.generateForm.customer_id) return;

      try {
        const params = {
          customer_id: this.generateForm.customer_id,
          ...this.workLogFilters
        };

        // Remove null/empty values
        Object.keys(params).forEach(key => {
          if (params[key] === null || params[key] === '') {
            delete params[key];
          }
        });

        const { data } = await axios.get('/api/invoices/unbilled-worklogs', { params });
        this.filteredWorkLogs = data || [];
        this.unbilledLogs = this.filteredWorkLogs;

        // Remove any selected work logs that are no longer in the filtered results
        const filteredIds = this.filteredWorkLogs.map(log => log.id);
        this.generateForm.work_log_ids = this.generateForm.work_log_ids.filter(id =>
          filteredIds.includes(id)
        );
      } catch (e) {
        this.showSnackbar('Failed to load work logs', 'error');
      }
    },

    resetWorkLogFilters() {
      this.workLogFilters = {
        project_id: null,
        start_date: null,
        end_date: null
      };
      this.loadFilteredWorkLogs();
    },

    toggleWorkLog(logId) {
      const index = this.generateForm.work_log_ids.indexOf(logId);
      if (index > -1) {
        this.generateForm.work_log_ids.splice(index, 1);
      } else {
        this.generateForm.work_log_ids.push(logId);
      }
    },

    selectAllWorkLogs() {
      this.generateForm.work_log_ids = this.filteredWorkLogs.map(log => log.id);
    },

    clearWorkLogSelection() {
      this.generateForm.work_log_ids = [];
    },

    calculateSelectedTotal() {
      const selectedLogs = this.filteredWorkLogs.filter(log =>
        this.generateForm.work_log_ids.includes(log.id)
      );
      const total = selectedLogs.reduce((sum, log) => {
        return sum + (log.billing_rate * (log.hours_worked || 0));
      }, 0);
      return total.toFixed(2);
    },

    calculateItemsTotal() {
      return this.generateForm.items.reduce((sum, item) => {
        return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
      }, 0);
    },

    calculateGrandSubtotal() {
      return parseFloat(this.calculateSelectedTotal()) + this.calculateItemsTotal();
    },

    addGenerateItem() {
      this.generateForm.items.push({ description: '', quantity: 1, unit_price: 0 });
    },

    removeGenerateItem(index) {
      this.generateForm.items.splice(index, 1);
    },

    async generateInvoice() {
      try {
        const payload = {
          ...this.generateForm,
          // Drop empty rows and coerce numerics before hitting the API
          items: this.generateForm.items
            .filter(item => (item.description || '').trim() !== '')
            .map(item => ({
              description: item.description,
              quantity: parseFloat(item.quantity) || 0,
              unit_price: parseFloat(item.unit_price) || 0,
            }))
        };
        const { data } = await axios.post('/api/invoices/generate', payload);
        this.allInvoices.unshift(data);
        this.applyFilters();
        this.generateDialog = false;
        this.showSnackbar('Invoice generated from work logs', 'success');
      } catch (e) {
        const message = e.response?.data?.message || 'Failed to generate invoice';
        this.showSnackbar(message, 'error');
      }
    },

    async viewPdf(invoice) {
      try {
        window.open(`/api/invoices/${invoice.id}/pdf`, '_blank');
      } catch (e) {
        this.showSnackbar('Failed to view PDF', 'error');
      }
    },

    async downloadPdf(invoice) {
      try {
        const link = document.createElement('a');
        link.href = `/api/invoices/${invoice.id}/pdf-download`;
        link.download = `invoice-${invoice.invoice_number}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        this.showSnackbar('Invoice PDF downloaded', 'success');
      } catch (e) {
        this.showSnackbar('Failed to download PDF', 'error');
      }
    },

    generatePdfDialog(item) {
      this.currentInvoice = { ...item };
      this.generatePdfConfirmDialog = true;
    },

    async generatePdfNow() {
      this.generatingPdf = true;
      try {
        const { data } = await axios.post(`/api/invoices/${this.currentInvoice.id}/generate-pdf`);

        // Update the invoice in the list with the new pdf_path
        const idx = this.allInvoices.findIndex(i => i.id === this.currentInvoice.id);
        if (idx !== -1) {
          this.allInvoices[idx].pdf_path = data.pdf_path;
        }

        this.applyFilters();
        this.generatePdfConfirmDialog = false;
        this.showSnackbar(this.$t('invoices.generatePdfSuccess'), 'success');
      } catch (e) {
        const message = e.response?.data?.message || this.$t('invoices.generatePdfError');
        this.showSnackbar(message, 'error');
      } finally {
        this.generatingPdf = false;
      }
    },

    isOverdue(invoice) {
      if (!invoice.due_date || invoice.status.toLowerCase() === 'paid') {
        return false;
      }

      const status = invoice.status.toLowerCase();
      if (status !== 'sent') {
        return false;
      }

      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const dueDate = new Date(invoice.due_date);
      dueDate.setHours(0, 0, 0, 0);

      return dueDate < today;
    },

    getRowProps({ item }) {
      return {
        class: this.isOverdue(item) ? 'bg-clay-900/40' : ''
      };
    }
  }
};
</script>
