<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">Customers</h1>
    
    <!-- Search & Actions -->
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="d-flex justify-end">
        <v-btn color="primary" @click="openCreateDialog" prepend-icon="mdi-plus">
          New Customer
        </v-btn>
      </v-col>
    </v-row>
    
    <v-card>
      <v-data-table
        :headers="headers"
        :items="customers"
        :loading="loading"
        class="elevation-1"
        :search="search"
        :sort-by="sortBy"
      >
        <template v-slot:item.hourly_rate="{ item }">
          {{ formatCurrency(item.hourly_rate || 0) }}
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="primary" @click="openEditDialog(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title>Delete Customer</v-card-title>
        <v-card-text>
          Are you sure you want to delete this customer? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteCustomerRecord">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Create Customer Dialog -->
    <v-dialog v-model="createDialog" max-width="800px">
      <v-card>
        <v-card-title>New Customer</v-card-title>
        <v-card-text>
          <customer-form ref="createForm" @save="saveCustomerRecord"></customer-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.createForm.submit()">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Edit Customer Dialog -->
    <v-dialog v-model="editDialog" max-width="800px">
      <v-card>
        <v-card-title>Edit Customer</v-card-title>
        <v-card-text>
          <customer-form ref="editForm" :customer="currentCustomer" @save="updateCustomerRecord"></customer-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="editDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.editForm.submit()">Update</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import CustomerForm from '../components/forms/CustomerForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatCurrency } from '../utils/formatters';

export default {
  name: 'CustomersIndex',
  components: {
    CustomerForm
  },
  data() {
    return {
      loading: false,
      search: '',
      deleteDialog: false,
      createDialog: false,
      editDialog: false,
      itemToDelete: null,
      currentCustomer: null,
      
      sortBy: [{ key: 'id', order: 'desc' }],
      headers: [
        { title: 'ID', key: 'id' },
        { title: 'Name', key: 'name' },
        { title: 'Contact Person', key: 'contact_person' },
        { title: 'Email', key: 'contact_email' },
        { title: 'Phone', key: 'contact_phone' },
        { title: 'City', key: 'city' },
        { title: 'Country', key: 'country' },
        { title: 'Hourly Rate', key: 'hourly_rate' },
        { title: 'Actions', key: 'actions', sortable: false }
      ]
    };
  },
  
  computed: {
    ...mapState(store, ['customers', 'currencySymbol'])
  },
  
  created() {
    this.fetchCustomers();
  },
  
  methods: {
    ...mapActions(store, [
      'showSnackbar',
      'fetchCustomers',
      'createCustomer',
      'updateCustomer',
      'deleteCustomer',
    ]),
    
    confirmDelete(item) {
      this.itemToDelete = item;
      this.deleteDialog = true;
    },
    
    async deleteCustomerRecord() {
      try {
        await this.deleteCustomer(this.itemToDelete.id);
        this.deleteDialog = false;
      } catch (error) {
        console.error('Error deleting customer:', error);
      }
    },
    
    openCreateDialog() {
      this.createDialog = true;
    },
    
    openEditDialog(item) {
      this.currentCustomer = { ...item };
      this.editDialog = true;
    },
    
    async saveCustomerRecord(customer) {
      try {
        await this.createCustomer(customer);
        this.createDialog = false;
      } catch (error) {
        console.error('Error creating customer:', error);
      }
    },
    
    async updateCustomerRecord(customer) {
      try {
        await this.updateCustomer(customer);
        this.editDialog = false;
      } catch (error) {
        console.error('Error updating customer:', error);
      }
    },
    
    formatCurrency(amount) {
      return formatCurrency(amount, this.settings);
    }
  }
};
</script>