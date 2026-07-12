<template>
	<ui-form ref="form" @submit="submit">
		<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
			<div class="md:col-span-2">
				<ui-input
					v-model="formData.name"
					:label="$t('forms.project.name')"
					:icon="Briefcase"
					:rules="[v => !!v || $t('forms.project.nameRequired')]"
				/>
			</div>

			<ui-select
				v-model="formData.customer_id"
				:items="customers"
				item-title="name"
				item-value="id"
				:label="$t('forms.project.customer')"
				:rules="[v => !!v || $t('forms.project.customerRequired')]"
				@update:model-value="updateHourlyRate"
			/>

			<ui-input
				v-model="formData.hourly_rate"
				:label="$t('forms.project.hourlyRate')"
				type="number"
				:icon="Banknote"
				:hint="$t('forms.project.hourlyRateHint')"
			/>

			<ui-input
				v-model="formData.deadline"
				:label="$t('forms.project.deadline')"
				type="date"
				:icon="Calendar"
				:hint="$t('forms.project.deadlineHint')"
				clearable
			/>

			<div class="md:col-span-2">
				<ui-textarea
					v-model="formData.description"
					:label="$t('forms.project.description')"
				/>
			</div>

			<div class="md:col-span-2">
				<ui-autocomplete
					ref="usersField"
					:model-value="null"
					:items="availableFreelancers"
					item-title="display"
					item-value="id"
					:label="$t('forms.project.assignedUsers')"
					:rules="[() => formData.users.length > 0 || $t('forms.project.assignedUsersRequired')]"
					@update:model-value="addUser"
				/>
				<div v-if="formData.users.length" class="mt-2 flex flex-wrap gap-1.5">
					<ui-chip v-for="userId in formData.users" :key="userId" color="brass">
						{{ userName(userId) }}
						<button type="button" class="-mr-0.5 rounded-full hover:text-bone-100" @click="removeUser(userId)">
							<X class="h-3 w-3" />
						</button>
					</ui-chip>
				</div>
			</div>
		</div>
	</ui-form>
</template>

<script>
import { formatDate } from '../../utils/formatters';
import { mapState } from 'pinia';
import { store } from '../../store';
import { Briefcase, Banknote, Calendar, X } from 'lucide-vue-next';

export default {
	name: 'ProjectForm',
	components: { X },
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

	setup() {
		return { Briefcase, Banknote, Calendar };
	},

	computed: {
		...mapState(store, ['settings']),

		formattedDate() {
			if (!this.formData.deadline) return '';

			// Display the deadline in the user's preferred format
			return formatDate(this.formData.deadline, this.settings);
		},

		// Freelancers not yet assigned, with their rate in the option label
		// (replaces the old v-select item subtitle)
		availableFreelancers() {
			return this.freelancers
				.filter(f => !this.formData.users.includes(f.id))
				.map(f => ({
					...f,
					display: `${f.name} — $${f.hourly_rate}/${this.$t('forms.project.hourlyRateUnit')}`
				}));
		}
	},

	data() {
		return {
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
		}
	},

	methods: {
		initializeFormData() {
			if (this.project) {
				this.formData = {
					...this.project,
					users: this.project.users?.map(user => user.id) || []
				};

				// Native date input needs a YYYY-MM-DD string
				this.formData.deadline = this.normalizeDate(this.formData.deadline);
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

		userName(userId) {
			return this.freelancers.find(f => f.id === userId)?.name ?? userId;
		},

		addUser(userId) {
			if (userId == null || this.formData.users.includes(userId)) return;
			this.formData.users.push(userId);
			this.$refs.usersField?.validate();
			this.updateUserRates(this.formData.users);
		},

		removeUser(userId) {
			this.formData.users = this.formData.users.filter(id => id !== userId);
			this.$refs.usersField?.validate();
			this.updateUserRates(this.formData.users);
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
		}
	}
};
</script>
