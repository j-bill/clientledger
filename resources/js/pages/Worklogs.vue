<template>
	<div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
		<!-- Heading + actions -->
		<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
			<h1 class="text-2xl font-semibold tracking-tight">{{ $t('pages.workLogs.title') }}</h1>
			<div class="flex items-center gap-2">
				<ui-button variant="ghost" :icon="Filter" @click="toggleFilters" />
				<ui-button
					variant="primary"
					:icon="Plus"
					data-test="btn-new-worklog"
					@click="openCreateDialog"
				>
					{{ $t('pages.workLogs.newWorkLog') }}
				</ui-button>
			</div>
		</div>

		<!-- Search -->
		<div class="mb-4 max-w-sm">
			<ui-input
				v-model="filters.search"
				:icon="Search"
				clearable
				:placeholder="$t('common.search')"
				@update:model-value="loadWorkLogs"
			/>
		</div>

		<!-- Filters -->
		<ui-card v-if="showFilters" :title="$t('common.filters')" class="mb-4">
			<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
				<ui-input
					v-model="filters.start_date"
					type="date"
					:label="$t('pages.workLogs.startDate')"
					:icon="Calendar"
				/>

				<ui-input
					v-model="filters.end_date"
					type="date"
					:label="$t('pages.workLogs.endDate')"
					:icon="Calendar"
				/>

				<ui-select
					v-model="filters.project_id"
					:items="projects"
					item-title="name"
					item-value="id"
					:label="$t('pages.workLogs.project')"
					clearable
				/>

				<ui-select
					v-model="filters.user_id"
					:items="users"
					item-title="name"
					item-value="id"
					:label="$t('pages.workLogs.userFreelancer')"
					clearable
				/>

				<ui-select
					v-model="filters.billable"
					:items="billableOptions"
					:label="$t('pages.workLogs.billable')"
					clearable
				/>
			</div>

			<div class="mt-4 flex justify-end gap-2">
				<ui-button variant="ghost" @click="resetFilters">
					{{ $t('common.reset') }}
				</ui-button>
				<ui-button variant="primary" @click="loadWorkLogs">
					{{ $t('common.applyFilters') }}
				</ui-button>
			</div>
		</ui-card>

		<!-- Work Logs Table -->
		<ui-card dense>
			<ui-data-table
				v-model:items-per-page="filters.per_page"
				:headers="headers"
				:items="workLogs"
				:server-items-length="totalItems"
				:sort-by="sortBy"
				@update:options="loadWorkLogs"
			>
				<template v-slot:item.date="{ item }">
					<span class="tnum">{{ formatDate(item.date) }}</span>
				</template>

				<template v-slot:item.user.name="{ item }">
					{{ item.user?.name || $t('common.notAvailable') }}
				</template>

				<template v-slot:item.start_time="{ item }">
					<span class="tnum">{{ formatTime(item.start_time) }}</span>
				</template>

				<template v-slot:item.end_time="{ item }">
					<span class="tnum">{{ item.end_time ? formatTime(item.end_time) : $t('common.notAvailable') }}</span>
				</template>

				<template v-slot:item.hours_worked="{ item }">
					<span class="tnum">{{ formatNumber(item.hours_worked || 0, 2) }}</span>
				</template>

				<template v-slot:item.user_hourly_rate="{ item }">
					<span class="tnum">{{ formatCurrency(item.user_hourly_rate) }}</span>
				</template>

				<template v-slot:item.amount="{ item }">
					<span class="tnum">{{ formatCurrency(item.amount) }}</span>
				</template>

				<template v-slot:item.billable="{ item }">
					<Check v-if="item.billable" class="h-4 w-4 text-sage-400" />
					<X v-else class="h-4 w-4 text-clay-400" />
				</template>

				<template v-slot:item.description="{ item }">
					{{ truncateDescription(item.description) }}
				</template>

				<template v-slot:item.actions="{ item }">
					<div class="flex gap-1">
						<ui-button variant="ghost" size="sm" :icon="Pencil" @click="openEditDialog(item)" />
						<ui-button variant="danger-ghost" size="sm" :icon="Trash2" @click="confirmDelete(item)" />
					</div>
				</template>
			</ui-data-table>
		</ui-card>

		<!-- Delete Confirmation Dialog -->
		<ui-dialog v-model="deleteDialog" :title="$t('pages.workLogs.deleteWorkLog')" max-width="500px" persistent>
			<p class="text-sm text-bone-300">{{ $t('pages.workLogs.deleteConfirmation') }}</p>
			<template #actions>
				<ui-button variant="ghost" @click="deleteDialog = false">{{ $t('common.cancel') }}</ui-button>
				<ui-button variant="danger" @click="deleteWorkLogRecord">{{ $t('common.delete') }}</ui-button>
			</template>
		</ui-dialog>

		<!-- Create Work Log Dialog -->
		<ui-dialog v-model="createDialog" :title="$t('pages.workLogs.newWorkLog')" max-width="1000px" persistent>
			<ui-alert v-if="createClash" type="error" :text="createClash" class="mb-4" />
			<work-log-form ref="createForm"
						   :projects="projects"
						   @save="saveWorkLogRecord"></work-log-form>
			<template #actions>
				<ui-button variant="ghost" @click="createDialog = false">{{ $t('common.cancel') }}</ui-button>
				<ui-button variant="primary" @click="$refs.createForm.submit()">{{ $t('common.save') }}</ui-button>
			</template>
		</ui-dialog>

		<!-- Edit Work Log Dialog -->
		<ui-dialog
			v-model="editDialog"
			:title="currentWorkLog?.wasAutoSaved
				? $t('pages.workLogs.reviewTimeTracking')
				: (currentWorkLog?.id && !currentWorkLog?.end_time
					? $t('pages.workLogs.completeTimeTracking')
					: $t('pages.workLogs.editWorkLog'))"
			max-width="1000px"
			persistent
		>
			<ui-alert v-if="editClash" type="error" :text="editClash" class="mb-4" />
			<ui-alert
				v-if="currentWorkLog?.wasAutoSaved"
				type="success"
				:text="$t('pages.workLogs.autoSavedMessage')"
				class="mb-4"
			/>
			<work-log-form ref="editForm"
						   :work-log="currentWorkLog"
						   :projects="projects"
						   @save="updateWorkLogRecord"></work-log-form>
			<template #actions>
				<ui-button variant="ghost" @click="editDialog = false">{{ $t('common.close') }}</ui-button>
				<ui-button variant="primary" @click="$refs.editForm.submit()">
					<span v-if="currentWorkLog?.wasAutoSaved">{{ $t('pages.workLogs.updateDetails') }}</span>
					<span v-else-if="currentWorkLog?.id && !currentWorkLog?.end_time">{{ $t('pages.workLogs.completeTracking') }}</span>
					<span v-else>{{ $t('common.update') }}</span>
				</ui-button>
			</template>
		</ui-dialog>

	</div>
</template>

<script>
import WorkLogForm from '../components/forms/WorkLogForm.vue';
import eventBus from '../eventBus';
import { mapActions, mapState } from 'pinia';
import { store } from '../store';
import { formatDate, formatTime, formatCurrency, formatNumber } from '../utils/formatters';
import { useI18n } from 'vue-i18n';
import { Plus, Search, Filter, Calendar, Pencil, Trash2, Check, X } from 'lucide-vue-next';

export default {
	name: 'WorkLogsIndex',
	components: {
		WorkLogForm,
		Check,
		X
	},
	setup() {
		const { t } = useI18n();
		return { t, Plus, Search, Filter, Calendar, Pencil, Trash2 };
	},
	data() {
		return {
			workLogs: [],
			loading: false,
			page: 1,
			totalPages: 0,
			deleteDialog: false,
			createDialog: false,
			editDialog: false,
			itemToDelete: null,
			currentWorkLog: null,
			createClash: null,
			editClash: null,
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
	beforeRouteUpdate(to) {
		// Check if query parameters related to tracking completion have changed
		if (to.query.completeTracking && to.query.workLogId) {
			// Call the handler with the new query params
			this.handleCompletingTracking(to.query);
		}
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
				this.editClash = null;
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
			this.createClash = null;
			this.createDialog = true;
		},

		openEditDialog(item) {
			this.editClash = null;
			this.currentWorkLog = { ...item };
			this.editDialog = true;
		},

		async saveWorkLogRecord(workLog) {
			this.createClash = null;
			try {
				await this.createWorkLog(workLog);
				this.createDialog = false;
				this.loadWorkLogs(); // Refresh the list
			} catch (error) {
				// A clashing work log is rejected outright; keep the dialog open
				// and name the entry that is in the way.
				this.createClash = this.clashMessage(error);
				console.error('Error creating work log:', error);
			}
		},

		async updateWorkLogRecord(workLog) {
			this.editClash = null;
			try {
				await this.updateWorkLog(workLog);
				this.editDialog = false;
				this.loadWorkLogs(); // Refresh the list
			} catch (error) {
				this.editClash = this.clashMessage(error);
				console.error('Error updating work log:', error);
			}
		},

		clashMessage(error) {
			return error.response?.data?.conflicting_work_log
				? error.response.data.message
				: null;
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
