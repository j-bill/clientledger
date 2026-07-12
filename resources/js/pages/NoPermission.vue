<template>
	<div class="flex min-h-[70vh] items-center justify-center px-4">
		<div class="text-center">
			<p class="tnum font-mono text-7xl font-semibold text-bone-700">403</p>
			<h1 class="mt-6 text-xl font-semibold tracking-tight text-bone-100">
				{{ $t('pages.errorPages.accessDenied') }}
			</h1>
			<p class="mt-2 text-sm text-bone-500">
				{{ $t('pages.errorPages.accessDeniedMessage') }}
			</p>
			<div class="mt-8 flex items-center justify-center gap-3">
				<ui-button variant="primary" :icon="House" @click="goHome">
					{{ $t('pages.errorPages.goHome') }}
				</ui-button>
				<ui-button variant="ghost" :icon="ArrowLeft" @click="goBack">
					{{ $t('pages.errorPages.goBack') }}
				</ui-button>
			</div>
		</div>
	</div>
</template>

<script>
import { House, ArrowLeft } from 'lucide-vue-next';

export default {
	name: 'NoPermission',
	setup() {
		return { House, ArrowLeft };
	},
	methods: {
		goHome() {
			this.$router.push('/')
		},
		goBack() {
			this.$router.go(-1)
		}
	}
}
</script>
