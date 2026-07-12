<template>
  <div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
    <!-- Heading + primary action -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold tracking-tight">{{ $t('pages.users.title') }}</h1>
      <ui-button variant="primary" :icon="Plus" @click="openCreateDialog">
        {{ $t('pages.users.newUser') }}
      </ui-button>
    </div>

    <!-- Search -->
    <div class="mb-4 max-w-sm">
      <ui-input v-model="search" :icon="Search" clearable :placeholder="$t('common.search')" />
    </div>

    <ui-card dense>
      <ui-data-table
        :headers="headers"
        :items="users"
        :loading="loading"
        :search="search"
        :sort-by="sortBy"
      >
        <template v-slot:item.actions="{ item }">
          <div class="flex justify-end gap-1">
            <ui-button variant="ghost" size="sm" :icon="Pencil" @click="openEditDialog(item)" />
            <ui-button variant="brass-ghost" size="sm" :icon="KeyRound" @click="confirmResetPassword(item)" />
            <ui-button variant="danger-ghost" size="sm" :icon="Trash2" @click="confirmDelete(item)" />
          </div>
        </template>
        <template v-slot:item.created_at="{ item }">
          <span class="tnum">{{ formatDate(item.created_at) }}</span>
        </template>
        <template v-slot:item.updated_at="{ item }">
          <span class="tnum">{{ formatDate(item.updated_at) }}</span>
        </template>
        <template v-slot:item.hourly_rate="{ item }">
          <span class="tnum">{{ formatCurrency(item.hourly_rate || 0) }}</span>
        </template>
      </ui-data-table>
    </ui-card>

    <ui-dialog v-model="deleteDialog" :title="$t('pages.users.deleteUser')" max-width="500px" persistent>
      <p class="text-sm text-bone-300">{{ $t('pages.users.deleteConfirmation') }}</p>
      <template #actions>
        <ui-button variant="ghost" @click="deleteDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="danger" @click="deleteUserRecord">{{ $t('common.delete') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Reset Password Dialog -->
    <ui-dialog v-model="resetPasswordDialog" :title="$t('pages.users.resetPassword')" max-width="500px" persistent>
      <p class="text-sm text-bone-300">
        {{ $t('pages.users.sendResetLink') }} <strong class="text-bone-100">{{ resetPasswordUser?.email }}</strong>?
      </p>
      <p class="mt-3 text-sm text-bone-500">
        The user will receive an email with instructions to reset their password.
      </p>
      <template #actions>
        <ui-button variant="ghost" @click="resetPasswordDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" @click="resetPassword">{{ $t('pages.users.sendResetLink') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Create User Dialog -->
    <ui-dialog v-model="createDialog" :title="$t('pages.users.newUser')" max-width="800px" persistent>
      <user-form ref="createForm" @save="saveUserRecord"></user-form>
      <template #actions>
        <ui-button variant="ghost" @click="createDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" @click="$refs.createForm.submit()">{{ $t('common.save') }}</ui-button>
      </template>
    </ui-dialog>

    <!-- Edit User Dialog -->
    <ui-dialog v-model="editDialog" :title="`${$t('common.edit')} ${$t('users.user')}`" max-width="800px" persistent>
      <user-form ref="editForm" :user="currentUser" @save="updateUserRecord"></user-form>
      <template #actions>
        <ui-button variant="ghost" @click="editDialog = false">{{ $t('common.cancel') }}</ui-button>
        <ui-button variant="primary" @click="$refs.editForm.submit()">{{ $t('common.save') }}</ui-button>
      </template>
    </ui-dialog>
  </div>
</template>

<script>
import UserForm from '../components/forms/UserForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatDate, formatCurrency } from '../utils/formatters';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { Plus, Search, Pencil, Trash2, KeyRound } from 'lucide-vue-next';

export default {
  name: 'UsersIndex',
  components: {
    UserForm
  },
  setup() {
    const { t } = useI18n();
    return { t, Plus, Search, Pencil, Trash2, KeyRound };
  },
  data() {
    return {
      loading: false,
      search: '',
      deleteDialog: false,
      itemToDelete: null,
      createDialog: false,
      editDialog: false,
      currentUser: null,
      resetPasswordDialog: false,
      resetPasswordUser: null,

      sortBy: [{ key: 'id', order: 'desc' }]
    };
  },

  computed: {
    ...mapState(store, ['users', 'currencySymbol', 'settings']),

    headers() {
      return [
        { title: this.t('pages.users.id'), key: 'id' },
        { title: this.t('pages.users.name'), key: 'name' },
        { title: this.t('common.email'), key: 'email' },
        { title: this.t('pages.users.role'), key: 'role' },
        { title: this.t('pages.users.hourlyRate'), key: 'hourly_rate' },
        { title: this.t('pages.users.created'), key: 'created_at' },
        { title: this.t('pages.users.updated'), key: 'updated_at' },
        { title: this.t('common.actions'), key: 'actions', sortable: false, align: 'end' }
      ];
    }
  },

  created() {
    this.fetchUsers();
  },

  methods: {
    ...mapActions(store, [
      'showSnackbar',
      'fetchUsers',
      'createUser',
      'updateUser',
      'deleteUser'
    ]),

    confirmDelete(item) {
      this.itemToDelete = item;
      this.deleteDialog = true;
    },

    async deleteUserRecord() {
      try {
        await this.deleteUser(this.itemToDelete.id);
        this.deleteDialog = false;
      } catch (error) {
        console.error('Error deleting user:', error);
      }
    },

    openCreateDialog() {
      this.createDialog = true;
    },

    openEditDialog(item) {
      this.currentUser = { ...item };
      this.editDialog = true;
    },

    async saveUserRecord(user) {
      try {
        await this.createUser(user);
        this.createDialog = false;
      } catch (error) {
        console.error('Error creating user:', error);
      }
    },

    async updateUserRecord(user) {
      try {
        await this.updateUser(user);
        this.editDialog = false;
      } catch (error) {
        console.error('Error updating user:', error);
      }
    },

    confirmResetPassword(item) {
      this.resetPasswordUser = item;
      this.resetPasswordDialog = true;
    },

    async resetPassword() {
      try {
        await axios.post(`/api/users/${this.resetPasswordUser.id}/reset-password`);
        this.resetPasswordDialog = false;
        this.showSnackbar('Password reset link sent successfully', 'success');
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to send reset link. Please try again.';
        this.showSnackbar(message, 'error');
      }
    },

    formatDate(dateStr) {
      return formatDate(dateStr, this.settings);
    },

    formatCurrency(amount) {
      return formatCurrency(amount, this.settings);
    }
  }
};
</script>
