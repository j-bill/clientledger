<template>
	<div class="mx-auto max-w-[1800px] px-6 py-8 lg:px-10">
		<!-- Hero Section with Profile Picture -->
		<ui-card>
			<div class="flex flex-col items-center py-4 text-center">
				<button type="button" class="group relative rounded-full" @click="triggerFileInput">
					<ui-avatar :name="profile.name" :image="profile.avatar || ''" :size="112">
						<User class="h-12 w-12 text-bone-500" />
					</ui-avatar>
					<span class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
						<Camera class="h-6 w-6 text-bone-100" />
					</span>
				</button>
				<input
					ref="fileInput"
					type="file"
					accept="image/*"
					class="hidden"
					@change="onFileSelected"
				/>
				<h1 class="mt-4 text-2xl font-semibold tracking-tight text-bone-100">{{ profile.name || $t('pages.profile.yourName') }}</h1>
				<p class="mt-1 text-sm text-bone-500">{{ profile.email }}</p>
				<ui-chip
					v-if="profile.email_verified_at"
					color="success"
					:icon="CircleCheck"
					:text="$t('pages.profile.verified')"
					class="mt-3"
				/>
			</div>
		</ui-card>

		<!-- Statistics Cards -->
		<div v-if="statistics" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
			<ui-card>
				<div class="flex flex-col items-center py-2 text-center">
					<Briefcase class="mb-3 h-6 w-6 text-bone-500" />
					<div class="tnum text-2xl font-semibold text-bone-100">{{ statistics.total_work_logs || 0 }}</div>
					<div class="mt-1 text-xs text-bone-500">{{ $t('pages.profile.workSessions') }}</div>
				</div>
			</ui-card>

			<ui-card>
				<div class="flex flex-col items-center py-2 text-center">
					<Clock class="mb-3 h-6 w-6 text-bone-500" />
					<div class="tnum text-2xl font-semibold text-bone-100">{{ formatHours(statistics.total_hours) }}</div>
					<div class="mt-1 text-xs text-bone-500">{{ $t('pages.profile.hoursTracked') }}</div>
				</div>
			</ui-card>

			<ui-card>
				<div class="flex flex-col items-center py-2 text-center">
					<Banknote class="mb-3 h-6 w-6 text-bone-500" />
					<div class="tnum text-2xl font-semibold text-bone-100">{{ formatMoney(statistics.total_earnings) }}</div>
					<div class="mt-1 text-xs text-bone-500">{{ $t('pages.profile.totalEarned') }}</div>
				</div>
			</ui-card>

			<ui-card>
				<div class="flex flex-col items-center py-2 text-center">
					<Folders class="mb-3 h-6 w-6 text-bone-500" />
					<div class="tnum text-2xl font-semibold text-bone-100">{{ statistics.active_projects || 0 }}</div>
					<div class="mt-1 text-xs text-bone-500">{{ $t('pages.profile.activeProjects') }}</div>
				</div>
			</ui-card>
		</div>

		<!-- Main Content Tabs -->
		<ui-card dense class="mt-4">
			<ui-tabs v-model="tab" :tabs="profileTabs" class="px-3" />

			<div class="p-6 sm:p-8">
				<!-- Personal Information Tab -->
				<div v-if="tab === 'personal'">
					<ui-form ref="form">
						<div class="grid grid-cols-1 gap-8 md:grid-cols-2">
							<div>
								<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
									<User class="h-4 w-4 text-bone-500" />
									{{ $t('pages.profile.basicInformation') }}
								</div>
								<div class="space-y-4">
									<ui-input
										v-model="profile.name"
										:label="$t('pages.profile.fullName')"
										:icon="User"
										:rules="[rules.required]"
									/>

									<ui-input
										v-model="profile.email"
										:label="$t('pages.profile.emailAddress')"
										:icon="Mail"
										:rules="[rules.required, rules.email]"
									/>

									<ui-switch
										v-model="profile.notify_on_project_assignment"
										:label="$t('pages.profile.projectNotifications')"
										:hint="$t('pages.profile.projectNotificationsHint')"
									/>
								</div>
							</div>

							<div>
								<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
									<Info class="h-4 w-4 text-bone-500" />
									{{ $t('pages.profile.accountDetails') }}
								</div>
								<div class="rounded-lg border border-ink-700/60 p-4">
									<div class="flex items-start gap-3">
										<Calendar class="mt-0.5 h-4 w-4 text-bone-500" />
										<div>
											<div class="font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">{{ $t('pages.profile.memberSince') }}</div>
											<div class="tnum mt-0.5 text-sm text-bone-100">{{ formatDate(profile.created_at) }}</div>
										</div>
									</div>

									<hr class="my-3 border-ink-700/60" />

									<div class="flex items-start gap-3">
										<RefreshCw class="mt-0.5 h-4 w-4 text-bone-500" />
										<div>
											<div class="font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">{{ $t('pages.profile.lastUpdated') }}</div>
											<div class="tnum mt-0.5 text-sm text-bone-100">{{ formatDate(profile.updated_at) }}</div>
										</div>
									</div>

									<hr class="my-3 border-ink-700/60" />

									<div class="flex items-start gap-3">
										<component
											:is="profile.email_verified_at ? ShieldCheck : TriangleAlert"
											class="mt-0.5 h-4 w-4"
											:class="profile.email_verified_at ? 'text-sage-400' : 'text-ochre-400'"
										/>
										<div>
											<div class="font-mono text-[11px] uppercase tracking-[0.12em] text-bone-500">{{ $t('pages.profile.emailStatus') }}</div>
											<div class="mt-0.5 text-sm text-bone-100">
												{{ profile.email_verified_at ? $t('pages.profile.verified') : $t('pages.profile.notVerified') }}
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<hr class="my-6 border-ink-700/60" />

						<div class="flex justify-end gap-2">
							<ui-button variant="ghost" :disabled="loading" @click="resetForm">
								{{ $t('common.cancel') }}
							</ui-button>
							<ui-button variant="primary" :loading="loading" @click="updateProfile">
								{{ $t('pages.profile.saveChanges') }}
							</ui-button>
						</div>
					</ui-form>
				</div>

				<!-- Security Tab -->
				<div v-if="tab === 'security'">
					<!-- Password Section -->
					<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
						<Lock class="h-4 w-4 text-bone-500" />
						{{ $t('pages.profile.changePassword') }}
					</div>

					<div class="max-w-xl space-y-4">
						<div class="flex items-end gap-2">
							<ui-input
								v-model="passwordData.current_password"
								:label="$t('pages.profile.currentPassword')"
								:icon="Lock"
								:type="showCurrentPassword ? 'text' : 'password'"
								wrapper-class="flex-1"
							/>
							<ui-button
								variant="ghost"
								:icon="showCurrentPassword ? EyeOff : Eye"
								@click="showCurrentPassword = !showCurrentPassword"
							/>
						</div>

						<div class="flex items-end gap-2">
							<ui-input
								v-model="passwordData.new_password"
								:label="$t('pages.profile.newPassword')"
								:icon="Lock"
								:type="showNewPassword ? 'text' : 'password'"
								:rules="passwordData.new_password ? [rules.minLength] : []"
								wrapper-class="flex-1"
							/>
							<ui-button
								variant="ghost"
								:icon="showNewPassword ? EyeOff : Eye"
								@click="showNewPassword = !showNewPassword"
							/>
						</div>

						<div class="flex items-end gap-2">
							<ui-input
								v-model="passwordData.new_password_confirmation"
								:label="$t('pages.profile.confirmPassword')"
								:icon="Lock"
								:type="showConfirmPassword ? 'text' : 'password'"
								:rules="passwordData.new_password ? [rules.passwordMatch] : []"
								wrapper-class="flex-1"
							/>
							<ui-button
								variant="ghost"
								:icon="showConfirmPassword ? EyeOff : Eye"
								@click="showConfirmPassword = !showConfirmPassword"
							/>
						</div>

						<ui-alert type="info" :title="$t('pages.profile.passwordRequirements')">
							{{ $t('pages.profile.passwordRequirementsText') }}
							{{ $t('pages.profile.passwordAdvice') }}
						</ui-alert>

						<ui-button
							variant="primary"
							:icon="ShieldCheck"
							:loading="loading"
							:disabled="!passwordData.current_password || !passwordData.new_password"
							class="mt-2"
							@click="updateProfile"
						>
							{{ $t('pages.profile.updatePassword') }}
						</ui-button>
					</div>

					<hr class="my-8 border-ink-700/60" />

					<!-- 2FA Section -->
					<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
						<ShieldCheck class="h-4 w-4 text-bone-500" />
						{{ $t('pages.profile.twoFactorAuthentication') }}
					</div>

					<div class="rounded-lg border border-ink-700/60 p-4">
						<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
							<div class="flex items-center gap-3">
								<component
									:is="twoFactorStatus.enabled ? ShieldCheck : ShieldAlert"
									class="h-6 w-6 shrink-0"
									:class="twoFactorStatus.enabled ? 'text-sage-400' : 'text-ochre-400'"
								/>
								<div>
									<div class="text-sm font-semibold text-bone-100">
										{{ twoFactorStatus.enabled ? $t('pages.profile.twoFactorEnabled') : $t('pages.profile.twoFactorDisabled') }}
									</div>
									<div class="text-xs text-bone-500">
										{{ twoFactorStatus.enabled
											? $t('pages.profile.twoFactorEnabledHint')
											: $t('pages.profile.twoFactorDisabledHint')
										}}
									</div>
								</div>
							</div>
							<ui-chip
								:color="twoFactorStatus.enabled ? 'success' : 'warning'"
								:text="twoFactorStatus.enabled ? $t('pages.profile.twoFactorActive') : $t('pages.profile.twoFactorInactive')"
							/>
						</div>

						<hr class="mb-4 border-ink-700/60" />

						<!-- When 2FA is enabled -->
						<div v-if="twoFactorStatus.enabled">
							<ui-alert type="success" class="mb-4">
								{{ $t('pages.profile.twoFactorAlertText') }}
							</ui-alert>

							<!-- 2FA Management Actions -->
							<div class="mb-6 flex gap-2">
								<ui-button
									variant="danger-ghost"
									:icon="RefreshCw"
									:loading="resetting2FA"
									@click="reset2FADialog = true"
								>
									{{ $t('pages.profile.reset2FA') }}
								</ui-button>
							</div>

							<hr class="mb-4 border-ink-700/60" />

							<!-- Trusted Devices Section -->
							<div class="mb-6">
								<div class="mb-3 flex flex-wrap items-center justify-between gap-3">
									<div>
										<div class="text-sm font-semibold text-bone-100">{{ $t('pages.profile.trustedDevices') }}</div>
										<div class="text-xs text-bone-500"><span class="tnum">{{ twoFactorStatus.trusted_devices_count }}</span> {{ $t('pages.profile.devicesTrusted') }}</div>
									</div>
									<ui-button
										variant="ghost"
										size="sm"
										:icon="RefreshCw"
										:loading="loadingDevices"
										@click="fetchTrustedDevices"
									>
										{{ $t('pages.profile.refreshDevices') }}
									</ui-button>
								</div>

								<div class="rounded-lg border border-ink-700/60">
									<div v-if="loadingDevices" class="py-8 text-center">
										<div class="flex justify-center">
											<ui-spinner :size="28" />
										</div>
										<div class="mt-2 text-xs text-bone-500">{{ $t('pages.profile.loadingDevices') }}</div>
									</div>
									<div v-else-if="trustedDevices.length === 0" class="py-8 text-center">
										<MonitorSmartphone class="mx-auto mb-2 h-10 w-10 text-bone-700" />
										<div class="text-sm text-bone-500">{{ $t('pages.profile.noDevicesFound') }}</div>
									</div>
									<ul v-else class="divide-y divide-ink-700/60">
										<li
											v-for="device in trustedDevices"
											:key="device.fingerprint"
											class="flex items-start gap-3 px-4 py-3"
										>
											<component
												:is="getDeviceIcon(device.user_agent)"
												class="mt-0.5 h-5 w-5 shrink-0"
												:class="device.is_current ? 'text-brass-400' : 'text-bone-500'"
											/>

											<div class="min-w-0 flex-1">
												<div class="flex flex-wrap items-center gap-2 text-sm text-bone-100">
													{{ formatUserAgent(device.user_agent) }}
													<ui-chip
														v-if="device.is_current"
														color="brass"
														:text="$t('pages.profile.currentDevice')"
													/>
												</div>
												<div class="mt-1 space-y-0.5 text-xs text-bone-500">
													<div class="flex items-center gap-1.5">
														<Clock class="h-3 w-3" />
														{{ $t('pages.profile.addedAt') }} <span class="tnum">{{ device.added_at_human }}</span>
													</div>
													<div class="flex items-center gap-1.5">
														<Clock class="h-3 w-3" />
														{{ $t('pages.profile.expires') }} <span class="tnum">{{ device.expires_at_human }}</span>
													</div>
												</div>
											</div>

											<ui-button
												v-if="!device.is_current"
												variant="danger-ghost"
												size="sm"
												:icon="Trash2"
												:loading="removingDevice === device.fingerprint"
												@click="removeDevice(device.fingerprint)"
											/>
											<ui-tooltip v-else :text="$t('pages.profile.cannotRemoveCurrentDevice')">
												<Lock class="mt-1 h-4 w-4 text-bone-700" />
											</ui-tooltip>
										</li>
									</ul>
								</div>
							</div>

							<!-- Recovery Codes Section -->
							<div class="mb-2">
								<div class="mb-3">
									<div class="text-sm font-semibold text-bone-100">{{ $t('pages.profile.recoveryCodes') }}</div>
									<div class="text-xs text-bone-500">{{ $t('pages.profile.recoveryCodesHint') }}</div>
								</div>

								<div class="rounded-lg border border-ink-700/60 p-4">
									<div v-if="!showingRecoveryCodes" class="py-4 text-center">
										<KeyRound class="mx-auto mb-2 h-10 w-10 text-bone-700" />
										<div class="mb-4 text-sm text-bone-500">
											{{ $t('pages.profile.recoveryCodesHidden') }}
										</div>
										<div class="flex flex-wrap justify-center gap-2">
											<ui-button
												variant="outline"
												:icon="Eye"
												:loading="loadingRecoveryCodes"
												@click="fetchRecoveryCodes"
											>
												{{ $t('pages.profile.viewCodes') }}
											</ui-button>
											<ui-button
												variant="danger-ghost"
												:icon="RefreshCw"
												:loading="loadingRecoveryCodes"
												@click="regenerateCodesDialog = true"
											>
												{{ $t('pages.profile.regenerate') }}
											</ui-button>
										</div>
									</div>

									<div v-else>
										<ui-alert type="warning" :title="$t('pages.profile.saveTheseCodes')" class="mb-4">
											{{ $t('pages.profile.recoveryCodesWarning') }}
										</ui-alert>

										<div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
											<div
												v-for="(code, index) in recoveryCodes"
												:key="index"
												class="rounded-md border border-ink-700/60 bg-ink-850 px-3 py-2 text-center"
											>
												<code class="tnum text-sm text-bone-100">{{ code }}</code>
											</div>
										</div>

										<div class="flex flex-wrap justify-center gap-2">
											<ui-button variant="outline" :icon="Copy" @click="copyRecoveryCodes">
												{{ $t('pages.profile.copyAll') }}
											</ui-button>
											<ui-button variant="outline" :icon="Download" @click="downloadRecoveryCodes">
												{{ $t('common.download') }}
											</ui-button>
											<ui-button variant="ghost" :icon="EyeOff" @click="hideRecoveryCodes" />
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- When 2FA is disabled -->
						<div v-else>
							<ui-alert type="warning" class="mb-4">
								{{ $t('pages.profile.twoFactorDisabledAlert') }}
							</ui-alert>

							<ui-button variant="primary" :icon="ShieldPlus" @click="$router.push('/2fa/setup')">
								{{ $t('pages.profile.enable2FA') }}
							</ui-button>
						</div>
					</div>
				</div>

				<!-- Activity Tab -->
				<div v-if="tab === 'activity'">
					<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
						<ChartLine class="h-4 w-4 text-bone-500" />
						{{ $t('pages.profile.yourActivity') }}
					</div>

					<div v-if="statistics" class="rounded-lg border border-ink-700/60 p-4">
						<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
							<div>
								<div class="text-sm font-semibold text-bone-100">{{ $t('pages.profile.activityHeatmap') }}</div>
								<div class="text-xs text-bone-500">{{ $t('pages.profile.activityHeatmapHint') }}</div>
							</div>
							<div class="heatmap-legend">
								<div class="heatmap-cell legend-0"></div>
								<div class="heatmap-cell legend-1"></div>
								<div class="heatmap-cell legend-2"></div>
								<div class="heatmap-cell legend-3"></div>
								<div class="heatmap-cell legend-4"></div>
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
									<div class="day-label">{{ weekdaysArray[1] }}</div>
									<div class="day-label"></div>
									<div class="day-label">{{ weekdaysArray[3] }}</div>
									<div class="day-label"></div>
									<div class="day-label">{{ weekdaysArray[5] }}</div>
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
					</div>

					<ui-alert v-else type="info" class="mt-4">
						{{ $t('pages.profile.startTrackingTime') }}
					</ui-alert>
				</div>

				<!-- Info & Legal Tab -->
				<div v-if="tab === 'info'">
					<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
						<Info class="h-4 w-4 text-bone-500" />
						{{ $t('pages.profile.informationLegal') }}
					</div>

					<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
						<button
							type="button"
							class="rounded-lg border border-ink-700/60 text-left transition-colors hover:bg-ink-850"
							@click="$router.push({ name: 'Privacy' })"
						>
							<div class="p-5">
								<div class="mb-3 flex items-center gap-3">
									<Shield class="h-6 w-6 shrink-0 text-bone-500" />
									<div>
										<div class="text-[15px] font-semibold text-bone-100">{{ $t('pages.profile.privacyNotice') }}</div>
										<div class="text-xs text-bone-500">{{ $t('pages.profile.privacyNoticeHint') }}</div>
									</div>
								</div>
								<p class="text-sm text-bone-300">
									{{ $t('pages.profile.privacyNoticeDescription') }}
								</p>
							</div>
							<div class="flex items-center justify-end gap-1.5 border-t border-ink-700/60 px-5 py-3 text-sm font-medium text-bone-300">
								{{ $t('pages.profile.readPrivacyNotice') }}
								<ArrowRight class="h-4 w-4" />
							</div>
						</button>

						<button
							type="button"
							class="rounded-lg border border-ink-700/60 text-left transition-colors hover:bg-ink-850"
							@click="$router.push({ name: 'Imprint' })"
						>
							<div class="p-5">
								<div class="mb-3 flex items-center gap-3">
									<Scale class="h-6 w-6 shrink-0 text-bone-500" />
									<div>
										<div class="text-[15px] font-semibold text-bone-100">{{ $t('pages.profile.imprint') }}</div>
										<div class="text-xs text-bone-500">{{ $t('pages.profile.imprintHint') }}</div>
									</div>
								</div>
								<p class="text-sm text-bone-300">
									{{ $t('pages.profile.imprintDescription') }}
								</p>
							</div>
							<div class="flex items-center justify-end gap-1.5 border-t border-ink-700/60 px-5 py-3 text-sm font-medium text-bone-300">
								{{ $t('pages.profile.readImprint') }}
								<ArrowRight class="h-4 w-4" />
							</div>
						</button>
					</div>

					<hr class="my-8 border-ink-700/60" />

					<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
						<CircleHelp class="h-4 w-4 text-bone-500" />
						{{ $t('pages.profile.applicationInformation') }}
					</div>

					<div class="rounded-lg border border-ink-700/60 p-5">
						<div class="flex items-start gap-3">
							<AppWindow class="mt-0.5 h-4 w-4 text-bone-500" />
							<div>
								<div class="text-sm font-medium text-bone-100">{{ $t('pages.profile.applicationName') }}</div>
								<div class="text-sm text-bone-300">{{ $t('pages.profile.applicationNameValue') }}</div>
							</div>
						</div>

						<hr class="my-3 border-ink-700/60" />

						<div class="flex items-start gap-3">
							<ShieldCheck class="mt-0.5 h-4 w-4 text-bone-500" />
							<div>
								<div class="text-sm font-medium text-bone-100">{{ $t('pages.profile.securityTitle') }}</div>
								<div class="text-sm text-bone-300">{{ $t('pages.profile.securityDescription') }}</div>
							</div>
						</div>

						<hr class="my-3 border-ink-700/60" />

						<div class="flex items-start gap-3">
							<Lock class="mt-0.5 h-4 w-4 text-bone-500" />
							<div>
								<div class="text-sm font-medium text-bone-100">{{ $t('pages.profile.accessTitle') }}</div>
								<div class="text-sm text-bone-300">{{ $t('pages.profile.accessDescription') }}</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</ui-card>

		<!-- Reset 2FA Confirmation Dialog -->
		<ui-dialog v-model="reset2FADialog" :title="$t('pages.profile.resetTwoFactorAuthentication')" max-width="600px" persistent>
			<ui-alert type="warning" :title="$t('common.warning')" class="mb-4">
				{{ $t('pages.profile.resetTwoFactorWarning') }}
			</ui-alert>

			<p class="mb-4 text-sm text-bone-300">{{ $t('pages.profile.resetTwoFactorConfirmation') }}</p>

			<p class="mb-2 text-sm text-bone-300">{{ $t('pages.profile.resetTwoFactorWill') }}:</p>
			<ul class="mb-4 ml-5 list-disc space-y-1 text-sm text-bone-300">
				<li>{{ $t('pages.profile.resetTwoFactorItem1') }}</li>
				<li>{{ $t('pages.profile.resetTwoFactorItem2') }}</li>
				<li>{{ $t('pages.profile.resetTwoFactorItem3') }}</li>
				<li>{{ $t('pages.profile.resetTwoFactorItem4') }}</li>
			</ul>

			<p class="mb-4 text-sm text-bone-300">{{ $t('pages.profile.resetTwoFactorRedirect') }}</p>

			<ui-input
				v-model="reset2FAPassword"
				:label="$t('pages.profile.enterPasswordToConfirm')"
				type="password"
				:icon="Lock"
				autofocus
			/>

			<template #actions>
				<ui-button variant="ghost" @click="reset2FADialog = false; reset2FAPassword = ''">
					{{ $t('common.cancel') }}
				</ui-button>
				<ui-button
					variant="danger"
					:loading="resetting2FA"
					:disabled="!reset2FAPassword"
					@click="handleReset2FA"
				>
					{{ $t('pages.profile.reset2FA') }}
				</ui-button>
			</template>
		</ui-dialog>

		<!-- Regenerate Recovery Codes Confirmation Dialog -->
		<ui-dialog v-model="regenerateCodesDialog" :title="$t('pages.profile.regenerateRecoveryCodes')" max-width="600px" persistent>
			<ui-alert type="warning" :title="$t('common.warning')" class="mb-4">
				{{ $t('pages.profile.regenerateCodesWarning') }}
			</ui-alert>

			<p class="mb-4 text-sm text-bone-300">
				{{ $t('pages.profile.regenerateCodesConfirmation') }}
			</p>

			<p class="text-sm text-bone-300">
				{{ $t('pages.profile.regenerateCodesDescription') }}
			</p>

			<template #actions>
				<ui-button variant="ghost" @click="regenerateCodesDialog = false">
					{{ $t('common.cancel') }}
				</ui-button>
				<ui-button
					variant="danger"
					:loading="loadingRecoveryCodes"
					@click="handleRegenerateRecoveryCodes"
				>
					{{ $t('pages.profile.regenerateCodes') }}
				</ui-button>
			</template>
		</ui-dialog>
	</div>
</template>

<script>
import { mapActions, mapState } from 'pinia'
import { store } from '../store'
import axios from 'axios'
import { formatDate, formatCurrency } from '../utils/formatters'
import { useI18n } from 'vue-i18n'
import {
	User,
	Camera,
	CircleCheck,
	Briefcase,
	Clock,
	Banknote,
	Folders,
	UserPen,
	Info,
	Mail,
	Calendar,
	RefreshCw,
	Shield,
	ShieldCheck,
	ShieldAlert,
	ShieldPlus,
	TriangleAlert,
	History,
	Lock,
	Eye,
	EyeOff,
	Trash2,
	Copy,
	Download,
	KeyRound,
	MonitorSmartphone,
	Smartphone,
	Tablet,
	Laptop,
	ChartLine,
	Scale,
	ArrowRight,
	CircleHelp,
	AppWindow,
} from 'lucide-vue-next'

export default {
	name: 'Profile',
	components: {
		User,
		Camera,
		Briefcase,
		Clock,
		Banknote,
		Folders,
		Info,
		Calendar,
		RefreshCw,
		Shield,
		ShieldCheck,
		Lock,
		MonitorSmartphone,
		KeyRound,
		ChartLine,
		Scale,
		ArrowRight,
		CircleHelp,
		AppWindow,
	},
	setup() {
		const { t } = useI18n()
		return {
			t,
			User,
			CircleCheck,
			Mail,
			Lock,
			Eye,
			EyeOff,
			ShieldCheck,
			ShieldAlert,
			ShieldPlus,
			TriangleAlert,
			RefreshCw,
			Trash2,
			Copy,
			Download,
		}
	},
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
		...mapState(store, ['settings']),
		profileTabs() {
			return [
				{ value: 'personal', label: this.t('pages.profile.personalInfo'), icon: UserPen },
				{ value: 'security', label: this.t('pages.profile.security'), icon: ShieldCheck },
				{ value: 'activity', label: this.t('pages.profile.activity'), icon: History },
				{ value: 'info', label: 'Info & Legal', icon: Info },
			]
		},
		weekdaysArray() {
			try {
				if (!window.$i18n?.global?.locale?.value) return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
				const locale = window.$i18n.global.locale.value
				const data = window.$i18n.global.getLocaleMessage(locale)?.pages?.profile?.weekdays
				return data || ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
			} catch (e) {
				console.error('Error getting weekdaysArray:', e)
				return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
			}
		},
		monthsArray() {
			try {
				if (!window.$i18n?.global?.locale?.value) return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
				const locale = window.$i18n.global.locale.value
				const data = window.$i18n.global.getLocaleMessage(locale)?.pages?.profile?.months
				return data || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
			} catch (e) {
				console.error('Error getting monthsArray:', e)
				return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
			}
		}
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
			if (this.tab === 'personal' && this.$refs.form) {
				const { valid } = await this.$refs.form.validate()
				if (!valid) return
			}

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

				months.push({
					name: this.monthsArray[month],
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
	},		getHeatmapClass(count) {
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
				return Smartphone
			} else if (ua.includes('tablet') || ua.includes('ipad')) {
				return Tablet
			} else {
				return Laptop
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
</script>

<style scoped>
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
	color: var(--color-bone-500);
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
	color: var(--color-bone-500);
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
	outline: 1.5px solid var(--color-brass-400);
	outline-offset: 1px;
}

.heatmap-cell.level-0,
.heatmap-cell.legend-0 {
	background-color: var(--color-ink-800);
}

.heatmap-cell.level-1,
.heatmap-cell.legend-1 {
	background-color: color-mix(in srgb, var(--color-sage-500) 30%, var(--color-ink-900));
}

.heatmap-cell.level-2,
.heatmap-cell.legend-2 {
	background-color: color-mix(in srgb, var(--color-sage-500) 55%, var(--color-ink-900));
}

.heatmap-cell.level-3,
.heatmap-cell.legend-3 {
	background-color: var(--color-sage-500);
}

.heatmap-cell.level-4,
.heatmap-cell.legend-4 {
	background-color: var(--color-sage-400);
}

.heatmap-legend {
	display: flex;
	gap: 3px;
}

.heatmap-legend .heatmap-cell {
	width: 12px;
}
</style>
