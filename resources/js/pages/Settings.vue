<template>
	<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
		<!-- Heading -->
		<div class="mb-6">
			<h1 class="text-2xl font-semibold tracking-tight">{{ $t('pages.settings.systemSettings') }}</h1>
			<p class="mt-1 text-sm text-bone-500">{{ $t('pages.settings.configurePreferences') }}</p>
		</div>

		<!-- Non-Admin View -->
		<ui-alert v-if="!isAdmin" type="info" :text="$t('pages.settings.settingsManagedByAdmin')" />

		<!-- Admin Settings Tabs -->
		<ui-card v-else dense>
			<ui-tabs v-model="tab" :tabs="tabItems" />

			<div class="p-6">
				<!-- Company Settings Tab -->
				<div v-if="tab === 'company'">
					<ui-form ref="companyForm">
						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Building2 class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.companyInformation') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<ui-input
								v-model="settings.company_name"
								:label="$t('pages.settings.companyName')"
								:icon="Building2"
							/>
							<ui-input
								v-model="settings.company_email"
								:label="$t('pages.settings.companyEmail')"
								:icon="Mail"
								type="email"
							/>
							<ui-input
								v-model="settings.company_phone"
								:label="$t('pages.settings.companyPhone')"
								:icon="Phone"
							/>
							<ui-input
								v-model="settings.company_website"
								:label="$t('pages.settings.websiteUrl')"
								:icon="Globe"
								type="url"
							/>
							<ui-input
								v-model="settings.company_vat_id"
								:label="$t('pages.settings.vatId')"
								:icon="Hash"
							/>
						</div>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<MapPin class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.companyAddress') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
							<ui-input
								v-model="settings.company_address_street"
								:label="$t('pages.settings.street')"
								:icon="MapPin"
								wrapper-class="md:col-span-2"
							/>
							<ui-input
								v-model="settings.company_address_number"
								:label="$t('pages.settings.number')"
							/>
							<ui-input
								v-model="settings.company_address_zipcode"
								:label="$t('pages.settings.zipCode')"
							/>
							<ui-input
								v-model="settings.company_address_city"
								:label="$t('pages.settings.city')"
								:icon="Building2"
								wrapper-class="md:col-span-2"
							/>
						</div>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Landmark class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.bankInformation') }}
						</div>

						<ui-textarea
							v-model="settings.company_bank_info"
							:label="$t('pages.settings.bankInfo')"
							rows="4"
							:hint="$t('pages.settings.bankInfoHint')"
						/>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Image class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.companyLogo') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<div>
								<div
									class="cursor-pointer rounded-lg border-2 border-dashed border-ink-700 p-6 text-center transition-colors hover:border-ink-600 hover:bg-ink-850"
									@click="triggerLogoInput"
								>
									<div v-if="settings.company_logo" class="flex items-center justify-center">
										<img :src="settings.company_logo" alt="" class="max-h-[120px] max-w-[200px] object-contain" />
									</div>
									<div v-else class="flex flex-col items-center justify-center">
										<ImagePlus class="h-12 w-12 text-bone-700" />
										<p class="mt-2 text-sm text-bone-500">{{ $t('pages.settings.clickToUpload') }}</p>
									</div>
									<input
										ref="logoInput"
										type="file"
										accept="image/*"
										class="hidden"
										@change="onLogoSelected"
									/>
								</div>
								<p class="mt-2 flex flex-wrap items-center gap-2 text-xs text-bone-500">
									{{ $t('pages.settings.logoRecommendation') }}
									<ui-button
										v-if="settings.company_logo"
										variant="danger-ghost"
										size="sm"
										@click="removeLogo"
									>
										{{ $t('pages.settings.removeLogo') }}
									</ui-button>
								</p>
							</div>
						</div>
					</ui-form>
				</div>

				<!-- Localization Settings Tab -->
				<div v-if="tab === 'localization'">
					<ui-form ref="localizationForm">
						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Languages class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.languageLocalization') }}
						</div>

						<ui-select
							v-model="settings.language"
							:items="languageOptionsComputed"
							:label="$t('pages.settings.applicationLanguage')"
							:hint="$t('pages.settings.languageHint')"
							class="max-w-sm"
						/>

						<hr class="my-6 border-ink-700/60" />

						<ui-alert type="info">
							<strong>{{ $t('pages.settings.note') }}:</strong> {{ $t('pages.settings.languageChangeAffects') }}
							<ul class="mt-1 list-disc pl-5">
								<li>{{ $t('pages.settings.languageChangeInvoices') }}</li>
								<li>{{ $t('pages.settings.languageChangeEmails') }}</li>
								<li>{{ $t('pages.settings.languageChangeFormats') }}</li>
							</ul>
						</ui-alert>
					</ui-form>
				</div>

				<!-- Financial & Invoice Settings Tab -->
				<div v-if="tab === 'financial'">
					<ui-form ref="financialForm">
						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Banknote class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.currencyTax') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<ui-select
								v-model="settings.currency_symbol"
								:items="currencyOptions"
								:label="$t('pages.settings.currency')"
								item-title="title"
								item-value="value"
								@update:model-value="updateCurrencyCode"
							/>
							<ui-input
								v-model="settings.tax_rate"
								:label="$t('pages.settings.taxRate')"
								:icon="Percent"
								type="number"
								step="0.01"
								:hint="$t('pages.settings.taxRateHint')"
							/>
						</div>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<FileText class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.invoiceConfiguration') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<ui-input
								v-model="settings.invoice_prefix"
								:label="$t('pages.settings.invoicePrefix')"
								:icon="Type"
								:hint="$t('pages.settings.invoicePrefixHint')"
							/>
							<ui-select
								v-model="settings.invoice_number_format"
								:items="invoiceNumberFormats"
								:label="$t('pages.settings.invoiceNumberFormat')"
								item-title="title"
								item-value="value"
							/>
							<ui-switch
								v-model="settings.invoice_number_random"
								:label="$t('pages.settings.randomInvoiceNumbers')"
								:hint="$t('pages.settings.randomInvoiceNumbersHint')"
							/>
							<ui-input
								v-model="settings.invoice_number_random_length"
								:label="$t('pages.settings.randomNumberLength')"
								:icon="Hash"
								type="number"
								min="4"
								max="20"
								:disabled="!settings.invoice_number_random"
								:hint="$t('pages.settings.randomNumberLengthHint')"
							/>
							<ui-input
								v-model="settings.invoice_number_start"
								:label="$t('pages.settings.startingInvoiceNumber')"
								:icon="Hash"
								type="number"
								:disabled="settings.invoice_number_random"
								:hint="$t('pages.settings.startingInvoiceNumberHint')"
							/>
							<ui-select
								v-model="settings.invoice_default_status"
								:items="invoiceStatuses"
								:label="$t('pages.settings.defaultInvoiceStatus')"
								item-title="title"
								item-value="value"
							/>
							<ui-switch
								v-model="settings.invoice_auto_send"
								:label="$t('pages.settings.autoSendInvoices')"
								:hint="$t('pages.settings.autoSendInvoicesHint')"
							/>
						</div>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<MessageSquareText class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.invoiceContent') }}
						</div>

						<div class="space-y-4">
							<ui-textarea
								v-model="settings.invoice_default_message"
								:label="$t('pages.settings.defaultInvoiceMessage')"
								rows="3"
								:hint="$t('pages.settings.defaultInvoiceMessageHint')"
							/>
							<ui-textarea
								v-model="settings.invoice_payment_terms"
								:label="$t('pages.settings.paymentTerms')"
								rows="4"
								:hint="$t('pages.settings.paymentTermsHint')"
							/>
						</div>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<LayoutTemplate class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.invoiceFooterLayout') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-3">
							<ui-select
								v-model="settings.invoice_footer_col1"
								:items="footerColumnOptions"
								:label="$t('pages.settings.leftColumn')"
								item-title="title"
								item-value="value"
							/>
							<ui-select
								v-model="settings.invoice_footer_col2"
								:items="footerColumnOptions"
								:label="$t('pages.settings.centerColumn')"
								item-title="title"
								item-value="value"
							/>
							<ui-select
								v-model="settings.invoice_footer_col3"
								:items="footerColumnOptions"
								:label="$t('pages.settings.rightColumn')"
								item-title="title"
								item-value="value"
							/>
						</div>
					</ui-form>
				</div>

				<!-- Date & Time Settings Tab -->
				<div v-if="tab === 'datetime'">
					<ui-form ref="datetimeForm">
						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Calendar class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.dateTimeFormats') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<div>
								<ui-select
									v-model="settings.date_format"
									:items="dateFormats"
									:label="$t('pages.settings.dateFormat')"
									item-title="title"
									item-value="value"
								/>
								<p class="mt-1.5 text-xs text-bone-500">
									{{ $t('pages.settings.preview') }}: <span class="tnum text-bone-300">{{ formatPreviewDate(new Date()) }}</span>
								</p>
							</div>
							<div>
								<ui-select
									v-model="settings.time_format"
									:items="timeFormats"
									:label="$t('pages.settings.timeFormat')"
									item-title="title"
									item-value="value"
								/>
								<p class="mt-1.5 text-xs text-bone-500">
									{{ $t('pages.settings.preview') }}: <span class="tnum text-bone-300">{{ formatPreviewTime(new Date()) }}</span>
								</p>
							</div>
						</div>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Hash class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.numberFormats') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<div>
								<ui-select
									v-model="settings.number_format"
									:items="numberFormats"
									:label="$t('pages.settings.numberFormat')"
									item-title="title"
									item-value="value"
									:hint="$t('pages.settings.numberFormatHint')"
								/>
								<p class="mt-1.5 text-xs text-bone-500">
									{{ $t('pages.settings.preview') }}: <span class="tnum text-bone-300">{{ formatPreviewNumber(1234567.89) }}</span>
								</p>
							</div>
						</div>
					</ui-form>
				</div>

				<!-- Email Settings Tab -->
				<div v-if="tab === 'email'">
					<ui-form ref="emailForm">
						<ui-alert type="warning" class="mb-4">
							<strong>{{ $t('pages.settings.important') }}:</strong> {{ $t('pages.settings.emailSettingsWarning') }}
						</ui-alert>

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Mail class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.smtpConfiguration') }}
						</div>

						<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
							<ui-input
								v-model="settings.mail_host"
								:label="$t('pages.settings.smtpHost')"
								:icon="Server"
								:hint="$t('pages.settings.smtpHostHint')"
							/>
							<ui-input
								v-model="settings.mail_port"
								:label="$t('pages.settings.smtpPort')"
								:icon="Network"
								type="number"
								:hint="$t('pages.settings.smtpPortHint')"
							/>
							<ui-input
								v-model="settings.mail_username"
								:label="$t('pages.settings.smtpUsername')"
								:icon="User"
							/>
							<div class="flex items-end gap-2">
								<ui-input
									v-model="settings.mail_password"
									:label="$t('pages.settings.smtpPassword')"
									:icon="Lock"
									:type="showMailPassword ? 'text' : 'password'"
									wrapper-class="flex-1"
								/>
								<ui-button
									variant="ghost"
									:icon="showMailPassword ? EyeOff : Eye"
									@click="showMailPassword = !showMailPassword"
								/>
							</div>
							<ui-select
								v-model="settings.mail_encryption"
								:items="mailEncryptions"
								:label="$t('pages.settings.encryption')"
								item-title="title"
								item-value="value"
							/>
							<ui-input
								v-model="settings.mail_from_address"
								:label="$t('pages.settings.fromEmailAddress')"
								:icon="Mail"
								type="email"
							/>
							<ui-input
								v-model="settings.mail_from_name"
								:label="$t('pages.settings.fromName')"
								:icon="User"
							/>
						</div>
					</ui-form>
				</div>

				<!-- Legal Settings Tab -->
				<div v-if="tab === 'legal'">
					<ui-form ref="legalForm">
						<ui-alert type="info" class="mb-4">
							<strong>{{ $t('pages.settings.legalInformation') }}:</strong> {{ $t('pages.settings.legalInformationHint') }}
						</ui-alert>

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Shield class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.privacyNotice') }}
						</div>

						<ui-textarea
							v-model="settings.privacy_notice"
							:label="$t('pages.settings.privacyNoticeHtml')"
							rows="10"
							:hint="$t('pages.settings.htmlFormattingHint')"
						/>

						<hr class="my-6 border-ink-700/60" />

						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Gavel class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.imprint') }}
						</div>

						<ui-textarea
							v-model="settings.imprint"
							:label="$t('pages.settings.imprintHtml')"
							rows="10"
							:hint="$t('pages.settings.htmlFormattingHint')"
						/>
					</ui-form>
				</div>

				<!-- AI Settings Tab -->
				<div v-if="tab === 'ai'">
					<ui-form ref="aiForm">
						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Bot class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.aiConfiguration') }}
						</div>

						<div class="space-y-4">
							<ui-switch
								v-model="settings.ai_worklog_enabled"
								:label="$t('pages.settings.aiWorklogEnabled')"
							/>

							<div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2">
								<div>
									<ui-input
										v-model="openaiApiKeyInput"
										:label="$t('pages.settings.openaiApiKey')"
										:icon="Key"
										type="password"
										autocomplete="new-password"
										:placeholder="settings.openai_api_key ? settings.openai_api_key : $t('pages.settings.openaiApiKeyPlaceholder')"
									/>
									<p class="mt-1.5 text-xs text-bone-500">
										<template v-if="settings.openai_api_key">
											{{ $t('pages.settings.openaiApiKeyCurrent') }}: <span class="tnum text-bone-300">{{ settings.openai_api_key }}</span> — {{ $t('pages.settings.openaiApiKeyReplaceHint') }}
										</template>
										<template v-else>{{ $t('pages.settings.openaiApiKeyHint') }}</template>
									</p>
								</div>
								<ui-input
									v-model="settings.openai_model"
									:label="$t('pages.settings.openaiModel')"
									:icon="Brain"
									:hint="$t('pages.settings.openaiModelHint')"
								/>
							</div>

							<ui-textarea
								v-model="settings.ai_worklog_prompt"
								:label="$t('pages.settings.aiWorklogPrompt')"
								rows="5"
								:hint="$t('pages.settings.aiWorklogPromptHint')"
							/>
						</div>
					</ui-form>
				</div>

				<!-- Sound Settings Tab -->
				<div v-if="tab === 'sounds'">
					<ui-form ref="soundsForm">
						<div class="mb-4 flex items-center gap-2 text-sm font-semibold text-bone-100">
							<Volume2 class="h-4 w-4 text-bone-500" />
							{{ $t('pages.settings.soundConfiguration') }}
						</div>

						<div class="space-y-4">
							<ui-switch
								v-model="settings.worklog_sound_enabled"
								:label="$t('pages.settings.worklogSoundEnabled')"
								:hint="$t('pages.settings.worklogSoundEnabledHint')"
							/>

							<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
								<ui-select
									v-model="settings.worklog_sound"
									:items="worklogSoundOptions"
									:label="$t('pages.settings.worklogSound')"
									item-title="title"
									item-value="value"
									:disabled="!settings.worklog_sound_enabled"
								/>
								<div class="flex items-end">
									<ui-button
										variant="outline"
										:icon="Play"
										:disabled="!settings.worklog_sound_enabled"
										@click="previewSound"
									>
										{{ $t('pages.settings.previewSound') }}
									</ui-button>
								</div>
							</div>
						</div>
					</ui-form>
				</div>
			</div>

			<!-- Actions -->
			<div class="flex items-center justify-end gap-2 border-t border-ink-700/60 px-6 py-4">
				<ui-button variant="ghost" :disabled="loading" @click="resetSettings">
					{{ $t('common.reset') }}
				</ui-button>
				<ui-button variant="primary" :icon="Save" :loading="loading" @click="saveSettings">
					{{ $t('pages.settings.saveAllSettings') }}
				</ui-button>
			</div>
		</ui-card>
	</div>
</template>

<script>
import { mapActions, mapGetters } from 'pinia'
import { store } from '../store'
import { formatNumber, formatDate, formatTime } from '../utils/formatters'
import { useLanguage } from '../composables/useLanguage'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import {
	Building2, Globe, Banknote, CalendarClock, Mail, Gavel, Bot, Volume2,
	Phone, Hash, MapPin, Landmark, Image, ImagePlus, Languages, Percent,
	Type, FileText, MessageSquareText, LayoutTemplate, Calendar, Server,
	Network, User, Lock, Eye, EyeOff, Shield, Key, Brain, Play, Save
} from 'lucide-vue-next'

export default {
	name: 'Settings',
	components: {
		Building2, MapPin, Landmark, Image, ImagePlus, Languages, Banknote,
		FileText, MessageSquareText, LayoutTemplate, Calendar, Hash, Mail,
		Shield, Gavel, Bot, Volume2
	},
	setup() {
		const { setLanguage } = useLanguage()
		const { t } = useI18n()
		return {
			setLanguage, t,
			Building2, Mail, Phone, Globe, Hash, MapPin, Percent, Type,
			Server, Network, User, Lock, Eye, EyeOff, Key, Brain, Play, Save
		}
	},
	data() {
		return {
			loading: false,
			tab: 'company',
			showMailPassword: false,
			openaiApiKeyInput: '',
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
				imprint: '',

				// AI
				ai_worklog_enabled: false,
				openai_api_key: '',
				openai_model: 'gpt-4o-mini',
				ai_worklog_prompt: 'You are an assistant that rewrites rough time-tracking notes into a polished work log description. Write in first person, past tense, professional but plain language. Keep it concise (1-4 sentences), factual, and suitable for a client-facing invoice. Do not invent work that is not mentioned in the notes. Respond only with the rewritten description, in the same language as the notes.',

				// Sounds
				worklog_sound_enabled: true,
				worklog_sound: 'cash-register'
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
		tabItems() {
			return [
				{ value: 'company', label: this.t('pages.settings.company'), icon: Building2 },
				{ value: 'localization', label: this.t('pages.settings.localization'), icon: Globe },
				{ value: 'financial', label: this.t('pages.settings.financialInvoices'), icon: Banknote },
				{ value: 'datetime', label: this.t('pages.settings.dateTime'), icon: CalendarClock },
				{ value: 'email', label: this.t('pages.settings.email'), icon: Mail },
				{ value: 'legal', label: this.t('pages.settings.legal'), icon: Gavel },
				{ value: 'ai', label: this.t('pages.settings.ai'), icon: Bot },
				{ value: 'sounds', label: this.t('pages.settings.sounds'), icon: Volume2 }
			]
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
				{ title: this.t('pages.settings.dropdownOptions.footerColumns.bankInfo'), value: 'bank_info' },
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
		worklogSoundOptions() {
			return [
				{ title: this.t('pages.settings.dropdownOptions.worklogSounds.cashRegister'), value: 'cash-register' },
				{ title: this.t('pages.settings.dropdownOptions.worklogSounds.coinBag'), value: 'coin-bag' },
				{ title: this.t('pages.settings.dropdownOptions.worklogSounds.coinRetro'), value: 'coin-retro' },
				{ title: this.t('pages.settings.dropdownOptions.worklogSounds.coinChime'), value: 'coin-chime' }
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

				// The API key field only ever shows a masked preview, never
				// the real value, so only send it when the user actually
				// typed a replacement; otherwise leave the stored key alone.
				const payload = { ...this.settings }
				if (this.openaiApiKeyInput) {
					payload.openai_api_key = this.openaiApiKeyInput
				} else {
					delete payload.openai_api_key
				}

				const response = await axios.post('/api/settings/batch', payload)

				if (response.data?.settings?.openai_api_key !== undefined) {
					this.settings.openai_api_key = response.data.settings.openai_api_key
				}
				this.openaiApiKeyInput = ''

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
			this.openaiApiKeyInput = ''
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

		previewSound() {
			const audio = new Audio(`/sounds/${this.settings.worklog_sound || 'cash-register'}.mp3`)
			audio.play().catch(() => {
				this.showSnackbar(this.t('pages.settings.soundPlaybackFailed'), 'error')
			})
		},

		getLanguageName(code) {
			const language = this.languageOptionsComputed.find(l => l.value === code)
			return language ? language.title : code
		}
	}
}
</script>
