<template>
  <v-form ref="form" @submit.prevent="submit">
    <v-row>
      <!-- Customer Selection -->
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.customer_id"
          :items="customers"
          item-title="name"
          item-value="id"
          label="Customer"
          :rules="[rules.required]"
          required
          :disabled="!!invoice"
          data-test="invoice-customer"
        ></v-select>
      </v-col>

      <!-- Invoice Number -->
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.invoice_number"
          label="Invoice Number"
          :rules="[]"
          hint="Leave empty to auto-generate"
          persistent-hint
        ></v-text-field>
      </v-col>

      <!-- Due Date -->
       <v-col cols="12" md="6">
         <v-menu
            v-model="dueDateMenu"
            :close-on-content-click="false"
            transition="scale-transition"
            offset-y
            min-width="auto">
            <template v-slot:activator="{ props }">
              <v-text-field
                :model-value="formattedDueDate"
                label="Due Date"
                prepend-icon="mdi-calendar"
                readonly
                :rules="[rules.required]"
                required
                data-test="invoice-due-date"
                v-bind="props"></v-text-field>
            </template>
            <v-date-picker 
              v-model="internalDueDate"
              @update:model-value="updateDueDate"></v-date-picker>
         </v-menu>
      </v-col>

      <!-- Status -->
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.status"
          :items="statusOptions"
          label="Status"
          :rules="[rules.required]"
          required
        ></v-select>
      </v-col>

       <!-- Total Amount -->
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.total_amount"
          label="Total Amount"
          type="number"
          step="0.01"
          :prefix="currencySymbol"
          :rules="[rules.required]"
          data-test="invoice-total"
        ></v-text-field>
      </v-col>

    </v-row>
  </v-form>
</template>

<script>
import { mapState, mapActions } from 'pinia';
import { store } from '../../store'; // Assuming store path
import { formatDate } from '../../utils/formatters';

export default {
  name: 'InvoiceForm',
  props: {
    invoice: { // Pass the invoice object for editing, null for creating
      type: Object,
      default: null
    }
  },
  data() {
    return {
      formData: {
        customer_id: null,
        invoice_number: '',
        due_date: null,
        total_amount: 0.00,
        status: 'draft',
      },
      internalDueDate: null,
      dueDateMenu: false,
      statusOptions: ['draft', 'sent', 'paid', 'overdue', 'cancelled'],
      rules: {
        required: value => !!value || 'Required.',
      },
      loading: false,
    };
  },
  computed: {
    ...mapState(store, ['customers', 'currencySymbol', 'settings']), // Need customers for the dropdown
    formTitle() {
      return this.invoice ? 'Edit Invoice' : 'Create Invoice';
    },
    formattedDueDate() {
      if (!this.formData.due_date) return '';
      // Display the due date in the user's preferred format
      return formatDate(this.formData.due_date, this.settings);
    }
  },
  created() {
    // Pre-populate form if editing an existing invoice
    if (this.invoice) {
      // Store the ISO date format in formData
      this.formData = { 
          customer_id: this.invoice.customer_id,
          invoice_number: this.invoice.invoice_number,
          due_date: this.invoice.due_date,
          total_amount: this.invoice.total_amount,
          status: this.invoice.status
      };
      // Initialize the internal date picker value with the ISO date as a Date object
      if (this.invoice.due_date) {
        this.internalDueDate = new Date(this.invoice.due_date);
      }
    }
    // Fetch customers if not already loaded (optional, depends on app flow)
    if (!this.customers || this.customers.length === 0) {
      this.fetchCustomers();
    }
  },
  methods: {
     ...mapActions(store, ['createInvoice', 'updateInvoice', 'fetchCustomers']),

    updateDueDate(date) {
      // Convert Date object to ISO string format (YYYY-MM-DD)
      if (date instanceof Date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        this.formData.due_date = `${year}-${month}-${day}`;
      } else if (typeof date === 'string') {
        // Already a string, store as is
        this.formData.due_date = date;
      }
      this.dueDateMenu = false;
    },

    async submit() {
      const { valid } = await this.$refs.form.validate();
      if (!valid) return;

      this.loading = true;
      let result = null;

      // Prepare data - only include the fields we need
      const payload = {
        customer_id: this.formData.customer_id,
        invoice_number: this.formData.invoice_number,
        due_date: this.formData.due_date,
        total_amount: this.formData.total_amount,
        status: this.formData.status
      };

      if (this.invoice) {
        // Update existing invoice
        result = await this.updateInvoice({ ...payload, id: this.invoice.id });
      } else {
        // Create new invoice
        result = await this.createInvoice(payload);
      }
      this.loading = false;

      if (result) {
        this.$emit('save', result); // Emit event with saved/created invoice data
      }
      // Error handling is done within the store actions via snackbar
    }
  }
};
</script>
