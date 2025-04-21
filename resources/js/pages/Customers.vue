<template>
  <v-container>
    <h1 class="text-h4 mb-4">Customers</h1>
    
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
          @input="fetchCustomers"
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="text-right">
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
      >
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="info" @click="viewCustomer(item)">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
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
    
    <!-- View Customer Dialog -->
    <v-dialog v-model="viewDialog" max-width="800px">
      <v-card v-if="currentCustomer">
        <v-card-title>{{ currentCustomer.name }}</v-card-title>
        <v-card-text>
          <v-list>
            <v-list-item>
              <v-list-item-title>Contact Person:</v-list-item-title>
              <v-list-item-subtitle>{{ currentCustomer.contact_person || 'N/A' }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Contact Email:</v-list-item-title>
              <v-list-item-subtitle>{{ currentCustomer.contact_email || 'N/A' }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Contact Phone:</v-list-item-title>
              <v-list-item-subtitle>{{ currentCustomer.contact_phone || 'N/A' }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Address:</v-list-item-title>
              <v-list-item-subtitle>
                {{ currentCustomer.address_line_1 }}<br v-if="currentCustomer.address_line_2"/>
                {{ currentCustomer.address_line_2 || '' }}<br/>
                {{ currentCustomer.city }} {{ currentCustomer.state }} {{ currentCustomer.postcode }}<br/>
                {{ currentCustomer.country }}
              </v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>VAT Number:</v-list-item-title>
              <v-list-item-subtitle>{{ currentCustomer.vat_number || 'N/A' }}</v-list-item-subtitle>
            </v-list-item>
            <v-list-item>
              <v-list-item-title>Hourly Rate:</v-list-item-title>
              <v-list-item-subtitle>{{ currentCustomer.hourly_rate || 'N/A' }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" @click="viewDialog = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import CustomerForm from '../components/forms/CustomerForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';

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
      viewDialog: false,
      itemToDelete: null,
      currentCustomer: null,
      
      headers: [
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
    ...mapState(store, ['customers'])
  },
  
  created() {
    this.fetchCustomers();
  },
  
  methods: {
    ...mapActions(store, [
      'showSnackbar',
      'fetchCustomers',
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
    
    viewCustomer(item) {
      this.currentCustomer = { ...item };
      this.viewDialog = true;
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
    }
  }
};
</script>