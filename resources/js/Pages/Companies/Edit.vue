<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h, onBeforeUnmount, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'companies' }, () => page),
});

const props = defineProps({
	company: { type: Object, default: null },
	cities: { type: Array, default: () => [] },
});

const icons = {
	chevron: ChevronRight,
};

const editing = computed(() => !!props.company);
const page = usePage();
const { text } = useText();

const form = useForm({
	name: props.company?.name || '',
	email: props.company?.email || '',
	phone_number: props.company?.phone_number || '',
	city_id: props.company?.city_id ?? props.company?.city?.id ?? null,
	website: props.company?.website || '',
	about: props.company?.about || '',
	country_code: props.company?.country_code || '',
	location: props.company?.location || '',
	logo: null,
});

// Imported employers have no account to hold a logo.
const canUploadLogo = computed(() => !editing.value || !!props.company.user_id);
const logoPreview = ref(props.company?.image || null);
const dropPreview = () => {
	if (logoPreview.value?.startsWith('blob:')) URL.revokeObjectURL(logoPreview.value);
};
const pickLogo = (event) => {
	const file = event.target.files?.[0] || null;
	dropPreview();
	form.logo = file;
	logoPreview.value = file ? URL.createObjectURL(file) : props.company?.image || null;
};
onBeforeUnmount(dropPreview);

const submit = () => {
	if (editing.value) {
		form.transform((data) => ({ ...data, _method: 'put' })).post(route('companies.update', props.company.id));
	} else {
		form.post(route('companies.store'));
	}
};

const destroy = () => {
	if (confirm(text('Delete this company and all of its roles? This cannot be undone.', 'Supprimer cette entreprise et toutes ses offres ? Cette action est irréversible.'))) {
		router.delete(route('companies.destroy', props.company.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[860px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('app.companies.index')">{{ text('Companies', 'Entreprises') }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ editing ? company.name : text('New company', 'Nouvelle entreprise') }}</span>
		</div>

		<div class="mb-6">
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? text('Edit company', 'Modifier l’entreprise') : text('Add a company', 'Ajouter une entreprise') }}</h1>
			<p class="text-muted text-[15px] mt-1.5">{{ text('This is what candidates see when they evaluate your roles.', 'Voici ce que les candidats voient lorsqu’ils consultent vos offres.') }}</p>
			<p v-if="!editing" class="text-sm text-muted mt-2">{{ text('We will email them a link to choose their password.', 'Nous leur enverrons par e-mail un lien pour choisir leur mot de passe.') }}</p>
		</div>

		<form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<div v-if="canUploadLogo" class="flex items-center gap-4 mb-6 pb-6 border-b border-line-2">
					<img v-if="logoPreview" :src="logoPreview" alt="" class="size-16 rounded-card border border-line object-contain bg-white shrink-0">
					<CompanyLogo v-else class="size-16 rounded-card text-[22px]" :name="form.name || text('Company', 'Entreprise')" />
					<div class="min-w-0">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="logo">{{ text('Logo', 'Logo') }}</label>
						<input id="logo" class="block w-full text-sm text-muted file:mr-3 file:rounded-card file:border file:border-line file:bg-white file:px-3.5 file:py-2 file:text-sm file:font-medium file:text-ink-700 hover:file:border-blue-300 file:cursor-pointer" type="file" accept="image/*" @change="pickLogo">
						<p class="text-xs text-muted mt-1.5">{{ text('An image up to 2 MB. A new file replaces the current logo.', 'Une image de 2 Mo maximum. Un nouveau fichier remplace le logo actuel.') }}</p>
						<div v-if="form.errors.logo" class="text-xs text-red-600 mt-[7px]">{{ form.errors.logo }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="name">{{ text('Company name', 'Nom de l’entreprise') }}</label>
						<input id="name" v-model="form.name" class="fld" type="text">
						<div v-if="form.errors.name" class="text-xs text-red-600 mt-[7px]">{{ form.errors.name }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="email">{{ text('Email', 'E-mail') }}</label>
						<input id="email" v-model="form.email" class="fld" type="email" placeholder="careers@example.com">
						<div v-if="form.errors.email" class="text-xs text-red-600 mt-[7px]">{{ form.errors.email }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="country_code">{{ text('Country code', 'Code pays') }}</label>
						<input id="country_code" v-model="form.country_code" class="fld" maxlength="2" placeholder="TN, FR, US...">
						<div v-if="form.errors.country_code" class="text-xs text-red-600 mt-[7px]">{{ form.errors.country_code }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="location">{{ text('City or location', 'Ville ou lieu') }}</label>
						<input id="location" v-model="form.location" class="fld">
						<div v-if="form.errors.location" class="text-xs text-red-600 mt-[7px]">{{ form.errors.location }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="phone_number">{{ text('Phone', 'Téléphone') }}</label>
						<input id="phone_number" v-model="form.phone_number" class="fld" type="tel">
						<div v-if="form.errors.phone_number" class="text-xs text-red-600 mt-[7px]">{{ form.errors.phone_number }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="city_id">{{ text('City', 'Ville') }}</label>
						<select id="city_id" v-model="form.city_id" class="fld">
							<option :value="null">{{ text('Not specified', 'Non précisée') }}</option>
							<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
						</select>
						<div v-if="form.errors.city_id" class="text-xs text-red-600 mt-[7px]">{{ form.errors.city_id }}</div>
					</div>
				</div>
				<div class="mb-4">
					<label class="block text-[14px] font-medium mb-2 text-navy" for="website">{{ text('Website', 'Site web') }}</label>
					<input id="website" v-model="form.website" class="fld" type="url" placeholder="https://example.com">
					<div v-if="form.errors.website" class="text-xs text-red-600 mt-[7px]">{{ form.errors.website }}</div>
				</div>
				<div>
					<label class="block text-[14px] font-medium mb-2 text-navy" for="about">{{ text('About', 'À propos') }}</label>
					<textarea id="about" v-model="form.about" class="fld !min-h-[140px]" rows="8" :placeholder="text('What do you build, and what will interns work on?', 'Que construisez-vous, et sur quoi travailleront les stagiaires ?')"></textarea>
					<div v-if="form.errors.about" class="text-xs text-red-600 mt-[7px]">{{ form.errors.about }}</div>
				</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-card">
				<button v-if="editing && page.props.auth?.user?.is_admin" class="cta !px-[18px] !py-2.5 cta-danger" type="button" @click="destroy">{{ text('Delete', 'Supprimer') }}</button>
				<div class="flex-1"></div>
				<Link class="cta cta-secondary" :href="editing ? route('companies.show', company.id) : route('app.companies.index')">{{ text('Cancel', 'Annuler') }}</Link>
				<button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? text('Save changes', 'Enregistrer les modifications') : text('Create company', 'Créer l’entreprise') }}</button>
			</div>
		</form>
	</div>
</template>
