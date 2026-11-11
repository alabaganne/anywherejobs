<script setup>
import { Search, Users } from '@lucide/vue';
import { h, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CandidateCard from '@/Components/Candidate/CandidateCard.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'talent' }, () => page),
});

const props = defineProps({
	candidates: Object,
	fields: { type: Array, default: () => [] },
	filters: { type: Object, default: () => ({}) },
});

const { text } = useText();
const search = ref(props.filters.search || '');
const field = ref(props.filters.field || '');
const resume = ref(!!props.filters.resume);

let timer;
const reload = () => {
	clearTimeout(timer);
	const query = {};
	if (search.value.trim()) query.search = search.value.trim();
	if (field.value) query.field = field.value;
	if (resume.value) query.resume = 1;
	router.get(route('talent.index'), query, { preserveState: true, preserveScroll: true, replace: true });
};
watch(search, () => {
	clearTimeout(timer);
	timer = setTimeout(reload, 300);
});
watch([field, resume], reload);
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader :title="text('Talent', 'Talents')" :description="text(`Candidates who chose to share their profile. The ${appName} team checks each one before it shows here.`, `Les candidats qui ont choisi de partager leur profil. L’équipe ${appName} vérifie chaque profil avant qu’il apparaisse ici.`)" />

		<div class="flex items-center gap-3 mb-5 flex-wrap">
			<label class="flex items-center gap-2.5 bg-white border border-line rounded-card px-3.5 h-11 flex-1 min-w-[240px] focus-within:border-blue-500 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-blue-600">
				<Search class="size-[18px] text-muted-2 shrink-0" aria-hidden="true" />
				<input v-model="search" class="border-0 bg-transparent p-0 outline-none focus:ring-0 text-[14.5px] w-full" type="search" :placeholder="text('Search by name, role, skill or location', 'Rechercher par nom, poste, compétence ou lieu')" :aria-label="text('Search candidates', 'Rechercher des candidats')">
			</label>
			<select v-model="field" class="fld !w-auto h-11 !py-0 bg-white" :aria-label="text('Field', 'Domaine')">
				<option value="">{{ text('All fields', 'Tous les domaines') }}</option>
				<option v-for="item in fields" :key="item.id" :value="item.id">{{ item.name }}</option>
			</select>
			<label class="inline-flex items-center gap-2.5 h-11 px-4 rounded-card border border-line bg-white text-sm font-medium cursor-pointer select-none">
				<input v-model="resume" type="checkbox" class="ck">
				{{ text('Resume shared', 'CV partagé') }}
			</label>
		</div>

		<p class="text-sm text-muted mb-4">{{ candidates.total }} {{ text(candidates.total === 1 ? 'candidate' : 'candidates', candidates.total > 1 ? 'candidats' : 'candidat') }}</p>

		<div v-if="candidates.data.length" class="grid grid-cols-3 gap-4 max-[1180px]:grid-cols-2 max-[760px]:grid-cols-1">
			<CandidateCard v-for="candidate in candidates.data" :key="candidate.id" :candidate="candidate" />
		</div>

		<div v-else class="flex flex-col items-center text-center py-16 px-5 bg-white border border-line rounded-card">
			<Users class="size-8 text-muted-2 mb-3" aria-hidden="true" />
			<h2 class="text-[20px] leading-[1.35] text-navy">{{ text('No candidates found', 'Aucun candidat trouvé') }}</h2>
			<p class="text-muted text-sm mt-1.5">{{ text('Try another name, skill or field.', 'Essayez un autre nom, une autre compétence ou un autre domaine.') }}</p>
		</div>

		<Pagination :meta="candidates" />
	</div>
</template>
