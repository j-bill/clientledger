<template>
	<div>
		<!-- App Bar -->
		<v-app-bar app
				   color="primary"
				   dark>
			<v-app-bar-nav-icon @click="drawer = !drawer"></v-app-bar-nav-icon>
			<v-toolbar-title>
				<h4>Clientledger</h4>
			</v-toolbar-title>
			<v-spacer />

			<!-- Desktop Navigation Links -->
			<div class="d-none d-md-flex">
				<v-btn to="/"
					   text>Home</v-btn>
				<v-btn to="/projects"
					   text>Projects</v-btn>
				<v-btn to="/customers"
					   text>Customers</v-btn>
				<v-btn to="/invoices"
					   text>Invoices</v-btn>
				<v-btn to="/work-logs"
					   text>Work Logs</v-btn>
			</div>

			<!-- Time Tracking Button -->
			<v-btn v-if="!activeWorkLog" 
				   icon 
				   class="mx-2"
				   color="success"
				   @click="startTimeTracking"
				   :loading="isLoading">
				<v-tooltip activator="parent" location="bottom">
					Start Working Now
				</v-tooltip>
				<v-icon>mdi-play-circle</v-icon>
			</v-btn>

			<div v-else class="d-flex align-center">
				<v-btn icon
					   class="mx-2 pulse-animation"
					   color="error"
					   @click="stopTimeTracking">
					<v-tooltip activator="parent" location="bottom">
						Stop Working
					</v-tooltip>
					<v-icon>mdi-stop-circle</v-icon>
				</v-btn>
				
				<div class="time-tracking-info px-2">
					<div class="time-display font-weight-medium">{{ timeElapsed }}</div>
					<div class="earnings-display text-caption">
						{{ formattedEarnings }}
					</div>
				</div>
			</div>

			<!-- User Menu -->
			<v-menu offset-y>
				<template v-slot:activator="{ props }">
					<v-btn icon
						   v-bind="props">
						<v-icon>mdi-account-circle</v-icon>
					</v-btn>
				</template>
				<v-list>
					<v-list-item to="/profile">
						<v-list-item-title>Profile</v-list-item-title>
					</v-list-item>
					<v-list-item to="/settings">
						<v-list-item-title>Settings</v-list-item-title>
					</v-list-item>
					<v-list-item @click="logout">
						<v-list-item-title>Logout</v-list-item-title>
					</v-list-item>
				</v-list>
			</v-menu>
		</v-app-bar>

		<!-- Navigation Drawer -->
		<v-navigation-drawer v-model="drawer"
							 temporary
							 app>
			<v-list class="py-0">
				<!-- <v-list-item title="Freelancer App" prepend-icon="mdi-view-dashboard" to="/"></v-list-item> -->
				<v-divider></v-divider>
				<v-list-item prepend-icon="mdi-home"
							 title="Home"
							 to="/"></v-list-item>
				<v-list-item prepend-icon="mdi-folder"
							 title="Projects"
							 to="/projects"></v-list-item>
				<v-list-item prepend-icon="mdi-account-multiple"
							 title="Customers"
							 to="/customers"></v-list-item>
				<v-list-item prepend-icon="mdi-receipt"
							 title="Invoices"
							 to="/invoices"></v-list-item>
				<v-list-item prepend-icon="mdi-account-group"
							 title="Users"
							 to="/users"></v-list-item>
				<v-list-item prepend-icon="mdi-clock"
							 title="Work Logs"
							 to="/work-logs"></v-list-item>
				<v-divider></v-divider>
				<!-- <v-list-item prepend-icon="mdi-cog" title="Settings" to="/settings"></v-list-item> -->
			</v-list>
		</v-navigation-drawer>

		<!-- Project Selection Dialog -->
		<v-dialog v-model="projectDialog" max-width="500px" persistent>
			<v-card>
				<v-card-title>Select Project</v-card-title>
				<v-card-text>
					<v-select v-model="selectedCustomer"
							  :items="customers"
							  item-title="name"
							  item-value="id"
							  label="Customer"
							  prepend-icon="mdi-account"
							  @update:model-value="filterProjects"></v-select>
					
					<v-select v-model="selectedProject"
							  :items="filteredProjects"
							  item-title="name"
							  item-value="id"
							  label="Project"
							  prepend-icon="mdi-folder"
							  :rules="[v => !!v || 'Project is required']"></v-select>
					
					<v-textarea v-model="workDescription"
							   label="What are you working on?"
							   prepend-icon="mdi-text"
							   counter
							   rows="3"
							   auto-grow></v-textarea>
				</v-card-text>
				<v-card-actions>
					<v-spacer></v-spacer>
					<v-btn color="error" text @click="cancelTimeTracking">Cancel</v-btn>
					<v-btn color="success" @click="confirmStartTracking" :disabled="!selectedProject">Start</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</div>
</template>

<script>
import { mapActions } from 'pinia'
import { store } from '../store'
import axios from 'axios'

export default {
	name: 'NavigationBar',
	data() {
		return {
			drawer: false,
			isLoading: false,
			projectDialog: false,
			selectedCustomer: null,
			selectedProject: null,
			workDescription: '',
			activeWorkLog: null,
			trackingStartTime: null,
			timeElapsed: '00:00:00',
			timerInterval: null,
			customers: [],
			projects: [],
			filteredProjects: [],
			hourlyRate: 0,
			secondsElapsed: 0,
			earnings: 0
		}
	},
	computed: {
		formattedEarnings() {
			return this.earnings > 0 ? `$${this.earnings.toFixed(2)}` : '';
		}
	},
	created() {
		this.checkForActiveWorkLog();
		this.fetchCustomers();
		this.fetchProjects();
	},
	beforeUnmount() {
		if (this.timerInterval) {
			clearInterval(this.timerInterval);
		}
	},
	methods: {
		...mapActions(store, ['logout', 'showSnackbar']),
		
		async fetchCustomers() {
			try {
				const response = await axios.get('/api/customers');
				this.customers = response.data;
			} catch (error) {
				console.error('Error fetching customers:', error);
			}
		},
		
		async fetchProjects() {
			try {
				const response = await axios.get('/api/projects');
				this.projects = response.data;
				this.filteredProjects = [...this.projects];
			} catch (error) {
				console.error('Error fetching projects:', error);
			}
		},
		
		filterProjects() {
			if (this.selectedCustomer) {
				this.filteredProjects = this.projects.filter(
					project => project.customer_id === this.selectedCustomer
				);
				// Reset selected project if it doesn't belong to the selected customer
				if (this.selectedProject && !this.filteredProjects.some(p => p.id === this.selectedProject)) {
					this.selectedProject = null;
				}
			} else {
				this.filteredProjects = [...this.projects];
			}
		},
		
		startTimeTracking() {
			this.projectDialog = true;
		},
		
		cancelTimeTracking() {
			this.projectDialog = false;
			this.selectedProject = null;
			this.workDescription = '';
		},
		
		async confirmStartTracking() {
			this.isLoading = true;
			try {
				const now = new Date();
				const currentTime = now.toTimeString().slice(0, 5); // Format: HH:MM
				
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
				this.trackingStartTime = now;
				
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
				
				// Navigate to work logs page and pass the active work log ID
				this.$router.push({ 
					path: '/work-logs', 
					query: { 
						completeTracking: true, 
						workLogId: this.activeWorkLog.id 
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
		
		checkForActiveWorkLog() {
			const savedWorkLog = localStorage.getItem('activeWorkLog');
			const savedStartTime = localStorage.getItem('trackingStartTime');
			const savedHourlyRate = localStorage.getItem('hourlyRate');
			
			if (savedWorkLog && savedStartTime) {
				this.activeWorkLog = JSON.parse(savedWorkLog);
				this.trackingStartTime = new Date(savedStartTime);
				this.hourlyRate = parseFloat(savedHourlyRate || '0');
				this.startElapsedTimer();
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
.pulse-animation {
	animation: pulse 1.5s infinite;
}

@keyframes pulse {
	0% {
		box-shadow: 0 0 0 0 rgba(244, 67, 54, 0.4);
	}
	70% {
		box-shadow: 0 0 0 10px rgba(244, 67, 54, 0);
	}
	100% {
		box-shadow: 0 0 0 0 rgba(244, 67, 54, 0);
	}
}

.time-tracking-info {
	background-color: rgba(255, 255, 255, 0.15);
	border-radius: 4px;
	padding: 2px 6px;
	min-width: 80px;
	text-align: center;
}

.time-display {
	font-size: 0.9rem;
	line-height: 1.2;
}

.earnings-display {
	color: #8aff8a;
	font-size: 0.75rem;
	line-height: 1;
}
</style>
