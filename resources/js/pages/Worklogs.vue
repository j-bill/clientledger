<template>
	<v-container>
		<h1 class="text-h4 mb-4">Work Logs</h1>

		<!-- Filters -->
		<v-card class="mb-6">
			<v-card-title>Filters</v-card-title>
			<v-card-text>
				<v-row>
					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-menu ref="startMenu"
								v-model="startMenu"
								:close-on-content-click="false"
								transition="scale-transition"
								offset-y
								min-width="auto">
							<template v-slot:activator="{ props }">
								<v-text-field v-model="filters.start_date"
											  label="Start Date"
											  prepend-icon="mdi-calendar"
											  readonly
											  v-bind="props"></v-text-field>
							</template>
							<v-date-picker v-model="filters.start_date"
										   @change="startMenu = false"></v-date-picker>
						</v-menu>
					</v-col>

					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-menu ref="endMenu"
								v-model="endMenu"
								:close-on-content-click="false"
								transition="scale-transition"
								offset-y
								min-width="auto">
							<template v-slot:activator="{ props }">
								<v-text-field v-model="filters.end_date"
											  label="End Date"
											  prepend-icon="mdi-calendar"
											  readonly
											  v-bind="props"></v-text-field>
							</template>
							<v-date-picker v-model="filters.end_date"
										   @change="endMenu = false"></v-date-picker>
						</v-menu>
					</v-col>

					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-select v-model="filters.project_id"
								  :items="projects"
								  item-title="name"
								  item-value="id"
								  label="Project"
								  clearable
								  prepend-icon="mdi-folder"></v-select>
					</v-col>

					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-select v-model="filters.billable"
								  :items="billableOptions"
								  label="Billable"
								  clearable
								  prepend-icon="mdi-cash"></v-select>
					</v-col>
				</v-row>

				<v-row>
					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-select v-model="filters.sort_by"
								  :items="sortOptions"
								  label="Sort By"
								  prepend-icon="mdi-sort"></v-select>
					</v-col>

					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-select v-model="filters.sort_dir"
								  :items="sortDirections"
								  label="Sort Direction"
								  prepend-icon="mdi-sort-ascending"></v-select>
					</v-col>

					<v-col cols="12"
						   class="text-right">
						<v-btn color="primary"
							   @click="fetchWorkLogs">
							Apply Filters
						</v-btn>
						<v-btn class="ms-2"
							   @click="resetFilters">
							Reset
						</v-btn>
					</v-col>
				</v-row>
			</v-card-text>
		</v-card>

		<!-- Action Bar -->
		<v-row class="mb-4">
			<v-col cols="12"
				   class="text-right">
				<v-btn color="primary"
					   @click="openCreateDialog"
					   prepend-icon="mdi-plus">
					New Work Log
				</v-btn>
			</v-col>
		</v-row>

		<!-- Work Logs Table -->
		<v-card>
			<v-data-table :headers="headers"
						  :items="workLogs"
						  :loading="loading"
						  class="elevation-1">
				<template v-slot:item.date="{ item }">
					{{ formatDate(item.date) }}
				</template>
				<template v-slot:item.billable="{ item }">
					<v-icon :color="item.billable ? 'success' : 'error'">
						{{ item.billable ? 'mdi-check' : 'mdi-close' }}
					</v-icon>
				</template>
				<template v-slot:item.actions="{ item }">
					<v-btn icon
						   variant="text"
						   size="small"
						   color="primary"
						   @click="openEditDialog(item)">
						<v-icon>mdi-pencil</v-icon>
					</v-btn>
					<v-btn icon
						   variant="text"
						   size="small"
						   color="error"
						   @click="confirmDelete(item)">
						<v-icon>mdi-delete</v-icon>
					</v-btn>
				</template>
			</v-data-table>

			<!-- Pagination -->
			<div class="pa-4 d-flex justify-end">
				<v-pagination v-model="page"
							  :length="totalPages"
							  @update:model-value="fetchWorkLogs"></v-pagination>
			</div>
		</v-card>

		<!-- Delete Confirmation Dialog -->
		<v-dialog v-model="deleteDialog"
				  max-width="500px">
			<v-card>
				<v-card-title>Delete Work Log</v-card-title>
				<v-card-text>
					Are you sure you want to delete this work log?
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="primary"
						   variant="text"
						   @click="deleteDialog = false">Cancel</v-btn>
					<v-btn color="error"
						   @click="deleteWorkLog">Delete</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

		<!-- Create Work Log Dialog -->
		<v-dialog v-model="createDialog"
				  max-width="1000px">
			<v-card>
				<v-card-title>New Work Log</v-card-title>
				<v-card-text>
					<work-log-form ref="createForm"
								   :projects="projects"
								   @save="saveWorkLog"></work-log-form>
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="error"
						   variant="text"
						   @click="createDialog = false">Cancel</v-btn>
					<v-btn color="primary"
						   @click="$refs.createForm.submit()">Save</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

		<!-- Edit Work Log Dialog -->
		<v-dialog v-model="editDialog"
				  max-width="1000px">
			<v-card>
				<v-card-title>Edit Work Log</v-card-title>
				<v-card-text>
					<work-log-form ref="editForm"
								   :work-log="currentWorkLog"
								   :projects="projects"
								   @save="updateWorkLog"></work-log-form>
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="error"
						   variant="text"
						   @click="editDialog = false">Cancel</v-btn>
					<v-btn color="primary"
						   @click="$refs.editForm.submit()">Update</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</v-container>
</template>

<script>
import axios from 'axios';
import WorkLogForm from '../components/forms/WorkLogForm.vue';
import eventBus from '../eventBus'; // Add this import
import { mapActions } from 'pinia';
import { store } from '../store';

export default {
	name: 'WorkLogsIndex',
	components: {
		WorkLogForm
	},
	data() {
		return {
			workLogs: [],
			loading: false,
			page: 1,
			totalPages: 0,
			startMenu: false,
			endMenu: false,
			deleteDialog: false,
			createDialog: false,
			editDialog: false,
			itemToDelete: null,
			currentWorkLog: null,

			filters: {
				start_date: null,
				end_date: null,
				project_id: null,
				billable: null,
				sort_by: 'date',
				sort_dir: 'desc',
				per_page: 10
			},

			headers: [
				{ title: 'Date', key: 'date' },
				{ title: 'Project', key: 'project.name' },
				{ title: 'Start Time', key: 'start_time' },
				{ title: 'End Time', key: 'end_time' },
				{ title: 'Hours', key: 'hours_worked' },
				{ title: 'Billable', key: 'billable' },
				{ title: 'Description', key: 'description' },
				{ title: 'Actions', key: 'actions', sortable: false }
			],

			projects: [],
			billableOptions: [
				{ title: 'Yes', value: true },
				{ title: 'No', value: false }
			],
			sortOptions: [
				{ title: 'Date', value: 'date' },
				{ title: 'Project', value: 'project_id' },
				{ title: 'Hours', value: 'hours_worked' }
			],
			sortDirections: [
				{ title: 'Ascending', value: 'asc' },
				{ title: 'Descending', value: 'desc' }
			]
		};
	},

	created() {
		this.fetchWorkLogs();
		this.fetchProjects();
	},

	methods: {
		...mapActions(store, ['showSnackbar']),
		async fetchWorkLogs() {
			this.loading = true;

			try {
				// Filter out null values
				const params = { ...this.filters, page: this.page };
				Object.keys(params).forEach(key => {
					if (params[key] === null) delete params[key];
				});

				const response = await axios.get('/api/worklogs', { params });
				this.workLogs = response.data.data;
				this.totalPages = Math.ceil(response.data.total / this.filters.per_page);
			} catch (error) {
				console.error('Error fetching work logs:', error);
			} finally {
				this.loading = false;
			}
		},

		async fetchProjects() {
			try {
				const response = await axios.get('/api/projects');
				this.projects = response.data;
			} catch (error) {
				console.error('Error fetching projects:', error);
			}
		},

		resetFilters() {
			this.filters = {
				start_date: null,
				end_date: null,
				project_id: null,
				billable: null,
				sort_by: 'date',
				sort_dir: 'desc',
				per_page: 10
			};
			this.page = 1;
			this.fetchWorkLogs();
		},

		confirmDelete(item) {
			this.itemToDelete = item;
			this.deleteDialog = true;
		},

		async deleteWorkLog() {
			try {
				await axios.delete(`/api/work-logs/${this.itemToDelete.id}`);
				this.workLogs = this.workLogs.filter(w => w.id !== this.itemToDelete.id);
				this.deleteDialog = false;
			} catch (error) {
				console.error('Error deleting work log:', error);
			}
		},

		openCreateDialog() {
			this.createDialog = true;
		},

		openEditDialog(item) {
			this.currentWorkLog = { ...item };
			this.editDialog = true;
		},

		async saveWorkLog(workLog) {
			try {
				const response = await axios.post('/api/worklogs', workLog);
				this.workLogs.unshift(response.data);
				this.createDialog = false;
				this.fetchWorkLogs();
				this.showSnackbar('Work log created successfully', 'success');
			} catch (error) {
				const message = error.response?.data?.errors
					? Object.values(error.response.data.errors)[0][0]
					: 'Failed to create work log';
				this.showSnackbar(message, 'error');
			}
		},

		async updateWorkLog(workLog) {
			try {
				console.log('Updating work log:', workLog);
				const response = await axios.put(`/api/worklogs/${workLog.id}`, workLog);
				console.log('Update response:', response);
				const index = this.workLogs.findIndex(w => w.id === workLog.id);
				if (index !== -1) {
					this.workLogs.splice(index, 1, response.data);
				}
				this.editDialog = false;
				this.showSnackbar('Work log updated successfully', 'success');
			} catch (error) {
				console.error('Error updating work log:', error);
				const message = error.response?.data?.errors
					? Object.values(error.response.data.errors)[0][0]
					: 'Failed to update work log';
				this.showSnackbar(message, 'error');
			}
		},

		formatDate(dateStr) {
			return new Date(dateStr).toLocaleDateString();
		}
	}
};
</script>
