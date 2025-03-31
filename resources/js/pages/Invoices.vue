<template>
  <v-container>
    <h1 class="text-h4 mb-4">Invoices</h1>
    
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
          @input="fetchInvoices"
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="text-right">
        <v-btn color="primary" to="/invoices/create" prepend-icon="mdi-plus">
          New Invoice
        </v-btn>
      </v-col>
    </v-row>
    
    <v-card>
      <v-data-table
        :headers="headers"
        :items="invoices"
        :loading="loading"
        class="elevation-1"
        :search="search"
      >
          <template v-slot:item.date="{ item }">
          {{ formatDate(item.date) }}
        </template>
        <template v-slot:item.status="{ item }">
          <v-chip
            :color="getStatusColor(item.status)"
            size="small"
          >
            {{ item.status }}
          </v-chip>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="info" :to="`/invoices/${item.id}`">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="primary" :to="`/invoices/${item.id}/edit`">
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
        <v-card-title>Delete Invoice</v-card-title>
        <v-card-text>
          Are you sure you want to delete this invoice? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteInvoice">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import axios from 'axios';

export default {
  name: 'InvoicesIndex',
  data() {
    return {
      invoices: [],
      loading: false,
      search: '',
      deleteDialog: false,
      itemToDelete: null,
      
      headers: [
        { title: 'Invoice #', key: 'number' },
        { title: 'Customer', key: 'customer.name' },
        { title: 'Date', key: 'date' },
        { title: 'total_amount', key: 'total_amount' },
        { title: 'Status', key: 'status' },
        { title: 'Actions', key: 'actions', sortable: false }
      ]
    };
  },
  
  created() {
    this.fetchInvoices();
  },
  
  methods: {
    async fetchInvoices() {
      this.loading = true;
      try {
        const response = await axios.get('/api/invoices');
        this.invoices = response.data;
      } catch (error) {
        console.error('Error fetching invoices:', error);
      } finally {
        this.loading = false;
      }
    },
    
    confirmDelete(item) {
      this.itemToDelete = item;
      this.deleteDialog = true;
    },
    
    async deleteInvoice() {
      try {
        await axios.delete(`/api/invoices/${this.itemToDelete.id}`);
        this.invoices = this.invoices.filter(i => i.id !== this.itemToDelete.id);
        this.deleteDialog = false;
      } catch (error) {
        console.error('Error deleting invoice:', error);
      }
    },
    
    formatDate(dateStr) {
      return new Date(dateStr).toLocaleDateString();
    },
    
    getStatusColor(status) {
      const colors = {
        'paid': 'success',
        'pending': 'warning',
        'overdue': 'error',
        'draft': 'grey'
      };
      return colors[status.toLowerCase()] || 'grey';
    }
  }
};
</script>
