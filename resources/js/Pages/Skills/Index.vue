<script setup>
import { computed, h, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'skills' }, () => page),
});

const props = defineProps({
	skills: { type: Object, required: true },
	filters: { type: Object, default: () => ({}) },
});

const { text } = useText();
const meta = computed(() => props.skills.meta ?? props.skills);
const search = ref(props.filters.search || '');
let timer;
watch(search, () => {
	clearTimeout(timer);
	timer = setTimeout(() => router.get(route('skills.index'), search.value.trim() ? { search: search.value.trim() } : {}, { preserveState: true, preserveScroll: true, replace: true }), 300);
});
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<div class="flex items-end justify-between flex-wrap gap-4 mb-6">
			<div>
				<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ text('Skills', 'Compétences') }}</h1>
				<p class="text-muted text-[15px] mt-1.5">{{ text('The skill tags roles and profiles can use.', 'Les étiquettes de compétences que les offres et les profils peuvent utiliser.') }}</p>
			</div>
			<Link class="cta cta-primary" :href="route('skills.create')">{{ text('Add skill', 'Ajouter une compétence') }}</Link>
		</div>

		<label class="flex items-center gap-2.5 bg-white border border-line rounded-card px-3.5 h-11 mb-3 max-w-[420px] focus-within:border-blue-500 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-blue-600">
			<Search class="size-[18px] text-muted-2 shrink-0" aria-hidden="true" />
			<input v-model="search" class="border-0 bg-transparent p-0 outline-none focus:ring-0 text-[14.5px] w-full" type="search" :placeholder="text('Search skills', 'Rechercher des compétences')" :aria-label="text('Search skills', 'Rechercher des compétences')">
		</label>
		<p class="text-sm text-muted mb-4">{{ meta.total }} {{ text(meta.total === 1 ? 'skill' : 'skills', meta.total > 1 ? 'compétences' : 'compétence') }}</p>

		<div class="bg-white border border-transparent rounded-card overflow-hidden">
			<div class="grid grid-cols-[1fr_160px_160px_90px] gap-4 px-[22px] py-3 border-b border-line-2 bg-paper-2 max-[760px]:hidden [&_span]:text-[11.5px] [&_span]:tracking-[.08em] [&_span]:uppercase [&_span]:text-muted-2 [&_span]:font-medium [&_span:last-child]:text-right">
				<span>{{ text('Skill', 'Compétence') }}</span><span>{{ text('Roles', 'Offres') }}</span><span>{{ text('Candidates', 'Candidats') }}</span><span></span>
			</div>
			<div
				v-for="(skill, index) in skills.data"
				:key="skill.id"
				class="grid grid-cols-[1fr_160px_160px_90px] gap-4 items-center px-[22px] py-4 max-[760px]:grid-cols-[minmax(0,1fr)_auto]"
				:class="{ 'border-b border-line-2': index !== skills.data.length - 1 }"
			>
				<b class="text-[15px] font-medium">{{ skill.name }}</b>
				<span class="text-[13.5px] text-muted max-[760px]:hidden">{{ skill.opportunities_count }}</span>
				<span class="text-[13.5px] text-muted max-[760px]:hidden">{{ skill.candidates_count }}</span>
				<Link class="cta cta-secondary cta-sm justify-self-end" :href="route('skills.edit', skill.id)">{{ text('Edit', 'Modifier') }}</Link>
			</div>
			<div v-if="!skills.data.length" class="px-[22px] py-8 text-[14px] text-muted">{{ search.trim() ? text('No skill matches that search.', 'Aucune compétence ne correspond à cette recherche.') : text('No skills yet.', 'Aucune compétence pour le moment.') }}</div>
		</div>

		<Pagination :meta="meta" />
	</div>
</template>
