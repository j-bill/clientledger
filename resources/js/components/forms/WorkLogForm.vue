<template>
	<v-form ref="form"
			@submit.prevent="submit">
		<v-row>
			<v-col cols="12"
				   md="6">
				<v-select v-model="selectedCustomer"
						  :items="customers"
						  item-title="name"
						  item-value="id"
						  label="Customer"
						  prepend-icon="mdi-account"
						  clearable
						  @update:model-value="filterProjects"></v-select>
			</v-col>

			<v-col cols="12"
				   md="6">
				<v-select v-model="formData.project_id"
						  :items="filteredProjects"
						  item-title="name"
						  item-value="id"
						  label="Project"
						  prepend-icon="mdi-folder"
						  :rules="[v => !!v || 'Project is required']"
						  @update:model-value="updateProjectDetails"></v-select>
			</v-col>

			<v-col cols="12"
				   md="6" v-if="isAdmin">
				<v-select v-model="formData.user_id"
						  :items="projectUsers"
						  item-title="name"
						  item-value="id"
						  label="Freelancer"
						  prepend-icon="mdi-account"
						  :rules="[v => !!v || 'Freelancer is required']"
						  @update:model-value="updateUserRate"></v-select>
			</v-col>

			<v-col cols="12"
				   md="4">
				<v-menu v-model="dateMenu"
						:close-on-content-click="false"
						transition="scale-transition"
						offset-y
						min-width="auto">
					<template v-slot:activator="{ props }">
						<v-text-field :model-value="formattedDate"
									  label="Date"
									  prepend-icon="mdi-calendar"
									  readonly
									  :rules="[v => !!v || 'Date is required']"
									  v-bind="props"></v-text-field>
					</template>
					<v-date-picker v-model="internalDate"
								   @update:model-value="updateDate"></v-date-picker>
				</v-menu>
			</v-col>

			<v-col cols="12"
				   md="4">
				<v-menu v-model="startTimeMenu"
						:close-on-content-click="false"
						transition="scale-transition"
						offset-y
						min-width="auto">
					<template v-slot:activator="{ props }">
						<v-text-field v-model="formData.start_time"
									  label="Start Time"
									  prepend-icon="mdi-clock-start"
									  readonly
									  :rules="[v => !!v || 'Start time is required']"
									  v-bind="props"></v-text-field>
					</template>
					<v-time-picker v-model="formData.start_time"
								   format="24hr"
								   @click:minute="startTimeMenu = false"></v-time-picker>
				</v-menu>
			</v-col>

			<v-col cols="12"
				   md="4">
				<v-menu v-model="endTimeMenu"
						:close-on-content-click="false"
						transition="scale-transition"
						offset-y
						min-width="auto">
					<template v-slot:activator="{ props }">
						<v-text-field v-model="formData.end_time"
									  label="End Time"
									  prepend-icon="mdi-clock-end"
									  readonly
									  :rules="[v => !!v || 'End time is required']"
									  v-bind="props"></v-text-field>
					</template>
					<v-time-picker v-model="formData.end_time"
								   format="24hr"
								   @click:minute="endTimeMenu = false"></v-time-picker>
				</v-menu>
			</v-col>

			<v-col cols="12"
				   md="6" v-if="isAdmin">
				<v-text-field v-model="formData.hourly_rate"
							  label="Freelancer Hourly Rate"
							  type="number"
							  prepend-icon="mdi-cash"
							  :disabled="isNewWorkLog && formData.user_id"
							  hint="Rate is inherited from freelancer for new work logs"></v-text-field>
			</v-col>

			<v-col cols="12">
				<v-textarea v-model="formData.description"
							label="Description"
							prepend-icon="mdi-text"
							:rules="[v => !!v || 'Description is required']"
							counter
							:maxlength="500"
							rows="4"
							auto-grow></v-textarea>
			</v-col>

			<v-col cols="12">
				<v-checkbox v-model="formData.billable"
							:true-value="1"
							:false-value="0"
							label="Billable"
							color="primary"></v-checkbox>
			</v-col>
		</v-row>
	</v-form>
</template>

<script>
import { mapState } from 'pinia'
import { store } from '../../store'
import { formatDate } from '../../utils/formatters';
import axios from 'axios'

export default {
	name: 'WorkLogForm',
	props: {
		workLog: {
			type: Object,
			default: null
		},
		projects: {
			type: Array,
			required: true
		}
	},

	data() {
		return {
			dateMenu: false,
			startTimeMenu: false,
			endTimeMenu: false,
			internalDate: null,
			formData: {
				date: new Date().toISOString().substr(0, 10),
				project_id: null,
				user_id: null,
				start_time: null,
				end_time: null,
				billable: 1,
				description: '',
				hourly_rate: null
			},
			selectedCustomer: null,
			customers: [],
			filteredProjects: [],
			projectUsers: [],
			isNewWorkLog: true,
			isAdmin: false
		};
	},

	computed: {
		...mapState(store, ['user', 'settings']),
		
		formattedDate() {
			if (!this.formData.date) return '';
			// Display the date in the user's preferred format
			return formatDate(this.formData.date, this.settings);
		}
	},

	created() {
		this.isNewWorkLog = !this.workLog;
		this.checkUserRole();

		if (this.workLog) {
			this.formData = { ...this.workLog };
			if (this.workLog.project && this.workLog.project.customer_id) {
				this.selectedCustomer = this.workLog.project.customer_id;
			}
			// Initialize the internal date for the date picker with a Date object
			if (this.workLog.date) {
				this.internalDate = new Date(this.workLog.date);
			}
		} else {
			// For new work logs, set the user_id to the authenticated user
			this.formData.user_id = this.user?.id;
			// Initialize internal date to today for new work logs
			this.internalDate = new Date();
		}
		this.fetchCustomers();
		this.filteredProjects = [...this.projects];
		
		// If editing a work log with a project, populate the project users
		if (this.workLog && this.workLog.project_id) {
			this.updateProjectDetails();
		}
	},

	methods: {
		async checkUserRole() {
			try {
				const response = await axios.get('/api/user');
				this.isAdmin = response.data.role === 'admin';
			} catch (error) {
				console.error('Error checking user role:', error);
			}
		},

		async fetchCustomers() {
			try {
				const response = await axios.get('/api/customers');
				this.customers = response.data;
			} catch (error) {
				console.error('Error fetching customers:', error);
			}
		},

		filterProjects() {
			if (this.selectedCustomer) {
				this.filteredProjects = this.projects.filter(
					project => project.customer_id === this.selectedCustomer
				);
				if (this.formData.project_id && !this.filteredProjects.some(p => p.id === this.formData.project_id)) {
					this.formData.project_id = null;
					this.formData.hourly_rate = null;
				}
			} else {
				this.filteredProjects = [...this.projects];
			}
		},

		updateProjectDetails() {
			if (this.formData.project_id) {
				const selectedProject = this.projects.find(p => p.id === this.formData.project_id);
				if (selectedProject) {
					this.projectUsers = selectedProject.users || [];
					if (this.formData.user_id && !this.projectUsers.some(u => u.id === this.formData.user_id)) {
						this.formData.user_id = null;
						this.formData.hourly_rate = null;
					}
				}
			} else {
				this.projectUsers = [];
				this.formData.user_id = null;
				this.formData.hourly_rate = null;
			}
		},

		updateUserRate() {
			if (this.isNewWorkLog && this.formData.user_id) {
				const selectedUser = this.projectUsers.find(u => u.id === this.formData.user_id);
				if (selectedUser && selectedUser.hourly_rate) {
					this.formData.hourly_rate = selectedUser.hourly_rate;
				}
			}
		},

		updateDate(date) {
			// Convert Date object to ISO string format (YYYY-MM-DD)
			if (date instanceof Date) {
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, '0');
				const day = String(date.getDate()).padStart(2, '0');
				this.formData.date = `${year}-${month}-${day}`;
			} else if (typeof date === 'string') {
				// Already a string, store as is
				this.formData.date = date;
			}
			this.dateMenu = false;
		},

		async submit() {
			const { valid } = await this.$refs.form.validate();

			if (!valid) {
				return;
			}

			// Format the data before emitting
			const formattedData = {
				...this.formData,
				// Ensure hourly_rate is a number
				hourly_rate: parseFloat(this.formData.hourly_rate) || 0,
				// Ensure billable is a number
				billable: parseInt(this.formData.billable) || 0
			};

			this.$emit('save', formattedData);
		}
	}
};
</script>
