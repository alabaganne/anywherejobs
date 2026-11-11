<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, FileText, Plus, Upload } from '@lucide/vue';
import axios from 'axios';
import Menu from '@/Components/Ui/Menu.vue';
import ResumeRow from '@/Components/Candidate/ResumeRow.vue';
import { menuItem } from '@/utils/menu';
import { useText } from '@/composables/useText';

// The candidate's resumes on their profile page: the main one first, translations and copies under the resume they came from.
const props = defineProps({
	resumes: { type: Array, default: () => [] },
	limit: { type: Number, default: 10 },
	emailVerified: { type: Boolean, default: false },
});
const page = usePage();
const { text } = useText();
const billing = computed(() => page.props.billing || {});
const paid = computed(() => !!billing.value.enabled && !billing.value.free);
const full = computed(() => props.resumes.length >= props.limit);
const aiOff = computed(() => !props.emailVerified ? text('Verify your email to use the AI tools.', 'Vérifiez votre e-mail pour utiliser les outils IA.') : full.value ? text(`You have ${props.limit} resumes. Delete one to add another.`, `Vous avez ${props.limit} CV. Supprimez-en un pour en ajouter un autre.`) : '');
const upload = useForm({ resume: null });
const fileInput = ref(null);
const busy = ref(null);
const error = ref('');
const billingUrl = ref('');

// A translation or copy sits under the resume it came from, unless it is the main one.
const byId = computed(() => Object.fromEntries(props.resumes.map((resume) => [resume.id, resume])));
const rootOf = (resume) => {
	let current = resume;
	const seen = new Set();
	while (!current.is_main && current.translated_from && byId.value[current.translated_from.id] && !seen.has(current.id)) {
		seen.add(current.id);
		current = byId.value[current.translated_from.id];
	}
	return current;
};
const groups = computed(() => props.resumes
	.filter((resume) => rootOf(resume) === resume)
	.map((resume) => ({ resume, children: props.resumes.filter((child) => child !== resume && rootOf(child) === resume) })));
const main = computed(() => groups.value.find((group) => group.resume.is_main));
const others = computed(() => groups.value.filter((group) => !group.resume.is_main));

const pick = (event) => {
	upload.resume = event.target.files?.[0] || null;
	if (upload.resume) upload.post(route('resumes.store'), { forceFormData: true, preserveScroll: true, onFinish: () => { upload.reset(); if (fileInput.value) fileInput.value.value = ''; } });
};
const makeMain = (resume) => router.post(route('resumes.main', resume.id), {}, { preserveScroll: true });
const remove = (resume) => {
	const note = resume.is_main ? text(' Your newest other resume becomes the main one.', ' Votre CV le plus récent deviendra le CV principal.') : '';
	if (window.confirm(text(`Delete “${resume.name}”?${note}`, `Supprimer « ${resume.name} » ?${note}`))) router.delete(route('resumes.destroy', resume.id), { preserveScroll: true });
};
// A translation, or an editable copy of an uploaded PDF (no target).
const startAi = async (resume, target = null) => {
	error.value = '';
	billingUrl.value = '';
	busy.value = `${resume.id}-${target?.code || 'copy'}`;
	try {
		const { data } = target
			? await axios.post(route('resumes.translate', resume.id), { locale: target.code })
			: await axios.post(route('resumes.copy', resume.id));
		router.visit(data.url);
	} catch (failure) {
		const status = failure.response?.status;
		billingUrl.value = failure.response?.data?.billing_url || '';
		error.value = status === 429 ? text('You asked the AI for a lot in a short time. Please wait a little and try again.', 'Vous avez beaucoup sollicité l’IA en peu de temps. Patientez un instant puis réessayez.')
			: status === 403 ? text('Verify your email address to use the AI tools.', 'Vérifiez votre adresse e-mail pour utiliser les outils IA.')
			: failure.response?.data?.message || text('We could not start right now. Please try again in a minute.', 'Impossible de démarrer pour le moment. Réessayez dans une minute.');
	} finally {
		busy.value = null;
	}
};
const rowProps = computed(() => ({ busy: busy.value, aiOff: aiOff.value, paid: paid.value }));
</script>

<template>
	<section id="resumes" class="bg-white border border-transparent rounded-card mb-[18px] pb-2 scroll-mt-24" aria-labelledby="resumes-title">
		<div class="flex flex-wrap items-start justify-between gap-3 px-7 pt-6 max-[560px]:px-5">
			<div class="min-w-0 grow basis-[260px]">
				<h3 id="resumes-title" class="text-[20px] leading-[1.35] text-navy">{{ text('Resumes', 'CV') }}</h3>
				<p class="text-[13px] text-muted mt-0.5">{{ text('Your main resume is shared on your public profile, sent when you apply and read by the AI.', 'Votre CV principal est affiché sur votre profil public, envoyé quand vous postulez et lu par l’IA.') }}</p>
			</div>
			<input ref="fileInput" type="file" accept="application/pdf" class="sr-only" tabindex="-1" aria-hidden="true" @change="pick">
			<Menu width="w-72">
				<template #button="{ open, toggle }">
					<button type="button" class="cta cta-primary disabled:opacity-60" :disabled="full || upload.processing" aria-haspopup="menu" :aria-expanded="open" @click="toggle"><Plus class="size-4" aria-hidden="true" />{{ upload.processing ? text('Uploading…', 'Envoi…') : text('Add resume', 'Ajouter un CV') }}<ChevronDown class="size-4" aria-hidden="true" /></button>
				</template>
				<button :class="menuItem" role="menuitem" type="button" @click="fileInput?.click()"><Upload class="size-4 text-muted-2" />{{ text('Upload a PDF', 'Importer un PDF') }}</button>
				<Link :class="menuItem" role="menuitem" :href="route('resumes.create')"><FileText class="size-4 text-muted-2" />{{ text('Make one from my profile', 'En créer un depuis mon profil') }}</Link>
			</Menu>
		</div>

		<p v-if="full" class="mx-7 mt-3 text-[13px] text-muted max-[560px]:mx-5">{{ text(`You have ${limit} resumes. Delete one to add another.`, `Vous avez ${limit} CV. Supprimez-en un pour en ajouter un autre.`) }}</p>
		<p v-if="upload.errors.resume" class="mx-7 mt-3 text-sm text-red-700 max-[560px]:mx-5" role="alert">{{ upload.errors.resume }}</p>
		<p v-if="error" class="mx-7 mt-3 text-sm text-red-700 max-[560px]:mx-5" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-medium">{{ text('Get credits', 'Obtenir des crédits') }}</a></p>

		<div v-if="main" class="mx-[22px] mt-5 rounded-card bg-pale p-5 space-y-4 max-[560px]:mx-3 max-[560px]:p-4">
			<p class="text-[12px] font-medium tracking-caps uppercase text-brand">{{ text('Main resume', 'CV principal') }}</p>
			<ResumeRow :resume="main.resume" v-bind="rowProps" @ai="startAi(main.resume, $event)" @main="makeMain(main.resume)" @remove="remove(main.resume)" />
			<ResumeRow v-for="child in main.children" :key="child.id" :resume="child" nested v-bind="rowProps" @ai="startAi(child, $event)" @main="makeMain(child)" @remove="remove(child)" />
		</div>

		<template v-if="others.length">
			<p class="px-7 mt-6 text-[12px] font-medium tracking-caps uppercase text-subtle max-[560px]:px-5">{{ main ? text('Other resumes', 'Autres CV') : text('Your resumes', 'Vos CV') }}</p>
			<ul class="mt-1 divide-y divide-line-2">
				<li v-for="group in others" :key="group.resume.id" class="px-[22px] py-4 space-y-3 max-[560px]:px-3">
					<ResumeRow :resume="group.resume" v-bind="rowProps" @ai="startAi(group.resume, $event)" @main="makeMain(group.resume)" @remove="remove(group.resume)" />
					<ResumeRow v-for="child in group.children" :key="child.id" :resume="child" nested v-bind="rowProps" @ai="startAi(child, $event)" @main="makeMain(child)" @remove="remove(child)" />
				</li>
			</ul>
		</template>

		<p v-if="!resumes.length" class="px-7 py-6 text-[14.5px] text-muted leading-[1.7] max-[560px]:px-5">{{ text('No resume yet. Upload the PDF you already have, or make one from your profile. Both are free.', 'Aucun CV pour l’instant. Importez le PDF que vous avez déjà, ou créez-en un depuis votre profil. Les deux sont gratuits.') }}</p>
	</section>
</template>
