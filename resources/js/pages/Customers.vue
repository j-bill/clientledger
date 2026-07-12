<template>
  <div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
    <!-- Heading + primary action -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('customers.title') }}</h1>
      <ui-button variant="primary" :icon="Plus" @click="openCreateDialog">
        {{ $t('customers.newCustomer') }}
      </ui-button>
    </div>

    <!-- Search -->
    <div class="mb-4 max-w-sm">
      <ui-input v-model="search" :icon="Search" clearable :placeholder="$t('common.search')" />
    </div>

    <ui-card dense>
      <ui-data-table
        :headers="headers"
        :items="customers"
        :loading="loading"
        :search="search"
        :sort-by="sortBy"
      >
        <template v-slot:item.hourly_rate="{ item }">
          <span class="tnum">{{ formatCurrency(item.hourly_rate || 0) }}</span>
        </template>
        <template v-slot:item.actions="{ item }">
          <div class="flex justify-end gap-1">
            <ui-button variant="ghost" size="sm" :icon="Pencil" @click="openEditDialog(item)" />
            <ui-button variant="danger-ghost" size="sm" :icon="Trash2" @click="confirmDelete(item)" />
          </div>
        </template>
      </ui-data-table>
    </ui-card>

    <ui-dialog v-model="deleteDialog" :title="`${$t('common.delete')} ${$t('customers.customer')}`" max-width="500px" persistent>
      <p class="text-sm text-bone-300">{{ $t('common.deleteConfirmation') }}</p>
      <template #actions>
        <ui-button variant="ghost" @click="deleteDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="danger" @click="deleteCustomerRecord">{{ $t('common.delete') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Create Customer Dialog -->
    <ui-dialog v-model="createDialog" :title="$t('customers.newCustomer')" max-width="800px" persistent>
      <customer-form ref="createForm" @save="saveCustomerRecord"></customer-form>
      <template #actions>
        <ui-button variant="ghost" @click="createDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" @click="$refs.createForm.submit()">{{ $t('common.save') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Edit Customer Dialog -->
    <ui-dialog v-model="editDialog" :title="`${$t('common.edit')} ${$t('customers.customer')}`" max-width="800px" persistent>
      <customer-form ref="editForm" :customer="currentCustomer" @save="updateCustomerRecord"></customer-form>
      <template #actions>
        <ui-button variant="ghost" @click="editDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" @click="$refs.editForm.submit()">{{ $t('common.save') }}</ui-button>
      </template>
    </ui-dialog>
  </div>
</template>

<script>
import CustomerForm from '../components/forms/CustomerForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatCurrency } from '../utils/formatters';
import { useI18n } from 'vue-i18n';
import { Plus, Search, Pencil, Trash2 } from 'lucide-vue-next';

export default {
  name: 'CustomersIndex',
  components: {
    CustomerForm
  },
  setup() {
    const { t } = useI18n();
    return { t, Plus, Search, Pencil, Trash2 };
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

      sortBy: [{ key: 'id', order: 'desc' }]
    };
  },

  computed: {
    ...mapState(store, ['customers', 'settings', 'currencySymbol']),
    headers() {
      return [
        { title: 'ID', key: 'id' },
        { title: this.t('customers.name'), key: 'name' },
        { title: this.t('common.email'), key: 'contact_email' },
        { title: this.t('customers.phone'), key: 'contact_phone' },
        { title: this.t('customers.city'), key: 'city' },
        { title: this.t('customers.country'), key: 'country' },
        { title: this.t('invoices.rateUnit'), key: 'hourly_rate', align: 'end' },
        { title: this.t('common.actions'), key: 'actions', sortable: false, align: 'end' }
      ];
    }
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
