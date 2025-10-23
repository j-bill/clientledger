<template>
	<v-form ref="form"
			@submit.prevent="submit">
		<v-row>
			<v-col cols="12">
				<v-text-field v-model="formData.name"
							  label="Project Name"
							  prepend-icon="mdi-briefcase"
							  :rules="[v => !!v || 'Project name is required']"></v-text-field>
			</v-col>

			<v-col cols="12"
				   md="6">
				<v-select v-model="formData.customer_id"
						  :items="customers"
						  item-title="name"
						  item-value="id"
						  label="Customer"
						  prepend-icon="mdi-account"
						  :rules="[v => !!v || 'Customer is required']"
						  @update:model-value="updateHourlyRate"></v-select>
			</v-col>

			<v-col cols="12"
				   md="6">
				<v-text-field v-model="formData.hourly_rate"
							  label="Project Hourly Rate"
							  type="number"
							  prepend-icon="mdi-cash"
							  hint="Inherited from customer by default, but can be customized"
							  persistent-hint></v-text-field>
			</v-col>

			<v-col cols="12"
				   md="6">
				<v-menu v-model="dateMenu"
						:close-on-content-click="false"
						transition="scale-transition"
						min-width="auto">
					<template v-slot:activator="{ props }">
						<v-text-field :model-value="formattedDate"
									  label="Deadline"
									  prepend-icon="mdi-calendar"
									  readonly
									  hint="Leave empty if no deadline"
									  v-bind="props"
									  clearable
									  @click:clear="clearDate"></v-text-field>
					</template>
					<v-date-picker v-model="internalDeadline"
								   locale="en-de"
								   @update:model-value="updateDeadline"></v-date-picker>
				</v-menu>
			</v-col>

			<v-col cols="12">
				<v-textarea v-model="formData.description"
							label="Description"
							prepend-icon="mdi-text"></v-textarea>
			</v-col>

		<v-col cols="12">
			<v-select v-model="formData.users"
					  :items="freelancers"
					  item-title="name"
					  item-value="id"
					  label="Assigned Users"
					  prepend-icon="mdi-account-group"
					  multiple
					  chips
					  :rules="[v => v.length > 0 || 'At least one user is required']"
					  @update:model-value="updateUserRates">
				<template v-slot:item="{ props, item }">
					<v-list-item v-bind="props">
						<v-list-item-subtitle>Rate: ${{ item.raw.hourly_rate }}/hr</v-list-item-subtitle>
					</v-list-item>
				</template>
			</v-select>
		</v-col>
		</v-row>
	</v-form>
</template>

<script>
import { formatDate } from '../../utils/formatters';
import { mapState } from 'pinia';
import { store } from '../../store';

export default {
	name: 'ProjectForm',
	props: {
		project: {
			type: Object,
			default: null
		},
		customers: {
			type: Array,
			required: true
		},
		freelancers: {
			type: Array,
			required: true
		}
	},

	computed: {
		...mapState(store, ['settings']),
		
		formattedDate() {
			if (!this.formData.deadline) return '';

			// Display the deadline in the user's preferred format
			return formatDate(this.formData.deadline, this.settings);
		}
	},

	data() {
		return {
			dateMenu: false,
			internalDeadline: null,
			formData: {
				name: '',
				customer_id: null,
				hourly_rate: null,
				deadline: null,
				description: '',
				users: []
			}
		};
	},

	created() {
		if (this.project) {
			this.formData = { 
				...this.project,
				users: this.project.users?.map(user => user.id) || []
			};

			// Store deadline in ISO format (yyyy-mm-dd)
			if (this.formData.deadline) {
				this.internalDeadline = this.formData.deadline;
			}
		}
	},

	methods: {
		async submit() {
			const { valid } = await this.$refs.form.validate();
			
			if (!valid) {
				return;
			}
			
			// Format the data before emitting
			const formattedData = {
				...this.formData,
				users: this.formData.users.map(userId => {
					const user = this.freelancers.find(f => f.id === userId);
					return {
						id: userId,
						hourly_rate: user.hourly_rate
					};
				})
			};

			this.$emit('save', formattedData);
		},

		updateHourlyRate() {
			if (this.formData.customer_id) {
				const selectedCustomer = this.customers.find(c => c.id === this.formData.customer_id);
				if (selectedCustomer && selectedCustomer.hourly_rate) {
					this.formData.hourly_rate = selectedCustomer.hourly_rate;
				}
			}
		},

		updateUserRates(selectedUserIds) {
			// This method can be used to handle any user-specific rate updates if needed
		},

		updateDate(date) {
			// Ensure we're storing the ISO format string (yyyy-mm-dd)
			if (date instanceof Date) {
				const year = date.getFullYear();
				const month = (date.getMonth() + 1).toString().padStart(2, '0');
				const day = date.getDate().toString().padStart(2, '0');
				this.formData.deadline = `${year}-${month}-${day}`;
			} else if (typeof date === 'string') {
				// Already a string, store as is
				this.formData.deadline = date;
			}
			this.internalDeadline = date;
			this.dateMenu = false;
		},

		clearDate() {
			this.formData.deadline = null;
			this.internalDeadline = null;
		}
	}
};
</script>
