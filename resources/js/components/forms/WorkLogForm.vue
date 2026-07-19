<template>
	<ui-form ref="form" @submit="submit">
		<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
			<ui-select v-model="selectedCustomer"
					   :items="customers"
					   item-title="name"
					   item-value="id"
					   :label="$t('forms.workLog.customer')"
					   clearable
					   @update:model-value="filterProjects" />

			<ui-select v-model="formData.project_id"
					   :items="filteredProjects"
					   item-title="name"
					   item-value="id"
					   :label="$t('forms.workLog.project')"
					   :rules="[v => !!v || $t('forms.workLog.projectRequired')]"
					   @update:model-value="updateProjectDetails" />

			<ui-select v-if="isAdmin"
					   v-model="formData.user_id"
					   :items="projectUsers"
					   item-title="name"
					   item-value="id"
					   :label="$t('forms.workLog.freelancer')"
					   :rules="[v => !!v || $t('forms.workLog.freelancerRequired')]"
					   @update:model-value="updateUserRate" />
		</div>

		<div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
			<ui-input v-model="formData.date"
					  type="date"
					  :label="$t('forms.workLog.date')"
					  :icon="Calendar"
					  :rules="[v => !!v || $t('forms.workLog.dateRequired')]" />

			<ui-input v-model="formData.start_time"
					  type="time"
					  :label="$t('forms.workLog.startTime')"
					  :icon="Clock"
					  :rules="[v => !!v || $t('forms.workLog.startTimeRequired')]" />

			<ui-input v-model="formData.end_time"
					  type="time"
					  :label="$t('forms.workLog.endTime')"
					  :icon="Clock"
					  :rules="[v => !!v || $t('forms.workLog.endTimeRequired')]" />
		</div>

		<div v-if="isAdmin" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
			<ui-input v-model="formData.hourly_rate"
					  :label="$t('forms.workLog.hourlyRate')"
					  type="number"
					  :icon="Banknote"
					  :disabled="!!(isNewWorkLog && formData.user_id)"
					  :hint="$t('forms.workLog.hourlyRateHint')" />
		</div>

		<div class="mt-4">
			<ui-textarea v-model="formData.description"
						 :label="$t('forms.workLog.description')"
						 :rules="[v => !!v || $t('forms.workLog.descriptionRequired')]"
						 :maxlength="1500"
						 rows="4" />
			<p class="mt-1 text-right font-mono text-xs text-bone-700 tnum">
				{{ (formData.description || '').length }} / 1500
			</p>
			<ui-button v-if="aiEnabled"
					   class="mt-1"
					   variant="brass-ghost"
					   size="sm"
					   :icon="Sparkles"
					   :loading="generatingAi"
					   :disabled="!formData.description || !!aiSuggestion"
					   @click="generateDescription">
				{{ $t('forms.workLog.generateWithAi') }}
			</ui-button>

			<div v-if="aiSuggestion" class="mt-3 rounded border border-brass-500/40 bg-ink-800/60">
				<p class="ledger-rule px-3 py-2 text-xs font-semibold uppercase tracking-wide text-brass-400">
					{{ $t('forms.workLog.aiSuggestionTitle') }}
				</p>
				<p class="whitespace-pre-wrap p-3 text-sm leading-relaxed">
					<template v-for="(part, i) in aiDiff" :key="i">
						<del v-if="part.removed" class="rounded-sm bg-clay-900 px-0.5 text-clay-400">{{ part.value }}</del>
						<ins v-else-if="part.added" class="rounded-sm bg-sage-900 px-0.5 text-sage-400 no-underline">{{ part.value }}</ins>
						<span v-else class="text-bone-300">{{ part.value }}</span>
					</template>
				</p>
				<p v-if="aiFeedback" class="border-t border-ink-700/60 px-3 py-2 text-xs text-bone-700">
					{{ aiFeedback }}
				</p>
				<div class="flex items-center justify-end gap-2 border-t border-ink-700/60 px-3 py-2">
					<ui-button variant="ghost" size="sm" @click="discardAiSuggestion">
						{{ $t('forms.workLog.aiDiscard') }}
					</ui-button>
					<ui-button variant="primary" size="sm" @click="applyAiSuggestion">
						{{ $t('forms.workLog.aiApply') }}
					</ui-button>
				</div>
			</div>
		</div>

		<div class="mt-4">
			<ui-checkbox :model-value="!!formData.billable"
						 :label="$t('forms.workLog.billable')"
						 @update:model-value="formData.billable = $event ? 1 : 0" />
		</div>

	</ui-form>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { store } from '../../store'
import { formatDate } from '../../utils/formatters';
import axios from 'axios'
import { diffWords } from 'diff';
import { Calendar, Clock, Banknote, Sparkles } from 'lucide-vue-next';

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

	setup() {
		return { Calendar, Clock, Banknote, Sparkles };
	},

	data() {
		return {
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
			isAdmin: false,
			aiEnabled: false,
			generatingAi: false,
			aiOriginal: '',
			aiSuggestion: '',
			aiFeedback: ''
		};
	},

	computed: {
		...mapState(store, ['user', 'settings']),

		formattedDate() {
			if (!this.formData.date) return '';
			// Display the date in the user's preferred format
			return formatDate(this.formData.date, this.settings);
		},

		aiDiff() {
			return diffWords(this.aiOriginal, this.aiSuggestion);
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
			// Native date input needs a plain YYYY-MM-DD string
			// (previously a Date object was fed into v-date-picker)
			if (this.formData.date) {
				this.formData.date = String(this.formData.date).slice(0, 10);
			}
		} else {
			// For new work logs, set the user_id to the authenticated user
			this.formData.user_id = this.user?.id;
		}
		this.fetchCustomers();
		this.checkAiEnabled();
		this.filteredProjects = [...this.projects];

		// If editing a work log with a project, populate the project users
		if (this.workLog && this.workLog.project_id) {
			this.updateProjectDetails();
		}
	},

	methods: {
		...mapActions(store, ['showSnackbar']),

		async checkAiEnabled() {
			try {
				const response = await axios.get('/api/settings/public');
				this.aiEnabled = response.data.ai_worklog_enabled === '1';
			} catch (error) {
				console.error('Error checking AI settings:', error);
			}
		},

		async generateDescription() {
			if (!this.formData.description) return;

			try {
				this.generatingAi = true;
				const response = await axios.post('/api/worklogs/generate-description', {
					notes: this.formData.description,
					project_id: this.formData.project_id
				});
				this.aiOriginal = this.formData.description;
				this.aiSuggestion = response.data.description;
				this.aiFeedback = response.data.feedback || '';
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || this.$t('forms.workLog.aiGenerationFailed'), 'error');
			} finally {
				this.generatingAi = false;
			}
		},

		applyAiSuggestion() {
			this.formData.description = this.aiSuggestion;
			this.discardAiSuggestion();
		},

		discardAiSuggestion() {
			this.aiOriginal = '';
			this.aiSuggestion = '';
			this.aiFeedback = '';
		},

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
