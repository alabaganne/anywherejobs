<script setup>
import { computed, h, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import VerifiedBadge from '@/Components/Company/VerifiedBadge.vue';
import { appPath } from '@/utils/url';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.companies' }, () => page) });
const props = defineProps({ companies: Array, perDay: Number });

const methods = { greenhouse: 'Greenhouse board', lever: 'Lever board', ashby: 'Ashby board', workable: 'Workable board', recruitee: 'Recruitee board', jsonld: 'Job pages on its site', careers: 'Careers page, read by AI' };
const search = ref('');
const filter = ref('followed');
const filters = computed(() => [
	{ key: 'followed', label: 'Followed', test: (company) => !!company.tracked_at },
	{ key: 'failed', label: 'Not followed', test: (company) => !company.tracked_at },
	{ key: 'errors', label: 'With errors', test: (company) => company.sources.some((source) => source.last_error) },
].map((item) => ({ ...item, count: props.companies.filter(item.test).length })));
const shown = computed(() => {
	const words = search.value.trim().toLowerCase();
	const test = filters.value.find((item) => item.key === filter.value).test;
	return props.companies.filter((company) => test(company) && (!words || `${company.name} ${company.website || ''} ${company.reason || ''}`.toLowerCase().includes(words)));
});
const date = (value) => value ? new Date(value).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Never';
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader title="Companies" :description="`The companies ${appName} follows. Each day the AI adds ${perDay} more that are hiring for IT roles; each one is checked on its own website, and its jobs are read from where that site lists them.`" />

		<div class="flex flex-wrap items-center gap-3 mb-5">
			<label class="relative w-full max-w-[320px]">
				<span class="sr-only">Search companies</span>
				<Search class="size-4 text-muted-2 absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true" />
				<input v-model="search" class="fld !py-2.5 !pl-10" type="search" placeholder="Search by name, site or reason">
			</label>
			<nav class="flex flex-wrap gap-2" aria-label="Filter companies">
				<button v-for="item in filters" :key="item.key" type="button" class="chip" :class="{ 'is-active': filter === item.key }" :aria-pressed="filter === item.key" @click="filter = item.key">
					{{ item.label }} <span class="opacity-70 tabular-nums">{{ item.count }}</span>
				</button>
			</nav>
		</div>

		<div class="bg-white border border-line rounded-[18px] overflow-x-auto">
			<table class="w-full min-w-[860px] text-left">
				<thead class="bg-paper-2 border-b border-line-2 [&_th]:px-[18px] [&_th]:py-3 [&_th]:text-[11.5px] [&_th]:tracking-[.08em] [&_th]:uppercase [&_th]:text-muted-2 [&_th]:font-bold">
					<tr><th scope="col">Company</th><th scope="col">Where its jobs come from</th><th scope="col">Listings</th><th scope="col">Followed since</th></tr>
				</thead>
				<tbody>
					<tr v-for="company in shown" :key="company.id" class="border-b border-line-2 last:border-b-0 align-top [&_td]:px-[18px] [&_td]:py-4">
						<td class="max-w-[320px]">
							<div class="flex items-center gap-1.5">
								<Link class="font-semibold text-[15px] hover:text-blue-700" :href="appPath(company.public_url)">{{ company.name }}</Link>
								<VerifiedBadge v-if="company.verified" compact />
							</div>
							<a v-if="company.website" :href="company.website" target="_blank" rel="noopener noreferrer" class="block text-[12.5px] text-muted mt-0.5 break-all hover:text-blue-700">{{ company.website }}</a>
							<p v-if="company.reason" class="text-[12.5px] text-ink-700 mt-1.5">{{ company.reason }}</p>
						</td>
						<td class="max-w-[360px] text-[13.5px]">
							<template v-if="company.tracking?.method">
								<p class="font-semibold">{{ methods[company.tracking.method] || company.tracking.method }}</p>
								<p class="text-[12.5px] text-muted mt-0.5 break-all">Linked from <a :href="company.tracking.linked_from" target="_blank" rel="noopener noreferrer" class="hover:text-blue-700">{{ company.tracking.linked_from }}</a></p>
							</template>
							<p v-else-if="company.tracking?.error" class="text-[12.5px] text-[#dc2626]">{{ company.tracking.error }}</p>
							<div v-for="source in company.sources" :key="source.id" class="mt-2 flex flex-wrap items-center gap-2">
								<StatusPill set="import" :status="source.enabled ? 'enabled' : 'paused'" />
								<Link class="text-[12.5px] text-blue-700 font-semibold" :href="route('imports.sources.edit', source.id)">{{ source.name }}</Link>
								<span class="text-[12.5px] text-muted">read {{ date(source.last_complete_at) }}</span>
								<p v-if="source.last_error" class="basis-full text-[12.5px] text-[#dc2626]">{{ source.last_error }}</p>
							</div>
						</td>
						<td class="text-[13.5px] tabular-nums whitespace-nowrap">
							{{ company.open_count }} open
							<span class="block text-[12.5px] text-muted">{{ company.sources.reduce((total, source) => total + source.items_count, 0) }} found</span>
						</td>
						<td class="text-[13.5px] text-muted whitespace-nowrap">{{ company.tracked_at ? date(company.tracked_at) : 'Not followed' }}</td>
					</tr>
					<tr v-if="!shown.length"><td colspan="4" class="px-[18px] py-8 text-[14px] text-muted">{{ companies.length ? 'No companies match.' : 'No companies yet. Run php artisan companies:discover, or wait for the daily run.' }}</td></tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
