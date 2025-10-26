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
						<h2 class="text-h4 font-weight-bold mt-6 text-white">System Settings</h2>
						<p class="text-subtitle-1 text-white mb-0">Configure your application preferences</p>
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
							Settings are managed by your administrator. If you need to change any settings, please contact them.
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
							Company
						</v-tab>
						<v-tab value="financial">
							<v-icon start>mdi-currency-usd</v-icon>
							Financial & Invoices
						</v-tab>
						<v-tab value="datetime">
							<v-icon start>mdi-calendar-clock</v-icon>
							Date & Time
						</v-tab>
						<v-tab value="email">
							<v-icon start>mdi-email</v-icon>
							Email
						</v-tab>
						<v-tab value="legal">
							<v-icon start>mdi-gavel</v-icon>
							Legal
						</v-tab>
					</v-tabs>

					<v-card-text class="pa-8">
						<v-window v-model="tab">
							<!-- Company Settings Tab -->
							<v-window-item value="company">
								<v-form ref="companyForm">
									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-office-building</v-icon>
										Company Information
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_name"
												label="Company Name"
												variant="outlined"
												prepend-inner-icon="mdi-domain"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_email"
												label="Company Email"
												variant="outlined"
												prepend-inner-icon="mdi-email"
												density="comfortable"
												type="email"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_phone"
												label="Company Phone"
												variant="outlined"
												prepend-inner-icon="mdi-phone"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_website"
												label="Website URL"
												variant="outlined"
												prepend-inner-icon="mdi-web"
												density="comfortable"
												type="url"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.company_vat_id"
												label="VAT/Tax ID"
												variant="outlined"
												prepend-inner-icon="mdi-identifier"
												density="comfortable"
											></v-text-field>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-map-marker</v-icon>
										Company Address
									</div>

									<v-row>
										<v-col cols="12" md="8">
											<v-text-field
												v-model="settings.company_address_street"
												label="Street"
												variant="outlined"
												prepend-inner-icon="mdi-road"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="4">
											<v-text-field
												v-model="settings.company_address_number"
												label="Number"
												variant="outlined"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="4">
											<v-text-field
												v-model="settings.company_address_zipcode"
												label="Zip Code"
												variant="outlined"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="8">
											<v-text-field
												v-model="settings.company_address_city"
												label="City"
												variant="outlined"
												prepend-inner-icon="mdi-city"
												density="comfortable"
											></v-text-field>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-bank</v-icon>
										Bank Information
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.company_bank_info"
												label="Bank Information"
												variant="outlined"
												prepend-inner-icon="mdi-bank"
												density="comfortable"
												rows="4"
												hint="Bank name, account number, IBAN, SWIFT/BIC, etc."
												persistent-hint
											></v-textarea>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-image</v-icon>
										Company Logo
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<div class="logo-upload-area" @click="triggerLogoInput">
												<div v-if="settings.company_logo" class="logo-preview mb-4">
													<v-img :src="settings.company_logo" max-height="120" max-width="200" contain></v-img>
												</div>
												<div v-else class="upload-placeholder">
													<v-icon size="60" color="grey">mdi-image-plus</v-icon>
													<p class="text-body-2 text-medium-emphasis mt-2">Click to upload logo</p>
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
												Recommended: PNG or JPG, max 2MB
												<v-btn 
													v-if="settings.company_logo" 
													size="small" 
													color="error" 
													variant="text" 
													@click.stop="removeLogo"
												>
													Remove Logo
												</v-btn>
											</p>
										</v-col>
									</v-row>
								</v-form>
							</v-window-item>

							<!-- Financial & Invoice Settings Tab -->
							<v-window-item value="financial">
								<v-form ref="financialForm">
									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-currency-usd</v-icon>
										Currency & Tax
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-select
												v-model="settings.currency_symbol"
												:items="currencyOptions"
												label="Currency"
												variant="outlined"
												prepend-inner-icon="mdi-currency-usd"
												density="comfortable"
												@update:model-value="updateCurrencyCode"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.tax_rate"
												label="Tax Rate (%)"
												variant="outlined"
												prepend-inner-icon="mdi-percent"
												density="comfortable"
												type="number"
												step="0.01"
												hint="Default tax rate for invoices"
												persistent-hint
											></v-text-field>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-file-document</v-icon>
										Invoice Configuration
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.invoice_prefix"
												label="Invoice Prefix"
												variant="outlined"
												prepend-inner-icon="mdi-format-text"
												density="comfortable"
												hint="e.g., INV-, BILL-"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-select
												v-model="settings.invoice_number_format"
												:items="invoiceNumberFormats"
												label="Invoice Number Format"
												variant="outlined"
												prepend-inner-icon="mdi-format-list-numbered"
												density="comfortable"
											></v-select>
										</v-col>

									<v-col cols="12" md="6">
										<v-switch
											v-model="settings.invoice_number_random"
											label="Random Invoice Numbers"
											color="primary"
											hint="Generate invoice numbers randomly instead of sequentially"
											persistent-hint
										></v-switch>
									</v-col>

									<v-col cols="12" md="6">
										<v-text-field
											v-model="settings.invoice_number_random_length"
											label="Random Number Length"
											variant="outlined"
											prepend-inner-icon="mdi-numeric"
											density="comfortable"
											type="number"
											min="4"
											max="20"
											:disabled="!settings.invoice_number_random"
											hint="Number of digits for random invoice numbers (4-20)"
											persistent-hint
										></v-text-field>
									</v-col>

									<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.invoice_number_start"
												label="Starting Invoice Number"
												variant="outlined"
												prepend-inner-icon="mdi-numeric"
												density="comfortable"
												type="number"
												:disabled="settings.invoice_number_random"
												hint="First invoice number to use (disabled when using random)"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-select
												v-model="settings.invoice_default_status"
												:items="invoiceStatuses"
												label="Default Invoice Status"
												variant="outlined"
												prepend-inner-icon="mdi-tag"
												density="comfortable"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-switch
												v-model="settings.invoice_auto_send"
												label="Auto-send Invoices"
												color="primary"
												hint="Automatically send invoices when created"
												persistent-hint
											></v-switch>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-text-box</v-icon>
										Invoice Content
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.invoice_default_message"
												label="Default Invoice Message"
												variant="outlined"
												prepend-inner-icon="mdi-message-text"
												density="comfortable"
												rows="3"
												hint="Greeting message that appears at the top of invoices"
												persistent-hint
											></v-textarea>
										</v-col>

										<v-col cols="12">
											<v-textarea
												v-model="settings.invoice_payment_terms"
												label="Payment Terms"
												variant="outlined"
												prepend-inner-icon="mdi-file-document-outline"
												density="comfortable"
												rows="4"
												hint="Payment terms, late fees, and rules"
												persistent-hint
											></v-textarea>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-page-layout-footer</v-icon>
										Invoice Footer Layout
									</div>

									<v-row>
										<v-col cols="12" md="4">
											<v-select
												v-model="settings.invoice_footer_col1"
												:items="footerColumnOptions"
												label="Left Column"
												variant="outlined"
												density="comfortable"
											></v-select>
										</v-col>

										<v-col cols="12" md="4">
											<v-select
												v-model="settings.invoice_footer_col2"
												:items="footerColumnOptions"
												label="Center Column"
												variant="outlined"
												density="comfortable"
											></v-select>
										</v-col>

										<v-col cols="12" md="4">
											<v-select
												v-model="settings.invoice_footer_col3"
												:items="footerColumnOptions"
												label="Right Column"
												variant="outlined"
												density="comfortable"
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
										Date & Time Formats
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-select
												v-model="settings.date_format"
												:items="dateFormats"
												label="Date Format"
												variant="outlined"
												prepend-inner-icon="mdi-calendar"
												density="comfortable"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="tonal" color="info">
												<v-card-text>
													<div class="text-subtitle-2 mb-2">Preview:</div>
													<div class="text-h6">{{ formatPreviewDate(new Date()) }}</div>
												</v-card-text>
											</v-card>
										</v-col>

										<v-col cols="12" md="6">
											<v-select
												v-model="settings.time_format"
												:items="timeFormats"
												label="Time Format"
												variant="outlined"
												prepend-inner-icon="mdi-clock"
												density="comfortable"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="tonal" color="info">
												<v-card-text>
													<div class="text-subtitle-2 mb-2">Preview:</div>
													<div class="text-h6">{{ formatPreviewTime(new Date()) }}</div>
												</v-card-text>
											</v-card>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-numeric</v-icon>
										Number Formats
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-select
												v-model="settings.number_format"
												:items="numberFormats"
												label="Number Format"
												variant="outlined"
												prepend-inner-icon="mdi-numeric"
												density="comfortable"
												hint="Format for displaying numbers, decimals and currency"
												persistent-hint
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-card variant="tonal" color="info">
												<v-card-text>
													<div class="text-subtitle-2 mb-2">Preview:</div>
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
										<strong>Important:</strong> These settings override your .env configuration. 
										Test carefully before enabling email notifications.
									</v-alert>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-email-settings</v-icon>
										SMTP Configuration
									</div>

									<v-row>
										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_host"
												label="SMTP Host"
												variant="outlined"
												prepend-inner-icon="mdi-server"
												density="comfortable"
												hint="e.g., smtp.gmail.com"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_port"
												label="SMTP Port"
												variant="outlined"
												prepend-inner-icon="mdi-network"
												density="comfortable"
												type="number"
												hint="e.g., 587, 465, 2525"
												persistent-hint
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_username"
												label="SMTP Username"
												variant="outlined"
												prepend-inner-icon="mdi-account"
												density="comfortable"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_password"
												label="SMTP Password"
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
												label="Encryption"
												variant="outlined"
												prepend-inner-icon="mdi-shield-lock"
												density="comfortable"
											></v-select>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_from_address"
												label="From Email Address"
												variant="outlined"
												prepend-inner-icon="mdi-email-send"
												density="comfortable"
												type="email"
											></v-text-field>
										</v-col>

										<v-col cols="12" md="6">
											<v-text-field
												v-model="settings.mail_from_name"
												label="From Name"
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
										<strong>Legal Information:</strong> Add your privacy notice and imprint here. 
										These pages will be publicly accessible on the login page and in user profiles.
									</v-alert>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-shield-account</v-icon>
										Privacy Notice
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.privacy_notice"
												label="Privacy Notice (HTML)"
												variant="outlined"
												prepend-inner-icon="mdi-file-document-edit"
												density="comfortable"
												rows="10"
												hint="You can use HTML for formatting (e.g., <h2>, <p>, <ul>, <li>, <strong>, etc.)"
												persistent-hint
											></v-textarea>
										</v-col>
									</v-row>

									<v-divider class="my-6"></v-divider>

									<div class="text-h6 mb-4 d-flex align-center">
										<v-icon class="mr-2" color="primary">mdi-gavel</v-icon>
										Imprint
									</div>

									<v-row>
										<v-col cols="12">
											<v-textarea
												v-model="settings.imprint"
												label="Imprint (HTML)"
												variant="outlined"
												prepend-inner-icon="mdi-file-document-edit"
												density="comfortable"
												rows="10"
												hint="You can use HTML for formatting (e.g., <h2>, <p>, <ul>, <li>, <strong>, etc.)"
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
							Reset
						</v-btn>
						<v-btn
							color="primary"
							@click="saveSettings"
							:loading="loading"
							size="large"
						>
							<v-icon start>mdi-content-save</v-icon>
							Save All Settings
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
import axios from 'axios'

export default {
	name: 'Settings',
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
			
			// Dropdown options
			currencyOptions: [
				{ title: '$ - US Dollar', value: '$', code: 'USD' },
				{ title: '€ - Euro', value: '€', code: 'EUR' },
				{ title: '£ - British Pound', value: '£', code: 'GBP' },
				{ title: '¥ - Japanese Yen', value: '¥', code: 'JPY' },
				{ title: 'CHF - Swiss Franc', value: 'CHF', code: 'CHF' },
				{ title: 'C$ - Canadian Dollar', value: 'C$', code: 'CAD' },
				{ title: 'A$ - Australian Dollar', value: 'A$', code: 'AUD' },
			],
			invoiceNumberFormats: [
				{ title: 'YYYY-MM-number (e.g., 2025-10-001)', value: 'YYYY-MM-number' },
				{ title: 'YYYY-number (e.g., 2025-001)', value: 'YYYY-number' },
				{ title: 'Number only (e.g., 001)', value: 'number' }
			],
			invoiceStatuses: [
				{ title: 'Draft', value: 'draft' },
				{ title: 'Sent', value: 'sent' },
				{ title: 'Paid', value: 'paid' },
				{ title: 'Overdue', value: 'overdue' },
				{ title: 'Cancelled', value: 'cancelled' }
			],
			footerColumnOptions: [
				{ title: 'Company Information', value: 'company_info' },
				{ title: 'Bank Information', value: 'bank_info' },
				{ title: 'Page Information (x/y)', value: 'page_info' },
				{ title: 'Empty', value: 'empty' }
			],
			dateFormats: [
				{ title: 'DD/MM/YYYY (31/12/2025)', value: 'DD/MM/YYYY' },
				{ title: 'MM/DD/YYYY (12/31/2025)', value: 'MM/DD/YYYY' },
				{ title: 'YYYY-MM-DD (2025-12-31)', value: 'YYYY-MM-DD' },
				{ title: 'D/M/YYYY (31/12/2025 - no leading zeros)', value: 'D/M/YYYY' },
				{ title: 'DD.MM.YYYY (31.12.2025)', value: 'DD.MM.YYYY' },
				{ title: 'DD-MM-YYYY (31-12-2025)', value: 'DD-MM-YYYY' },
				{ title: 'YYYY/MM/DD (2025/12/31)', value: 'YYYY/MM/DD' },
				{ title: 'DD MMM YYYY (31 Dec 2025)', value: 'DD MMM YYYY' },
				{ title: 'D MMMM YYYY (31 December 2025)', value: 'D MMMM YYYY' },
				{ title: 'EEEE, DD MMMM YYYY (Monday, 31 December 2025)', value: 'EEEE, DD MMMM YYYY' },
				{ title: 'EEE, DD MMM YYYY (Mon, 31 Dec 2025)', value: 'EEE, DD MMM YYYY' },
				{ title: 'MMM D, YYYY (Dec 31, 2025)', value: 'MMM D, YYYY' },
				{ title: 'MMMM D, YYYY (December 31, 2025)', value: 'MMMM D, YYYY' },
			],
			timeFormats: [
				{ title: '24-hour (23:59)', value: '24h' },
				{ title: '24-hour with seconds (23:59:59)', value: '24h:ss' },
				{ title: '12-hour (11:59 PM)', value: '12h' },
				{ title: '12-hour with seconds (11:59:59 PM)', value: '12h:ss' },
				{ title: '12-hour no leading zero (3:59 PM)', value: '12h-nozero' },
				{ title: '12-hour no leading zero with seconds (3:59:59 PM)', value: '12h-nozero:ss' },
			],
			numberFormats: [
				{ title: '1,234.56 (US, UK, Australia, etc.)', value: 'en-US' },
				{ title: '1.234,56 (Germany, Most of Europe)', value: 'de-DE' },
				{ title: '1 234,56 (France)', value: 'fr-FR' },
				{ title: '12,34,567.89 (India)', value: 'en-IN' }
			],
			mailEncryptions: [
				{ title: 'TLS', value: 'tls' },
				{ title: 'SSL', value: 'ssl' },
				{ title: 'None', value: 'null' }
			]
		}
	},
	computed: {
		...mapGetters(store, ['getUser']),
		isAdmin() {
			return this.getUser?.role === 'admin'
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
				
				await axios.post('/api/settings/batch', this.settings)
				
				this.showSnackbar('Settings saved successfully', 'success')
				
				// Update original settings
				this.originalSettings = { ...this.settings }
				
				// Fetch settings fresh from the backend to update the store
				await this.fetchSettings()
			} catch (error) {
				console.error('Error saving settings:', error)
				this.showSnackbar(error.response?.data?.message || 'Failed to save settings', 'error')
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
				this.showSnackbar('Logo size must be less than 2MB', 'error')
				return
			}

			// Check file type
			if (!file.type.startsWith('image/')) {
				this.showSnackbar('Please select an image file', 'error')
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
