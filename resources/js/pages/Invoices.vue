<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">Invoices</h1>
    
    <!-- Search & Actions -->
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          label="Search"
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
        <v-btn color="secondary" data-test="btn-generate" prepend-icon="mdi-file-plus" @click="openGenerateDialog" class="mr-2">
          Generate from Work Logs
        </v-btn>
        <v-btn color="primary" data-test="btn-new" prepend-icon="mdi-plus" @click="openCreateDialog">
          New Invoice
        </v-btn>
      </v-col>
    </v-row>
    
    <!-- Filters -->
    <v-card v-if="showFilters" class="mb-4">
      <v-card-title>Filters</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedCustomerId"
              :items="customers"
              item-title="name"
              item-value="id"
              label="Customer"
              prepend-inner-icon="mdi-account"
              clearable
              @update:model-value="applyFilters"
            ></v-select>
          </v-col>
          <v-col cols="12" md="4">
            <v-select
              v-model="selectedStatus"
              :items="statuses"
              label="Status"
              prepend-inner-icon="mdi-flag"
              clearable
              @update:model-value="applyFilters"
            ></v-select>
          </v-col>
          <v-col cols="12" class="text-right">
            <v-btn @click="resetFilters">Reset</v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
    
    <v-card>
      <v-data-table
        :headers="headers"
        :items="invoices"
        :loading="loading"
        class="elevation-1"
        :search="search"
        :sort-by="sortBy"
      >
        <template v-slot:item.invoice_number="{ item }">
          {{ item.invoice_number || '-' }}
        </template>
        <template v-slot:item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>
        <template v-slot:item.status="{ item }">
          <v-chip
            :color="getStatusColor(item.status)"
            size="small"
          >
            {{ item.status }}
          </v-chip>
        </template>
        <template v-slot:item.total_amount="{ item }">
          {{ formatCurrency(item.total_amount) }}
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="primary" @click="openEditDialog(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create Invoice Dialog -->
    <v-dialog v-model="createDialog" max-width="800px">
      <v-card>
        <v-card-title>New Invoice</v-card-title>
        <v-card-text>
          <invoice-form ref="createForm" @save="handleInvoiceSave"></invoice-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" data-test="btn-cancel-create" variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" data-test="btn-save-create" @click="$refs.createForm.submit()">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Generate from Work Logs Dialog -->
    <v-dialog v-model="generateDialog" max-width="1200px">
      <v-card>
        <v-card-title>Generate Invoice from Work Logs</v-card-title>
        <v-card-text>
          <!-- Customer Selection -->
          <v-row class="mb-4">
            <v-col cols="12">
              <v-autocomplete
                v-model="generateForm.customer_id"
                data-test="gen-customer"
                :items="customers"
                item-title="name"
                item-value="id"
                label="Customer"
                prepend-icon="mdi-account"
                @update:model-value="onGenerateCustomerChange"
                :rules="[v => !!v || 'Customer is required']"
              />
            </v-col>
          </v-row>

          <!-- Filters Section -->
          <v-card v-if="generateForm.customer_id" class="mb-4" variant="outlined">
            <v-card-title class="text-h6">Filter Work Logs</v-card-title>
            <v-card-text>
              <v-row>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="workLogFilters.project_id"
                    :items="customerProjects"
                    item-title="name"
                    item-value="id"
                    label="Project"
                    prepend-icon="mdi-folder"
                    clearable
                    @update:model-value="loadFilteredWorkLogs"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="workLogFilters.start_date"
                    type="date"
                    label="Start Date"
                    prepend-icon="mdi-calendar"
                    clearable
                    @update:model-value="loadFilteredWorkLogs"
                  />
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="workLogFilters.end_date"
                    type="date"
                    label="End Date"
                    prepend-icon="mdi-calendar"
                    clearable
                    @update:model-value="loadFilteredWorkLogs"
                  />
                </v-col>
              </v-row>
              <v-row>
                <v-col cols="12" class="text-right">
                  <v-btn color="secondary" @click="resetWorkLogFilters" class="mr-2">
                    <v-icon>mdi-filter-off</v-icon>
                    Reset Filters
                  </v-btn>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- Work Logs Selection -->
          <v-card v-if="generateForm.customer_id" variant="outlined" class="mb-4">
            <v-card-title class="d-flex justify-space-between align-center">
              <span class="text-h6">Available Work Logs ({{ filteredWorkLogs.length }})</span>
              <div>
                <v-btn 
                  color="primary" 
                  variant="text" 
                  size="small" 
                  @click="selectAllWorkLogs"
                  :disabled="filteredWorkLogs.length === 0"
                  class="mr-2"
                >
                  Select All
                </v-btn>
                <v-btn 
                  color="secondary" 
                  variant="text" 
                  size="small" 
                  @click="clearWorkLogSelection"
                  :disabled="generateForm.work_log_ids.length === 0"
                >
                  Clear Selection
                </v-btn>
              </div>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="filteredWorkLogs.length === 0" type="info" variant="tonal">
                No unbilled work logs found for the selected criteria.
              </v-alert>
              <v-list v-else>
                <v-list-item
                  v-for="log in filteredWorkLogs"
                  :key="log.id"
                  @click="toggleWorkLog(log.id)"
                  :class="{ 'bg-blue-lighten-5': generateForm.work_log_ids.includes(log.id) }"
                >
                  <template v-slot:prepend>
                    <v-checkbox
                      :model-value="generateForm.work_log_ids.includes(log.id)"
                      @update:model-value="toggleWorkLog(log.id)"
                      color="primary"
                    />
                  </template>
                  <v-list-item-title>
                    {{ formatDate(log.date) }} - {{ log.project?.name || 'Unknown Project' }}
                  </v-list-item-title>
                  <v-list-item-subtitle>
                    {{ log.user?.name || 'Unknown User' }} • 
                    {{ formatNumber(log.hours_worked || 0, 2) }}h • 
                    {{ formatCurrency(log.billing_rate * (log.hours_worked || 0)) }}
                    <br>
                    <span class="text-caption">{{ log.description || 'No description' }}</span>
                  </v-list-item-subtitle>
                  <template v-slot:append>
                    <v-chip size="small" color="primary" variant="outlined">
                      {{ formatCurrency(log.billing_rate * (log.hours_worked || 0)) }}
                    </v-chip>
                  </template>
                </v-list-item>
              </v-list>
            </v-card-text>
          </v-card>

          <!-- Invoice Details -->
          <v-row v-if="generateForm.customer_id">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="generateForm.due_date"
                data-test="gen-due-date"
                type="date"
                label="Due Date"
                prepend-icon="mdi-calendar"
                :rules="[v => !!v || 'Due date is required']"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="generateForm.status"
                data-test="gen-status"
                :items="['draft','sent','paid','overdue','cancelled']"
                label="Status"
                prepend-icon="mdi-flag"
                :rules="[v => !!v || 'Status is required']"
              />
            </v-col>
          </v-row>

          <!-- Selected Work Logs Summary -->
          <v-card v-if="generateForm.work_log_ids.length > 0" class="mt-4" color="primary" variant="tonal">
            <v-card-text>
              <div class="d-flex justify-space-between align-center">
                <div>
                  <strong>{{ generateForm.work_log_ids.length }}</strong> work log{{ generateForm.work_log_ids.length !== 1 ? 's' : '' }} selected
                </div>
                <div class="text-h6">
                  Total: {{ currencySymbol }}{{ calculateSelectedTotal() }}
                </div>
              </div>
            </v-card-text>
          </v-card>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" data-test="btn-cancel-generate" variant="text" @click="generateDialog = false">Cancel</v-btn>
          <v-btn 
            color="primary" 
            data-test="btn-generate-confirm" 
            :disabled="!generateForm.customer_id || generateForm.work_log_ids.length === 0 || !generateForm.due_date || !generateForm.status" 
            @click="generateInvoice"
          >
            Generate Invoice ({{ currencySymbol }}{{ calculateSelectedTotal() }})
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Invoice Dialog -->
    <v-dialog v-model="editDialog" max-width="800px">
      <v-card>
        <v-card-title>Edit Invoice</v-card-title>
        <v-card-text>
          <invoice-form ref="editForm" :invoice="currentInvoice" @save="handleInvoiceSave"></invoice-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.editForm.submit()">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title>Delete Invoice</v-card-title>
        <v-card-text>
          Are you sure you want to delete this invoice? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteInvoice">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import axios from 'axios';
import InvoiceForm from '../components/forms/InvoiceForm.vue';
import { formatDate, formatNumber, formatCurrency } from '../utils/formatters';

export default {
  name: 'InvoicesIndex',
  components: { InvoiceForm },
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
        due_date: new Date().toISOString().slice(0, 10),
        status: 'draft'
      },
      showFilters: false,
      selectedCustomerId: null,
      selectedStatus: null,
      statuses: ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'],
      sortBy: [{ key: 'id', order: 'desc' }],
      
      headers: [
        { title: 'ID', key: 'id' },
        { title: 'Invoice Number', key: 'invoice_number' },
        { title: 'Customer', key: 'customer.name' },
        { title: 'Created', key: 'created_at' },
        { title: 'Total', key: 'total_amount' },
        { title: 'Status', key: 'status' },
        { title: 'Actions', key: 'actions', sortable: false }
      ]
    };
  },
  
  computed: {
    ...mapState(store, ['currencySymbol', 'settings'])
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
        'draft': 'grey',
        'overdue': 'error',
        'cancelled': 'warning'
      };
      return colors[status.toLowerCase()] || 'grey';
    },
    openCreateDialog() {
      this.createDialog = true;
    },
    openEditDialog(item) {
      this.currentInvoice = { ...item };
      this.editDialog = true;
    },
    async handleInvoiceSave(payload) {
      // This handler is used for both create and update via the form's @save event
      if (this.currentInvoice?.id) {
        // Update existing invoice
        await this.updateInvoiceRecord(payload);
      } else {
        // Create new invoice
        await this.saveInvoice(payload);
      }
    },
    async saveInvoice(payload) {
      try {
        const { data } = await axios.post('/api/invoices', payload);
        this.allInvoices.unshift(data);
        this.applyFilters();
        this.createDialog = false;
        this.showSnackbar('Invoice created successfully', 'success');
      } catch (e) {
        const message = e.response?.data?.errors 
          ? Object.values(e.response.data.errors)[0][0]
          : (e.response?.data?.message || 'Failed to create invoice');
        this.showSnackbar(message, 'error');
      }
    },
    async updateInvoiceRecord(payload) {
      try {
        const { data } = await axios.put(`/api/invoices/${this.currentInvoice.id}`, payload);
        const idx = this.allInvoices.findIndex(i => i.id === data.id);
        if (idx !== -1) this.allInvoices.splice(idx, 1, data);
        this.applyFilters();
        this.editDialog = false;
        this.showSnackbar('Invoice updated successfully', 'success');
      } catch (e) {
        const message = e.response?.data?.errors 
          ? Object.values(e.response.data.errors)[0][0]
          : (e.response?.data?.message || 'Failed to update invoice');
        this.showSnackbar(message, 'error');
      }
    },
    async openGenerateDialog() {
      // Reset the form
      this.generateForm = {
        customer_id: null,
        work_log_ids: [],
        due_date: new Date().toISOString().slice(0, 10),
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
    async generateInvoice() {
      try {
        const payload = { ...this.generateForm };
        const { data } = await axios.post('/api/invoices/generate', payload);
        this.allInvoices.unshift(data);
        this.applyFilters();
        this.generateDialog = false;
        this.showSnackbar('Invoice generated from work logs', 'success');
      } catch (e) {
        const message = e.response?.data?.message || 'Failed to generate invoice';
        this.showSnackbar(message, 'error');
      }
    }
  }
};
</script>
