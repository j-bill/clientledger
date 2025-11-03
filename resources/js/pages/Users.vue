<template>
  <v-container fluid>
    <h1 class="text-h4 mb-4">{{ $t('pages.users.title') }}</h1>
    
    <!-- Search & Actions -->
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          :label="$t('common.search')"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="d-flex justify-end">
        <v-btn color="primary" @click="openCreateDialog" prepend-icon="mdi-plus">
          {{ $t('pages.users.newUser') }}
        </v-btn>
      </v-col>
    </v-row>
    
    <v-card>
      <v-data-table
        :headers="headers"
        :items="users"
        :loading="loading"
        class="elevation-1"
        :search="search"
        :sort-by="sortBy"
      >
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="primary" @click="openEditDialog(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="warning" @click="confirmResetPassword(item)">
            <v-icon>mdi-lock-reset</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
        <template v-slot:item.created_at="{ item }">
          {{ formatDate(item.created_at) }}
        </template>
        <template v-slot:item.updated_at="{ item }">
          {{ formatDate(item.updated_at) }}
        </template>
        <template v-slot:item.hourly_rate="{ item }">
          {{ formatCurrency(item.hourly_rate || 0) }}
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="deleteDialog" max-width="500px" persistent>
      <v-card>
        <v-card-title>{{ $t('pages.users.deleteUser') }}</v-card-title>
        <v-card-text>
          {{ $t('pages.users.deleteConfirmation') }}
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="error" @click="deleteUserRecord">{{ $t('common.delete') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Reset Password Dialog -->
    <v-dialog v-model="resetPasswordDialog" max-width="500px" persistent>
      <v-card>
        <v-card-title>{{ $t('pages.users.resetPassword') }}</v-card-title>
        <v-card-text>
          {{ $t('pages.users.sendResetLink') }} <strong>{{ resetPasswordUser?.email }}</strong>?
          <br><br>
          The user will receive an email with instructions to reset their password.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="resetPasswordDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="warning" @click="resetPassword">{{ $t('pages.users.sendResetLink') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Create User Dialog -->
    <v-dialog v-model="createDialog" max-width="800px" persistent>
      <v-card>
        <v-card-title>{{ $t('pages.users.newUser') }}</v-card-title>
        <v-card-text>
          <user-form ref="createForm" @save="saveUserRecord"></user-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="createDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="primary" @click="$refs.createForm.submit()">{{ $t('common.save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Edit User Dialog -->
    <v-dialog v-model="editDialog" max-width="800px" persistent>
      <v-card>
        <v-card-title>{{ $t('common.edit') }} {{ $t('users.user') }}</v-card-title>
        <v-card-text>
          <user-form ref="editForm" :user="currentUser" @save="updateUserRecord"></user-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="editDialog = false">{{ $t('common.cancel') }}</v-btn>
          <v-btn color="primary" @click="$refs.editForm.submit()">{{ $t('common.save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import UserForm from '../components/forms/UserForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatDate, formatCurrency } from '../utils/formatters';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

export default {
  name: 'UsersIndex',
  components: {
    UserForm
  },
  setup() {
    const { t } = useI18n();
    return { t };
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
        { title: this.t('common.actions'), key: 'actions', sortable: false }
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
