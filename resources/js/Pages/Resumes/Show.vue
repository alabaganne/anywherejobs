<script setup>
import { computed, h, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Check, ChevronDown, Download, Ellipsis, FilePenLine, FileText, Languages, RefreshCw, Star, Trash2 } from '@lucide/vue';
import axios from 'axios';
import AppShell from '@/Layouts/AppShell.vue';
import CvStudio from '@/Components/Cv/CvStudio.vue';
import Menu from '@/Components/Ui/Menu.vue';
import { menuItem } from '@/utils/menu';
import { downloadCvDocx } from '@/utils/cvDocx';
import { useEditorGuards } from '@/utils/editor';
import { useText } from '@/composables/useText';

const { fr, text } = useText();

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'profile', fill: true }, () => page) });
const props = defineProps({
	resume: { type: Object, default: null },
	draft: { type: Object, default: null },
	main: { type: Object, default: null },
	full: { type: Boolean, default: false },
	themes: { type: Object, required: true },
	locales: { type: Array, required: true },
	ai_enabled: { type: Boolean, default: false },
});

const page = usePage();
const resume = ref(props.resume);
const isNew = computed(() => !resume.value);
const start = props.resume || props.draft;
// Props are reactive proxies, which structuredClone can't copy.
const copy = (value) => JSON.parse(JSON.stringify(value || {}));
const blankCv = () => ({ name: '', contact: [], headline: '', summary: '', skills: [], experience: [], education: [], projects: [], languages: [] });
const cv = ref({ ...blankCv(), ...copy(start.content) });
const style = ref({ ...start.style });
const locale = ref(props.resume?.language?.code || props.draft?.locale || 'en');
const name = ref(start.name);
const replace = ref(!!props.main);
const makeMain = ref(false);
const saving = ref(false);
const error = ref('');
const billingUrl = ref('');
const snapshot = ref(JSON.stringify([cv.value, style.value, locale.value, name.value]));
const dirty = computed(() => isNew.value || JSON.stringify([cv.value, style.value, locale.value, name.value]) !== snapshot.value);
const status = computed(() => resume.value?.status || 'new');
const labels = computed(() => props.locales.find((option) => option.code === locale.value)?.labels || props.locales[0].labels);
const credits = computed(() => page.props.auth?.user?.credits ?? 0);
const free = computed(() => !page.props.billing?.enabled || page.props.billing?.free);

const take = (data) => {
	resume.value = data;
	if (data.content) {
		cv.value = { ...blankCv(), ...copy(data.content) };
		style.value = { ...data.style };
		name.value = data.name;
		// An editable copy learns its language only once the AI has read it.
		locale.value = data.language?.code || locale.value;
		snapshot.value = JSON.stringify([cv.value, style.value, locale.value, name.value]);
	}
};

let timer = null;
const poll = () => {
	clearTimeout(timer);
	if (resume.value?.status !== 'queued') return;
	timer = setTimeout(async () => {
		try {
			const { data } = await axios.get(route('resumes.show', resume.value.id), { headers: { Accept: 'application/json' } });
			take(data.resume);
		} catch { /* try again on the next tick */ }
		poll();
	}, 3000);
};
onMounted(poll);
onBeforeUnmount(() => clearTimeout(timer));

const blocked = computed(() => isNew.value && props.full && !replace.value);
const save = () => {
	if (saving.value || blocked.value) return;
	saving.value = true;
	error.value = '';
	const done = { preserveScroll: true, onFinish: () => (saving.value = false), onError: (errors) => (error.value = Object.values(errors)[0] || text('We could not save your resume.', 'Nous n’avons pas pu enregistrer votre CV.')) };
	if (isNew.value) {
		router.post(route('resumes.profile'), { name: name.value, locale: locale.value, style: style.value, content: cv.value, replace: replace.value }, done);
		return;
	}
	router.put(route('resumes.update', resume.value.id), {
		name: name.value, content: cv.value, style: style.value,
		...(resume.value.source === 'profile' ? { locale: locale.value } : {}),
		...(makeMain.value ? { main: true } : {}),
	}, { ...done, preserveState: true, onSuccess: () => take(page.props.resume) });
};
const { allowLeave } = useEditorGuards({ dirty: computed(() => !isNew.value && dirty.value), save });

const failed = (failure) => {
	const code = failure.response?.status;
	billingUrl.value = failure.response?.data?.billing_url || '';
	error.value = code === 429 ? text('You asked the AI for a lot in a short time. Please wait a little and try again.', 'Vous avez beaucoup sollicité l’IA en peu de temps. Veuillez patienter un peu, puis réessayer.')
		: code === 403 ? text('Verify your email address to use the AI tools.', 'Vérifiez votre adresse e-mail pour utiliser les outils d’IA.')
		: failure.response?.data?.message || text('We could not start right now. Please try again in a minute.', 'Nous n’avons pas pu démarrer pour le moment. Veuillez réessayer dans une minute.');
};
const translate = async (target) => {
	if (dirty.value && !window.confirm(text('Your latest changes are not saved. Translate the saved version?', 'Vos dernières modifications ne sont pas enregistrées. Traduire la version enregistrée ?'))) return;
	error.value = '';
	try {
		const { data } = await axios.post(route('resumes.translate', resume.value.id), { locale: target.code });
		allowLeave();
		router.visit(data.url);
	} catch (failure) { failed(failure); }
};
const makeCopy = async () => {
	error.value = '';
	try {
		const { data } = await axios.post(route('resumes.copy', resume.value.id));
		router.visit(data.url);
	} catch (failure) { failed(failure); }
};
const isCopy = computed(() => resume.value?.source === 'copy');
const regenerate = async () => {
	if (resume.value.status === 'draft' && !window.confirm(fr.value ? `${isCopy.value ? 'Relire le CV' : 'Traduire à nouveau'} ? Vos modifications de ce brouillon seront remplacées.` : `${isCopy.value ? 'Read it again' : 'Translate it again'}? Your edits to this draft will be replaced.`)) return;
	error.value = '';
	try {
		const { data } = await axios.post(route('resumes.regenerate', resume.value.id));
		resume.value = { ...resume.value, ...data.resume };
		poll();
	} catch (failure) { failed(failure); }
};
const refresh = () => {
	if (window.confirm(text('Lay this resume out again from your profile as it is now? Edits made here will be replaced.', 'Refaire la mise en page de ce CV à partir de votre profil actuel ? Les modifications faites ici seront remplacées.'))) {
		router.post(route('resumes.refresh', resume.value.id), {}, { preserveScroll: true, preserveState: true, onSuccess: () => take(page.props.resume) });
	}
};
const setMain = () => router.post(route('resumes.main', resume.value.id), {}, { preserveScroll: true, preserveState: true, onSuccess: () => (resume.value = { ...resume.value, is_main: true }) });
const remove = () => {
	if (!window.confirm(text(`Delete “${resume.value.name}”?`, `Supprimer « ${resume.value.name} » ?`))) return;
	allowLeave();
	router.delete(route('resumes.destroy', resume.value.id));
};
const word = () => downloadCvDocx({
	cv: cv.value, labels: labels.value, locale: locale.value,
	accent: props.themes.accents.find((accent) => accent.id === style.value.accent)?.hex,
	paper: style.value.paper, fileName: `${(cv.value.name || 'Resume').replace(/\s+/g, '_')}_${labels.value.cv}`,
});
const editable = computed(() => isNew.value || (resume.value.editable && ['ready', 'draft'].includes(resume.value.status)));
const title = computed(() => isNew.value ? text('New resume from your profile', 'Nouveau CV à partir de votre profil') : resume.value.name);
const meta = computed(() => isNew.value
	? text('Your profile, laid out with the theme you pick. Your profile itself does not change.', 'Votre profil, mis en page avec le thème que vous choisissez. Votre profil lui-même ne change pas.')
	: [resume.value.language?.label, { uploaded: text('Uploaded PDF', 'PDF importé'), profile: text('From your profile', 'À partir de votre profil'), translation: text('AI translation', 'Traduction par l’IA'), copy: text('Editable copy', 'Copie modifiable') }[resume.value.source], resume.value.is_main ? text('Main resume', 'CV principal') : null].filter(Boolean).join(' · '));
const label = 'block text-[14px] font-medium text-navy';
</script>

<template>
	<CvStudio v-if="editable" v-model:cv="cv" v-model:style="style" :themes="themes" :locale="locale">
		<template #title>
			<Link :href="`${route('profile.show')}#resumes`" class="icon-btn shrink-0 -ml-2" :aria-label="text('Back to your resumes', 'Retour à vos CV')"><ArrowLeft class="size-5" /></Link>
			<div class="min-w-0">
				<h1 class="text-[17px] font-medium text-ink truncate" dir="auto">{{ title }}</h1>
				<p class="text-[13px] text-muted truncate" dir="auto">{{ meta }}</p>
			</div>
		</template>

		<template #actions>
			<span v-if="!isNew" class="hidden xl:inline-flex items-center gap-1.5 text-[13px] text-muted mr-1" role="status">
				<template v-if="saving">{{ text('Saving…', 'Enregistrement…') }}</template>
				<template v-else-if="dirty"><span class="size-2 rounded-full bg-amber-500" aria-hidden="true" />{{ text('Unsaved changes', 'Modifications non enregistrées') }}</template>
				<template v-else><Check class="size-4 text-emerald-700" aria-hidden="true" />{{ text('All changes saved', 'Toutes les modifications sont enregistrées') }}</template>
			</span>
			<Menu v-if="!isNew" width="w-72">
				<template #button="{ open, toggle }">
					<button type="button" class="icon-btn border border-line" :aria-label="text('More actions', 'Plus d’actions')" aria-haspopup="menu" :aria-expanded="open" @click="toggle"><Ellipsis class="size-5" /></button>
				</template>
				<button v-if="!resume.is_main && status === 'ready'" :class="menuItem" role="menuitem" type="button" @click="setMain"><Star class="size-4 text-muted-2" />{{ text('Make it my main resume', 'En faire mon CV principal') }}</button>
				<button v-if="resume.source === 'profile'" :class="menuItem" role="menuitem" type="button" @click="refresh"><RefreshCw class="size-4 text-muted-2" />{{ text('Refresh from my profile', 'Actualiser depuis mon profil') }}</button>
				<button v-for="target in resume.targets" :key="target.code" :class="menuItem" role="menuitem" type="button" :disabled="!ai_enabled" @click="translate(target)"><Languages class="size-4 text-muted-2" />{{ text(`${target.label} version`, `Version ${target.label}`) }}</button>
				<p v-if="resume.targets?.length && !free" class="px-3 pb-1.5 pl-[38px] text-xs text-muted">{{ text(`A translation uses 1 credit (3 rewrites included). You have ${credits}.`, `Une traduction coûte 1 crédit (3 réécritures incluses). Vous en avez ${credits}.`) }}</p>
				<button v-if="status === 'draft'" :class="menuItem" role="menuitem" type="button" @click="regenerate"><RefreshCw class="size-4 text-muted-2" />{{ isCopy ? text('Read it again', 'Relire le CV') : text('Translate it again', 'Traduire à nouveau') }}</button>
				<p v-if="status === 'draft' && resume.rewrites_left !== null && resume.rewrites_left !== undefined" class="px-3 pb-1.5 pl-[38px] text-xs text-muted">{{ fr ? `${resume.rewrites_left} réécriture${resume.rewrites_left > 1 ? 's' : ''} restante${resume.rewrites_left > 1 ? 's' : ''} pour ce crédit` : `${resume.rewrites_left} rewrite${resume.rewrites_left === 1 ? '' : 's'} left on this credit` }}</p>
				<div class="h-px bg-line-2 my-1.5" />
				<button :class="menuItem" class="!text-red-700 hover:!bg-red-50 hover:!text-red-800" role="menuitem" type="button" @click="remove"><Trash2 class="size-4 text-red-600" />{{ text('Delete', 'Supprimer') }}</button>
			</Menu>
			<button type="button" class="cta cta-primary h-10 !py-0 !px-4 !text-sm disabled:opacity-60" :disabled="saving || blocked || (!isNew && !dirty)" @click="save">{{ saving ? text('Saving…', 'Enregistrement…') : isNew ? text('Save resume', 'Enregistrer le CV') : text('Save', 'Enregistrer') }}</button>
		</template>

		<template #notice>
			<p v-if="error" class="mb-4 rounded-card bg-red-50 px-4 py-3 text-[13px] text-red-800" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-medium">{{ text('Get credits', 'Obtenir des crédits') }}</a></p>
			<p v-if="status === 'draft'" class="mb-4 rounded-card bg-amber-50 px-4 py-3 text-[13px] leading-relaxed text-amber-900">{{ text(`Check every line, then save. The AI only ${isCopy ? 'copied' : 'translated'} your resume; fix anything that is not exactly right.`, `Relisez chaque ligne, puis enregistrez. L’IA a seulement ${isCopy ? 'copié' : 'traduit'} votre CV ; corrigez tout ce qui n’est pas exact.`) }}</p>
			<p v-else-if="blocked" class="mb-4 rounded-card bg-amber-50 px-4 py-3 text-[13px] text-amber-900">{{ text('You already have 10 resumes. Replace your main one, or delete one first.', 'Vous avez déjà 10 CV. Remplacez votre CV principal ou supprimez-en un d’abord.') }}</p>
			<label v-if="isNew && main" class="mb-4 flex items-center gap-2.5 rounded-card bg-paper-2 px-4 py-3 text-[14px] font-medium text-navy"><input v-model="replace" type="checkbox" class="rounded"> {{ text(`Replace my current resume (${main.name})`, `Remplacer mon CV actuel (${main.name})`) }}</label>
			<label v-if="!isNew && status === 'draft' && !resume.is_main" class="mb-4 flex items-center gap-2.5 rounded-card bg-paper-2 px-4 py-3 text-[14px] font-medium text-navy"><input v-model="makeMain" type="checkbox" class="rounded"> {{ text('Make it my main resume when I save', 'En faire mon CV principal à l’enregistrement') }}</label>
			<div class="mb-3" />
		</template>

		<template #design>
			<div class="grid gap-4 @lg:grid-cols-[1fr_auto] mb-8">
				<label :class="label">{{ text('Name of this resume', 'Nom de ce CV') }}<input v-model="name" class="fld mt-1.5 !py-2.5 !text-[14px]" maxlength="150" required></label>
				<label v-if="isNew || resume.source === 'profile'" :class="label">{{ text('Section titles in', 'Titres des rubriques en') }}<select v-model="locale" class="fld mt-1.5 !py-2.5 !text-[14px]"><option v-for="option in locales" :key="option.code" :value="option.code">{{ option.label }}</option></select></label>
			</div>
		</template>

		<template #preview-actions>
			<Menu width="w-64">
				<template #button="{ open, toggle }">
					<button type="button" class="cta cta-secondary cta-sm" aria-haspopup="menu" :aria-expanded="open" @click="toggle"><Download class="size-4" aria-hidden="true" />{{ text('Download', 'Télécharger') }}<ChevronDown class="size-4 text-muted-2" aria-hidden="true" /></button>
				</template>
				<a v-if="!isNew && resume.has_file && status === 'ready' && !dirty" :href="route('resumes.download', resume.id)" :class="menuItem" role="menuitem"><FileText class="size-4 text-muted-2" />PDF</a>
				<p v-else-if="!isNew && status === 'ready'" class="px-3 py-2 text-xs text-muted">{{ text('Save to download the PDF with your latest changes.', 'Enregistrez pour télécharger le PDF avec vos dernières modifications.') }}</p>
				<button :class="menuItem" role="menuitem" type="button" @click="word"><FileText class="size-4 text-muted-2" />Word</button>
			</Menu>
		</template>
	</CvStudio>

	<div v-else class="flex-1 overflow-y-auto">
		<div class="px-8 max-[680px]:px-5 pt-7 pb-14">
			<Link :href="`${route('profile.show')}#resumes`" class="inline-flex items-center gap-1.5 text-[13.5px] font-medium text-muted hover:text-blue-700"><ArrowLeft class="size-4" aria-hidden="true" /> {{ text('Your resumes', 'Vos CV') }}</Link>
			<h1 class="mt-3 text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy" dir="auto">{{ title }}</h1>
			<p class="text-sm text-muted mt-1" dir="auto">{{ meta }}</p>

			<p v-if="error" class="mt-6 rounded-card bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-medium">{{ text('Get credits', 'Obtenir des crédits') }}</a></p>

			<div v-if="status === 'queued'" class="mt-8 bg-white border border-transparent rounded-card px-6 py-14 text-center" role="status">
				<RefreshCw class="size-8 text-blue-600 mx-auto animate-spin" aria-hidden="true" />
				<p class="text-lg font-medium mt-4">{{ isCopy ? text('Reading your resume…', 'Lecture de votre CV…') : text('Translating your resume…', 'Traduction de votre CV…') }}</p>
				<p class="text-sm text-muted mt-1">{{ text('This usually takes under a minute. You can leave this page and come back.', 'Cela prend en général moins d’une minute. Vous pouvez quitter cette page et revenir.') }}</p>
			</div>

			<div v-else-if="status === 'failed'" class="mt-8 bg-white border border-transparent rounded-card px-6 py-14 text-center">
				<p class="text-lg font-medium">{{ resume.error || text('Something went wrong.', 'Une erreur s’est produite.') }}</p>
				<button class="cta cta-primary mt-6" type="button" @click="regenerate">{{ text('Try again', 'Réessayer') }}</button>
			</div>

			<div v-else class="mt-8 bg-white border border-transparent rounded-card px-7 py-7">
				<p class="text-[14.5px] text-ink-700">{{ text('This is a PDF you uploaded, so it can’t be edited as it is. Make an editable copy with AI, translate it, download it, or make a new resume from your profile.', 'Ce PDF a été importé par vos soins, il ne peut donc pas être modifié tel quel. Faites-en une copie modifiable avec l’IA, traduisez-le, téléchargez-le ou créez un nouveau CV à partir de votre profil.') }}</p>
				<p v-if="!free" class="text-xs text-muted mt-2">{{ text(`An editable copy or a translation uses 1 credit (3 rewrites included). You have ${credits}.`, `Une copie modifiable ou une traduction coûte 1 crédit (3 réécritures incluses). Vous en avez ${credits}.`) }}</p>
				<div class="flex flex-wrap gap-2.5 mt-6">
					<button v-if="status === 'ready'" type="button" class="cta cta-primary" :disabled="!ai_enabled" @click="makeCopy"><FilePenLine class="size-4" aria-hidden="true" /> {{ text('Make an editable copy', 'Faire une copie modifiable') }}</button>
					<a :href="route('resumes.download', resume.id)" class="cta cta-secondary"><Download class="size-4" aria-hidden="true" /> {{ text('Download PDF', 'Télécharger le PDF') }}</a>
					<button v-if="!resume.is_main" type="button" class="cta cta-secondary" @click="setMain"><Star class="size-4" aria-hidden="true" /> {{ text('Make main', 'Définir comme principal') }}</button>
					<button v-for="target in resume.targets" :key="target.code" type="button" class="cta cta-secondary" :disabled="!ai_enabled" @click="translate(target)"><Languages class="size-4" aria-hidden="true" /> {{ text(`${target.label} version`, `Version ${target.label}`) }}</button>
					<button type="button" class="cta cta-secondary text-red-700" @click="remove"><Trash2 class="size-4" aria-hidden="true" /> {{ text('Delete', 'Supprimer') }}</button>
				</div>
			</div>
		</div>
	</div>
</template>
