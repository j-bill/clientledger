<template>
	<div>
		<!-- Top bar -->
		<header class="sticky top-0 z-30 border-b border-ink-700/60 bg-ink-900/95 backdrop-blur">
			<div class="mx-auto flex h-14 max-w-7xl items-center gap-3 px-4 sm:px-6">
				<!-- Mobile menu toggle -->
				<button
					type="button"
					class="rounded-md p-2 text-bone-500 hover:bg-ink-800 hover:text-bone-300 md:hidden"
					@click="drawer = !drawer"
				>
					<Menu class="h-5 w-5" />
				</button>

				<!-- Wordmark / company logo -->
				<router-link to="/" class="flex shrink-0 items-center gap-2.5">
					<img v-if="companyLogo" :src="companyLogo" alt="Company Logo" class="max-h-8 max-w-[110px] object-contain" />
					<span v-else class="flex h-7 w-7 items-center justify-center rounded-md bg-brass-500 font-mono text-[13px] font-semibold text-ink-950">CL</span>
					<span class="hidden text-[15px] font-semibold tracking-tight text-bone-100 sm:block">
						Client<span class="text-brass-400">ledger</span>
					</span>
				</router-link>

				<!-- Desktop nav -->
				<nav class="ml-4 hidden items-center gap-1 md:flex">
					<router-link
						v-for="link in visibleLinks"
						:key="link.to"
						:to="link.to"
						class="relative rounded-md px-3 py-1.5 text-sm transition-colors"
						:class="isActive(link.to)
							? 'text-brass-400'
							: 'text-bone-500 hover:bg-ink-800 hover:text-bone-300'"
					>
						{{ $t(link.label) }}
						<span v-if="isActive(link.to)" class="absolute inset-x-3 -bottom-[13px] h-0.5 rounded-full bg-brass-400"></span>
					</router-link>
				</nav>

				<div class="ml-auto flex items-center gap-2">
					<!-- Time tracking -->
					<button
						v-if="!activeWorkLog"
						type="button"
						class="flex items-center gap-2 rounded-md border border-ink-700 px-3 py-1.5 text-sm text-sage-400 transition-colors hover:border-sage-500/50 hover:bg-sage-900"
						:disabled="isLoading"
						@click="startTimeTracking"
					>
						<Play class="h-4 w-4" />
						<span class="hidden sm:inline">{{ $t('workLogs.startWorking') }}</span>
					</button>

					<div v-else class="flex items-center gap-2 rounded-md border border-clay-500/40 bg-clay-900 py-1 pl-1.5 pr-3">
						<button
							type="button"
							class="tracking-pulse rounded p-1 text-clay-400 hover:bg-clay-500/20"
							:title="$t('workLogs.stopWorking')"
							@click="stopTimeTracking"
						>
							<Square class="h-4 w-4 fill-current" />
						</button>
						<span class="text-right leading-tight">
							<span class="tnum block text-[13px] font-medium text-bone-100">{{ timeElapsed }}</span>
							<span v-if="formattedEarnings" class="tnum block text-[11px] text-sage-400">{{ formattedEarnings }}</span>
						</span>
					</div>

					<!-- User menu -->
					<ui-menu align="right">
						<template #activator>
							<button type="button" class="rounded-full transition-opacity hover:opacity-80">
								<ui-avatar :name="getUser?.name || ''" :image="userAvatar" :size="34" />
							</button>
						</template>
						<ui-menu-item :icon="User" :title="$t('navigation.profile')" @click="$router.push('/profile')" />
						<ui-menu-item :icon="Settings" :title="$t('navigation.settings')" @click="$router.push('/settings')" />
						<hr class="my-1 border-ink-700/60" />
						<ui-menu-item :icon="LogOut" :title="$t('navigation.logout')" danger @click="logout" />
					</ui-menu>
				</div>
			</div>
		</header>

		<!-- Mobile nav panel -->
		<Transition name="ui-pop">
			<nav v-if="drawer" class="border-b border-ink-700/60 bg-ink-900 px-3 py-2 md:hidden">
				<router-link
					v-for="link in mobileLinks"
					:key="link.to"
					:to="link.to"
					class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm"
					:class="isActive(link.to) ? 'bg-ink-800 text-brass-400' : 'text-bone-300 hover:bg-ink-800'"
					@click="drawer = false"
				>
					<component :is="link.icon" class="h-4 w-4 opacity-70" />
					{{ $t(link.label) }}
				</router-link>
			</nav>
		</Transition>

		<!-- Project Selection Dialog -->
		<ui-dialog v-model="projectDialog" :title="$t('workLogs.startWorking')" max-width="500px" persistent>
			<ui-alert type="info" class="mb-4" :text="$t('workLogs.viewingAssignedProjectsOnly') || 'Showing only projects you are assigned to'" />

			<div class="space-y-4">
				<ui-select
					v-model="selectedCustomer"
					:items="filteredCustomers"
					item-title="name"
					item-value="id"
					:label="$t('customers.customer')"
					@update:model-value="filterProjects"
				/>

				<ui-select
					v-model="selectedProject"
					:items="filteredProjects"
					item-title="name"
					item-value="id"
					:label="$t('projects.project')"
					:rules="[v => !!v || $t('validation.projectRequired')]"
					:disabled="filteredProjects.length === 0"
				/>

				<ui-input v-model="startTime" :label="$t('workLogs.startTime')" type="time" />

				<ui-textarea v-model="workDescription" :label="$t('workLogs.description')" rows="3" />
			</div>

			<template #actions>
				<ui-button variant="ghost" @click="cancelTimeTracking">{{ $t('common.cancel') }}</ui-button>
				<ui-button variant="primary" :disabled="!selectedProject" @click="confirmStartTracking">
					{{ $t('workLogs.startWorking') }}
				</ui-button>
			</template>
		</ui-dialog>
	</div>
</template>

<script>
import { mapActions, mapGetters, mapState } from 'pinia'
import { store } from '../store'
import { formatCurrency } from '../utils/formatters'
import axios from 'axios'
import {
	Menu, Play, Square, User, Settings, LogOut,
	House, Folder, Users, Receipt, Banknote, Clock, UsersRound,
} from 'lucide-vue-next'

export default {
	name: 'NavigationBar',
	components: { Menu, Play, Square },
	data() {
		return {
			drawer: false,
			isLoading: false,
			projectDialog: false,
			selectedCustomer: null,
			selectedProject: null,
			workDescription: '',
			startTime: null,
			activeWorkLog: null,
			trackingStartTime: null,
			timeElapsed: '00:00:00',
			timerInterval: null,
			customers: [],
			projects: [],
			filteredCustomers: [],
			filteredProjects: [],
			hourlyRate: 0,
			secondsElapsed: 0,
			earnings: 0,
			companyLogo: null
		}
	},
	computed: {
		...mapGetters(store, ['isAdmin', 'getUser', 'has2FAEnabled']),
		...mapState(store, ['currencySymbol', 'settings']),
		User: () => User,
		Settings: () => Settings,
		LogOut: () => LogOut,
		formattedEarnings() {
			return this.earnings > 0 ? formatCurrency(this.earnings, this.settings) : '';
		},
		userAvatar() {
			return this.getUser?.avatar || null;
		},
		visibleLinks() {
			return [
				{ to: '/', label: 'navigation.home', icon: House },
				{ to: '/projects', label: 'navigation.projects', icon: Folder },
				...(this.isAdmin ? [
					{ to: '/customers', label: 'navigation.customers', icon: Users },
					{ to: '/invoices', label: 'navigation.invoices', icon: Receipt },
					{ to: '/expenses', label: 'navigation.expenses', icon: Banknote },
				] : []),
				{ to: '/work-logs', label: 'navigation.workLogs', icon: Clock },
			];
		},
		mobileLinks() {
			return [
				...this.visibleLinks,
				...(this.isAdmin ? [{ to: '/users', label: 'navigation.users', icon: UsersRound }] : []),
			];
		},
	},
	created() {
		// Only load data if 2FA is enabled
		if (this.has2FAEnabled) {
			this.checkForActiveWorkLog();
			this.fetchCustomers();
			this.fetchProjects();
		}

		// Load company logo from settings
		this.companyLogo = this.settings?.company_logo || null;
	},
	watch: {
		'settings.company_logo'(newValue) {
			this.companyLogo = newValue;
		}
	},
	beforeUnmount() {
		if (this.timerInterval) {
			clearInterval(this.timerInterval);
		}
	},
	methods: {
		...mapActions(store, ['logout', 'showSnackbar']),

		isActive(path) {
			return path === '/' ? this.$route.path === '/' : this.$route.path.startsWith(path);
		},

		async fetchCustomers() {
			try {
				const response = await axios.get('/api/customers');
				this.customers = response.data;
				// Will be filtered in fetchProjects after we know which projects are assigned
			} catch (error) {
				console.error('Error fetching customers:', error);
				this.showSnackbar('Failed to load customers', 'error');
			}
		},

		async fetchProjects() {
			try {
				const response = await axios.get('/api/projects');
				this.projects = response.data;

				// Filter projects: only show those assigned to the current user (unless admin)
				const currentUser = this.getUser;
				let assignedProjects = this.projects;

				if (currentUser && !this.isAdmin) {
					// Filter projects where current user is assigned
					assignedProjects = this.projects.filter(project => {
						return project.assigned_users &&
						       project.assigned_users.some(user => user.id === currentUser.id);
					});
				}

				this.filteredProjects = [...assignedProjects];

				// Filter customers to only those with assigned projects
				const assignedCustomerIds = new Set(
					assignedProjects.map(p => p.customer_id)
				);
				this.filteredCustomers = this.customers.filter(c =>
					assignedCustomerIds.has(c.id)
				);
			} catch (error) {
				console.error('Error fetching projects:', error);
				this.showSnackbar('Failed to load projects', 'error');
			}
		},

		filterProjects() {
			if (this.selectedCustomer) {
				// Get all assigned projects for the selected customer
				this.filteredProjects = this.projects.filter(
					project => project.customer_id === this.selectedCustomer &&
						       (!this.isAdmin ? (project.assigned_users &&
						        project.assigned_users.some(user => user.id === this.getUser?.id)) : true)
				);
				// Reset selected project if it doesn't belong to the selected customer
				if (this.selectedProject && !this.filteredProjects.some(p => p.id === this.selectedProject)) {
					this.selectedProject = null;
				}
			} else {
				this.filteredProjects = [...this.projects.filter(project =>
					!this.isAdmin ? (project.assigned_users &&
					               project.assigned_users.some(user => user.id === this.getUser?.id)) : true
				)];
			}
		},

		async startTimeTracking() {
			this.isLoading = true;
			try {
				// First check with the server if there's an active worklog
				const response = await axios.get('/api/active-worklog');
				if (response.data && response.data.id) {
					// User already has an active worklog
					this.showSnackbar('You already have an active work log. Please complete it before starting a new one.', 'warning');

					// Update local state with the active worklog
					this.activeWorkLog = response.data;

					// Calculate how long it's been running
					const startTimeParts = this.activeWorkLog.start_time.split(':');
					const startDate = new Date();
					startDate.setHours(parseInt(startTimeParts[0]), parseInt(startTimeParts[1]), 0, 0);

					// If the start time is in the future (of today), assume it started yesterday
					const now = new Date();
					if (startDate > now) {
						startDate.setDate(startDate.getDate() - 1);
					}

					this.trackingStartTime = startDate;
					this.hourlyRate = parseFloat(this.activeWorkLog.hourly_rate || '0');

					// Update localStorage
					localStorage.setItem('activeWorkLog', JSON.stringify(this.activeWorkLog));
					localStorage.setItem('trackingStartTime', this.trackingStartTime.toString());
					localStorage.setItem('hourlyRate', this.hourlyRate.toString());

					this.startElapsedTimer();
					return;
				}

				// No active work log, show dialog to start a new one
				const now = new Date();
				this.startTime = now.toTimeString().slice(0, 5);
				this.projectDialog = true;
			} catch (error) {
				console.error('Error checking for active work logs:', error);
				this.showSnackbar('Failed to check for active work logs', 'error');
			} finally {
				this.isLoading = false;
			}
		},

		cancelTimeTracking() {
			this.projectDialog = false;
			this.selectedProject = null;
			this.workDescription = '';
			this.startTime = null;
		},

		async confirmStartTracking() {
			this.isLoading = true;
			try {
				const now = new Date();
				const currentTime = this.startTime || now.toTimeString().slice(0, 5); // Format: HH:MM

				// Get the project hourly rate
				const selectedProject = this.projects.find(p => p.id === this.selectedProject);
				this.hourlyRate = selectedProject?.hourly_rate || 0;

				const workLog = {
					project_id: this.selectedProject,
					date: now.toISOString().split('T')[0], // Format: YYYY-MM-DD
					start_time: currentTime,
					billable: true,
					description: this.workDescription || 'Work in progress...',
					hourly_rate: this.hourlyRate
				};

				const response = await axios.post('/api/worklogs', workLog);
				this.activeWorkLog = response.data;

				// Calculate tracking start time based on the selected start time
				const startTimeParts = currentTime.split(':');
				const startDate = new Date();
				startDate.setHours(parseInt(startTimeParts[0]), parseInt(startTimeParts[1]), 0, 0);

				// If the start time is in the future (relative to now), assume it started yesterday
				if (startDate > now) {
					startDate.setDate(startDate.getDate() - 1);
				}

				this.trackingStartTime = startDate;

				this.startElapsedTimer();
				this.projectDialog = false;
				this.showSnackbar('Time tracking started', 'success');

				// Store in localStorage to persist across page refreshes
				localStorage.setItem('activeWorkLog', JSON.stringify(this.activeWorkLog));
				localStorage.setItem('trackingStartTime', this.trackingStartTime.toString());
				localStorage.setItem('hourlyRate', this.hourlyRate.toString());
			} catch (error) {
				console.error('Error starting time tracking:', error);
				this.showSnackbar('Failed to start time tracking', 'error');
			} finally {
				this.isLoading = false;
			}
		},

		async stopTimeTracking() {
			if (!this.activeWorkLog) return;

			try {
				clearInterval(this.timerInterval);

				// Get current time in HH:MM format
				const now = new Date();
				const currentTime = now.toTimeString().slice(0, 5);

				// Automatically save the end time to the server
				await axios.post(`/api/worklogs/${this.activeWorkLog.id}/complete`, {
					end_time: currentTime,
					description: this.activeWorkLog.description
				});

				this.playStopSound();

				// Navigate to work logs page and pass the active work log ID
				this.$router.push({
					path: '/work-logs',
					query: {
						completeTracking: true,
						workLogId: this.activeWorkLog.id,
						autoSaved: true // Add flag to indicate the worklog was already saved
					}
				});

				// Clear active work log data
				this.activeWorkLog = null;
				this.trackingStartTime = null;
				this.hourlyRate = 0;
				this.earnings = 0;
				localStorage.removeItem('activeWorkLog');
				localStorage.removeItem('trackingStartTime');
				localStorage.removeItem('hourlyRate');
			} catch (error) {
				console.error('Error stopping time tracking:', error);
				this.showSnackbar('Failed to stop time tracking', 'error');
			}
		},

		playStopSound() {
			const enabled = this.settings?.worklog_sound_enabled;
			if (enabled === '0' || enabled === 'false' || enabled === false) return;

			const sound = this.settings?.worklog_sound || 'cash-register';
			try {
				const audio = new Audio(`/sounds/${sound}.mp3`);
				audio.play().catch(() => {});
			} catch (error) {
				// Sound is best-effort; never block stopping the timer
			}
		},

		checkForActiveWorkLog() {
			try {
				// First try to restore from localStorage
				const savedWorkLog = localStorage.getItem('activeWorkLog');
				const savedStartTime = localStorage.getItem('trackingStartTime');
				const savedHourlyRate = localStorage.getItem('hourlyRate');

				if (savedWorkLog && savedStartTime) {
					this.activeWorkLog = JSON.parse(savedWorkLog);
					this.trackingStartTime = new Date(savedStartTime);
					this.hourlyRate = parseFloat(savedHourlyRate || '0');
					this.startElapsedTimer();
				}

				// Then check with the server for the most accurate data
				this.fetchActiveWorklogFromServer();
			} catch (error) {
				console.error('Error checking for active work log:', error);
				this.showSnackbar('Error loading active work log data', 'error');
				// Clear potentially corrupted data
				localStorage.removeItem('activeWorkLog');
				localStorage.removeItem('trackingStartTime');
				localStorage.removeItem('hourlyRate');
			}
		},

		async fetchActiveWorklogFromServer() {
			try {
				const response = await axios.get('/api/active-worklog');
				if (response.data && response.data.id) {
					// There is an active worklog in the database
					this.activeWorkLog = response.data;

					// Calculate how long it's been running
					const startTimeParts = this.activeWorkLog.start_time.split(':');
					const startDate = new Date();
					startDate.setHours(parseInt(startTimeParts[0]), parseInt(startTimeParts[1]), 0, 0);

					// If the start time is in the future (of today), assume it started yesterday
					const now = new Date();
					if (startDate > now) {
						startDate.setDate(startDate.getDate() - 1);
					}

					this.trackingStartTime = startDate;

					// Get hourly rate from the worklog
					this.hourlyRate = parseFloat(this.activeWorkLog.hourly_rate || '0');

					// Update localStorage to match server data
					localStorage.setItem('activeWorkLog', JSON.stringify(this.activeWorkLog));
					localStorage.setItem('trackingStartTime', this.trackingStartTime.toString());
					localStorage.setItem('hourlyRate', this.hourlyRate.toString());

					// Start the timer if not already running
					if (!this.timerInterval) {
						this.startElapsedTimer();
					}
				} else if (this.activeWorkLog) {
					// No active worklog on server but we have one in localStorage - clear it
					if (this.timerInterval) {
						clearInterval(this.timerInterval);
						this.timerInterval = null;
					}
					this.activeWorkLog = null;
					this.trackingStartTime = null;
					this.hourlyRate = 0;
					this.earnings = 0;
					localStorage.removeItem('activeWorkLog');
					localStorage.removeItem('trackingStartTime');
					localStorage.removeItem('hourlyRate');
				}
			} catch (error) {
				console.error('Error fetching active worklog from server:', error);
			}
		},

		startElapsedTimer() {
			this.timerInterval = setInterval(() => {
				const now = new Date();
				this.secondsElapsed = Math.floor((now - this.trackingStartTime) / 1000);

				const hours = Math.floor(this.secondsElapsed / 3600).toString().padStart(2, '0');
				const minutes = Math.floor((this.secondsElapsed % 3600) / 60).toString().padStart(2, '0');
				const seconds = Math.floor(this.secondsElapsed % 60).toString().padStart(2, '0');

				this.timeElapsed = `${hours}:${minutes}:${seconds}`;

				// Calculate earnings based on hourly rate and elapsed time
				if (this.hourlyRate > 0) {
					// Convert seconds to hours and calculate earnings
					const hoursWorked = this.secondsElapsed / 3600;
					this.earnings = hoursWorked * this.hourlyRate;
				}
			}, 1000);
		}
	},
}
</script>

<style scoped>
.tracking-pulse {
	animation: tracking-pulse 1.6s ease-in-out infinite;
}

@keyframes tracking-pulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.5; }
}
</style>
