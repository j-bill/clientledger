<template>
  <ui-form ref="form" @submit="submit">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <!-- Customer Selection -->
      <ui-select
        v-model="formData.customer_id"
        :items="customers"
        item-title="name"
        item-value="id"
        :label="$t('forms.invoice.customer')"
        :rules="[rules.required]"
        :disabled="!!invoice"
        data-test="invoice-customer"
      />

      <!-- Invoice Number -->
      <ui-input
        v-model="formData.invoice_number"
        :label="$t('forms.invoice.invoiceNumber')"
        :rules="[]"
        :hint="$t('forms.invoice.invoiceNumberHint')"
      />

      <!-- Issue Date -->
      <ui-input
        v-model="formData.issue_date"
        type="date"
        :label="$t('forms.invoice.issueDate')"
        :icon="Calendar"
        :rules="[rules.required]"
        data-test="invoice-issue-date"
      />

      <!-- Due Date -->
      <ui-input
        v-model="formData.due_date"
        type="date"
        :label="$t('forms.invoice.dueDate')"
        :icon="Calendar"
        :rules="[rules.required]"
        data-test="invoice-due-date"
      />

      <!-- Status -->
      <ui-select
        v-model="formData.status"
        :items="statusOptions"
        :label="$t('forms.invoice.status')"
        :rules="[rules.required]"
      />

      <!-- Total Amount -->
      <ui-input
        v-model="formData.total_amount"
        :label="$t('forms.invoice.totalAmount')"
        type="number"
        step="0.01"
        :suffix="currencySymbol"
        :rules="[rules.required]"
        data-test="invoice-total"
      />
    </div>

    <!-- Notes -->
    <div class="mt-4">
      <ui-textarea
        v-model="formData.notes"
        :label="$t('forms.invoice.notes')"
        :hint="$t('forms.invoice.notesHint')"
        rows="3"
        maxlength="500"
      />
    </div>
  </ui-form>
</template>

<script>
import { mapState, mapActions } from 'pinia';
import { store } from '../../store'; // Assuming store path
import { useI18n } from 'vue-i18n';
import { Calendar } from 'lucide-vue-next';

export default {
  name: 'InvoiceForm',
  setup() {
    const { t } = useI18n()
    return { t, Calendar }
  },
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
        issue_date: new Date().toISOString().substr(0, 10),
        due_date: null,
        total_amount: 0.00,
        status: 'draft',
        notes: '',
      },
      statusOptions: ['draft', 'sent', 'paid', 'overdue', 'cancelled'],
      rules: {
        required: value => !!value || this.t('forms.required'),
      },
      loading: false,
    };
  },
  computed: {
    ...mapState(store, ['customers', 'currencySymbol', 'settings']), // Need customers for the dropdown
    formTitle() {
      return this.invoice ? this.t('forms.invoice.editTitle') : this.t('forms.invoice.createTitle');
    }
  },
  created() {
    // Pre-populate form if editing an existing invoice
    if (this.invoice) {
      // Store the ISO date format in formData (native date inputs consume
      // YYYY-MM-DD strings directly, no Date object conversion needed)
      this.formData = {
          customer_id: this.invoice.customer_id,
          invoice_number: this.invoice.invoice_number,
          issue_date: this.invoice.issue_date,
          due_date: this.invoice.due_date,
          total_amount: this.invoice.total_amount,
          status: this.invoice.status,
          notes: this.invoice.notes || ''
      };
    } else {
      // For new invoice, set issue_date to today
      this.formData.issue_date = new Date().toISOString().substr(0, 10);
    }
    // Fetch customers if not already loaded (optional, depends on app flow)
    if (!this.customers || this.customers.length === 0) {
      this.fetchCustomers();
    }
  },
  methods: {
     ...mapActions(store, ['createInvoice', 'updateInvoice', 'fetchCustomers']),

    async submit() {
      const { valid } = await this.$refs.form.validate();
      if (!valid) return;

      this.loading = true;
      let result = null;

      // Prepare data - only include the fields we need
      const payload = {
        customer_id: this.formData.customer_id,
        invoice_number: this.formData.invoice_number,
        issue_date: this.formData.issue_date,
        due_date: this.formData.due_date,
        total_amount: this.formData.total_amount,
        status: this.formData.status,
        notes: this.formData.notes
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
