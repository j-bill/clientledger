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
						  @update:model-value="updateHourlyRate"></v-select>
			</v-col>

			<v-col cols="12"
				   md="4">
				<v-menu v-model="dateMenu"
						:close-on-content-click="false"
						transition="scale-transition"
						offset-y
						min-width="auto">
					<template v-slot:activator="{ props }">
						<v-text-field v-model="formData.date"
									  label="Date"
									  prepend-icon="mdi-calendar"
									  readonly
									  :rules="[v => !!v || 'Date is required']"
									  v-bind="props"></v-text-field>
					</template>
					<v-date-picker v-model="formData.date"
								   @change="dateMenu = false"></v-date-picker>
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
				   md="6">
				<v-text-field v-model="formData.hourly_rate"
							  label="Hourly Rate ($)"
							  type="number"
							  prepend-icon="mdi-currency-usd"
							  :disabled="isNewWorkLog && formData.project_id"
							  hint="Rate is inherited from project for new work logs"></v-text-field>
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
import axios from 'axios';

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
			formData: {
				date: new Date().toISOString().substr(0, 10),
				project_id: null,
				start_time: null,
				end_time: null,
				billable: true,
				description: '',
				hourly_rate: null
			},
			selectedCustomer: null,
			customers: [],
			filteredProjects: [],
			isNewWorkLog: true
		};
	},

	created() {
		this.isNewWorkLog = !this.workLog;

		if (this.workLog) {
			this.formData = { ...this.workLog };
			// If editing, try to set the customer based on the project
			if (this.workLog.project && this.workLog.project.customer_id) {
				this.selectedCustomer = this.workLog.project.customer_id;
			}
		}
		this.fetchCustomers();
		this.filteredProjects = [...this.projects];
	},

	methods: {
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
				// If the currently selected project doesn't belong to this customer, reset it
				if (this.formData.project_id && !this.filteredProjects.some(p => p.id === this.formData.project_id)) {
					this.formData.project_id = null;
					this.formData.hourly_rate = null;
				}
			} else {
				this.filteredProjects = [...this.projects];
			}
		},

		updateHourlyRate() {
			// Only update hourly_rate automatically for new work logs
			if (this.isNewWorkLog && this.formData.project_id) {
				const selectedProject = this.projects.find(p => p.id === this.formData.project_id);
				if (selectedProject && selectedProject.hourly_rate) {
					this.formData.hourly_rate = selectedProject.hourly_rate;
				}
			}
		},

		async submit() {
			const { valid } = await this.$refs.form.validate();

			if (!valid) {
				return;
			}

			this.$emit('save', this.formData);
		}
	}
};
</script>
