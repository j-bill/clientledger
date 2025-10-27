<template>
	<v-form ref="form"
			@submit.prevent="submit">
		<v-row>
			<v-col cols="12">
				<v-text-field v-model="formData.name"
							  :label="$t('forms.project.name')"
							  prepend-icon="mdi-briefcase"
							  :rules="[v => !!v || $t('forms.project.nameRequired')]"></v-text-field>
			</v-col>

			<v-col cols="12"
				   md="6">
				<v-select v-model="formData.customer_id"
						  :items="customers"
						  item-title="name"
						  item-value="id"
						  :label="$t('forms.project.customer')"
						  prepend-icon="mdi-account"
						  :rules="[v => !!v || $t('forms.project.customerRequired')]"
						  @update:model-value="updateHourlyRate"></v-select>
			</v-col>

			<v-col cols="12"
				   md="6">
				<v-text-field v-model="formData.hourly_rate"
							  :label="$t('forms.project.hourlyRate')"
							  type="number"
							  prepend-icon="mdi-cash"
							  :hint="$t('forms.project.hourlyRateHint')"
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
									  :label="$t('forms.project.deadline')"
									  prepend-icon="mdi-calendar"
									  readonly
									  :hint="$t('forms.project.deadlineHint')"
									  v-bind="props"
									  clearable
									  @click:clear="clearDate"></v-text-field>
					</template>
					<v-date-picker v-model="pickerDate"
								   locale="en-de"></v-date-picker>
				</v-menu>
			</v-col>

			<v-col cols="12">
				<v-textarea v-model="formData.description"
							:label="$t('forms.project.description')"
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
			pickerDate: null,  // Will be set to a Date object, not a string
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
		this.initializeFormData();
	},

	watch: {
		project: {
			immediate: true,
			handler() {
				this.initializeFormData();
			}
		},
		// Watch pickerDate and sync to formData.deadline as ISO string
		pickerDate(newVal) {
			if (newVal && newVal instanceof Date && !isNaN(newVal.getTime())) {
				const year = newVal.getFullYear();
				const month = String(newVal.getMonth() + 1).padStart(2, '0');
				const day = String(newVal.getDate()).padStart(2, '0');
				const isoDate = `${year}-${month}-${day}`;
				this.formData.deadline = isoDate;
			}
		}
	},

	methods: {
		initializeFormData() {
			if (this.project) {
				this.formData = { 
					...this.project,
					users: this.project.users?.map(user => user.id) || []
				};

				// Initialize the internal date for the date picker with a Date object
				// Vuetify's v-date-picker requires Date objects, not ISO strings
				if (this.formData.deadline) {
					const date = new Date(this.formData.deadline);
					if (!isNaN(date.getTime())) {
						this.pickerDate = date;
					}
				}
			}
		},

		normalizeDate(dateInput) {
			if (!dateInput) return null;
			
			const dateStr = String(dateInput).trim();
			
			// If already in ISO format (YYYY-MM-DD), return as is
			if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
				return dateStr;
			}
			
			// Try to parse it as a Date
			const date = new Date(dateStr);
			if (isNaN(date.getTime())) {
				return null;
			}
			
			// Convert to ISO format
			const year = date.getFullYear();
			const month = (date.getMonth() + 1).toString().padStart(2, '0');
			const day = date.getDate().toString().padStart(2, '0');
			return `${year}-${month}-${day}`;
		},

		convertDateToISO(dateObj) {
			// Convert Date object to ISO string format (YYYY-MM-DD)
			if (dateObj instanceof Date && !isNaN(dateObj.getTime())) {
				const year = dateObj.getFullYear();
				const month = String(dateObj.getMonth() + 1).padStart(2, '0');
				const day = String(dateObj.getDate()).padStart(2, '0');
				return `${year}-${month}-${day}`;
			}
			return null;
		},

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

		clearDate() {
			this.pickerDate = null;
			this.formData.deadline = null;
		}
	}
};
</script>
