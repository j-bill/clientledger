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
        ></v-select>
      </v-col>

      <!-- Invoice Number -->
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.invoice_number"
          label="Invoice Number"
          :rules="[rules.required]"
          required
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

       <!-- Total Amount (Readonly for now, calculated later) -->
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.total_amount"
          label="Total Amount"
          type="number"
          step="0.01"
          prefix="$"
          readonly 
          disabled 
        ></v-text-field>
      </v-col>

    </v-row>
    <v-card-actions>
      <v-spacer></v-spacer>
      <v-btn color="blue darken-1" text @click="$emit('close')">Cancel</v-btn>
      <v-btn color="blue darken-1" type="submit" :loading="loading">Save</v-btn>
    </v-card-actions>
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
    ...mapState(store, ['customers']), // Need customers for the dropdown
    formTitle() {
      return this.invoice ? 'Edit Invoice' : 'Create Invoice';
    }
  },
  created() {
    // Pre-populate form if editing an existing invoice
    if (this.invoice) {
      // Format date for the input type="date"
       const dueDate = this.invoice.due_date ? new Date(this.invoice.due_date).toISOString().split('T')[0] : null;
      this.formData = { 
          ...this.invoice,
          due_date: dueDate // Use formatted date
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

      // Prepare data (ensure total_amount is handled correctly if needed)
      const payload = { ...this.formData };
      // Remove total_amount if it should only be set by the backend based on worklogs
      // delete payload.total_amount; 

      if (this.invoice) {
        // Update existing invoice
        result = await this.updateInvoice({ ...payload, id: this.invoice.id });
      } else {
        // Create new invoice
        result = await this.createInvoice(payload);
      }
      this.loading = false;

      if (result) {
        this.$emit('close'); // Close the dialog on success
        this.$emit('saved', result); // Emit event with saved/created invoice data
      }
      // Error handling is done within the store actions via snackbar
    }
  }
};
</script>
