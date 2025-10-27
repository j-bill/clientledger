<template>
  <v-form ref="form" @submit.prevent="submit">
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.name"
          :label="$t('forms.customer.name')"
          prepend-icon="mdi-account"
          :rules="[v => !!v || $t('forms.customer.nameRequired')]"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.contact_person"
          :label="$t('forms.customer.contactPerson')"
          prepend-icon="mdi-account"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.contact_email"
          :label="$t('forms.customer.contactEmail')"
          type="email"
          prepend-icon="mdi-email"
          :rules="[
            v => !v || /.+@.+\..+/.test(v) || $t('forms.customer.emailValid')
          ]"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.contact_phone"
          :label="$t('forms.customer.contactPhone')"
          prepend-icon="mdi-phone"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.address_line_1"
          :label="$t('forms.customer.addressLine1')"
          prepend-icon="mdi-map-marker"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.address_line_2"
          :label="$t('forms.customer.addressLine2')"
          prepend-icon="mdi-map-marker"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.city"
          :label="$t('forms.customer.city')"
          prepend-icon="mdi-city"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.state"
          :label="$t('forms.customer.state')"
          prepend-icon="mdi-city"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.postcode"
          :label="$t('forms.customer.postcode')"
          prepend-icon="mdi-mailbox"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.country"
          :label="$t('forms.customer.country')"
          prepend-icon="mdi-earth"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.vat_number"
          :label="$t('forms.customer.vatNumber')"
          prepend-icon="mdi-numeric"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.hourly_rate"
          :label="$t('forms.customer.hourlyRate')"
          prepend-icon="mdi-cash"
          type="number"
        ></v-text-field>
      </v-col>
    </v-row>
  </v-form>
</template>

<script>
export default {
  name: 'CustomerForm',
  props: {
    customer: {
      type: Object,
      default: null
    }
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
        hourly_rate: 0
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
