<template>
  <v-container>
    <h1 class="text-h4 mb-4">Users</h1>
    
    <v-row class="mb-4">
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="search"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          single-line
          hide-details
          clearable
          @input="fetchUsers"
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" prepend-icon="mdi-plus">
          New User
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
      >
        <template v-slot:item.actions="{ item }">
          <v-btn icon variant="text" size="small" color="info" @click="openEditDialog(item)">
            <v-icon>mdi-eye</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="primary" @click="openEditDialog(item)">
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
        <template v-slot:item.created_at="{ item }">
          {{ new Date(item.created_at).toLocaleDateString() }}
        </template>
        <template v-slot:item.updated_at="{ item }">
          {{ new Date(item.updated_at).toLocaleDateString() }}
        </template>
        <template v-slot:item.hourly_rate="{ item }">
          <span v-if="item.role === 'freelancer'">${{ Number(item.hourly_rate || 0).toFixed(2) }}</span>
          <span v-else>-</span>
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="deleteDialog" max-width="500px">
      <v-card>
        <v-card-title>Delete User</v-card-title>
        <v-card-text>
          Are you sure you want to delete this user? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="primary" variant="text" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn color="error" @click="deleteUserRecord">Delete</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Create User Dialog -->
    <v-dialog v-model="createDialog" max-width="800px">
      <v-card>
        <v-card-title>New User</v-card-title>
        <v-card-text>
          <user-form ref="createForm" @save="saveUserRecord"></user-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" variant="text" @click="createDialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="$refs.createForm.submit()">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Edit User Dialog -->
    <v-dialog v-model="editDialog" max-width="800px">
      <v-card>
        <v-card-title>Edit User</v-card-title>
        <v-card-text>
          <user-form ref="editForm" :user="currentUser" @save="updateUserRecord"></user-form>
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
import UserForm from '../components/forms/UserForm.vue';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';

export default {
  name: 'UsersIndex',
  components: {
    UserForm
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
      
      headers: [
        { title: 'Name', key: 'name' },
        { title: 'Email', key: 'email' },
        { title: 'Role', key: 'role' },
        { title: 'Hourly Rate', key: 'hourly_rate' },
        { title: 'Created', key: 'created_at' },
        { title: 'Updated', key: 'updated_at' },
        { title: 'Actions', key: 'actions', sortable: false }
      ]
    };
  },
  
  computed: {
    ...mapState(store, ['users'])
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
    }
  }
};
</script>
