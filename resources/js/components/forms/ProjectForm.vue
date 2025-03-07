<template>
  <v-form ref="form" @submit.prevent="submit">
    <v-row>
      <v-col cols="12">
        <v-text-field
          v-model="formData.name"
          label="Project Name"
          prepend-icon="mdi-briefcase"
          :rules="[v => !!v || 'Project name is required']"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.customer_id"
          :items="customers"
          item-title="name"
          item-value="id"
          label="Customer"
          prepend-icon="mdi-account"
          :rules="[v => !!v || 'Customer is required']"
        ></v-select>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.hourly_rate"
          label="Hourly Rate ($)"
          type="number"
          prepend-icon="mdi-currency-usd"
          hint="Leave empty if not applicable"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-menu
          v-model="dateMenu"
          :close-on-content-click="false"
          transition="scale-transition"
          offset-y
          min-width="auto"
        >
          <template v-slot:activator="{ props }">
            <v-text-field
              v-model="formattedDate"
              label="Deadline"
              prepend-icon="mdi-calendar"
              readonly
              hint="Leave empty if no deadline"
              v-bind="props"
            ></v-text-field>
          </template>
          <v-date-picker
            v-model="formData.deadline"
            @change="dateMenu = false"
          ></v-date-picker>
        </v-menu>
      </v-col>
      
      <v-col cols="12">
        <v-textarea
          v-model="formData.description"
          label="Description"
          prepend-icon="mdi-text"
        ></v-textarea>
      </v-col>
    </v-row>
  </v-form>
</template>

<script>
export default {
  name: 'ProjectForm',
  props: {
    project: {
      type: Object,
      default: null
    },
    customers: {
      type: Array,
      required: true
    }
  },

  computed: {
    formattedDate() {
      // use Intl.DateTimeFormat to format the date
      if (this.formData.deadline) {
        return new Intl.DateTimeFormat('en-US', {
          year: 'numeric',
          month: 'short',
          day: '2-digit'
        }).format(new Date(this.formData.deadline));
      }
    }
  },
  
  data() {
    return {
      dateMenu: false,
      formData: {
        name: '',
        customer_id: null,
        hourly_rate: null,
        deadline: null,
        description: ''
      }
    };
  },
  
  created() {
    if (this.project) {
      this.formData = { ...this.project };
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
