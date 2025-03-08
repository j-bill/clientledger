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
							  label="Hourly Rate ($)"
							  type="number"
							  prepend-icon="mdi-currency-usd"
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
					<v-date-picker v-model="dateValue"
								   locale="en-de"
								   @update:model-value="updateDate"></v-date-picker>
				</v-menu>
			</v-col>

			<v-col cols="12">
				<v-textarea v-model="formData.description"
							label="Description"
							prepend-icon="mdi-text"></v-textarea>
			</v-col>
		</v-row>
	</v-form>
</template>

<script>
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
		}
	},

	computed: {
		formattedDate() {
			if (!this.formData.deadline) return '';

			let deadline = new Date(this.formData.deadline);

			const year = deadline.getFullYear();
			const month = deadline.getMonth() + 1;
			const day = deadline.getDate();

			const formattedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

			return formattedDate;
		}
	},

	data() {
		return {
			dateMenu: false,
			dateValue: null,
			formData: {
				name: '',
				customer_id: null,
				hourly_rate: null,
				deadline: null,
				description: ''
			}
		};
	},

	created() {
		if (this.project) {
			this.formData = { ...this.project };

			// convert deadline to Date object
			if (this.formData.deadline) {
				// deadline is stored in yyyy-mm-dd format
				this.formData.deadline = new Date(this.formData.deadline);
			}

			// Initialize the date picker value
			if (this.formData.deadline) {
				this.dateValue = this.formData.deadline;
			}
		}
	},

	methods: {
		async submit() {
			const { valid } = await this.$refs.form.validate();
			
			if (!valid) {
				return;
			}
			
			this.$emit('save', this.formData);
		},

		updateHourlyRate() {
			if (this.formData.customer_id) {
				const selectedCustomer = this.customers.find(c => c.id === this.formData.customer_id);
				if (selectedCustomer && selectedCustomer.hourly_rate) {
					this.formData.hourly_rate = selectedCustomer.hourly_rate;
				}
			}
		},

		updateDate(date) {

			const year = date.getFullYear();
			const month = date.getMonth() + 1;
			const day = date.getDate();

			const formattedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;

			this.formData.deadline = formattedDate;
			this.dateValue = date;

			this.dateMenu = false;
		},

		clearDate() {
			this.formData.deadline = null;
			this.dateValue = null;
		}
	}
};
</script>
