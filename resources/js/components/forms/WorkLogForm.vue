<template>
  <v-form ref="form" @submit.prevent="submit">
    <v-row>
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
              v-model="formData.date"
              label="Date"
              prepend-icon="mdi-calendar"
              readonly
              :rules="[v => !!v || 'Date is required']"
              v-bind="props"
            ></v-text-field>
          </template>
          <v-date-picker
            v-model="formData.date"
            @change="dateMenu = false"
          ></v-date-picker>
        </v-menu>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.project_id"
          :items="projects"
          item-title="name"
          item-value="id"
          label="Project"
          prepend-icon="mdi-folder"
          :rules="[v => !!v || 'Project is required']"
        ></v-select>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.start_time"
          label="Start Time"
          type="time"
          prepend-icon="mdi-clock-start"
          :rules="[v => !!v || 'Start time is required']"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12" md="6">
        <v-text-field
          v-model="formData.end_time"
          label="End Time"
          type="time"
          prepend-icon="mdi-clock-end"
          :rules="[v => !!v || 'End time is required']"
        ></v-text-field>
      </v-col>
      
      <v-col cols="12">
        <v-switch
          v-model="formData.billable"
          label="Billable"
          color="primary"
        ></v-switch>
      </v-col>
      
      <v-col cols="12">
        <v-textarea
          v-model="formData.description"
          label="Description"
          prepend-icon="mdi-text"
          :rules="[v => !!v || 'Description is required']"
        ></v-textarea>
      </v-col>
    </v-row>
  </v-form>
</template>

<script>
export default {
  name: 'WorkLogForm',
  props: {
    workLog: {
      type: Object,
      default: null
    },
    projects: {
      type: Array,
      required: true
    }
  },
  
  data() {
    return {
      dateMenu: false,
      formData: {
        date: new Date().toISOString().substr(0, 10),
        project_id: null,
        start_time: '09:00',
        end_time: '17:00',
        billable: true,
        description: ''
      }
    };
  },
  
  created() {
    if (this.workLog) {
      this.formData = { ...this.workLog };
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
