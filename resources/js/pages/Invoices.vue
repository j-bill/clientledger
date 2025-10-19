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
      >
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
          {{ currencySymbol + Number(item.total_amount).toFixed(2) }}
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
          <invoice-form ref="createForm" @save="saveInvoice"></invoice-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" data-test="btn-cancel-create" variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" data-test="btn-save-create" @click="$refs.createForm.submit()">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Generate from Work Logs Dialog -->
    <v-dialog v-model="generateDialog" max-width="800px">
      <v-card>
        <v-card-title>Generate Invoice from Work Logs</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <v-autocomplete
                v-model="generateForm.customer_id"
                data-test="gen-customer"
                :items="customers"
                item-title="name"
                item-value="id"
                label="Customer"
                prepend-icon="mdi-account"
                @update:model-value="onGenerateCustomerChange"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="generateForm.work_log_ids"
                data-test="gen-worklogs"
                :items="unbilledLogs"
                item-title="label"
                item-value="id"
                chips
                multiple
                label="Work Logs"
                prepend-icon="mdi-timelapse"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field v-model="generateForm.due_date" data-test="gen-due-date" type="date" label="Due Date" />
            </v-col>
            <v-col cols="12" md="3">
              <v-select v-model="generateForm.status" data-test="gen-status" :items="['draft','sent','paid','overdue','cancelled']" label="Status" />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" data-test="btn-cancel-generate" variant="text" @click="generateDialog = false">Cancel</v-btn>
          <v-btn color="primary" data-test="btn-generate-confirm" :disabled="!generateForm.customer_id || generateForm.work_log_ids.length===0" @click="generateInvoice">Generate</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Edit Invoice Dialog -->
    <v-dialog v-model="editDialog" max-width="800px">
      <v-card>
        <v-card-title>Edit Invoice</v-card-title>
        <v-card-text>
          <invoice-form ref="editForm" :invoice="currentInvoice" @save="updateInvoiceRecord"></invoice-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.editForm.submit()">Update</v-btn>
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
import { formatDate } from '../utils/formatters';

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
      
      headers: [
        { title: 'Invoice #', key: 'id' },
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
    async fetchInvoices() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/invoices');
        this.allInvoices = data;
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
      this.generateDialog = true;
      try {
        const { data } = await axios.get('/api/customers');
        this.customers = data;
      } catch (e) {
        this.showSnackbar('Failed to load customers', 'error');
      }
    },
    async onGenerateCustomerChange() {
      this.unbilledLogs = [];
      this.generateForm.work_log_ids = [];
      if (!this.generateForm.customer_id) return;
      try {
        const { data } = await axios.get('/api/invoices/unbilled-worklogs', {
          params: { customer_id: this.generateForm.customer_id }
        });
        this.unbilledLogs = (data || []).map(l => ({
          id: l.id,
          label: `${l.date} - ${l.project?.name || 'Project'} - ${l.hours_worked || 0}h`
        }));
      } catch (e) {
        this.showSnackbar('Failed to load work logs', 'error');
      }
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
