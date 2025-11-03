<template>
	<v-container fluid>
		<h1 class="text-h4 mb-4">{{ $t('pages.workLogs.title') }}</h1>

		<!-- Search & Actions -->
		<v-row class="mb-4">
			<v-col cols="12" sm="6">
				<v-text-field
					v-model="filters.search"
					:label="$t('common.search')"
					prepend-inner-icon="mdi-magnify"
					single-line
					hide-details
					clearable
					@input="loadWorkLogs"
				></v-text-field>
			</v-col>
			<v-col cols="12" sm="6" class="d-flex justify-end">
				<v-btn color="secondary" @click="toggleFilters" class="mr-2">
					<v-icon>mdi-filter</v-icon>
				</v-btn>
				<v-btn color="primary"
					   data-test="btn-new-worklog"
					   @click="openCreateDialog"
					   prepend-icon="mdi-plus">
					{{ $t('pages.workLogs.newWorkLog') }}
				</v-btn>
			</v-col>
		</v-row>

		<!-- Filters -->
		<v-card v-if="showFilters"
				class="mb-4">
			<v-card-title>{{ $t('common.filters') }}</v-card-title>
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
											  :label="$t('pages.workLogs.startDate')"
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
											  :label="$t('pages.workLogs.endDate')"
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
								  :label="$t('pages.workLogs.project')"
								  clearable
								  prepend-icon="mdi-folder"></v-select>
					</v-col>

					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-select v-model="filters.user_id"
								  :items="users"
								  item-title="name"
								  item-value="id"
								  :label="$t('pages.workLogs.userFreelancer')"
								  clearable
								  prepend-icon="mdi-account"></v-select>
					</v-col>

					<v-col cols="12"
						   sm="6"
						   md="3">
						<v-select v-model="filters.billable"
								  :items="billableOptions"
								  :label="$t('pages.workLogs.billable')"
								  clearable
								  prepend-icon="mdi-cash"></v-select>
					</v-col>
				</v-row>

				<v-row>
					<v-col cols="12"
						   class="text-right">
						<v-btn color="primary"
							   @click="loadWorkLogs">
							{{ $t('common.applyFilters') }}
						</v-btn>
						<v-btn class="ms-2"
							   @click="resetFilters">
							{{ $t('common.reset') }}
						</v-btn>
					</v-col>
				</v-row>
			</v-card-text>
		</v-card>

		<!-- Work Logs Table -->
		<v-card>
			<v-data-table-server v-model:items-per-page="filters.per_page"
								 :headers="headers"
								 :items="workLogs"
								 :items-length="totalItems"
								 item-value="name"
								 :sort-by="sortBy"
								 @update:options="loadWorkLogs">

			<template v-slot:item.date="{ item }">
				{{ formatDate(item.date) }}
			</template>

			<template v-slot:item.user="{ item }">
				{{ item.user?.name || $t('common.notAvailable') }}
			</template>
			
			<template v-slot:item.start_time="{ item }">
				{{ formatTime(item.start_time) }}
			</template>
			
			<template v-slot:item.end_time="{ item }">
				{{ item.end_time ? formatTime(item.end_time) : $t('common.notAvailable') }}
			</template>

			<template v-slot:item.hours="{ item }">
				{{ formatNumber(item.hours_worked || 0, 2) }}
			</template>				<template v-slot:item.hourly_rate="{ item }">
					{{ formatCurrency(item.user_hourly_rate) }}
				</template>

				<template v-slot:item.amount="{ item }">
					{{ formatCurrency(item.amount) }}
				</template>

				<template v-slot:item.billable="{ item }">
					<v-icon :color="item.billable ? 'success' : 'error'">
						{{ item.billable ? 'mdi-check' : 'mdi-close' }}
					</v-icon>
				</template>
				<template v-slot:item.description="{ item }">
					{{ truncateDescription(item.description) }}
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

				
			</v-data-table-server>
		</v-card>

		<!-- Delete Confirmation Dialog -->
		<v-dialog v-model="deleteDialog"
				  max-width="500px" persistent>
			<v-card>
				<v-card-title>{{ $t('pages.workLogs.deleteWorkLog') }}</v-card-title>
				<v-card-text>
					{{ $t('pages.workLogs.deleteConfirmation') }}
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="primary"
						   variant="text"
						   @click="deleteDialog = false">{{ $t('common.cancel') }}</v-btn>
					<v-btn color="error"
						   @click="deleteWorkLogRecord">{{ $t('common.delete') }}</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

		<!-- Create Work Log Dialog -->
		<v-dialog v-model="createDialog"
				  max-width="1000px" persistent>
			<v-card>
				<v-card-title>{{ $t('pages.workLogs.newWorkLog') }}</v-card-title>
				<v-card-text>
					<work-log-form ref="createForm"
								   :projects="projects"
								   @save="saveWorkLogRecord"></work-log-form>
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="error"
						   variant="text"
						   @click="createDialog = false">{{ $t('common.cancel') }}</v-btn>
					<v-btn color="primary"
						   @click="$refs.createForm.submit()">{{ $t('common.save') }}</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

		<!-- Edit Work Log Dialog -->
		<v-dialog v-model="editDialog"
				  max-width="1000px" persistent>
			<v-card>
				<v-card-title>
					<span v-if="currentWorkLog?.wasAutoSaved">{{ $t('pages.workLogs.reviewTimeTracking') }}</span>
					<span v-else-if="currentWorkLog?.id && !currentWorkLog?.end_time">{{ $t('pages.workLogs.completeTimeTracking') }}</span>
					<span v-else>{{ $t('pages.workLogs.editWorkLog') }}</span>
				</v-card-title>
				<v-card-text>
					<div v-if="currentWorkLog?.wasAutoSaved" class="mb-4 pa-4 bg-success-lighten-5 rounded">
						<v-icon color="success" class="mr-2">mdi-check-circle</v-icon>
						{{ $t('pages.workLogs.autoSavedMessage') }}
					</div>
					<work-log-form ref="editForm"
								   :work-log="currentWorkLog"
								   :projects="projects"
								   @save="updateWorkLogRecord"></work-log-form>
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="error"
						   variant="text"
						   @click="editDialog = false">{{ $t('common.close') }}</v-btn>
					<v-btn color="primary"
						   @click="$refs.editForm.submit()">
						<span v-if="currentWorkLog?.wasAutoSaved">{{ $t('pages.workLogs.updateDetails') }}</span>
						<span v-else-if="currentWorkLog?.id && !currentWorkLog?.end_time">{{ $t('pages.workLogs.completeTracking') }}</span>
						<span v-else>{{ $t('common.update') }}</span>
					</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

	</v-container>
</template>

<script>
import WorkLogForm from '../components/forms/WorkLogForm.vue';
import eventBus from '../eventBus';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatDate, formatTime, formatCurrency, formatNumber } from '../utils/formatters';
import { useI18n } from 'vue-i18n';

export default {
	name: 'WorkLogsIndex',
	components: {
		WorkLogForm
	},
	setup() {
		const { t } = useI18n();
		return { t };
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
			showFilters: false,
			totalItems: 0,
			
			filters: {
				search: '',
				start_date: null,
				end_date: null,
				project_id: null,
				user_id: null,
				billable: null,
				per_page: 10
			},

		sortBy: [{ key: 'id', order: 'desc' }],
		billableOptions: [
				{ title: 'Yes', value: true },
				{ title: 'No', value: false }
			],
		};
	},

	created() {
		this.loadWorkLogs();
		this.fetchProjects();
		this.fetchUsers();
		this.checkForCompletingTracking();
	},
	
	computed: {
		...mapState(store, ['projects', 'users', 'currencySymbol', 'settings']),
		
		headers() {
			return [
				{ title: this.t('pages.workLogs.id'), key: 'id', sortable: true },
				{ title: this.t('pages.workLogs.date'), key: 'date', sortable: true },
				{ title: this.t('pages.workLogs.project'), key: 'project.name', sortable: true },
				{ title: this.t('pages.workLogs.freelancer'), key: 'user.name', sortable: true },
				{ title: this.t('pages.workLogs.startTime'), key: 'start_time', sortable: true },
				{ title: this.t('pages.workLogs.endTime'), key: 'end_time', sortable: true },
				{ title: this.t('pages.workLogs.hours'), key: 'hours_worked', sortable: true },
				{ title: this.t('pages.workLogs.rate'), key: 'user_hourly_rate', sortable: true },
				{ title: this.t('pages.workLogs.amount'), key: 'amount', sortable: true },
				{ title: this.t('pages.workLogs.billable'), key: 'billable', sortable: true },
				{ title: this.t('pages.workLogs.description'), key: 'description', sortable: true },
				{ title: this.t('common.actions'), key: 'actions', sortable: false }
			];
		}
	},
	
	// Add navigation guard to handle when already on worklogs page
	beforeRouteUpdate(to, from, next) {
		// Check if query parameters related to tracking completion have changed
		if (to.query.completeTracking && to.query.workLogId) {
			// Call the handler with the new query params
			this.handleCompletingTracking(to.query);
		}
		next();
	},
	
	// Add watcher for route query changes
	watch: {
		'$route.query': {
			handler(newQuery) {
				if (newQuery.completeTracking && newQuery.workLogId) {
					this.handleCompletingTracking(newQuery);
				}
			},
			immediate: true
		}
	},
	
	methods: {
		...mapActions(store, [
			'showSnackbar', 
			'showLoading', 
			'hideLoading',
			'fetchProjects',
			'fetchUsers',
			'fetchWorkLogs',
			'getWorkLog',
			'createWorkLog',
			'updateWorkLog',
			'deleteWorkLog'
		]),
		
		toggleFilters() {
			this.showFilters = !this.showFilters;
		},
		
		checkForCompletingTracking() {
			// Initial check during component creation
			const { completeTracking, workLogId, autoSaved } = this.$route.query;
			
			if (completeTracking && workLogId) {
				this.handleCompletingTracking({ completeTracking, workLogId, autoSaved });
			}
		},
		
		handleCompletingTracking(queryParams) {
			const { workLogId, autoSaved } = queryParams;
			
			// Set global loading state to true
			this.showLoading();
			
			// First, fetch the work logs to ensure they're loaded
			this.loadWorkLogs().then(() => {
				// Then fetch the specific work log to edit or view
				this.fetchWorkLogForEditing(workLogId, autoSaved === 'true');
			});
			
			// Clean up query params
			this.$router.replace({
				query: Object.assign({}, this.$route.query, {
					completeTracking: undefined,
					workLogId: undefined,
					autoSaved: undefined
				})
			});
		},
		
		async fetchWorkLogForEditing(workLogId, wasAutoSaved = false) {
			try {
				const workLog = await this.getWorkLog(workLogId);
				
				// If not auto-saved, we need to set the end time
				if (!wasAutoSaved && !workLog.end_time) {
					// Set current time as the default end time
					const now = new Date();
					workLog.end_time = now.toTimeString().slice(0, 5); // Format: HH:MM
				}
				
				// Calculate hours worked if needed
				if (workLog.start_time && workLog.end_time && !workLog.hours_worked) {
					const startParts = workLog.start_time.split(':').map(Number);
					const endParts = workLog.end_time.split(':').map(Number);
					const startMinutes = startParts[0] * 60 + startParts[1];
					const endMinutes = endParts[0] * 60 + endParts[1];
					
					// Handle case where end time is on the next day
					let minutesWorked = endMinutes >= startMinutes ? 
						endMinutes - startMinutes : 
						endMinutes + (24 * 60) - startMinutes;
					
					// Convert to hours with 2 decimal places
					workLog.hours_worked = (minutesWorked / 60).toFixed(2);
				}

				// Ensure all required fields are present
				workLog.project_id = workLog.project?.id || workLog.project_id;
				workLog.date = workLog.date || new Date().toISOString().split('T')[0];
				workLog.billable = workLog.billable ?? true;
				workLog.description = workLog.description || 'Work in progress...';
				workLog.hourly_rate = workLog.hourly_rate || workLog.project?.hourly_rate || 0;
				
				// Store if this worklog was auto-saved
				workLog.wasAutoSaved = wasAutoSaved;
				
				// Open the edit dialog with the work log data
				this.currentWorkLog = workLog;
				this.editDialog = true;
				
			} catch (error) {
				console.error('Error fetching work log for editing:', error);
				this.showSnackbar('Could not load time tracking session for editing', 'error');
			} finally {
				this.hideLoading();
			}
		},
		
	async loadWorkLogs(options = {}) {
		this.showLoading();

		try {
			// Extract sorting from Vuetify table options if provided
			let params = { ...this.filters, page: this.page };
			
			if (options.sortBy && options.sortBy.length > 0) {
				let sortKey = options.sortBy[0].key;
				
				// Map frontend sort keys to backend sort fields
				const sortKeyMap = {
					'project.name': 'project',
					'user.name': 'user',
					'hours_worked': 'hours',
					'user_hourly_rate': 'hourly_rate'
				};
				
				params.sort_by = sortKeyMap[sortKey] || sortKey;
				params.sort_dir = options.sortBy[0].order;
			} else {
				// Default sorting
				params.sort_by = 'date';
				params.sort_dir = 'desc';
			}
			
			if (options.page) {
				params.page = options.page;
			}
			
			if (options.itemsPerPage) {
				params.per_page = options.itemsPerPage;
			}
			
			// Filter out null values
			Object.keys(params).forEach(key => {
				if (params[key] === null) delete params[key];
			});

			const response = await this.fetchWorkLogs(params);
			this.workLogs = response.data;
			this.totalItems = response.total;
			this.totalPages = Math.ceil(response.total / (params.per_page || this.filters.per_page));
            return response; // Return the response for promise chaining
		} catch (error) {
			console.error('Error fetching work logs:', error);
            throw error; // Re-throw for promise chaining
		} finally {
			this.hideLoading();
		}
	},		resetFilters() {
			this.filters = {
				start_date: null,
				end_date: null,
				project_id: null,
				user_id: null,
				billable: null,
				per_page: 10
			};
			this.page = 1;
			this.loadWorkLogs();
		},

		confirmDelete(item) {
			this.itemToDelete = item;
			this.deleteDialog = true;
		},

		async deleteWorkLogRecord() {
			try {
				await this.deleteWorkLog(this.itemToDelete.id);
				this.deleteDialog = false;
				this.loadWorkLogs(); // Refresh the list
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

		async saveWorkLogRecord(workLog) {
			try {
				await this.createWorkLog(workLog);
				this.createDialog = false;
				this.loadWorkLogs(); // Refresh the list
			} catch (error) {
				console.error('Error creating work log:', error);
			}
		},

		async updateWorkLogRecord(workLog) {
			try {
				await this.updateWorkLog(workLog);
				this.editDialog = false;
				this.loadWorkLogs(); // Refresh the list
			} catch (error) {
				console.error('Error updating work log:', error);
			}
		},

		formatDate(dateStr) {
			return formatDate(dateStr, this.settings);
		},
		
		formatTime(timeStr) {
			return formatTime(timeStr);
		},
		
		formatCurrency(amount) {
			return formatCurrency(amount, this.settings);
		},
		
		formatNumber(value, decimals = 2) {
			return formatNumber(value, decimals, this.settings);
		},
		
		truncateDescription(description, maxWords = 8) {
			if (!description) return '';
			const words = description.trim().split(/\s+/);
			if (words.length > maxWords) {
				return words.slice(0, maxWords).join(' ') + '...';
			}
			return description;
		}
	}
};
</script>

<style scoped>
</style>
