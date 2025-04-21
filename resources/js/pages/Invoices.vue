<template>
  <v-container>
    <h1 class="text-h4 mb-4">Invoices</h1>
    
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
          @input="fetchInvoices"
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" prepend-icon="mdi-plus">
          New Invoice
        </v-btn>
      </v-col>
    </v-row>
    
    <v-card>
      <v-data-table
        :headers="headers"
        :items="invoices"
        :loading="loading"
        class="elevation-1"
        :search="search"
      >
          <template v-slot:item.due_date="{ item }">
          {{ formatDate(item.due_date) }}
        </template>
        <template v-slot:item.status="{ item }">
          <v-chip
            :color="getStatusColor(item.status)"
            size="small"
          >
            {{ item.status }}
          </v-chip>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="info" :to="`/invoices/${item.id}`">
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

    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title>Delete Invoice</v-card-title>
        <v-card-text>
          Are you sure you want to delete this invoice? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="performDelete">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="generateDialog" max-width="500px">
      <v-card>
        <v-card-title>Generate Invoice from Work Logs</v-card-title>
        <v-card-text>
          <v-select
            v-model="generatePayload.customer_id"
            :items="customers"
            item-text="name"
            item-value="id"
            label="Select Customer"
            :rules="[rules.required]"
          ></v-select>
          <v-date-picker
            v-model="generatePayload.start_date"
            label="Start Date"
            :rules="[rules.required]"
          ></v-date-picker>
          <v-date-picker
            v-model="generatePayload.end_date"
            label="End Date"
            :rules="[rules.required]"
          ></v-date-picker>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="generateDialog = false">Cancel</v-btn>
          <v-btn color="success" @click="performGenerate">Generate</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <InvoiceForm
      v-model:dialog="dialog"
      :invoice.sync="editedInvoice"
      @saved="onInvoiceSaved"
    />

    <LoadingOverlay :active="loading" />
  </v-container>
</template>

<script>
import { store } from "../store";
import { mapActions, mapState } from "pinia";
import InvoiceForm from '../components/forms/InvoiceForm.vue'; // Import the new form
import LoadingOverlay from '../components/LoadingOverlay.vue'; // Assuming you have this

export default {
  name: 'InvoicesIndex',
  components: {
    InvoiceForm,
    LoadingOverlay
  },
  data() {
    return {
      search: '',
      dialog: false, // Controls create/edit dialog
      deleteDialog: false,
      generateDialog: false, // Controls generate dialog
      editedInvoice: null, // Holds invoice being edited/created
      invoiceToDelete: null, // Holds invoice to be deleted
      generatePayload: { // Data for generating invoice
          customer_id: null,
          start_date: null,
          end_date: null,
      },
      headers: [
        { title: 'Invoice #', key: 'invoice_number', sortable: true },
        { title: 'Customer', key: 'customer.name', sortable: true },
        { title: 'Due Date', key: 'due_date', sortable: true },
        { title: 'Total', key: 'total_amount', sortable: true },
        { title: 'Status', key: 'status', sortable: true },
        { title: 'Actions', key: 'actions', sortable: false },
      ],
      rules: {
          required: value => !!value || 'Required.',
      },
    };
  },

  computed: {
    ...mapState(store, ['invoices', 'loading', 'customers']), // Map state from store
    formTitle() {
      return this.editedInvoice && this.editedInvoice.id ? 'Edit Invoice' : 'Create New Invoice';
    },
  },

  created() {
    this.fetchInvoices(); // Fetch invoices using store action
    this.fetchCustomers(); // Fetch customers if needed for generate dialog
  },

  methods: {
    ...mapActions(store, [
        'fetchInvoices',
        'deleteInvoice',
        'generateInvoiceFromWorkLogs',
        'fetchCustomers' // Map store actions
    ]),

    openCreateDialog() {
      this.editedInvoice = {}; // Clear for new invoice
      this.dialog = true;
    },

    openEditDialog(item) {
      this.editedInvoice = { ...item }; // Copy item to avoid direct state mutation
      this.dialog = true;
    },

     openGenerateDialog() {
        this.generatePayload = { customer_id: null, start_date: null, end_date: null }; // Reset payload
        this.generateDialog = true;
    },

    confirmDelete(item) {
      this.invoiceToDelete = item;
      this.deleteDialog = true;
    },

    closeDialogs() {
      this.dialog = false;
      this.deleteDialog = false;
      this.generateDialog = false;
      this.editedInvoice = null;
      this.invoiceToDelete = null;
    },

    async performDelete() {
      if (!this.invoiceToDelete) return;
      const success = await this.deleteInvoice(this.invoiceToDelete.id);
      if (success) {
        this.closeDialogs();
      }
    },

     async performGenerate() {
        // Basic validation
        if (!this.generatePayload.customer_id || !this.generatePayload.start_date || !this.generatePayload.end_date) {
            // Use store's snackbar for error
            store().showSnackbar('Please select customer and date range.', 'warning');
            return;
        }
        const result = await this.generateInvoiceFromWorkLogs(this.generatePayload);
        if (result) {
            this.closeDialogs();
            // Invoices are refetched by the action
        }
    },

    // Called when InvoiceForm emits 'saved'
    onInvoiceSaved() {
        this.closeDialogs();
        // No need to manually fetch, store actions update the state
    },

    formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        // Adjust formatting as needed
        return new Date(dateStr).toLocaleDateString();
    },

    formatCurrency(value) {
        if (typeof value !== 'number') {
            value = parseFloat(value) || 0;
        }
        return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
    },

    getStatusColor(status) {
      switch (status) {
        case 'paid': return 'success';
        case 'overdue': return 'error';
        case 'sent': return 'info';
        case 'draft': return 'grey';
        case 'cancelled': return 'warning';
        default: return 'default';
      }
    }
  }
};
</script>
