<script setup>
import { h } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FileUser } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'kits' }, () => page) });
defineProps({ kits: { type: Array, default: () => [] } });
const status = { queued: 'Being written', ready: 'Ready', failed: 'Failed' };
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="CV & letters" description="The CVs and cover letters written for the roles you picked. Open one to edit it, download it or use it to apply.">
			<template #actions><Link :href="route('app.opportunities.index')" class="cta cta-secondary h-11 !py-0">Browse roles</Link></template>
		</PageHeader>
		<div v-if="!kits.length" class="bg-white border border-transparent rounded-card px-6 py-10 text-center">
			<FileUser class="w-9 h-9 text-blue-600 mx-auto" aria-hidden="true" />
			<p class="text-lg font-medium mt-3">Nothing here yet</p>
			<p class="text-sm text-muted mt-1 max-w-md mx-auto">Open any listing and press “Write my CV” or “Write my cover letter”. Add your resume on your profile page first for the best result.</p>
		</div>
		<ul v-else class="bg-white border border-transparent rounded-card divide-y divide-line-2">
			<li v-for="kit in kits" :key="kit.id">
				<Link :href="route('kits.show', kit.id)" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-6 py-4 max-[680px]:px-4 hover:bg-paper-2 transition-colors">
					<div class="min-w-0 grow basis-[220px]">
						<b class="block font-medium truncate">{{ kit.role?.title || 'Role removed' }}</b>
						<span class="text-sm text-muted">{{ [kit.role?.company, kit.language, kit.updated_label].filter(Boolean).join(' · ') }}</span>
					</div>
					<span class="flex shrink-0 flex-wrap items-center gap-1.5 text-xs font-medium">
						<span v-if="kit.has_cv" class="rounded-full bg-paper-3 px-2.5 py-1 text-ink-700">CV</span>
						<span v-if="kit.has_letter" class="rounded-full bg-paper-3 px-2.5 py-1 text-ink-700">Letter</span>
						<span v-if="kit.status !== 'ready'" class="rounded-full px-2.5 py-1" :class="kit.status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800'">{{ status[kit.status] }}</span>
					</span>
				</Link>
			</li>
		</ul>
	</div>
</template>
