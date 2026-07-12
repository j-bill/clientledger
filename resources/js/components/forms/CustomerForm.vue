<template>
  <ui-form ref="form" @submit="submit">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <ui-input
        v-model="formData.name"
        :label="$t('forms.customer.name')"
        :icon="User"
        :rules="[v => !!v || $t('forms.customer.nameRequired')]"
      />
      <ui-input
        v-model="formData.contact_person"
        :label="$t('forms.customer.contactPerson')"
        :icon="User"
      />
      <ui-input
        v-model="formData.contact_email"
        :label="$t('forms.customer.contactEmail')"
        type="email"
        :icon="Mail"
        :rules="[
          v => !v || /.+@.+\..+/.test(v) || $t('forms.customer.emailValid')
        ]"
      />
      <ui-input
        v-model="formData.contact_phone"
        :label="$t('forms.customer.contactPhone')"
        :icon="Phone"
      />
      <ui-input
        v-model="formData.address_line_1"
        :label="$t('forms.customer.addressLine1')"
        :icon="MapPin"
      />
      <ui-input
        v-model="formData.address_line_2"
        :label="$t('forms.customer.addressLine2')"
        :icon="MapPin"
      />
      <ui-input
        v-model="formData.city"
        :label="$t('forms.customer.city')"
        :icon="Building2"
      />
      <ui-input
        v-model="formData.state"
        :label="$t('forms.customer.state')"
        :icon="Building2"
      />
      <ui-input
        v-model="formData.postcode"
        :label="$t('forms.customer.postcode')"
        :icon="Inbox"
      />
      <ui-input
        v-model="formData.country"
        :label="$t('forms.customer.country')"
        :icon="Globe"
      />
      <ui-input
        v-model="formData.vat_number"
        :label="$t('forms.customer.vatNumber')"
        :icon="Hash"
      />
      <ui-input
        v-model="formData.hourly_rate"
        :label="$t('forms.customer.hourlyRate')"
        :icon="Banknote"
        type="number"
      />
    </div>

    <hr class="my-5 border-ink-700/60" />

    <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-bone-100">
      <FileText class="h-4 w-4 text-bone-500" />
      {{ $t('forms.customer.invoiceSettings') }}
    </div>

    <div class="space-y-4">
      <ui-textarea
        v-model="formData.invoice_default_message"
        :label="$t('forms.customer.invoiceDefaultMessage')"
        rows="3"
        :hint="$t('forms.customer.invoiceDefaultMessageHint')"
      />
      <ui-textarea
        v-model="formData.invoice_payment_terms"
        :label="$t('forms.customer.invoicePaymentTerms')"
        rows="4"
        :hint="$t('forms.customer.invoicePaymentTermsHint')"
      />
    </div>
  </ui-form>
</template>

<script>
import { User, Mail, Phone, MapPin, Building2, Inbox, Globe, Hash, Banknote, FileText } from 'lucide-vue-next';

export default {
  name: 'CustomerForm',
  components: { FileText },
  props: {
    customer: {
      type: Object,
      default: null
    }
  },

  setup() {
    return { User, Mail, Phone, MapPin, Building2, Inbox, Globe, Hash, Banknote };
  },

  data() {
    return {
      formData: {
        name: '',
        contact_person: '',
        contact_email: '',
        contact_phone: '',
        address_line_1: '',
        address_line_2: '',
        city: '',
        state: '',
        postcode: '',
        country: '',
        vat_number: '',
        hourly_rate: 0,
        invoice_default_message: '',
        invoice_payment_terms: ''
      }
    };
  },

  created() {
    if (this.customer) {
      this.formData = { ...this.customer };
    }
  },

  methods: {
    async submit() {
      const { valid } = await this.$refs.form.validate();

      if (!valid) {
        return;
      }

      this.$emit('save', this.formData);
    }
  }
};
</script>
