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
         <v-text-field
            v-model="formData.due_date"
            label="Due Date"
            type="date"
            :rules="[rules.required]"
            required
            data-test="invoice-due-date"
          ></v-text-field>
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
        total_amount: 0.00, // Initialize or calculate later
        status: 'draft', // Default status
      },
      statusOptions: ['draft', 'sent', 'paid', 'overdue', 'cancelled'],
      rules: {
        required: value => !!value || 'Required.',
        // Add more rules as needed
      },
      loading: false, // Local loading state for the form submission
    };
  },
  computed: {
    ...mapState(store, ['customers', 'currencySymbol']), // Need customers for the dropdown
    formTitle() {
      return this.invoice ? 'Edit Invoice' : 'Create Invoice';
    }
  },
  created() {
    // Pre-populate form if editing an existing invoice
    if (this.invoice) {
      // Format dates for the input type="date"
      const dueDate = this.invoice.due_date ? new Date(this.invoice.due_date).toISOString().split('T')[0] : null;
      this.formData = { 
          customer_id: this.invoice.customer_id,
          invoice_number: this.invoice.invoice_number,
          due_date: dueDate,
          total_amount: this.invoice.total_amount,
          status: this.invoice.status
      };
    }
    // Fetch customers if not already loaded (optional, depends on app flow)
    if (!this.customers || this.customers.length === 0) {
      this.fetchCustomers();
    }
  },
  methods: {
     ...mapActions(store, ['createInvoice', 'updateInvoice', 'fetchCustomers']), // Map store actions

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
