<template>
	<v-container fluid class="settings-container">
		<!-- Hero Section -->
		<v-row>
			<v-col cols="12">
				<v-card class="settings-hero" elevation="0">
					<div class="hero-gradient"></div>
					<v-card-text class="text-center position-relative">
						<div class="avatar-wrapper">
							<v-avatar 
								size="150" 
								class="settings-avatar elevation-8"
							>
								<v-icon size="80" color="white">mdi-cog</v-icon>
							</v-avatar>
						</div>
						<h2 class="text-h4 font-weight-bold mt-6 text-white">{{ $t('pages.settings.systemSettings') }}</h2>
						<p class="text-subtitle-1 text-white mb-0">{{ $t('pages.settings.configurePreferences') }}</p>
					</v-card-text>
				</v-card>
			</v-col>
		</v-row>

		<!-- Non-Admin View -->
		<v-row v-if="!isAdmin">
			<v-col cols="12">
				<v-card elevation="2">
					<v-card-text>
						<v-alert type="info" variant="tonal">
							{{ $t('pages.settings.settingsManagedByAdmin') }}
						</v-alert>
					</v-card-text>
				</v-card>
			</v-col>
		</v-row>

		<!-- Admin Settings Tabs -->
		<v-row v-else class="mt-2">
			<v-col cols="12">
				<v-card elevation="2">
					<v-tabs v-model="tab" bg-color="primary" dark>
						<v-tab value="company">
							<v-icon start>mdi-domain</v-icon>
							{{ $t('pages.settings.company') }}
						</v-tab>
						<v-tab value="localization">
							<v-icon start>mdi-earth</v-icon>
							{{ $t('pages.settings.localization') }}
						</v-tab>
						<v-tab value="financial">
							<v-icon start>mdi-currency-usd</v-icon>
							{{ $t('pages.settings.financialInvoices') }}
						</v-tab>
						<v-tab value="datetime">
							<v-icon start>mdi-calendar-clock</v-icon>
							{{ $t('pages.settings.dateTime') }}
						</v-tab>
						<v-tab value="email">
							<v-icon start>mdi-email</v-icon>
							{{ $t('pages.settings.email') }}
						</v-tab>
						<v-tab value="legal">
							<v-icon start>mdi-gavel</v-icon>
							{{ $t('pages.settings.legal') }}
						</v-tab>
					</v-tabs>

					<v-card-text class="pa-8">
						<v-window v-model="tab">
							<!-- Company Settings Tab -->
							<v-window-item value="company">
								<v-form ref="companyForm">
									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-office-building</v-icon>
										{{ $t('pages.settings.companyInformation') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_name"
												:label="$t('pages.settings.companyName')"
												variant="outlined"
												prepend-inner-icon="mdi-domain"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_email"
												:label="$t('pages.settings.companyEmail')"
												variant="outlined"
												prepend-inner-icon="mdi-email"
												density="comfortable"
												type="email"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_phone"
												:label="$t('pages.settings.companyPhone')"
												variant="outlined"
												prepend-inner-icon="mdi-phone"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_website"
												:label="$t('pages.settings.websiteUrl')"
												variant="outlined"
												prepend-inner-icon="mdi-web"
												density="comfortable"
												type="url"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_vat_id"
												:label="$t('pages.settings.vatId')"
												variant="outlined"
												prepend-inner-icon="mdi-identifier"
												density="comfortable"
											></v-text-field>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-map-marker</v-icon>
										{{ $t('pages.settings.companyAddress') }}
									</div>

									<v-row>
										<v-col cols="12" md="8">
											<v-text-field
												v-model="settings.company_address_street"
												:label="$t('pages.settings.street')"
												variant="outlined"
												prepend-inner-icon="mdi-road"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="4">
											<v-text-field
												v-model="settings.company_address_number"
												:label="$t('pages.settings.number')"
												variant="outlined"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="4">
											<v-text-field
												v-model="settings.company_address_zipcode"
												:label="$t('pages.settings.zipCode')"
												variant="outlined"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="8">
											<v-text-field
												v-model="settings.company_address_city"
												:label="$t('pages.settings.city')"
												variant="outlined"
												prepend-inner-icon="mdi-city"
												density="comfortable"
											></v-text-field>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-bank</v-icon>
										{{ $t('pages.settings.bankInformation') }}
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.company_bank_info"
												:label="$t('pages.settings.bankInfo')"
												variant="outlined"
												prepend-inner-icon="mdi-bank"
												density="comfortable"
												rows="4"
												:hint="$t('pages.settings.bankInfoHint')"
												persistent-hint
											></v-textarea>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-image</v-icon>
										{{ $t('pages.settings.companyLogo') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<div class="logo-upload-area" @click="triggerLogoInput">
												<div v-if="settings.company_logo" class="logo-preview mb-4">
													<v-img :src="settings.company_logo" max-height="120" max-width="200" contain></v-img>
												</div>
												<div v-else class="upload-placeholder">
													<v-icon size="60" color="grey">mdi-image-plus</v-icon>
													<p class="text-body-2 text-medium-emphasis mt-2">{{ $t('pages.settings.clickToUpload') }}</p>
												</div>
												<input 
													ref="logoInput" 
													type="file" 
													accept="image/*" 
													style="display: none" 
													@change="onLogoSelected"
												/>
											</div>
											<p class="text-caption text-medium-emphasis mt-2">
												{{ $t('pages.settings.logoRecommendation') }}
												<v-btn 
													v-if="settings.company_logo" 
													size="small" 
													color="error" 
													variant="text" 
													@click.stop="removeLogo"
												>
													{{ $t('pages.settings.removeLogo') }}
												</v-btn>
											</p>
										</v-col>
									</v-row>
								</v-form>
							</v-window-item>

							<!-- Localization Settings Tab -->
							<v-window-item value="localization">
								<v-form ref="localizationForm">
									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-language</v-icon>
										{{ $t('pages.settings.languageLocalization') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-select
												v-model="settings.language"
												:items="languageOptionsComputed"
												:label="$t('pages.settings.applicationLanguage')"
												variant="outlined"
												prepend-inner-icon="mdi-earth"
												density="comfortable"
												:hint="$t('pages.settings.languageHint')"
												persistent-hint
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="tonal" color="info">
												<v-card-text>
													<div class="text-subtitle-2 mb-2">{{ $t('pages.settings.currentLanguage') }}:</div>
													<div class="text-h6">{{ getLanguageName(settings.language) }}</div>
												</v-card-text>
											</v-card>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<v-alert type="info" variant="tonal">
										<strong>{{ $t('pages.settings.note') }}:</strong> {{ $t('pages.settings.languageChangeAffects') }}
										<ul>
											<li>{{ $t('pages.settings.languageChangeInvoices') }}</li>
											<li>{{ $t('pages.settings.languageChangeEmails') }}</li>
											<li>{{ $t('pages.settings.languageChangeFormats') }}</li>
										</ul>
									</v-alert>
								</v-form>
							</v-window-item>

							<!-- Financial & Invoice Settings Tab -->
							<v-window-item value="financial">
								<v-form ref="financialForm">
									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-currency-usd</v-icon>
										{{ $t('pages.settings.currencyTax') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-select
												v-model="settings.currency_symbol"
												:items="currencyOptions"
												:label="$t('pages.settings.currency')"
												variant="outlined"
												prepend-inner-icon="mdi-currency-usd"
												density="comfortable"
												item-title="title"
												item-value="value"
												@update:model-value="updateCurrencyCode"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.tax_rate"
												:label="$t('pages.settings.taxRate')"
												variant="outlined"
												prepend-inner-icon="mdi-percent"
												density="comfortable"
												type="number"
												step="0.01"
												:hint="$t('pages.settings.taxRateHint')"
												persistent-hint
											></v-text-field>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-file-document</v-icon>
										{{ $t('pages.settings.invoiceConfiguration') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.invoice_prefix"
												:label="$t('pages.settings.invoicePrefix')"
												variant="outlined"
												prepend-inner-icon="mdi-format-text"
												density="comfortable"
												:hint="$t('pages.settings.invoicePrefixHint')"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-select
												v-model="settings.invoice_number_format"
												:items="invoiceNumberFormats"
												:label="$t('pages.settings.invoiceNumberFormat')"
												variant="outlined"
												prepend-inner-icon="mdi-format-list-numbered"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>

									<v-col cols="12" md="6">
										<v-switch
											v-model="settings.invoice_number_random"
											:label="$t('pages.settings.randomInvoiceNumbers')"
											color="primary"
											:hint="$t('pages.settings.randomInvoiceNumbersHint')"
											persistent-hint
										></v-switch>
									</v-col>

									<v-col cols="12" md="6">
										<v-text-field
											v-model="settings.invoice_number_random_length"
											:label="$t('pages.settings.randomNumberLength')"
											variant="outlined"
											prepend-inner-icon="mdi-numeric"
											density="comfortable"
											type="number"
											min="4"
											max="20"
											:disabled="!settings.invoice_number_random"
											:hint="$t('pages.settings.randomNumberLengthHint')"
											persistent-hint
										></v-text-field>
									</v-col>

									<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.invoice_number_start"
												:label="$t('pages.settings.startingInvoiceNumber')"
												variant="outlined"
												prepend-inner-icon="mdi-numeric"
												density="comfortable"
												type="number"
												:disabled="settings.invoice_number_random"
												:hint="$t('pages.settings.startingInvoiceNumberHint')"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-select
												v-model="settings.invoice_default_status"
												:items="invoiceStatuses"
												:label="$t('pages.settings.defaultInvoiceStatus')"
												variant="outlined"
												prepend-inner-icon="mdi-tag"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-switch
												v-model="settings.invoice_auto_send"
												:label="$t('pages.settings.autoSendInvoices')"
												color="primary"
												:hint="$t('pages.settings.autoSendInvoicesHint')"
												persistent-hint
											></v-switch>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-text-box</v-icon>
										{{ $t('pages.settings.invoiceContent') }}
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.invoice_default_message"
												:label="$t('pages.settings.defaultInvoiceMessage')"
												variant="outlined"
												prepend-inner-icon="mdi-message-text"
												density="comfortable"
												rows="3"
												:hint="$t('pages.settings.defaultInvoiceMessageHint')"
												persistent-hint
											></v-textarea>
										</v-col>

										<v-col cols="12">
											<v-textarea
												v-model="settings.invoice_payment_terms"
												:label="$t('pages.settings.paymentTerms')"
												variant="outlined"
												prepend-inner-icon="mdi-file-document-outline"
												density="comfortable"
												rows="4"
												:hint="$t('pages.settings.paymentTermsHint')"
												persistent-hint
											></v-textarea>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-page-layout-footer</v-icon>
										{{ $t('pages.settings.invoiceFooterLayout') }}
									</div>

									<v-row>
										<v-col cols="12" md="4">
											<v-select
												v-model="settings.invoice_footer_col1"
												:items="footerColumnOptions"
												:label="$t('pages.settings.leftColumn')"
												variant="outlined"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>

										<v-col cols="12" md="4">
											<v-select
												v-model="settings.invoice_footer_col2"
												:items="footerColumnOptions"
												:label="$t('pages.settings.centerColumn')"
												variant="outlined"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>

										<v-col cols="12" md="4">
											<v-select
												v-model="settings.invoice_footer_col3"
												:items="footerColumnOptions"
												:label="$t('pages.settings.rightColumn')"
												variant="outlined"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>
									</v-row>
								</v-form>
							</v-window-item>

							<!-- Date & Time Settings Tab -->
							<v-window-item value="datetime">
								<v-form ref="datetimeForm">
									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-calendar</v-icon>
										{{ $t('pages.settings.dateTimeFormats') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-select
												v-model="settings.date_format"
												:items="dateFormats"
												:label="$t('pages.settings.dateFormat')"
												variant="outlined"
												prepend-inner-icon="mdi-calendar"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="tonal" color="info">
												<v-card-text>
													<div class="text-subtitle-2 mb-2">{{ $t('pages.settings.preview') }}:</div>
													<div class="text-h6">{{ formatPreviewDate(new Date()) }}</div>
												</v-card-text>
											</v-card>
										</v-col>

										<v-col cols="12" md="6">
											<v-select
												v-model="settings.time_format"
												:items="timeFormats"
												:label="$t('pages.settings.timeFormat')"
												variant="outlined"
												prepend-inner-icon="mdi-clock"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="tonal" color="info">
												<v-card-text>
													<div class="text-subtitle-2 mb-2">{{ $t('pages.settings.preview') }}:</div>
													<div class="text-h6">{{ formatPreviewTime(new Date()) }}</div>
												</v-card-text>
											</v-card>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-numeric</v-icon>
										{{ $t('pages.settings.numberFormats') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-select
												v-model="settings.number_format"
												:items="numberFormats"
												:label="$t('pages.settings.numberFormat')"
												variant="outlined"
												prepend-inner-icon="mdi-numeric"
												density="comfortable"
												item-title="title"
												item-value="value"
												:hint="$t('pages.settings.numberFormatHint')"
												persistent-hint
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="tonal" color="info">
												<v-card-text>
													<div class="text-subtitle-2 mb-2">{{ $t('pages.settings.preview') }}:</div>
													<div class="text-h6">{{ formatPreviewNumber(1234567.89) }}</div>
												</v-card-text>
											</v-card>
										</v-col>
									</v-row>
								</v-form>
							</v-window-item>

							<!-- Email Settings Tab -->
							<v-window-item value="email">
								<v-form ref="emailForm">
									<v-alert type="warning" variant="tonal" class="mb-4">
										<strong>{{ $t('pages.settings.important') }}:</strong> {{ $t('pages.settings.emailSettingsWarning') }}
									</v-alert>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-email-settings</v-icon>
										{{ $t('pages.settings.smtpConfiguration') }}
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_host"
												:label="$t('pages.settings.smtpHost')"
												variant="outlined"
												prepend-inner-icon="mdi-server"
												density="comfortable"
												:hint="$t('pages.settings.smtpHostHint')"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_port"
												:label="$t('pages.settings.smtpPort')"
												variant="outlined"
												prepend-inner-icon="mdi-network"
												density="comfortable"
												type="number"
												:hint="$t('pages.settings.smtpPortHint')"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_username"
												:label="$t('pages.settings.smtpUsername')"
												variant="outlined"
												prepend-inner-icon="mdi-account"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_password"
												:label="$t('pages.settings.smtpPassword')"
												variant="outlined"
												prepend-inner-icon="mdi-lock"
												:type="showMailPassword ? 'text' : 'password'"
												:append-inner-icon="showMailPassword ? 'mdi-eye-off' : 'mdi-eye'"
												@click:append-inner="showMailPassword = !showMailPassword"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-select
												v-model="settings.mail_encryption"
												:items="mailEncryptions"
												:label="$t('pages.settings.encryption')"
												variant="outlined"
												prepend-inner-icon="mdi-shield-lock"
												density="comfortable"
												item-title="title"
												item-value="value"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_from_address"
												:label="$t('pages.settings.fromEmailAddress')"
												variant="outlined"
												prepend-inner-icon="mdi-email"
												density="comfortable"
												type="email"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_from_name"
												:label="$t('pages.settings.fromName')"
												variant="outlined"
												prepend-inner-icon="mdi-account-circle"
												density="comfortable"
											></v-text-field>
										</v-col>
									</v-row>
								</v-form>
							</v-window-item>

							<!-- Legal Settings Tab -->
							<v-window-item value="legal">
								<v-form ref="legalForm">
									<v-alert type="info" variant="tonal" class="mb-4">
										<strong>{{ $t('pages.settings.legalInformation') }}:</strong> {{ $t('pages.settings.legalInformationHint') }}
									</v-alert>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-shield-account</v-icon>
										{{ $t('pages.settings.privacyNotice') }}
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.privacy_notice"
												:label="$t('pages.settings.privacyNoticeHtml')"
												variant="outlined"
												prepend-inner-icon="mdi-file-document-edit"
												density="comfortable"
												rows="10"
												:hint="$t('pages.settings.htmlFormattingHint')"
												persistent-hint
											></v-textarea>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-gavel</v-icon>
										{{ $t('pages.settings.imprint') }}
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.imprint"
												:label="$t('pages.settings.imprintHtml')"
												variant="outlined"
												prepend-inner-icon="mdi-file-document-edit"
												density="comfortable"
												rows="10"
												:hint="$t('pages.settings.htmlFormattingHint')"
												persistent-hint
											></v-textarea>
										</v-col>
									</v-row>
								</v-form>
							</v-window-item>
						</v-window>
					</v-card-text>

					<v-divider></v-divider>

					<v-card-actions class="pa-4">
						<v-spacer></v-spacer>
						<v-btn
							variant="text"
							@click="resetSettings"
							:disabled="loading"
						>
							{{ $t('common.reset') }}
						</v-btn>
						<v-btn
							color="primary"
							@click="saveSettings"
							:loading="loading"
							size="large"
						>
							<v-icon start>mdi-content-save</v-icon>
							{{ $t('pages.settings.saveAllSettings') }}
						</v-btn>
					</v-card-actions>
				</v-card>
			</v-col>
		</v-row>
	</v-container>
</template>

<script>
import { mapActions, mapGetters } from 'pinia'
import { store } from '../store'
import { formatNumber, formatDate, formatTime } from '../utils/formatters'
import { useLanguage } from '../composables/useLanguage'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

export default {
	name: 'Settings',
	setup() {
		const { setLanguage } = useLanguage()
		const { t } = useI18n()
		return { setLanguage, t }
	},
	data() {
		return {
			loading: false,
			tab: 'company',
			showMailPassword: false,
			settings: {
				// Company Information
				company_name: '',
				company_logo: '',
				company_address_street: '',
				company_address_number: '',
				company_address_zipcode: '',
				company_address_city: '',
				company_email: '',
				company_phone: '',
				company_vat_id: '',
				company_website: '',
				company_bank_info: '',
				
				// Localization
				language: 'en',
				
				// Financial & Invoice
				currency_symbol: '$',
				currency_code: 'USD',
				tax_rate: '',
				invoice_prefix: 'INV-',
				invoice_number_format: 'YYYY-MM-number',
				invoice_number_start: '1',
				invoice_number_random: false,
				invoice_number_random_length: 8,
				invoice_default_status: 'draft',
				invoice_auto_send: false,
				invoice_default_message: '',
				invoice_payment_terms: '',
				invoice_footer_col1: 'company_info',
				invoice_footer_col2: 'bank_info',
				invoice_footer_col3: 'page_info',
				
				// Date & Time
				date_format: 'DD/MM/YYYY',
				time_format: '24h',
				number_format: 'en-US',
				
				// Email
				mail_host: '',
				mail_port: '587',
				mail_username: '',
				 mail_password: '',
				mail_encryption: 'tls',
				mail_from_address: '',
				mail_from_name: '',
				
				// Legal
				privacy_notice: '',
				imprint: ''
			},
			originalSettings: {},
			
			// Dropdown options - language only in data (static)
			languageOptions: []
		}
	},
	computed: {
		...mapGetters(store, ['getUser']),
		isAdmin() {
			return this.getUser?.role === 'admin'
		},
		languageOptionsComputed() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.languages.english'), value: 'en' },
				{ title: this.t('pages.settings.dropdownOptions.languages.german'), value: 'de' },
				{ title: this.t('pages.settings.dropdownOptions.languages.french'), value: 'fr' },
				{ title: this.t('pages.settings.dropdownOptions.languages.italian'), value: 'it' },
				{ title: this.t('pages.settings.dropdownOptions.languages.spanish'), value: 'es' }
			]
		},
		currencyOptions() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.currencies.usd'), value: '$', code: 'USD' },
				{ title: this.t('pages.settings.dropdownOptions.currencies.eur'), value: '€', code: 'EUR' },
				{ title: this.t('pages.settings.dropdownOptions.currencies.gbp'), value: '£', code: 'GBP' },
				{ title: this.t('pages.settings.dropdownOptions.currencies.jpy'), value: '¥', code: 'JPY' },
				{ title: this.t('pages.settings.dropdownOptions.currencies.chf'), value: 'CHF', code: 'CHF' },
				{ title: this.t('pages.settings.dropdownOptions.currencies.cad'), value: 'C$', code: 'CAD' },
				{ title: this.t('pages.settings.dropdownOptions.currencies.aud'), value: 'A$', code: 'AUD' }
			];
		},
		invoiceNumberFormats() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.invoiceNumberFormats.yyyymmnumber'), value: 'YYYY-MM-number' },
				{ title: this.t('pages.settings.dropdownOptions.invoiceNumberFormats.yyyynumber'), value: 'YYYY-number' },
				{ title: this.t('pages.settings.dropdownOptions.invoiceNumberFormats.number'), value: 'number' }
			];
		},
		invoiceStatuses() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.invoiceStatuses.draft'), value: 'draft' },
				{ title: this.t('pages.settings.dropdownOptions.invoiceStatuses.sent'), value: 'sent' },
				{ title: this.t('pages.settings.dropdownOptions.invoiceStatuses.paid'), value: 'paid' },
				{ title: this.t('pages.settings.dropdownOptions.invoiceStatuses.overdue'), value: 'overdue' },
				{ title: this.t('pages.settings.dropdownOptions.invoiceStatuses.cancelled'), value: 'cancelled' }
			];
		},
		footerColumnOptions() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.footerColumns.companyInfo'), value: 'company_info' },
				{ title: this.t('pages.settings.dropdownOptions.footerColumns.bankInfo'), value: 'bankInfo' },
				{ title: this.t('pages.settings.dropdownOptions.footerColumns.pageInfo'), value: 'page_info' },
				{ title: this.t('pages.settings.dropdownOptions.footerColumns.empty'), value: 'empty' }
			];
		},
		dateFormats() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.ddmmyyyy'), value: 'DD/MM/YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.mmddyyyy'), value: 'MM/DD/YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.yyyymmdd'), value: 'YYYY-MM-DD' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.dMyyyy'), value: 'D/M/YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.ddmmyyyydot'), value: 'DD.MM.YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.ddmmyyyydash'), value: 'DD-MM-YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.yyyymmddslash'), value: 'YYYY/MM/DD' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.ddmmmyyyy'), value: 'DD MMM YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.dmmmmyyyy'), value: 'D MMMM YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.eeeeddmmmmyyyy'), value: 'EEEE, DD MMMM YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.eeeddmmmyyyy'), value: 'EEE, DD MMM YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.mmmddyyyy'), value: 'MMM D, YYYY' },
				{ title: this.t('pages.settings.dropdownOptions.dateFormats.mmmmddyyyy'), value: 'MMMM D, YYYY' }
			];
		},
		timeFormats() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.timeFormats.24h'), value: '24h' },
				{ title: this.t('pages.settings.dropdownOptions.timeFormats.24hss'), value: '24h:ss' },
				{ title: this.t('pages.settings.dropdownOptions.timeFormats.12h'), value: '12h' },
				{ title: this.t('pages.settings.dropdownOptions.timeFormats.12hss'), value: '12h:ss' },
				{ title: this.t('pages.settings.dropdownOptions.timeFormats.12hnozero'), value: '12h-nozero' },
				{ title: this.t('pages.settings.dropdownOptions.timeFormats.12hnozeroSs'), value: '12h-nozero:ss' }
			];
		},
		numberFormats() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.numberFormats.enUS'), value: 'en-US' },
				{ title: this.t('pages.settings.dropdownOptions.numberFormats.deDE'), value: 'de-DE' },
				{ title: this.t('pages.settings.dropdownOptions.numberFormats.frFR'), value: 'fr-FR' },
				{ title: this.t('pages.settings.dropdownOptions.numberFormats.enIN'), value: 'en-IN' }
			];
		},
		mailEncryptions() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.mailEncryptions.tls'), value: 'tls' },
				{ title: this.t('pages.settings.dropdownOptions.mailEncryptions.ssl'), value: 'ssl' },
				{ title: this.t('pages.settings.dropdownOptions.mailEncryptions.none'), value: 'null' }
			];
		}
	},
	mounted() {
		this.fetchLocalSettings()
	},
	methods: {
		...mapActions(store, ['showSnackbar', 'fetchSettings']),
		
		async fetchLocalSettings() {
			try {
				this.loading = true
				const response = await axios.get('/api/settings/batch')
				
				// Merge fetched settings with defaults
				if (response.data && typeof response.data === 'object') {
					Object.keys(this.settings).forEach(key => {
						if (response.data[key] !== undefined && response.data[key] !== null) {
							// Handle boolean conversion
							if (typeof this.settings[key] === 'boolean') {
								this.settings[key] = response.data[key] === true || response.data[key] === '1' || response.data[key] === 'true'
							} else {
								this.settings[key] = response.data[key]
							}
						}
					})
				}
				
				// Store original settings for reset
				this.originalSettings = { ...this.settings }
			} catch (error) {
				console.error('Error fetching settings:', error)
				if (error.response?.status !== 404) {
					this.showSnackbar('Failed to load settings', 'error')
				}
				// If 404, use defaults (first time setup)
				this.originalSettings = { ...this.settings }
			} finally {
				this.loading = false
			}
		},
		
		async saveSettings() {
			if (!this.isAdmin) return
			
			try {
				this.loading = true
				
				// Store the language before saving
				const newLanguage = this.settings.language
				const oldLanguage = this.originalSettings.language
				
				await axios.post('/api/settings/batch', this.settings)
				
				this.showSnackbar(this.t('notifications.settingsSaved'), 'success')
				
				// Update original settings
				this.originalSettings = { ...this.settings }
				
				// Apply language change if it was updated
				if (newLanguage !== oldLanguage) {
					this.setLanguage(newLanguage)
					console.log(`Language changed from ${oldLanguage} to ${newLanguage}`)
				}
				
				// Fetch settings fresh from the backend to update the store
				await this.fetchSettings()
			} catch (error) {
				console.error('Error saving settings:', error)
				this.showSnackbar(error.response?.data?.message || this.t('notifications.settingsFailedToSave'), 'error')
			} finally {
				this.loading = false
			}
		},
		
		resetSettings() {
			this.settings = { ...this.originalSettings }
		},
		
		triggerLogoInput() {
			this.$refs.logoInput.click()
		},
		
		onLogoSelected(event) {
			const file = event.target.files[0]
			if (!file) return

			// Check file size (max 2MB)
			if (file.size > 2 * 1024 * 1024) {
				this.showSnackbar(this.t('notifications.logoSizeTooLarge'), 'error')
				return
			}

			// Check file type
			if (!file.type.startsWith('image/')) {
				this.showSnackbar(this.t('notifications.logoMustBeImage'), 'error')
				return
			}

			// Convert to base64
			const reader = new FileReader()
			reader.onload = (e) => {
				this.settings.company_logo = e.target.result
			}
			reader.readAsDataURL(file)
		},
		
		removeLogo() {
			this.settings.company_logo = ''
			if (this.$refs.logoInput) {
				this.$refs.logoInput.value = ''
			}
		},
		
		updateCurrencyCode(value) {
			const currency = this.currencyOptions.find(c => c.value === value)
			if (currency) {
				this.settings.currency_code = currency.code
			}
		},
		
		formatPreviewNumber(value) {
			return formatNumber(value, 2, this.settings)
		},
		
		formatPreviewDate(date) {
			return formatDate(date, this.settings)
		},
		
		formatPreviewTime(date) {
			return formatTime(date, this.settings)
		},
		
		getLanguageName(code) {
			const language = this.languageOptionsComputed.find(l => l.value === code)
			return language ? language.title : code
		}
	}
}
</script>

<style scoped>
.settings-container {
	max-width: 1400px;
	margin: 0 auto;
}

.settings-hero {
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

.position-relative {
	position: relative;
}

.avatar-wrapper {
	position: relative;
	display: inline-block;
	margin-top: 20px;
}

.settings-avatar {
	border: 5px solid white;
	background: rgba(255, 255, 255, 0.2);
}

.logo-upload-area {
	border: 2px dashed #ccc;
	border-radius: 8px;
	padding: 24px;
	text-align: center;
	cursor: pointer;
	transition: all 0.3s ease;
}

.logo-upload-area:hover {
	border-color: #3b82f6;
	background-color: rgba(59, 130, 246, 0.05);
}

.logo-preview {
	display: flex;
	justify-content: center;
	align-items: center;
}

.upload-placeholder {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
</style>
