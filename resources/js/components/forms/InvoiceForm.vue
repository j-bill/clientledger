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

    <!-- Line Items -->
    <div class="mt-4">
      <div class="mb-1 flex items-center justify-between">
        <span class="text-sm font-medium text-bone-100">{{ $t('forms.invoice.items') }}</span>
        <ui-button variant="ghost" size="sm" :icon="Plus" data-test="invoice-add-item" @click="addItem">
          {{ $t('forms.invoice.addItem') }}
        </ui-button>
      </div>
      <p class="mb-2 text-xs text-bone-500">{{ $t('forms.invoice.itemsHint') }}</p>
      <div
        v-for="(item, index) in formData.items"
        :key="index"
        class="mb-2 flex items-start gap-2"
        :data-test="`invoice-item-${index}`"
      >
        <div class="min-w-0 flex-1">
          <ui-input
            v-model="item.description"
            :label="$t('forms.invoice.itemDescription')"
            :rules="[rules.required]"
          />
        </div>
        <div class="w-24 shrink-0">
          <ui-input
            v-model="item.quantity"
            :label="$t('forms.invoice.quantity')"
            type="number"
            step="0.01"
            min="0"
            :rules="[rules.required]"
          />
        </div>
        <div class="w-32 shrink-0">
          <ui-input
            v-model="item.unit_price"
            :label="$t('forms.invoice.unitPrice')"
            type="number"
            step="0.01"
            :suffix="currencySymbol"
            :rules="[rules.required]"
          />
        </div>
        <ui-button
          variant="danger-ghost"
          size="sm"
          class="mt-6 shrink-0"
          :icon="Trash2"
          :title="$t('common.delete')"
          @click="removeItem(index)"
        />
      </div>
      <div v-if="formData.items.length > 0" class="mt-1 text-right text-sm text-bone-300">
        {{ $t('forms.invoice.itemsSubtotal') }}:
        <strong class="tnum">{{ itemsSubtotal.toFixed(2) }}{{ currencySymbol }}</strong>
      </div>
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
import { Calendar, Plus, Trash2 } from 'lucide-vue-next';

export default {
  name: 'InvoiceForm',
  setup() {
    const { t } = useI18n()
    return { t, Calendar, Plus, Trash2 }
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
        items: [],
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
    },
    itemsSubtotal() {
      return this.formData.items.reduce((sum, item) => {
        return sum + (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
      }, 0);
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
          notes: this.invoice.notes || '',
          items: (this.invoice.items || []).map(item => ({
            description: item.description,
            quantity: item.quantity,
            unit_price: item.unit_price,
          })),
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

    addItem() {
      this.formData.items.push({ description: '', quantity: 1, unit_price: 0 });
    },

    removeItem(index) {
      this.formData.items.splice(index, 1);
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
        issue_date: this.formData.issue_date,
        due_date: this.formData.due_date,
        total_amount: this.formData.total_amount,
        status: this.formData.status,
        notes: this.formData.notes,
        items: this.formData.items.map(item => ({
          description: item.description,
          quantity: parseFloat(item.quantity) || 0,
          unit_price: parseFloat(item.unit_price) || 0,
        }))
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
