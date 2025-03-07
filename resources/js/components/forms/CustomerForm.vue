<template>
  <v-form ref="form" @submit.prevent="submit">
    <v-row>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.name"
          label="Name"
          prepend-icon="mdi-account"
          :rules="[v => !!v || 'Name is required']"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.contact_person"
          label="Contact Person"
          prepend-icon="mdi-account"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.contact_email"
          label="Contact Email"
          type="email"
          prepend-icon="mdi-email"
          :rules="[
            v => !v || /.+@.+\..+/.test(v) || 'Email must be valid'
          ]"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.contact_phone"
          label="Contact Phone"
          prepend-icon="mdi-phone"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.address_line_1"
          label="Address Line 1"
          prepend-icon="mdi-map-marker"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.address_line_2"
          label="Address Line 2"
          prepend-icon="mdi-map-marker"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.city"
          label="City"
          prepend-icon="mdi-city"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.state"
          label="State"
          prepend-icon="mdi-city"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.postcode"
          label="Postcode"
          prepend-icon="mdi-mailbox"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.country"
          label="Country"
          prepend-icon="mdi-earth"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.vat_number"
          label="VAT Number"
          prepend-icon="mdi-numeric"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.hourly_rate"
          label="Hourly Rate"
          prepend-icon="mdi-currency-usd"
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
    submit() {
      if (this.$refs.form.validate()) {
        this.$emit('save', this.formData);
      }
    }
  }
};
</script>
