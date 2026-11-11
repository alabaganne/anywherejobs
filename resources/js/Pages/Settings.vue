<script setup>
import { FileText, ShieldCheck, SlidersHorizontal, UserRound } from '@lucide/vue';
import { computed, h, nextTick, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import HistoryList from '@/Components/Candidate/HistoryList.vue';
import axios from 'axios';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'settings' }, () => page),
});

const props = defineProps({
	settings: { type: Object, required: true },
	fields: { type: Array, default: () => [] },
	cities: { type: Array, default: () => [] },
});

const page = usePage();
const { text } = useText();
const isCandidate = computed(() => props.settings.userable_type === 'candidate');
// Shown before the username field, e.g. example.com/candidates/
const profileBase = computed(() => `${route('home').replace(/^https?:\/\//, '').replace(/\/$/, '')}/candidates/`);

const tabs = computed(() => [
	{ key: 'profile', label: text('Profile', 'Profil'), icon: UserRound },
	...(isCandidate.value ? [
		{ key: 'resume', label: text('Resume & experience', 'CV et expérience'), icon: FileText },
		{ key: 'preferences', label: text('Job preferences', 'Préférences d’emploi'), icon: SlidersHorizontal },
	] : []),
	{ key: 'security', label: text('Security', 'Sécurité'), icon: ShieldCheck },
]);
// ?tab= opens a tab directly, so other pages can link to it.
const askedTab = new URLSearchParams(page.url.split('?')[1] || '').get('tab');
const tab = ref(tabs.value.some((item) => item.key === askedTab) ? askedTab : 'profile');
watch(tab, (key) => {
	const url = new URL(window.location.href);
	if (key === 'profile') url.searchParams.delete('tab');
	else url.searchParams.set('tab', key);
	window.history.replaceState(window.history.state, '', url);
});

const accountForm = useForm({
	name: props.settings.name,
	username: props.settings.username || '',
	phone_number: props.settings.phone_number,
	linkedin_profile_url: props.settings.linkedin_profile_url,
	city_id: props.settings.city_id,
	field_id: props.settings.field_id,
	about: props.settings.about,
	website: props.settings.website,
	headline: props.settings.headline || '',
	experience_years: props.settings.experience_years,
	country_code: props.settings.country_code || '',
	location: props.settings.location || '',
	languages: props.settings.languages || [],
	experience: props.settings.experience || [],
	education: props.settings.education || [],
	projects: props.settings.projects || [],
	preferences: {
		kinds: props.settings.preferences?.kinds || [],
		countries: props.settings.preferences?.countries || [],
		work_types: props.settings.preferences?.work_types || [],
		fields: props.settings.preferences?.fields || [],
		skills: props.settings.preferences?.skills || [],
		seniority: props.settings.preferences?.seniority || [],
		include_unknown_eligibility: props.settings.preferences?.include_unknown_eligibility || false,
		needs_visa: props.settings.preferences?.needs_visa || false,
	},
});

const targetCountries = ref(accountForm.preferences.countries.join(', '));
const skillsText = ref(accountForm.preferences.skills.join(', '));
const languagesText = ref(accountForm.languages.join(', '));
const splitValues = (value) => [...new Set(value.split(',').map((part) => part.trim()).filter(Boolean))];
const saveAccount = () => {
	accountForm.country_code = accountForm.country_code.toUpperCase();
	accountForm.preferences.countries = splitValues(targetCountries.value.toUpperCase());
	accountForm.preferences.skills = splitValues(skillsText.value);
	accountForm.languages = splitValues(languagesText.value);
	accountForm.put(route('profile.update'), { preserveScroll: true, onError: showFirstError });
};
// One form spans three tabs, so an error can sit on a tab that is not open.
const tabForError = (key) =>
	/^(experience|education|projects)\b/.test(key) ? 'resume'
		: key.startsWith('preferences') && !key.startsWith('preferences.skills') ? 'preferences'
			: 'profile';
const errorTabs = computed(() => [...new Set(Object.keys(accountForm.errors).map(tabForError))]);
// Errors can sit far above the Save button, so bring the first one into view once Inertia has restored the scroll.
const showFirstError = () => {
	if (errorTabs.value.length && !errorTabs.value.includes(tab.value)) tab.value = errorTabs.value[0];
	nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => {
		const error = document.querySelector('#settings-details [data-error]');
		error?.scrollIntoView({ behavior: 'smooth', block: 'center' });
		error?.parentElement.closest('label, div')?.querySelector('input, select, textarea')?.focus({ preventScroll: true });
	})));
};
// List fields come back as "languages.0" or "preferences.skills.2", so match on the prefix too.
const accountError = (key) =>
	Object.entries(accountForm.errors).find(([name]) => name === key || name.startsWith(`${key}.`))?.[1];
const hasAccountErrors = computed(() => Object.keys(accountForm.errors).length > 0);

const mainResume = computed(() => props.settings.main_resume);
const aiFree = computed(() => !page.props.billing?.enabled || page.props.billing?.free);
const readBillingUrl = ref('');

const workLabel = computed(() => ({ onsite: text('On-site', 'Sur site'), hybrid: text('Hybrid', 'Hybride'), remote: text('Remote', 'À distance') }));
const levelLabel = computed(() => ({
	entry: text('Entry', 'Débutant'), graduate: text('Graduate', 'Jeune diplômé'), mid: text('Mid-level', 'Confirmé'),
	senior: text('Senior', 'Senior'), lead: text('Lead', 'Lead'), executive: text('Executive', 'Direction'),
}));
const historyFields = computed(() => ({
	experience: [
		{ key: 'title', label: text('Job title', 'Intitulé du poste') }, { key: 'company', label: text('Company', 'Entreprise') },
		{ key: 'location', label: text('Location', 'Lieu') }, { key: 'current', label: text('I work here now', 'J’y travaille actuellement'), type: 'checkbox' },
		{ key: 'start', label: text('Start', 'Début'), placeholder: '2022-09' }, { key: 'end', label: text('End', 'Fin'), placeholder: '2024-06' },
		{ key: 'summary', label: text('What you did', 'Ce que vous avez fait'), type: 'textarea', wide: true },
	],
	education: [
		{ key: 'school', label: text('School', 'Établissement') }, { key: 'degree', label: text('Degree', 'Diplôme') },
		{ key: 'field', label: text('Field of study', 'Domaine d’études'), wide: true },
		{ key: 'start', label: text('Start', 'Début'), placeholder: '2019' }, { key: 'end', label: text('End', 'Fin'), placeholder: '2022' },
	],
	projects: [
		{ key: 'name', label: text('Project name', 'Nom du projet') }, { key: 'url', label: text('Link', 'Lien'), type: 'url', placeholder: 'https://…' },
		{ key: 'summary', label: text('What it is', 'De quoi il s’agit'), type: 'textarea', wide: true },
	],
}));

// Resume reading: suggestions are shown for review and only fill the form; Save changes stores them.
const reading = ref(false);
const readError = ref('');
const review = ref(null);
const appliedNote = ref(false);
const readResume = async () => {
	reading.value = true;
	readError.value = '';
	appliedNote.value = false;
	try {
		const { data } = await axios.post(route('profile.resume.read'));
		const s = data.suggestions;
		const join = (list) => (list || []).join(', ');
		const titles = (list, key) => (list || []).map((row) => row[key]).filter(Boolean).join('; ');
		const rows = [
			{ key: 'headline', label: text('Headline', 'Titre du profil'), now: accountForm.headline, next: s.headline },
			{ key: 'about', label: text('About', 'À propos'), now: accountForm.about, next: s.about },
			{ key: 'experience_years', label: text('Years of experience', 'Années d’expérience'), now: accountForm.experience_years ?? '', next: s.experience_years || '' },
			{ key: 'location', label: text('City or location', 'Ville ou lieu'), now: accountForm.location, next: s.location },
			{ key: 'country_code', label: text('Country code', 'Code pays'), now: accountForm.country_code, next: s.country_code },
			{ key: 'phone_number', label: text('Phone', 'Téléphone'), now: accountForm.phone_number, next: s.phone_number },
			{ key: 'website', label: text('Website or portfolio', 'Site web ou portfolio'), now: accountForm.website, next: s.website },
			{ key: 'linkedin_profile_url', label: text('LinkedIn URL', 'URL LinkedIn'), now: accountForm.linkedin_profile_url, next: s.linkedin_profile_url },
			{ key: 'languages', label: text('Languages', 'Langues'), now: languagesText.value, next: join(s.languages), value: s.languages },
			{ key: 'skills', label: text('Skills', 'Compétences'), now: skillsText.value, next: join(s.skills), value: s.skills },
			{ key: 'experience', label: text('Experience', 'Expérience'), now: titles(accountForm.experience, 'title'), next: titles(s.experience, 'title'), value: s.experience },
			{ key: 'education', label: text('Education', 'Formation'), now: titles(accountForm.education, 'school'), next: titles(s.education, 'school'), value: s.education },
			{ key: 'projects', label: text('Projects', 'Projets'), now: titles(accountForm.projects, 'name'), next: titles(s.projects, 'name'), value: s.projects },
		]
			.map((row) => ({ ...row, now: String(row.now ?? ''), next: String(row.next ?? ''), value: row.value ?? s[row.key] }))
			.filter((row) => row.next && row.next !== row.now)
			.map((row) => ({ ...row, use: !row.now }));
		review.value = rows;
	} catch (error) {
		readBillingUrl.value = error.response?.data?.billing_url || '';
		readError.value = error.response?.status === 403 ? text('Verify your email address to use the AI tools.', 'Vérifiez votre adresse e-mail pour utiliser les outils IA.')
			: error.response?.data?.message || text('We could not read your resume right now. Please try again in a minute.', 'Impossible de lire votre CV pour le moment. Réessayez dans une minute.');
	} finally {
		reading.value = false;
	}
};
const applyReview = () => {
	for (const row of review.value.filter((item) => item.use)) {
		if (row.key === 'languages') languagesText.value = row.value.join(', ');
		else if (row.key === 'skills') skillsText.value = [...new Set([...splitValues(skillsText.value), ...row.value])].join(', ');
		else if (['experience', 'education', 'projects'].includes(row.key)) accountForm[row.key] = row.value.map((item) => ({ ...item }));
		else accountForm[row.key] = row.value;
	}
	review.value = null;
	appliedNote.value = true;
	nextTick(() => document.getElementById('settings-details')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
};

const emailForm = useForm({ email: '', password: '' });
const changeEmail = () =>
	emailForm.post(route('email.change'), {
		preserveScroll: true,
		preserveState: true,
		onSuccess: () => emailForm.reset(),
	});
const cancelEmailChange = () => router.delete(route('email.change.cancel'), { preserveScroll: true, preserveState: true });
const verificationSent = ref(false);
const resendVerification = () =>
	router.post(route('verification.send'), {}, { preserveScroll: true, preserveState: true, onSuccess: () => (verificationSent.value = true) });
const openEmailSettings = () => {
	tab.value = 'security';
	nextTick(() => {
		document.getElementById('settings-email')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
		document.getElementById('settings-new-email')?.focus({ preventScroll: true });
	});
};

const passwordForm = useForm({
	current_password: '',
	password: '',
	password_confirmation: '',
});

const savePassword = () =>
	passwordForm.put(route('password.change'), {
		preserveScroll: true,
		onSuccess: () => passwordForm.reset(),
	});

const deleteForm = useForm({ password: '' });
const confirmingDelete = ref(false);
const deletePassword = ref(null);
const deleteButton = ref(null);

const startDelete = () => {
	confirmingDelete.value = true;
	nextTick(() => deletePassword.value?.focus());
};
const cancelDelete = () => {
	confirmingDelete.value = false;
	deleteForm.reset();
	deleteForm.clearErrors();
	nextTick(() => deleteButton.value?.focus());
};
const deleteAccount = () =>
	deleteForm.delete(route('profile.destroy'), {
		preserveScroll: true,
		onError: () => deletePassword.value?.focus(),
		onFinish: () => deleteForm.reset('password'),
	});
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<PageHeader :title="text('Settings', 'Paramètres')" :description="isCandidate ? text('Manage your profile, resume, job preferences and security.', 'Gérez votre profil, votre CV, vos préférences d’emploi et votre sécurité.') : text('Manage your profile and security.', 'Gérez votre profil et votre sécurité.')" />

		<div class="grid grid-cols-[226px_1fr] gap-[22px] items-start max-[860px]:grid-cols-1">
			<!-- SUB NAV -->
			<div class="flex flex-col gap-[3px] sticky top-[92px] max-[860px]:flex-row max-[860px]:flex-wrap max-[860px]:static">
				<button
					v-for="item in tabs"
					:key="item.key"
					class="flex cursor-pointer items-center gap-[11px] rounded-card px-[13px] py-[11px] text-left font-body text-[14.5px] font-medium transition-colors"
					:class="tab === item.key ? 'bg-white text-brand' : 'text-subtle hover:bg-white hover:text-navy'"
					type="button"
					:aria-pressed="tab === item.key"
					@click="tab = item.key"
				>
					<component :is="item.icon" class="size-[18px] shrink-0" :class="tab === item.key ? 'text-blue-600' : 'text-muted-2'" aria-hidden="true" /> {{ item.label }}
					<span v-if="errorTabs.includes(item.key)" class="ml-auto w-2 h-2 rounded-full bg-red-600" :aria-label="text('Has errors', 'Contient des erreurs')"></span>
				</button>
			</div>

			<!-- PANELS -->
			<div>
				<form v-if="tab !== 'security'" id="settings-details" class="scroll-mt-24" @submit.prevent="saveAccount">
					<p v-if="hasAccountErrors" class="text-sm text-red-600 font-medium mb-[18px] rounded-card bg-red-50 px-4 py-3" role="alert">
						{{ text('Please fix the fields marked in red', 'Corrigez les champs marqués en rouge') }}<template v-if="errorTabs.some((key) => key !== tab)"> {{ text('(also on', '(aussi dans') }}
							<template v-for="(key, index) in errorTabs.filter((key) => key !== tab)" :key="key">{{ index ? ', ' : '' }}<button type="button" class="underline" @click="tab = key">{{ tabs.find((item) => item.key === key)?.label }}</button></template>)</template>.
					</p>
					<p v-else-if="appliedNote && accountForm.isDirty" class="text-[15px] text-blue-700 mb-[18px] rounded-card bg-white px-6 py-4" role="status">{{ text('Details from your resume are filled in on the Profile and Resume tabs. Check them, then press Save changes.', 'Les informations de votre CV sont remplies dans les onglets Profil et CV. Vérifiez-les, puis cliquez sur Enregistrer.') }}</p>

					<!-- PROFILE -->
					<template v-if="tab === 'profile'">
						<div class="bg-white border border-transparent rounded-card mb-[18px]">
							<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
								<h3 class="text-[20px] leading-[1.35] text-navy">{{ text('Personal details', 'Informations personnelles') }}</h3>
								<p class="text-[15px] text-subtle mt-1">{{ text('Your name and how people can reach you.', 'Votre nom et comment vous joindre.') }}</p>
							</div>
							<div class="px-7 pt-5 pb-7 max-[560px]:px-5 grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Full name', 'Nom complet') }}</span>
									<input v-model="accountForm.name" class="fld" type="text" autocomplete="name">
									<span v-if="accountForm.errors.name" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.name }}</span>
								</label>
								<div>
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Email', 'E-mail') }}</span>
									<div class="fld flex items-center justify-between gap-3 bg-paper-3">
										<span class="truncate">{{ settings.email }}</span>
										<button type="button" class="text-[13px] font-medium text-blue-700 hover:underline shrink-0" @click="openEmailSettings">{{ text('Change', 'Modifier') }}</button>
									</div>
								</div>
								<label v-if="isCandidate" class="block col-span-2 max-[560px]:col-span-1">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Username', 'Nom d’utilisateur') }}</span>
									<span class="fld flex items-center gap-0.5 has-[:focus-visible]:border-blue-500">
										<span class="text-muted shrink-0 max-[560px]:hidden">{{ profileBase }}</span>
										<input v-model="accountForm.username" class="border-0 bg-transparent p-0 outline-none focus:ring-0 w-full min-w-0" type="text" maxlength="30" autocomplete="off" autocapitalize="none" spellcheck="false" :placeholder="text('your-name', 'votre-nom')" aria-describedby="username-note">
									</span>
									<span v-if="accountForm.errors.username" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.username }}</span>
									<span v-else id="username-note" class="block text-[13px] text-subtle mt-2">{{ text('Your username is also your public profile link. Use lowercase letters, numbers and dashes. Leave it empty to use your name.', 'Votre nom d’utilisateur est aussi le lien de votre profil public. Utilisez des minuscules, des chiffres et des tirets. Laissez vide pour utiliser votre nom.') }}</span>
								</label>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Phone', 'Téléphone') }}</span>
									<input v-model="accountForm.phone_number" class="fld" type="tel" autocomplete="tel">
									<span v-if="accountForm.errors.phone_number" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.phone_number }}</span>
								</label>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('LinkedIn URL', 'URL LinkedIn') }}</span>
									<input v-model="accountForm.linkedin_profile_url" class="fld" type="url" placeholder="https://linkedin.com/in/…">
									<span v-if="accountForm.errors.linkedin_profile_url" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.linkedin_profile_url }}</span>
								</label>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ isCandidate ? text('Website or portfolio', 'Site web ou portfolio') : text('Website', 'Site web') }}</span>
									<input v-model="accountForm.website" class="fld" type="url" placeholder="https://…">
									<span v-if="accountError('website')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('website') }}</span>
								</label>
							</div>
						</div>

						<div class="bg-white border border-transparent rounded-card mb-[18px]">
							<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
								<h3 class="text-[20px] leading-[1.35] text-navy">{{ text('About you', 'À propos de vous') }}</h3>
								<p class="text-[15px] text-subtle mt-1">{{ isCandidate ? text('What companies see first on your profile.', 'Ce que les entreprises voient en premier sur votre profil.') : text('A short intro shown on your profile.', 'Une courte présentation affichée sur votre profil.') }}</p>
							</div>
							<div class="px-7 pt-5 pb-7 max-[560px]:px-5">
								<div v-if="isCandidate" class="grid grid-cols-2 gap-4 mb-4 max-[560px]:grid-cols-1">
									<label class="block">
										<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Headline', 'Titre du profil') }}</span>
										<input v-model="accountForm.headline" class="fld" maxlength="255" :placeholder="text('Your role or area of interest', 'Votre métier ou domaine d’intérêt')">
										<span v-if="accountError('headline')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('headline') }}</span>
									</label>
									<label class="block">
										<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Field of work or study', 'Domaine de travail ou d’études') }}</span>
										<select v-model="accountForm.field_id" class="fld">
											<option :value="null">{{ text('Not selected', 'Non sélectionné') }}</option>
											<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
										</select>
										<span v-if="accountForm.errors.field_id" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.field_id }}</span>
									</label>
									<label class="block">
										<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Years of experience', 'Années d’expérience') }}</span>
										<input v-model.number="accountForm.experience_years" class="fld" type="number" min="0" max="80">
										<span v-if="accountError('experience_years')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('experience_years') }}</span>
									</label>
									<label class="block">
										<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Languages', 'Langues') }}</span>
										<input v-model="languagesText" class="fld" :placeholder="text('Arabic, French, English', 'Arabe, français, anglais')">
										<span v-if="accountError('languages')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('languages') }}</span>
									</label>
									<label class="block col-span-2 max-[560px]:col-span-1">
										<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Skills', 'Compétences') }}</span>
										<input v-model="skillsText" class="fld" placeholder="Python, React, Docker">
										<span class="block text-xs text-muted mt-[7px]">{{ text('Comma-separated. Shown on your profile and used to match roles.', 'Séparées par des virgules. Affichées sur votre profil et utilisées pour trouver des offres.') }}</span>
										<span v-if="accountError('preferences.skills')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('preferences.skills') }}</span>
									</label>
								</div>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('About', 'À propos') }}</span>
									<textarea v-model="accountForm.about" class="fld"></textarea>
									<span v-if="accountForm.errors.about" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.about }}</span>
									<span class="block text-xs text-muted mt-[7px]">{{ text('Brief intro shown at the top of your profile.', 'Courte présentation affichée en haut de votre profil.') }}</span>
								</label>
							</div>
						</div>

						<div class="bg-white border border-transparent rounded-card mb-[18px]">
							<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
								<h3 class="text-[20px] leading-[1.35] text-navy">{{ text('Location', 'Localisation') }}</h3>
								<p class="text-[15px] text-subtle mt-1">{{ text('Where you are based.', 'Là où vous êtes basé(e).') }}</p>
							</div>
							<div class="px-7 pt-5 pb-7 max-[560px]:px-5 grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('City or location', 'Ville ou lieu') }}</span>
									<input v-model="accountForm.location" class="fld" :placeholder="text('Your location anywhere in the world', 'Votre lieu, où que vous soyez dans le monde')">
									<span v-if="accountForm.errors.location" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.location }}</span>
								</label>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Country code', 'Code pays') }}</span>
									<input v-model="accountForm.country_code" class="fld" maxlength="2" placeholder="TN, FR, US...">
									<span v-if="accountForm.errors.country_code" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.country_code }}</span>
								</label>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('City', 'Ville') }}</span>
									<select v-model="accountForm.city_id" class="fld">
										<option :value="null">{{ text('Not selected', 'Non sélectionnée') }}</option>
										<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
									</select>
									<span v-if="accountForm.errors.city_id" data-error class="block text-[13px] text-red-700 mt-2">{{ accountForm.errors.city_id }}</span>
								</label>
							</div>
						</div>
					</template>

					<!-- RESUME & EXPERIENCE -->
					<template v-if="tab === 'resume'">
						<div class="bg-white border border-transparent rounded-card mb-[18px]">
							<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
								<h3 class="text-[20px] leading-[1.35] text-navy">{{ text('Resume', 'CV') }}</h3>
								<p class="text-[15px] text-subtle mt-1">{{ text('Your resumes live on your profile page: upload PDFs, make one from this profile with a theme, translate them and pick the main one. The main one is read to fill in your profile and used with your profile to tailor CVs and cover letters.', 'Vos CV se trouvent sur votre page de profil : importez des PDF, créez-en un depuis ce profil avec un thème, traduisez-les et choisissez le CV principal. Le CV principal sert à remplir votre profil et, avec votre profil, à adapter vos CV et lettres de motivation.') }}</p>
							</div>
							<div class="px-7 pt-5 pb-7 max-[560px]:px-5">
								<div class="flex items-center justify-between gap-4 flex-wrap">
									<div class="flex items-center gap-3 min-w-0">
										<FileText class="w-5 h-5 text-muted shrink-0" aria-hidden="true" />
										<a v-if="mainResume" :href="route('profile.resume.show')" class="text-[14px] font-medium text-blue-700 hover:underline truncate">{{ mainResume.name }}</a>
										<span v-else class="text-[13.5px] text-muted">{{ text('No resume yet.', 'Aucun CV pour l’instant.') }}</span>
									</div>
									<a :href="`${route('profile.show')}#resumes`" class="cta cta-secondary">{{ text('Manage your resumes', 'Gérer vos CV') }}</a>
								</div>
								<div v-if="mainResume && mainResume.source === 'uploaded' && !review" class="mt-4 rounded-card bg-blue-50 px-4 py-3.5 flex items-center justify-between gap-3 flex-wrap">
									<p class="text-[13.5px] text-ink-700">{{ text('Save time: we read your main resume and suggest profile details. You check them before anything is saved.', 'Gagnez du temps : nous lisons votre CV principal et proposons des informations pour votre profil. Vous les vérifiez avant tout enregistrement.') }}<template v-if="!aiFree"> {{ text('Uses 1 credit.', 'Utilise 1 crédit.') }}</template></p>
									<button class="cta cta-primary disabled:opacity-60" type="button" :disabled="reading" @click="readResume">{{ reading ? text('Reading your resume…', 'Lecture de votre CV…') : text('Fill my profile from this resume', 'Remplir mon profil depuis ce CV') }}</button>
								</div>
								<p v-if="mainResume && mainResume.source === 'profile'" class="mt-4 text-[13px] text-muted">{{ text('Your main resume is made from this profile. After you save changes here, open it on your profile page and press “Refresh from my profile”.', 'Votre CV principal est créé à partir de ce profil. Après avoir enregistré vos modifications ici, ouvrez-le depuis votre page de profil et cliquez sur « Actualiser depuis mon profil ».') }}</p>
								<p v-if="readError" class="text-xs text-red-600 mt-[10px]" role="alert">{{ readError }} <a v-if="readBillingUrl" :href="readBillingUrl" class="underline font-medium">{{ text('Get credits', 'Obtenir des crédits') }}</a></p>
								<div v-if="review" class="mt-4">
									<h4 class="text-[15px] font-medium">{{ text('From your resume', 'Depuis votre CV') }}</h4>
									<p v-if="!review.length" class="text-[13.5px] text-muted mt-1">{{ text('Your profile already has everything we found.', 'Votre profil contient déjà tout ce que nous avons trouvé.') }}</p>
									<template v-else>
										<p class="text-[13px] text-muted mt-1 mb-3">{{ text('Tick what you want to use. It fills the form; nothing is saved until you press Save changes.', 'Cochez ce que vous voulez utiliser. Cela remplit le formulaire ; rien n’est enregistré tant que vous n’avez pas cliqué sur Enregistrer.') }}</p>
										<ul class="flex flex-col gap-2">
											<li v-for="row in review" :key="row.key">
												<label class="flex gap-3 items-start rounded-card bg-paper-3 px-3.5 py-3 cursor-pointer">
													<input v-model="row.use" type="checkbox" class="mt-1">
													<span class="min-w-0 text-[13.5px]">
														<b class="font-medium block">{{ row.label }}</b>
														<span class="block text-ink-700 whitespace-pre-line break-words">{{ row.next }}</span>
														<span v-if="row.now" class="block text-muted mt-1 break-words">{{ text('Now:', 'Actuellement :') }} {{ row.now }}</span>
													</span>
												</label>
											</li>
										</ul>
									</template>
									<div class="flex gap-2.5 mt-3">
										<button v-if="review.length" class="cta cta-primary" type="button" @click="applyReview">{{ text('Use selected', 'Utiliser la sélection') }}</button>
										<button class="cta cta-secondary" type="button" @click="review = null">{{ text('Close', 'Fermer') }}</button>
									</div>
								</div>
							</div>
						</div>

						<div class="bg-white border border-transparent rounded-card mb-[18px] px-7 pt-1 pb-7 max-[560px]:px-5">
							<HistoryList v-model="accountForm.experience" name="experience" :title="text('Experience', 'Expérience')" :add-label="text('Add a job', 'Ajouter un poste')" :fields="historyFields.experience" :errors="accountForm.errors" />
						</div>
						<div class="bg-white border border-transparent rounded-card mb-[18px] px-7 pt-1 pb-7 max-[560px]:px-5">
							<HistoryList v-model="accountForm.education" name="education" :title="text('Education', 'Formation')" :add-label="text('Add a school', 'Ajouter un établissement')" :fields="historyFields.education" :errors="accountForm.errors" />
						</div>
						<div class="bg-white border border-transparent rounded-card mb-[18px] px-7 pt-1 pb-7 max-[560px]:px-5">
							<HistoryList v-model="accountForm.projects" name="projects" :title="text('Projects', 'Projets')" :add-label="text('Add a project', 'Ajouter un projet')" :fields="historyFields.projects" :errors="accountForm.errors" />
						</div>
					</template>

					<!-- JOB PREFERENCES -->
					<div v-if="tab === 'preferences'" class="bg-white border border-transparent rounded-card mb-[18px]">
						<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
							<h3 class="text-[20px] leading-[1.35] text-navy">{{ text('Job preferences', 'Préférences d’emploi') }}</h3>
							<p class="text-[15px] text-subtle mt-1">{{ text('These shape your dashboard. You can still browse every role. Manage email alerts in Saved searches.', 'Elles personnalisent votre tableau de bord. Vous pouvez toujours parcourir toutes les offres. Gérez vos alertes e-mail dans Recherches sauvegardées.') }}</p>
						</div>
						<div class="px-7 pt-5 pb-7 max-[560px]:px-5">
							<p v-if="accountForm.errors.preferences" data-error class="text-xs text-red-600 mb-4">{{ accountForm.errors.preferences }}</p>
							<fieldset class="mb-5"><legend class="text-[14px] font-medium mb-2 text-navy">{{ text('Role types', 'Types d’offres') }}</legend><div class="flex gap-5 text-sm"><label class="flex gap-2 items-center"><input v-model="accountForm.preferences.kinds" type="checkbox" value="internship"> {{ text('Internships', 'Stages') }}</label><label class="flex gap-2 items-center"><input v-model="accountForm.preferences.kinds" type="checkbox" value="job"> {{ text('Jobs', 'Emplois') }}</label></div><p v-if="accountError('preferences.kinds')" data-error class="text-xs text-red-600 mt-[7px]">{{ accountError('preferences.kinds') }}</p></fieldset>
							<fieldset class="mb-5"><legend class="text-[14px] font-medium mb-2 text-navy">{{ text('Work arrangements', 'Modes de travail') }}</legend><div class="flex flex-wrap gap-5 text-sm"><label v-for="mode in ['onsite', 'hybrid', 'remote']" :key="mode" class="flex gap-2 items-center capitalize"><input v-model="accountForm.preferences.work_types" type="checkbox" :value="mode"> {{ workLabel[mode] }}</label></div><p v-if="accountError('preferences.work_types')" data-error class="text-xs text-red-600 mt-[7px]">{{ accountError('preferences.work_types') }}</p></fieldset>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<label class="block col-span-2 max-[560px]:col-span-1">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Target countries', 'Pays visés') }}</span>
									<input v-model="targetCountries" class="fld" placeholder="TN, FR, DE">
									<span class="block text-xs text-muted mt-[7px]">{{ text('Comma-separated country codes. Leave empty to explore all locations.', 'Codes pays séparés par des virgules. Laissez vide pour explorer tous les lieux.') }}</span>
									<span v-if="accountError('preferences.countries')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('preferences.countries') }}</span>
								</label>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Fields of interest', 'Domaines d’intérêt') }}</span>
									<select v-model="accountForm.preferences.fields" class="fld" multiple><option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option></select>
									<span v-if="accountError('preferences.fields')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('preferences.fields') }}</span>
								</label>
								<label class="block">
									<span class="block text-[14px] font-medium mb-2 text-navy">{{ text('Experience levels', 'Niveaux d’expérience') }}</span>
									<select v-model="accountForm.preferences.seniority" class="fld capitalize" multiple><option v-for="level in ['entry','graduate','mid','senior','lead','executive']" :key="level" :value="level">{{ levelLabel[level] }}</option></select>
									<span v-if="accountError('preferences.seniority')" data-error class="block text-[13px] text-red-700 mt-2">{{ accountError('preferences.seniority') }}</span>
								</label>
							</div>
							<label class="flex gap-2 items-start mt-5 text-sm"><input v-model="accountForm.preferences.include_unknown_eligibility" type="checkbox" class="mt-1"> {{ text('Include remote roles whose eligibility from Tunisia is unknown. Check the employer\'s requirements before applying.', 'Inclure les offres à distance dont l’accessibilité depuis la Tunisie est inconnue. Vérifiez les conditions de l’employeur avant de postuler.') }}</label>
							<p v-if="accountError('preferences.include_unknown_eligibility')" data-error class="text-xs text-red-600 mt-[7px]">{{ accountError('preferences.include_unknown_eligibility') }}</p>
							<label class="flex gap-2 items-start mt-3 text-sm"><input v-model="accountForm.preferences.needs_visa" type="checkbox" class="mt-1"> {{ text('I need a visa to work abroad. On-site and hybrid roles in other countries then only count when they offer visa sponsorship or relocation, and remote roles must be open to people in my country.', 'J’ai besoin d’un visa pour travailler à l’étranger. Les offres sur site et hybrides dans d’autres pays ne comptent alors que si elles proposent un parrainage de visa ou une aide à la relocalisation, et les offres à distance doivent être ouvertes aux personnes de mon pays.') }}</label>
							<p v-if="accountError('preferences.needs_visa')" data-error class="text-xs text-red-600 mt-[7px]">{{ accountError('preferences.needs_visa') }}</p>
						</div>
					</div>

					<div class="flex flex-wrap items-center justify-end gap-4 pt-1.5">
						<span v-if="hasAccountErrors" class="text-[13.5px] font-medium text-red-600">{{ text('Not saved. Fix the fields marked in red.', 'Non enregistré. Corrigez les champs marqués en rouge.') }}</span>
						<span v-else-if="accountForm.isDirty" class="text-[13.5px] text-muted">{{ text('You have unsaved changes.', 'Vous avez des modifications non enregistrées.') }}</span>
						<span v-else-if="accountForm.recentlySuccessful" class="text-[13.5px] font-medium text-emerald-700">{{ text('Saved.', 'Enregistré.') }}</span>
						<button class="cta cta-primary disabled:opacity-60" type="submit" :disabled="accountForm.processing">{{ accountForm.processing ? text('Saving…', 'Enregistrement…') : text('Save changes', 'Enregistrer') }}</button>
					</div>
				</form>

				<!-- SECURITY -->
				<section v-if="tab === 'security'">
					<form id="settings-email" class="bg-white border border-transparent rounded-card mb-[18px] scroll-mt-24" @submit.prevent="changeEmail">
						<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
							<h3 class="text-[20px] leading-[1.35] text-navy">{{ text('Email address', 'Adresse e-mail') }}</h3>
							<p class="text-[15px] text-subtle mt-1">{{ text('You sign in with this email, and we send your alerts and messages to it.', 'Vous vous connectez avec cet e-mail, et nous y envoyons vos alertes et vos messages.') }}</p>
						</div>
						<div class="px-7 pt-5 pb-7 max-[560px]:px-5">
							<div class="flex items-center gap-2.5 flex-wrap">
								<b class="text-[14.5px] font-medium break-all">{{ settings.email }}</b>
								<span v-if="settings.email_verified" class="text-xs font-medium px-[11px] py-[5px] rounded-full bg-green-100 text-emerald-700">{{ text('Verified', 'Vérifié') }}</span>
								<span v-else class="text-xs font-medium px-[11px] py-[5px] rounded-full bg-amber-100 text-amber-700">{{ text('Not verified', 'Non vérifié') }}</span>
								<span v-if="!settings.email_verified && verificationSent" class="text-[13px] font-medium text-emerald-700" role="status">{{ text('Link sent. Check your inbox.', 'Lien envoyé. Vérifiez votre boîte de réception.') }}</span>
								<button v-else-if="!settings.email_verified" type="button" class="text-[13px] font-medium text-blue-700 hover:underline" @click="resendVerification">{{ text('Send the verification link again', 'Renvoyer le lien de vérification') }}</button>
							</div>
							<div v-if="settings.pending_email" class="mt-4 rounded-card bg-blue-50 px-4 py-3.5 flex items-center justify-between gap-3 flex-wrap" role="status">
								<p class="text-[13.5px] text-ink-700 min-w-0">{{ text('We sent a link to', 'Nous avons envoyé un lien à') }} <b class="break-all">{{ settings.email }}</b> {{ text('to confirm the change to', 'pour confirmer le changement vers') }} <b class="break-all">{{ settings.pending_email }}</b>. {{ text('Open it within 60 minutes. Nothing changes until you do.', 'Ouvrez-le dans les 60 minutes. Rien ne change tant que vous ne l’avez pas fait.') }}</p>
								<button type="button" class="cta cta-secondary !px-3.5 !py-2 !text-[13.5px]" @click="cancelEmailChange">{{ text('Cancel the change', 'Annuler le changement') }}</button>
							</div>
							<p class="text-[13px] text-muted mt-5 mb-4">{{ settings.email_verified ? text('To change it, we first send a link to your current email so you can confirm. Then you verify the new address.', 'Pour la modifier, nous envoyons d’abord un lien à votre e-mail actuel pour que vous confirmiez. Vous vérifiez ensuite la nouvelle adresse.') : text('Your email is not verified yet, so you can correct it here. We then send a verification link to the new address.', 'Votre e-mail n’est pas encore vérifié, vous pouvez donc le corriger ici. Nous envoyons ensuite un lien de vérification à la nouvelle adresse.') }}</p>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div>
									<label class="block text-[14px] font-medium mb-2 text-navy" for="settings-new-email">{{ text('New email', 'Nouvel e-mail') }}</label>
									<input id="settings-new-email" v-model="emailForm.email" class="fld" type="email" autocomplete="email" required>
									<div v-if="emailForm.errors.email" class="text-xs text-red-600 mt-[7px]">{{ emailForm.errors.email }}</div>
								</div>
								<div>
									<label class="block text-[14px] font-medium mb-2 text-navy" for="settings-email-password">{{ text('Current password', 'Mot de passe actuel') }}</label>
									<input id="settings-email-password" v-model="emailForm.password" class="fld" type="password" autocomplete="current-password" required>
									<div v-if="emailForm.errors.password" class="text-xs text-red-600 mt-[7px]">{{ emailForm.errors.password }}</div>
								</div>
							</div>
						</div>
						<div class="px-[22px] py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-card">
							<button class="cta cta-primary disabled:opacity-60" type="submit" :disabled="emailForm.processing">{{ emailForm.processing ? text('Sending…', 'Envoi…') : settings.email_verified ? text('Send confirmation link', 'Envoyer le lien de confirmation') : text('Change email', 'Changer l’e-mail') }}</button>
						</div>
					</form>

					<form class="bg-white border border-transparent rounded-card mb-[18px]" @submit.prevent="savePassword">
						<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
							<h3 class="text-[20px] leading-[1.35] text-navy">{{ text('Password', 'Mot de passe') }}</h3>
							<p class="text-[15px] text-subtle mt-1">{{ text('Use a strong password you don\'t reuse elsewhere.', 'Utilisez un mot de passe solide que vous n’utilisez pas ailleurs.') }}</p>
						</div>
						<div class="px-7 pt-5 pb-7 max-[560px]:px-5">
							<div class="mb-4">
								<label class="block text-[14px] font-medium mb-2 text-navy" for="current-password">{{ text('Current password', 'Mot de passe actuel') }}</label>
								<input id="current-password" v-model="passwordForm.current_password" class="fld" type="password" placeholder="••••••••" autocomplete="current-password">
								<div v-if="passwordForm.errors.current_password" class="text-xs text-red-600 mt-[7px]">{{ passwordForm.errors.current_password }}</div>
							</div>
							<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
								<div class="mb-4">
									<label class="block text-[14px] font-medium mb-2 text-navy" for="new-password">{{ text('New password', 'Nouveau mot de passe') }}</label>
									<input id="new-password" v-model="passwordForm.password" class="fld" type="password" placeholder="••••••••" autocomplete="new-password">
									<div v-if="passwordForm.errors.password" class="text-xs text-red-600 mt-[7px]">{{ passwordForm.errors.password }}</div>
								</div>
								<div class="mb-4">
									<label class="block text-[14px] font-medium mb-2 text-navy" for="confirm-password">{{ text('Confirm new password', 'Confirmer le nouveau mot de passe') }}</label>
									<input id="confirm-password" v-model="passwordForm.password_confirmation" class="fld" type="password" placeholder="••••••••" autocomplete="new-password">
								</div>
							</div>
						</div>
						<div class="px-[22px] py-4 border-t border-line-2 flex justify-end gap-2.5 bg-white rounded-b-card">
							<span v-if="passwordForm.recentlySuccessful" class="self-center text-[13.5px] font-medium text-emerald-700">{{ text('Password updated.', 'Mot de passe mis à jour.') }}</span>
							<button class="cta cta-primary" type="submit" :disabled="passwordForm.processing">{{ text('Update password', 'Mettre à jour le mot de passe') }}</button>
						</div>
					</form>

					<div class="bg-white border border-transparent rounded-card mb-[18px]">
						<div class="px-7 pt-6 pb-1 max-[560px]:px-5">
							<h3 class="text-red-700 text-[20px] leading-[1.35] text-navy">{{ text('Danger zone', 'Zone de danger') }}</h3>
							<p class="text-[15px] text-subtle mt-1">{{ text('Irreversible actions. Please be certain.', 'Actions irréversibles. Soyez sûr(e) de votre choix.') }}</p>
						</div>
						<div class="px-7 pt-5 pb-7 max-[560px]:px-5">
							<div class="flex flex-wrap items-center justify-between gap-[18px]">
								<div class="min-w-0 grow basis-[220px]">
									<b class="text-[14.5px] font-medium block">{{ text('Delete account', 'Supprimer le compte') }}</b>
									<span class="text-[13px] text-muted block mt-0.5">{{ text('Permanently remove your profile, applications, and messages.', 'Supprimez définitivement votre profil, vos candidatures et vos messages.') }}</span>
								</div>
								<button v-if="!confirmingDelete" ref="deleteButton" class="cta !px-[18px] !py-2.5 cta-danger whitespace-nowrap" type="button" @click="startDelete">
									{{ text('Delete account', 'Supprimer le compte') }}
								</button>
							</div>
							<form v-if="confirmingDelete" class="mt-[18px] rounded-card border border-red-200 bg-red-50 p-[18px]" @submit.prevent="deleteAccount" @keydown.esc="cancelDelete">
								<p class="text-[13.5px] text-red-800 font-medium">{{ text('This can\'t be undone. Your profile, applications, and messages will be deleted for good.', 'Cette action est irréversible. Votre profil, vos candidatures et vos messages seront supprimés définitivement.') }}</p>
								<label class="block text-[14px] font-medium mt-4 mb-2 text-navy" for="delete-password">{{ text('Enter your password to confirm', 'Saisissez votre mot de passe pour confirmer') }}</label>
								<input
									id="delete-password"
									ref="deletePassword"
									v-model="deleteForm.password"
									class="fld max-w-[360px]"
									type="password"
									autocomplete="current-password"
									required
									:aria-invalid="!!deleteForm.errors.password"
									:aria-describedby="deleteForm.errors.password ? 'delete-password-error' : undefined"
								>
								<div v-if="deleteForm.errors.password" id="delete-password-error" class="text-xs text-red-600 mt-[7px]" role="alert">{{ deleteForm.errors.password }}</div>
								<div class="flex flex-wrap gap-2.5 mt-4">
									<button class="cta cta-secondary !px-[18px] !py-2.5" type="button" @click="cancelDelete">{{ text('Cancel', 'Annuler') }}</button>
									<button class="cta !px-[18px] !py-2.5 cta-danger disabled:opacity-60" type="submit" :disabled="deleteForm.processing || !deleteForm.password">
										{{ deleteForm.processing ? text('Deleting…', 'Suppression…') : text('Delete account', 'Supprimer le compte') }}
									</button>
								</div>
							</form>
						</div>
					</div>
				</section>
			</div>
		</div>
	</div>
</template>
