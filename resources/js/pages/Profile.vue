<template>
	<v-container fluid class="profile-container">
		<!-- Hero Section with Profile Picture -->
		<v-row>
			<v-col cols="12">
				<v-card class="profile-hero" elevation="0">
					<div class="hero-gradient"></div>
					<v-card-text class="text-center position-relative">
						<div class="avatar-wrapper">
							<v-avatar 
								size="150" 
								class="profile-avatar elevation-8"
								@click="triggerFileInput"
								style="cursor: pointer;"
							>
								<v-img 
									v-if="profile.avatar" 
									:src="profile.avatar"
									cover
								></v-img>
								<v-icon v-else size="80" color="white">mdi-account-circle</v-icon>
								<div class="avatar-overlay">
									<v-icon color="white">mdi-camera</v-icon>
								</div>
							</v-avatar>
							<input 
								ref="fileInput" 
								type="file" 
								accept="image/*" 
								style="display: none" 
								@change="onFileSelected"
							/>
						</div>
						<h2 class="text-h4 font-weight-bold mt-6 text-white">{{ profile.name || 'Your Name' }}</h2>
						<p class="text-subtitle-1 text-white mb-0">{{ profile.email }}</p>
						<v-chip 
							v-if="profile.email_verified_at" 
							color="success" 
							size="small" 
							class="mt-2"
						>
							<v-icon start size="small">mdi-check-circle</v-icon>
							Verified
						</v-chip>
					</v-card-text>
				</v-card>
			</v-col>
		</v-row>

		<!-- Statistics Cards -->
		<v-row v-if="statistics" class="mt-2">
			<v-col cols="12" sm="6" md="3">
				<v-card class="stat-card" elevation="2">
					<v-card-text class="text-center pa-6">
						<v-icon size="40" color="primary" class="mb-3">mdi-briefcase-clock</v-icon>
						<div class="text-h4 font-weight-bold">{{ statistics.total_work_logs || 0 }}</div>
						<div class="text-caption text-medium-emphasis">Work Sessions</div>
					</v-card-text>
				</v-card>
			</v-col>
			
			<v-col cols="12" sm="6" md="3">
				<v-card class="stat-card" elevation="2">
					<v-card-text class="text-center pa-6">
						<v-icon size="40" color="success" class="mb-3">mdi-clock-outline</v-icon>
						<div class="text-h4 font-weight-bold">{{ formatHours(statistics.total_hours) }}</div>
						<div class="text-caption text-medium-emphasis">Hours Tracked</div>
					</v-card-text>
				</v-card>
			</v-col>
			
			<v-col cols="12" sm="6" md="3">
				<v-card class="stat-card" elevation="2">
					<v-card-text class="text-center pa-6">
						<v-icon size="40" color="info" class="mb-3">mdi-cash-multiple</v-icon>
						<div class="text-h4 font-weight-bold">{{ formatMoney(statistics.total_earnings) }}</div>
						<div class="text-caption text-medium-emphasis">Total Earned</div>
					</v-card-text>
				</v-card>
			</v-col>
			
			<v-col cols="12" sm="6" md="3">
				<v-card class="stat-card" elevation="2">
					<v-card-text class="text-center pa-6">
						<v-icon size="40" color="warning" class="mb-3">mdi-folder-multiple</v-icon>
						<div class="text-h4 font-weight-bold">{{ statistics.active_projects || 0 }}</div>
						<div class="text-caption text-medium-emphasis">Active Projects</div>
					</v-card-text>
				</v-card>
			</v-col>
		</v-row>

		<!-- Main Content Tabs -->
		<v-row class="mt-2">
			<v-col cols="12">
				<v-card elevation="2">
					<v-tabs v-model="tab" bg-color="primary" dark>
						<v-tab value="personal">
							<v-icon start>mdi-account-edit</v-icon>
							Personal Info
						</v-tab>
						<v-tab value="security">
							<v-icon start>mdi-shield-lock</v-icon>
							Security
						</v-tab>
						<v-tab value="activity">
							<v-icon start>mdi-history</v-icon>
							Activity
						</v-tab>
						<v-tab value="info">
							<v-icon start>mdi-information</v-icon>
							Info & Legal
						</v-tab>
					</v-tabs>

					<v-card-text class="pa-8">
						<v-window v-model="tab">
							<!-- Personal Information Tab -->
							<v-window-item value="personal">
								<v-form ref="form" v-model="valid">
									<v-row>
										<v-col cols="12" md="6">
											<div class="text-h6 mb-4 d-flex align-center">
												<v-icon class="mr-2" color="primary">mdi-account</v-icon>
												Basic Information
											</div>
											<v-text-field
												v-model="profile.name"
												label="Full Name"
												variant="outlined"
												prepend-inner-icon="mdi-account"
												:rules="[rules.required]"
												density="comfortable"
											></v-text-field>

											<v-text-field
												v-model="profile.email"
												label="Email Address"
												variant="outlined"
												prepend-inner-icon="mdi-email"
												:rules="[rules.required, rules.email]"
												density="comfortable"
											></v-text-field>

											<v-switch
												v-model="profile.notify_on_project_assignment"
												color="primary"
												density="comfortable"
												hide-details
											>
												<template v-slot:label>
													<div>
														<div class="text-body-2 font-weight-medium">Project Assignment Notifications</div>
														<div class="text-caption text-medium-emphasis">Receive email notifications when assigned to new projects</div>
													</div>
												</template>
											</v-switch>
										</v-col>

										<v-col cols="12" md="6">
											<div class="text-h6 mb-4 d-flex align-center">
												<v-icon class="mr-2" color="primary">mdi-information</v-icon>
												Account Details
											</div>
											<v-card variant="outlined" class="pa-4">
												<v-list density="compact" bg-color="transparent">
													<v-list-item>
														<template v-slot:prepend>
															<v-icon color="primary">mdi-calendar-check</v-icon>
														</template>
														<v-list-item-title class="text-caption text-medium-emphasis">Member Since</v-list-item-title>
														<v-list-item-subtitle class="text-body-2 font-weight-medium">
															{{ formatDate(profile.created_at) }}
														</v-list-item-subtitle>
													</v-list-item>
													
													<v-divider class="my-2"></v-divider>
													
													<v-list-item>
														<template v-slot:prepend>
															<v-icon color="info">mdi-update</v-icon>
														</template>
														<v-list-item-title class="text-caption text-medium-emphasis">Last Updated</v-list-item-title>
														<v-list-item-subtitle class="text-body-2 font-weight-medium">
															{{ formatDate(profile.updated_at) }}
														</v-list-item-subtitle>
													</v-list-item>
													
													<v-divider class="my-2"></v-divider>
													
													<v-list-item>
														<template v-slot:prepend>
															<v-icon :color="profile.email_verified_at ? 'success' : 'warning'">
																{{ profile.email_verified_at ? 'mdi-check-decagram' : 'mdi-alert-circle' }}
															</v-icon>
														</template>
														<v-list-item-title class="text-caption text-medium-emphasis">Email Status</v-list-item-title>
														<v-list-item-subtitle class="text-body-2 font-weight-medium">
															{{ profile.email_verified_at ? 'Verified' : 'Not Verified' }}
														</v-list-item-subtitle>
													</v-list-item>
												</v-list>
											</v-card>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="d-flex justify-end">
										<v-btn
											variant="text"
											@click="resetForm"
											:disabled="loading"
											class="mr-2"
										>
											Cancel
										</v-btn>
										<v-btn
											color="primary"
											@click="updateProfile"
											:loading="loading"
											:disabled="!valid"
											size="large"
										>
											<v-icon start>mdi-content-save</v-icon>
											Save Changes
										</v-btn>
									</div>
								</v-form>
							</v-window-item>

							<!-- Security Tab -->
							<v-window-item value="security">
								<!-- Password Section -->
								<div class="text-h6 mb-4 d-flex align-center">
									<v-icon class="mr-2" color="primary">mdi-lock-reset</v-icon>
									Change Password
								</div>
								
								<v-row>
									<v-col cols="12" md="12">
										<v-text-field
											v-model="passwordData.current_password"
											label="Current Password"
											variant="outlined"
											prepend-inner-icon="mdi-lock"
											:append-inner-icon="showCurrentPassword ? 'mdi-eye-off' : 'mdi-eye'"
											:type="showCurrentPassword ? 'text' : 'password'"
											@click:append-inner="showCurrentPassword = !showCurrentPassword"
											density="comfortable"
										></v-text-field>

										<v-text-field
											v-model="passwordData.new_password"
											label="New Password"
											variant="outlined"
											prepend-inner-icon="mdi-lock-plus"
											:append-inner-icon="showNewPassword ? 'mdi-eye-off' : 'mdi-eye'"
											:type="showNewPassword ? 'text' : 'password'"
											@click:append-inner="showNewPassword = !showNewPassword"
											:rules="passwordData.new_password ? [rules.minLength] : []"
											density="comfortable"
										></v-text-field>

										<v-text-field
											v-model="passwordData.new_password_confirmation"
											label="Confirm New Password"
											variant="outlined"
											prepend-inner-icon="mdi-lock-check"
											:append-inner-icon="showConfirmPassword ? 'mdi-eye-off' : 'mdi-eye'"
											:type="showConfirmPassword ? 'text' : 'password'"
											@click:append-inner="showConfirmPassword = !showConfirmPassword"
											:rules="passwordData.new_password ? [rules.passwordMatch] : []"
											density="comfortable"
										></v-text-field>

										<v-alert
											type="info"
											variant="tonal"
											density="compact"
											class="mb-4"
										>
											<strong>Password Requirements:</strong> Minimum 16 characters. 
											This length provides strong protection against brute-force attacks and ensures 
											your account remains secure even if password databases are compromised. 
											Longer passwords are exponentially harder to crack than complex shorter ones.
										</v-alert>

										<v-btn
											color="primary"
											@click="updateProfile"
											:loading="loading"
											:disabled="!passwordData.current_password || !passwordData.new_password"
											size="large"
											class="mt-4"
										>
											<v-icon start>mdi-shield-check</v-icon>
											Update Password
										</v-btn>
									</v-col>
								</v-row>

								<v-divider class="my-8"></v-divider>

								<!-- 2FA Section -->
								<div class="text-h6 mb-4 d-flex align-center">
									<v-icon class="mr-2" color="primary">mdi-shield-lock</v-icon>
									Two-Factor Authentication (2FA)
								</div>

								<v-row>
									<v-col cols="12" md="12">
										<v-card variant="outlined" class="pa-4">
											<div class="d-flex align-center justify-space-between mb-4">
												<div class="d-flex align-center">
													<v-icon 
														:color="twoFactorStatus.enabled ? 'success' : 'warning'" 
														size="large" 
														class="mr-3"
													>
														{{ twoFactorStatus.enabled ? 'mdi-shield-check' : 'mdi-shield-alert' }}
													</v-icon>
													<div>
														<div class="text-subtitle-1 font-weight-bold">
															{{ twoFactorStatus.enabled ? 'Enabled' : 'Disabled' }}
														</div>
														<div class="text-caption text-medium-emphasis">
															{{ twoFactorStatus.enabled 
																? 'Your account is protected with 2FA' 
																: 'Add an extra layer of security to your account' 
															}}
														</div>
													</div>
												</div>
												<v-chip 
													:color="twoFactorStatus.enabled ? 'success' : 'warning'" 
													size="small"
												>
													{{ twoFactorStatus.enabled ? 'Active' : 'Inactive' }}
												</v-chip>
											</div>

											<v-divider class="mb-4"></v-divider>

											<!-- When 2FA is enabled -->
											<div v-if="twoFactorStatus.enabled">
												<v-alert type="success" variant="tonal" density="compact" class="mb-4">
													2FA is currently protecting your account. You'll need to enter a code from your authenticator app when logging in from new devices.
												</v-alert>

												<!-- 2FA Management Actions -->
												<div class="d-flex gap-2 mb-6">
													<v-btn 
														variant="outlined" 
														color="warning"
														prepend-icon="mdi-refresh"
														@click="reset2FADialog = true"
														:loading="resetting2FA"
													>
														Reset 2FA
													</v-btn>
												</div>

												<v-divider class="mb-4"></v-divider>

												<!-- Trusted Devices Section -->
												<div class="mb-6">
													<div class="d-flex align-center justify-space-between mb-3">
														<div>
															<div class="text-subtitle-1 font-weight-bold">Trusted Devices</div>
															<div class="text-caption text-medium-emphasis">{{ twoFactorStatus.trusted_devices_count }} device(s) currently trusted</div>
														</div>
														<v-btn 
															variant="outlined" 
															size="small"
															prepend-icon="mdi-refresh"
															@click="fetchTrustedDevices"
															:loading="loadingDevices"
														>
															Refresh
														</v-btn>
													</div>

													<v-card variant="outlined" class="mb-4">
														<v-card-text v-if="loadingDevices" class="text-center py-8">
															<v-progress-circular indeterminate color="primary"></v-progress-circular>
															<div class="text-caption text-medium-emphasis mt-2">Loading devices...</div>
														</v-card-text>
														
														<v-card-text v-else-if="trustedDevices.length === 0" class="text-center py-8">
															<v-icon size="48" color="grey-lighten-1" class="mb-2">mdi-devices-off</v-icon>
															<div class="text-body-2 text-medium-emphasis">No trusted devices found</div>
														</v-card-text>

														<v-list v-else density="compact">
															<template v-for="(device, index) in trustedDevices" :key="device.fingerprint">
																<v-list-item>
																	<template v-slot:prepend>
																		<v-icon :color="device.is_current ? 'primary' : 'grey'">
																			{{ getDeviceIcon(device.user_agent) }}
																		</v-icon>
																	</template>

																	<v-list-item-title>
																		{{ formatUserAgent(device.user_agent) }}
																		<v-chip 
																			v-if="device.is_current" 
																			size="x-small" 
																			color="primary" 
																			class="ml-2"
																		>
																			Current
																		</v-chip>
																	</v-list-item-title>

																	<v-list-item-subtitle class="mt-1">
																		<div class="text-caption">
																			<v-icon size="12" class="mr-1">mdi-clock-plus-outline</v-icon>
																			Added {{ device.added_at_human }}
																		</div>
																		<div class="text-caption">
																			<v-icon size="12" class="mr-1">mdi-clock-alert-outline</v-icon>
																			Expires {{ device.expires_at_human }}
																		</div>
																	</v-list-item-subtitle>

																	<template v-slot:append>
																		<v-btn
																			v-if="!device.is_current"
																			icon="mdi-delete"
																			size="small"
																			variant="text"
																			color="error"
																			@click="removeDevice(device.fingerprint)"
																			:loading="removingDevice === device.fingerprint"
																		></v-btn>
																		<v-tooltip v-else location="left">
																			<template v-slot:activator="{ props }">
																				<v-icon v-bind="props" size="small" color="grey">mdi-lock</v-icon>
																			</template>
																			<span>Cannot remove current device</span>
																		</v-tooltip>
																	</template>
																</v-list-item>
																<v-divider v-if="index < trustedDevices.length - 1"></v-divider>
															</template>
														</v-list>
													</v-card>
												</div>

												<!-- Recovery Codes Section -->
												<div class="mb-6">
													<div class="d-flex align-center justify-space-between mb-3">
														<div>
															<div class="text-subtitle-1 font-weight-bold">Recovery Codes</div>
															<div class="text-caption text-medium-emphasis">Use these codes if you lose access to your authenticator</div>
														</div>
													</div>

													<v-card variant="outlined">
														<v-card-text>
															<div v-if="!showingRecoveryCodes" class="text-center py-4">
																<v-icon size="48" color="warning" class="mb-2">mdi-shield-key</v-icon>
																<div class="text-body-2 text-medium-emphasis mb-4">
																	Recovery codes are hidden for security
																</div>
																<div class="d-flex justify-center gap-2">
																	<v-btn 
																		variant="outlined" 
																		prepend-icon="mdi-eye"
																		@click="fetchRecoveryCodes"
																		:loading="loadingRecoveryCodes"
																	>
																		View Codes
																	</v-btn>
																	<v-btn 
																		variant="outlined" 
																		color="warning"
																		prepend-icon="mdi-refresh"
																		@click="regenerateCodesDialog = true"
																		:loading="loadingRecoveryCodes"
																	>
																		Regenerate
																	</v-btn>
																</div>
															</div>

															<div v-else>
																<v-alert type="warning" variant="tonal" density="compact" class="mb-4">
																	<strong>Save these codes!</strong> Each code can only be used once. Store them securely.
																</v-alert>

																<div class="recovery-codes-grid mb-4">
																	<v-card
																		v-for="(code, index) in recoveryCodes"
																		:key="index"
																		variant="outlined"
																		class="pa-3 text-center"
																	>
																		<code class="text-body-2 font-weight-bold">{{ code }}</code>
																	</v-card>
																</div>

																<div class="d-flex justify-center gap-2">
																	<v-btn 
																		variant="outlined"
																		prepend-icon="mdi-content-copy"
																		@click="copyRecoveryCodes"
																	>
																		Copy All
																	</v-btn>
																	<v-btn 
																		variant="outlined"
																		prepend-icon="mdi-download"
																		@click="downloadRecoveryCodes"
																	>
																		Download
																	</v-btn>
																	<v-btn 
																		variant="text"
																		prepend-icon="mdi-eye-off"
																		@click="hideRecoveryCodes"
																	>
																		Hide
																	</v-btn>
																</div>
															</div>
														</v-card-text>
													</v-card>
												</div>
											</div>

											<!-- When 2FA is disabled -->
											<div v-else>
												<v-alert type="warning" variant="tonal" density="compact" class="mb-4">
													Your account is not protected by 2FA. Enable it now to add an extra layer of security.
												</v-alert>

												<v-btn 
													color="primary"
													prepend-icon="mdi-shield-plus"
													@click="$router.push('/2fa/setup')"
												>
													Enable 2FA
												</v-btn>
											</div>
										</v-card>
									</v-col>
								</v-row>
							</v-window-item>

							<!-- Activity Tab -->
							<v-window-item value="activity">
								<div class="text-h6 mb-4 d-flex align-center">
									<v-icon class="mr-2" color="primary">mdi-chart-timeline-variant</v-icon>
									Your Activity
								</div>
								
								<v-row v-if="statistics">
									<!-- Activity Heatmap -->
									<v-col cols="12">
										<v-card variant="outlined">
											<v-card-text>
												<div class="d-flex align-center justify-space-between mb-4">
													<div>
														<div class="text-h6 font-weight-bold">Activity Heatmap</div>
														<div class="text-caption text-medium-emphasis">Your work session activity over the past year</div>
													</div>
													<div class="d-flex align-center gap-2">
														<span class="text-caption text-medium-emphasis">Less</span>
														<div class="heatmap-legend">
															<div class="heatmap-cell legend-0"></div>
															<div class="heatmap-cell legend-1"></div>
															<div class="heatmap-cell legend-2"></div>
															<div class="heatmap-cell legend-3"></div>
															<div class="heatmap-cell legend-4"></div>
														</div>
														<span class="text-caption text-medium-emphasis">More</span>
													</div>
												</div>

												<div class="heatmap-container">
													<div class="heatmap-wrapper">
														<!-- Month labels -->
														<div class="heatmap-months">
															<div v-for="month in visibleMonths" :key="month.name" :style="{ gridColumn: `span ${month.weeks}` }" class="month-label">
																{{ month.name }}
															</div>
														</div>

														<!-- Day labels -->
														<div class="heatmap-days">
															<div class="day-label">Mon</div>
															<div class="day-label"></div>
															<div class="day-label">Wed</div>
															<div class="day-label"></div>
															<div class="day-label">Fri</div>
															<div class="day-label"></div>
															<div class="day-label"></div>
														</div>

														<!-- Heatmap grid -->
														<div class="heatmap-grid">
															<div
																v-for="(day, index) in heatmapData"
																:key="index"
																class="heatmap-cell"
																:class="getHeatmapClass(day.count)"
															></div>
														</div>
													</div>
												</div>
											</v-card-text>
										</v-card>
									</v-col>
								</v-row>

								<v-alert
									v-else
									type="info"
									variant="tonal"
									class="mt-4"
								>
									Start tracking your time to see your activity statistics here!
								</v-alert>
							</v-window-item>

							<!-- Info & Legal Tab -->
							<v-window-item value="info">
								<div class="text-h6 mb-4 d-flex align-center">
									<v-icon class="mr-2" color="primary">mdi-information-outline</v-icon>
									Information & Legal
								</div>
								
								<v-row>
									<v-col cols="12" md="6">
										<v-card variant="outlined" class="info-card" hover @click="$router.push({ name: 'Privacy' })">
											<v-card-text class="pa-6">
												<div class="d-flex align-center mb-4">
													<v-avatar color="primary" size="56" class="mr-4">
														<v-icon size="32" color="white">mdi-shield-account</v-icon>
													</v-avatar>
													<div>
														<div class="text-h6 font-weight-bold">Privacy Notice</div>
														<div class="text-caption text-medium-emphasis">How we handle your data</div>
													</div>
												</div>
												<p class="text-body-2 mb-0">
													Learn about our data protection practices, your privacy rights, and how we use and protect your personal information.
												</p>
											</v-card-text>
											<v-divider></v-divider>
											<v-card-actions class="px-6 py-3">
												<v-spacer></v-spacer>
												<v-btn variant="text" color="primary">
													Read Privacy Notice
													<v-icon end>mdi-arrow-right</v-icon>
												</v-btn>
											</v-card-actions>
										</v-card>
									</v-col>

									<v-col cols="12" md="6">
										<v-card variant="outlined" class="info-card" hover @click="$router.push({ name: 'Imprint' })">
											<v-card-text class="pa-6">
												<div class="d-flex align-center mb-4">
													<v-avatar color="secondary" size="56" class="mr-4">
														<v-icon size="32" color="white">mdi-gavel</v-icon>
													</v-avatar>
													<div>
														<div class="text-h6 font-weight-bold">Imprint</div>
														<div class="text-caption text-medium-emphasis">Legal information and contact</div>
													</div>
												</div>
												<p class="text-body-2 mb-0">
													View our legal information, company details, and contact information as required by law.
												</p>
											</v-card-text>
											<v-divider></v-divider>
											<v-card-actions class="px-6 py-3">
												<v-spacer></v-spacer>
												<v-btn variant="text" color="secondary">
													Read Imprint
													<v-icon end>mdi-arrow-right</v-icon>
												</v-btn>
											</v-card-actions>
										</v-card>
									</v-col>
								</v-row>

								<v-divider class="my-8"></v-divider>

								<div class="text-h6 mb-4 d-flex align-center">
									<v-icon class="mr-2" color="primary">mdi-help-circle-outline</v-icon>
									Application Information
								</div>

								<v-row>
									<v-col cols="12">
										<v-card variant="outlined">
											<v-card-text class="pa-6">
												<v-list density="compact" bg-color="transparent">
													<v-list-item>
														<template v-slot:prepend>
															<v-icon color="primary">mdi-application</v-icon>
														</template>
														<v-list-item-title class="text-body-2 font-weight-medium">Application Name</v-list-item-title>
														<v-list-item-subtitle class="text-body-1">Client Ledger</v-list-item-subtitle>
													</v-list-item>

													<v-divider class="my-2"></v-divider>

													<v-list-item>
														<template v-slot:prepend>
															<v-icon color="info">mdi-shield-check</v-icon>
														</template>
														<v-list-item-title class="text-body-2 font-weight-medium">Security</v-list-item-title>
														<v-list-item-subtitle class="text-body-1">
															This is a secure business application. All users are managed by administrators.
														</v-list-item-subtitle>
													</v-list-item>

													<v-divider class="my-2"></v-divider>

													<v-list-item>
														<template v-slot:prepend>
															<v-icon color="success">mdi-account-lock</v-icon>
														</template>
														<v-list-item-title class="text-body-2 font-weight-medium">Access</v-list-item-title>
														<v-list-item-subtitle class="text-body-1">
															User accounts must be created by an administrator. Self-registration is not available.
														</v-list-item-subtitle>
													</v-list-item>
												</v-list>
											</v-card-text>
										</v-card>
									</v-col>
								</v-row>
							</v-window-item>
						</v-window>
					</v-card-text>
				</v-card>
			</v-col>
		</v-row>

		<!-- Reset 2FA Confirmation Dialog -->
		<v-dialog v-model="reset2FADialog" max-width="600">
			<v-card>
				<v-card-title class="d-flex align-center bg-warning pa-4">
					<v-icon color="white" class="mr-2">mdi-refresh-circle</v-icon>
					<span class="text-white">Reset Two-Factor Authentication</span>
				</v-card-title>
				<v-card-text class="pa-6">
					<v-alert type="warning" variant="tonal" density="compact" class="mb-4">
						<strong>Warning:</strong> This action will reset your 2FA setup
					</v-alert>
					
					<p class="text-body-1 mb-4">Are you sure you want to reset 2FA?</p>
					
					<p class="text-body-2 mb-2">This will:</p>
					<ul class="text-body-2 ml-4 mb-4">
						<li>Generate a new QR code and secret</li>
						<li>Clear all trusted devices</li>
						<li>Generate new recovery codes</li>
						<li>Require you to re-scan the QR code in your authenticator app</li>
					</ul>
					
					<p class="text-body-2 mb-4">You will be redirected to the 2FA setup page.</p>
					
					<v-text-field
						v-model="reset2FAPassword"
						label="Enter your password to confirm"
						type="password"
						variant="outlined"
						prepend-inner-icon="mdi-lock"
						density="comfortable"
						autofocus
					></v-text-field>
				</v-card-text>
				<v-card-actions class="pa-4">
					<v-spacer></v-spacer>
					<v-btn 
						variant="text" 
						@click="reset2FADialog = false; reset2FAPassword = ''"
					>
						Cancel
					</v-btn>
					<v-btn 
						color="warning" 
						@click="handleReset2FA"
						:loading="resetting2FA"
						:disabled="!reset2FAPassword"
					>
						Reset 2FA
					</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

		<!-- Regenerate Recovery Codes Confirmation Dialog -->
		<v-dialog v-model="regenerateCodesDialog" max-width="600">
			<v-card>
				<v-card-title class="d-flex align-center bg-warning pa-4">
					<v-icon color="white" class="mr-2">mdi-shield-refresh</v-icon>
					<span class="text-white">Regenerate Recovery Codes</span>
				</v-card-title>
				<v-card-text class="pa-6">
					<v-alert type="warning" variant="tonal" density="compact" class="mb-4">
						<strong>Warning:</strong> Your old recovery codes will no longer work
					</v-alert>
					
					<p class="text-body-1 mb-4">
						Are you sure you want to regenerate your recovery codes?
					</p>
					
					<p class="text-body-2 mb-4">
						This will invalidate all your existing recovery codes and generate a new set.
						Make sure to save the new codes in a secure location.
					</p>
				</v-card-text>
				<v-card-actions class="pa-4">
					<v-spacer></v-spacer>
					<v-btn 
						variant="text" 
						@click="regenerateCodesDialog = false"
					>
						Cancel
					</v-btn>
					<v-btn 
						color="warning" 
						@click="handleRegenerateRecoveryCodes"
						:loading="loadingRecoveryCodes"
					>
						Regenerate Codes
					</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>
	</v-container>
</template>

<script>
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import axios from 'axios'
import { formatDate, formatCurrency } from '../utils/formatters'

export default {
	name: 'Profile',
	data() {
		return {
			profile: {
				name: '',
				email: '',
				avatar: null,
				created_at: null,
				updated_at: null,
				email_verified_at: null,
				notify_on_project_assignment: true,
			},
			originalProfile: {},
			passwordData: {
				current_password: '',
				new_password: '',
				new_password_confirmation: '',
			},
			statistics: null,
			activityData: [],
			heatmapData: [],
			visibleMonths: [],
			valid: false,
			loading: false,
			showCurrentPassword: false,
			showNewPassword: false,
			showConfirmPassword: false,
			tab: 'personal',
			twoFactorStatus: {
				enabled: false,
				confirmed: false,
				trusted_devices_count: 0,
			},
			trustedDevices: [],
			recoveryCodes: [],
			showingRecoveryCodes: false,
			loadingDevices: false,
			loadingRecoveryCodes: false,
			removingDevice: null,
			resetting2FA: false,
			reset2FADialog: false,
			reset2FAPassword: '',
			regenerateCodesDialog: false,
			rules: {
				required: v => !!v || 'This field is required',
				email: v => /.+@.+\..+/.test(v) || 'Email must be valid',
				minLength: v => (v && v.length >= 16) || 'Password must be at least 16 characters',
				passwordMatch: v => v === this.passwordData.new_password || 'Passwords must match',
			},
		}
	},
	created() {
		this.fetchProfile()
		this.fetchStatistics()
		this.fetch2FAStatus()
	},
	watch: {
		tab(newTab) {
			// Lazy load activity data when switching to activity tab
			if (newTab === 'activity' && this.heatmapData.length === 0) {
				this.fetchActivityData()
			}
		}
	},
	computed: {
		...mapState(store, ['settings'])
	},
	methods: {
		...mapActions(store, ['showSnackbar', 'updateAuthUser']),
		
		triggerFileInput() {
			this.$refs.fileInput.click()
		},
		
		onFileSelected(event) {
			const file = event.target.files[0]
			if (!file) return
			
			// Validate file type
			if (!file.type.startsWith('image/')) {
				this.showSnackbar('Please select an image file', 'error')
				return
			}
			
			// Validate file size (max 2MB)
			if (file.size > 2 * 1024 * 1024) {
				this.showSnackbar('Image size must be less than 2MB', 'error')
				return
			}
			
			// Convert to base64
			const reader = new FileReader()
			reader.onload = (e) => {
				this.profile.avatar = e.target.result
				// Auto-save the avatar
				this.updateProfile()
			}
			reader.readAsDataURL(file)
		},
		
		async fetchProfile() {
			this.loading = true
			try {
				const response = await axios.get('/api/user')
				this.profile = {
					name: response.data.name,
					email: response.data.email,
					avatar: response.data.avatar || null,
					created_at: response.data.created_at,
					updated_at: response.data.updated_at,
					email_verified_at: response.data.email_verified_at,
					notify_on_project_assignment: response.data.notify_on_project_assignment ?? true,
				}
				this.originalProfile = { ...this.profile }
			} catch (error) {
				console.error('Error fetching profile:', error)
				this.showSnackbar('Failed to load profile', 'error')
			} finally {
				this.loading = false
			}
		},
		
		async fetchStatistics() {
			try {
				const response = await axios.get('/api/profile/statistics')
				this.statistics = response.data
			} catch (error) {
				console.error('Error fetching statistics:', error)
				// Don't show error for statistics as it's not critical
			}
		},
		
		async updateProfile() {
			if (!this.valid && this.tab === 'personal') return
			
			this.loading = true
			try {
				const payload = {
					name: this.profile.name,
					email: this.profile.email,
					avatar: this.profile.avatar,
					notify_on_project_assignment: this.profile.notify_on_project_assignment,
				}
				
				// Only include password if provided
				if (this.passwordData.current_password && this.passwordData.new_password) {
					payload.current_password = this.passwordData.current_password
					payload.new_password = this.passwordData.new_password
					payload.new_password_confirmation = this.passwordData.new_password_confirmation
				}
				
				const response = await axios.put('/api/profile', payload)
				
				// Update the profile data
				this.profile = {
					name: response.data.name,
					email: response.data.email,
					avatar: response.data.avatar || null,
					created_at: response.data.created_at,
					updated_at: response.data.updated_at,
					email_verified_at: response.data.email_verified_at,
					notify_on_project_assignment: response.data.notify_on_project_assignment ?? true,
				}
				this.originalProfile = { ...this.profile }
				
				// Update the store with the new user data
				this.updateAuthUser(response.data)
				
				// Clear password fields
				this.passwordData = {
					current_password: '',
					new_password: '',
					new_password_confirmation: '',
				}
				
				this.showSnackbar('Profile updated successfully', 'success')
			} catch (error) {
				console.error('Error updating profile:', error)
				const message = error.response?.data?.message || 'Failed to update profile'
				this.showSnackbar(message, 'error')
			} finally {
				this.loading = false
			}
		},
		
		resetForm() {
			this.profile = { ...this.originalProfile }
			this.passwordData = {
				current_password: '',
				new_password: '',
				new_password_confirmation: '',
			}
			if (this.$refs.form) {
				this.$refs.form.resetValidation()
			}
		},
		
		async fetchActivityData() {
			// Only fetch if we haven't already
			if (this.heatmapData.length > 0) return
			
			try {
				const response = await axios.get('/api/profile/activity')
				this.activityData = response.data
				this.generateHeatmap()
			} catch (error) {
				console.error('Error fetching activity data:', error)
				// Generate empty heatmap if no data
				this.generateHeatmap()
			}
		},
		
		generateHeatmap() {
			// Don't regenerate if already generated
			if (this.heatmapData.length > 0) return
			
			const today = new Date()
			const oneYearAgo = new Date(today)
			oneYearAgo.setFullYear(today.getFullYear() - 1)
			
			// Start from the first Sunday before one year ago
			const startDate = new Date(oneYearAgo)
			startDate.setDate(startDate.getDate() - startDate.getDay())
			
			// Generate all days for the past year
			const heatmapData = []
			const activityMap = {}
			
			// Create a map of dates to activity counts
			this.activityData.forEach(activity => {
				activityMap[activity.date] = activity.count
			})
			
			// Generate 53 weeks of data (371 days)
			let currentDate = new Date(startDate)
			for (let i = 0; i < 371; i++) {
				const dateStr = currentDate.toISOString().split('T')[0]
				heatmapData.push({
					date: dateStr,
					count: activityMap[dateStr] || 0
				})
				currentDate.setDate(currentDate.getDate() + 1)
			}
			
			this.heatmapData = heatmapData
			this.calculateVisibleMonths()
		},
		
		calculateVisibleMonths() {
			const months = []
			let currentMonth = null
			let weekCount = 0
			
			this.heatmapData.forEach((day, index) => {
				const date = new Date(day.date)
				const month = date.getMonth()
				
				// Count weeks (every 7 days)
				if (index % 7 === 0) {
					weekCount++
				}
				
				if (currentMonth !== month) {
					if (currentMonth !== null) {
						months[months.length - 1].weeks = weekCount
						weekCount = 0
					}
					
					const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
					months.push({
						name: monthNames[month],
						weeks: 0
					})
					currentMonth = month
				}
			})
			
			// Set the last month's week count
			if (months.length > 0) {
				months[months.length - 1].weeks = weekCount + 1
			}
			
			this.visibleMonths = months
		},
		
		getHeatmapClass(count) {
			if (count === 0) return 'level-0'
			if (count === 1) return 'level-1'
			if (count === 2) return 'level-2'
			if (count === 3) return 'level-3'
			return 'level-4' // 4 or more sessions = darkest green
		},
		
		formatHeatmapDate(dateStr) {
			const date = new Date(dateStr)
			return date.toLocaleDateString('en-US', {
				weekday: 'short',
				month: 'short',
				day: 'numeric',
				year: 'numeric'
			})
		},
		
		async fetch2FAStatus() {
			try {
				const response = await axios.get('/api/2fa/status')
				this.twoFactorStatus = response.data
				
				// Auto-fetch trusted devices if 2FA is enabled
				if (response.data.enabled) {
					await this.fetchTrustedDevices()
				}
			} catch (error) {
				console.error('Error fetching 2FA status:', error)
			}
		},
		
		async fetchTrustedDevices() {
			this.loadingDevices = true
			try {
				const response = await axios.get('/api/2fa/devices')
				this.trustedDevices = response.data.devices
			} catch (error) {
				console.error('Error fetching trusted devices:', error)
				this.showSnackbar(error.response?.data?.message || 'Failed to load devices', 'error')
			} finally {
				this.loadingDevices = false
			}
		},
		
		async removeDevice(fingerprint) {
			if (!confirm('Are you sure you want to remove this device? You will need to verify 2FA the next time you log in from this device.')) {
				return
			}
			
			this.removingDevice = fingerprint
			try {
				await axios.delete('/api/2fa/devices', {
					data: { fingerprint }
				})
				
				this.showSnackbar('Device removed successfully', 'success')
				await this.fetchTrustedDevices()
				await this.fetch2FAStatus()
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Failed to remove device', 'error')
			} finally {
				this.removingDevice = null
			}
		},
		
		async fetchRecoveryCodes() {
			this.loadingRecoveryCodes = true
			try {
				const response = await axios.get('/api/2fa/recovery-codes')
				this.recoveryCodes = response.data.recovery_codes
				this.showingRecoveryCodes = true
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Failed to load recovery codes', 'error')
			} finally {
				this.loadingRecoveryCodes = false
			}
		},
		
		hideRecoveryCodes() {
			this.showingRecoveryCodes = false
			this.recoveryCodes = []
		},
		
		async handleRegenerateRecoveryCodes() {
			this.loadingRecoveryCodes = true
			try {
				const response = await axios.post('/api/2fa/recovery-codes/regenerate')
				this.recoveryCodes = response.data.recovery_codes
				this.showingRecoveryCodes = true
				this.regenerateCodesDialog = false
				this.showSnackbar('Recovery codes regenerated successfully', 'success')
			} catch (error) {
				this.showSnackbar(error.response?.data?.message || 'Failed to regenerate recovery codes', 'error')
			} finally {
				this.loadingRecoveryCodes = false
			}
		},
		
		copyRecoveryCodes() {
			const codesText = this.recoveryCodes.join('\n')
			navigator.clipboard.writeText(codesText).then(() => {
				this.showSnackbar('Recovery codes copied to clipboard', 'success')
			}).catch(() => {
				this.showSnackbar('Failed to copy codes', 'error')
			})
		},
		
		downloadRecoveryCodes() {
			const codesText = this.recoveryCodes.join('\n')
			const blob = new Blob([codesText], { type: 'text/plain' })
			const url = window.URL.createObjectURL(blob)
			const a = document.createElement('a')
			a.href = url
			a.download = `recovery-codes-${new Date().toISOString().split('T')[0]}.txt`
			document.body.appendChild(a)
			a.click()
			window.URL.revokeObjectURL(url)
			document.body.removeChild(a)
			this.showSnackbar('Recovery codes downloaded', 'success')
		},
		
		getDeviceIcon(userAgent) {
			const ua = userAgent.toLowerCase()
			if (ua.includes('mobile') || ua.includes('android') || ua.includes('iphone')) {
				return 'mdi-cellphone'
			} else if (ua.includes('tablet') || ua.includes('ipad')) {
				return 'mdi-tablet'
			} else {
				return 'mdi-laptop'
			}
		},
		
		formatUserAgent(userAgent) {
			// Simple user agent parsing
			const ua = userAgent
			
			// Extract browser
			let browser = 'Unknown Browser'
			if (ua.includes('Chrome') && !ua.includes('Edg')) browser = 'Chrome'
			else if (ua.includes('Firefox')) browser = 'Firefox'
			else if (ua.includes('Safari') && !ua.includes('Chrome')) browser = 'Safari'
			else if (ua.includes('Edg')) browser = 'Edge'
			
			// Extract OS
			let os = 'Unknown OS'
			if (ua.includes('Windows')) os = 'Windows'
			else if (ua.includes('Mac OS')) os = 'macOS'
			else if (ua.includes('Linux')) os = 'Linux'
			else if (ua.includes('Android')) os = 'Android'
			else if (ua.includes('iOS') || ua.includes('iPhone') || ua.includes('iPad')) os = 'iOS'
			
			return `${browser} on ${os}`
		},
		
		async handleReset2FA() {
			if (!this.reset2FAPassword) {
				this.showSnackbar('Please enter your password', 'error')
				return
			}
			
			this.resetting2FA = true
			try {
				// Disable current 2FA first
				await axios.post('/api/2fa/disable', { password: this.reset2FAPassword })
				
				// Close dialog and reset password
				this.reset2FADialog = false
				this.reset2FAPassword = ''
				
				// Redirect to setup page
				this.$router.push('/2fa/setup')
				this.showSnackbar('2FA reset. Please set up your authenticator again.', 'success')
			} catch (error) {
				console.error('Error resetting 2FA:', error)
				this.showSnackbar(error.response?.data?.message || 'Failed to reset 2FA', 'error')
			} finally {
				this.resetting2FA = false
			}
		},
	
	formatDate(date) {
		return formatDate(date, this.settings);
	},		formatHours(hours) {
			if (!hours) return '0h'
			return `${parseFloat(hours).toFixed(1)}h`
		},
		
	
	formatMoney(amount) {
		return formatCurrency(amount);
	},
},
}
</script><style scoped>
.profile-container {
	max-width: 1400px;
	margin: 0 auto;
}

.profile-hero {
	position: relative;
	overflow: hidden;
	border-radius: 16px !important;
}

.hero-gradient {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
	opacity: 0.95;
}

.avatar-wrapper {
	position: relative;
	display: inline-block;
	margin-top: 20px;
}

.profile-avatar {
	border: 5px solid white;
	background: rgba(255, 255, 255, 0.2);
	transition: all 0.3s ease;
}

.profile-avatar:hover {
	transform: scale(1.05);
}

.avatar-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	display: flex;
	align-items: center;
	justify-content: center;
	opacity: 0;
	transition: opacity 0.3s ease;
	border-radius: 50%;
}

.profile-avatar:hover .avatar-overlay {
	opacity: 1;
}

.stat-card {
	border-radius: 12px !important;
	transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}

.position-relative {
	position: relative;
}

.opacity-20 {
	opacity: 0.2;
}

/* Heatmap Styles */
.heatmap-container {
	width: 100%;
	overflow-x: auto;
	padding: 10px 0;
}

.heatmap-wrapper {
	display: grid;
	grid-template-areas:
		"empty months"
		"days  grid";
	grid-template-columns: auto 1fr;
	grid-template-rows: auto 1fr;
	gap: 8px;
}

.heatmap-months {
	grid-area: months;
	display: grid;
	grid-auto-flow: column;
	grid-auto-columns: minmax(12px, 1fr);
	gap: 3px;
}

.month-label {
	font-size: 10px;
	color: rgb(var(--v-theme-on-surface));
	opacity: 0.6;
	grid-row: 1;
}

.heatmap-days {
	grid-area: days;
	display: grid;
	grid-template-rows: repeat(7, 12px);
	gap: 3px;
	width: 30px;
}

.day-label {
	font-size: 9px;
	line-height: 12px;
	color: rgb(var(--v-theme-on-surface));
	opacity: 0.6;
}

.heatmap-grid {
	grid-area: grid;
	display: grid;
	grid-template-rows: repeat(7, 12px);
	grid-auto-flow: column;
	grid-auto-columns: minmax(12px, 1fr);
	gap: 3px;
}

.heatmap-cell {
	width: 100%;
	height: 12px;
	border-radius: 2px;
}

.heatmap-cell:hover {
	outline: 2px solid rgba(102, 126, 234, 0.5);
	outline-offset: 1px;
}

.heatmap-cell.level-0,
.heatmap-cell.legend-0 {
	background-color: #ebedf0;
}

.heatmap-cell.level-1,
.heatmap-cell.legend-1 {
	background-color: #9be9a8;
}

.heatmap-cell.level-2,
.heatmap-cell.legend-2 {
	background-color: #40c463;
}

.heatmap-cell.level-3,
.heatmap-cell.legend-3 {
	background-color: #30a14e;
}

.heatmap-cell.level-4,
.heatmap-cell.legend-4 {
	background-color: #216e39;
}

.heatmap-legend {
	display: flex;
	gap: 3px;
}

.gap-2 {
	gap: 8px;
}

/* Recovery Codes Grid */
.recovery-codes-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
	gap: 12px;
}

.recovery-codes-grid code {
	font-family: 'Courier New', monospace;
	color: rgb(var(--v-theme-primary));
}

.info-card {
	cursor: pointer;
	transition: all 0.3s ease;
}

.info-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
}
</style>
