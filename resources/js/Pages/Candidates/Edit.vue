<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'candidates' }, () => page),
});

const props = defineProps({
	candidate: { type: Object, default: null },
	fields: { type: Array, default: () => [] },
	cities: { type: Array, default: () => [] },
});

const icons = {
	chevron: ChevronRight,
};
const editing = computed(() => !!props.candidate);
const { text } = useText();

const form = useForm({
	name: props.candidate?.name || '',
	email: props.candidate?.email || '',
	phone_number: props.candidate?.phone_number || '',
	city_id: props.candidate?.city_id ?? props.candidate?.city?.id ?? null,
	field_id: props.candidate?.field_id ?? props.candidate?.field?.id ?? null,
	about: props.candidate?.about || '',
	headline: props.candidate?.headline || '',
	experience_years: props.candidate?.experience_years ?? null,
	country_code: props.candidate?.country_code || '',
	location: props.candidate?.location || '',
});

const submit = () => editing.value ? form.put(route('candidates.update', props.candidate.id)) : form.post(route('candidates.store'));

const destroy = () => {
	if (confirm(text('Delete this candidate and everything attached to them? This cannot be undone.', 'Supprimer ce candidat et tout ce qui lui est lié ? Cette action est irréversible.'))) {
		router.delete(route('candidates.destroy', props.candidate.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[860px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('candidates.index')">{{ text('Candidates', 'Candidats') }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ editing ? candidate.name : text('New candidate', 'Nouveau candidat') }}</span>
		</div>

		<div class="mb-6">
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? text('Edit candidate', 'Modifier le candidat') : text('Add a candidate', 'Ajouter un candidat') }}</h1>
			<p v-if="!editing" class="text-sm text-muted mt-2">{{ text('They can use Forgot password to set their password.', 'Ils peuvent utiliser « Mot de passe oublié » pour définir leur mot de passe.') }}</p>
		</div>

		<form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<p v-if="Object.keys(form.errors).length" class="text-sm text-red-600 font-medium mb-4" role="alert">{{ text('Please fix the fields below.', 'Veuillez corriger les champs ci-dessous.') }}</p>
				<div class="grid grid-cols-2 gap-4 mb-5 max-[560px]:grid-cols-1">
					<label class="text-sm font-medium">{{ text('Headline', 'Titre du profil') }}<input v-model="form.headline" class="fld mt-2" maxlength="255"><span v-if="form.errors.headline" class="block text-xs font-normal text-red-600 mt-[7px]">{{ form.errors.headline }}</span></label>
					<label class="text-sm font-medium">{{ text('Country code', 'Code pays') }}<input v-model="form.country_code" class="fld mt-2" maxlength="2" placeholder="TN, FR, US..."><span v-if="form.errors.country_code" class="block text-xs font-normal text-red-600 mt-[7px]">{{ form.errors.country_code }}</span></label>
					<label class="text-sm font-medium">{{ text('City or location', 'Ville ou lieu') }}<input v-model="form.location" class="fld mt-2"><span v-if="form.errors.location" class="block text-xs font-normal text-red-600 mt-[7px]">{{ form.errors.location }}</span></label>
					<label class="text-sm font-medium">{{ text('Years of experience', 'Années d’expérience') }}<input v-model.number="form.experience_years" class="fld mt-2" type="number" min="0" max="80"><span v-if="form.errors.experience_years" class="block text-xs font-normal text-red-600 mt-[7px]">{{ form.errors.experience_years }}</span></label>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="candidate-name">{{ text('Full name', 'Nom complet') }}</label>
						<input id="candidate-name" v-model="form.name" class="fld" type="text">
						<div v-if="form.errors.name" class="text-xs text-red-600 mt-[7px]">{{ form.errors.name }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="candidate-email">{{ text('Email', 'E-mail') }}</label>
						<input id="candidate-email" v-model="form.email" class="fld" type="email">
						<div v-if="form.errors.email" class="text-xs text-red-600 mt-[7px]">{{ form.errors.email }}</div>
					</div>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="candidate-phone">{{ text('Phone', 'Téléphone') }}</label>
						<input id="candidate-phone" v-model="form.phone_number" class="fld" type="tel">
						<div v-if="form.errors.phone_number" class="text-xs text-red-600 mt-[7px]">{{ form.errors.phone_number }}</div>
					</div>
					<div class="mb-4">
						<label class="block text-[14px] font-medium mb-2 text-navy" for="candidate-city">{{ text('City', 'Ville') }}</label>
						<select id="candidate-city" v-model="form.city_id" class="fld">
							<option :value="null">-</option>
							<option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
						</select>
						<div v-if="form.errors.city_id" class="text-xs text-red-600 mt-[7px]">{{ form.errors.city_id }}</div>
					</div>
				</div>
				<div class="mb-4">
					<label class="block text-[14px] font-medium mb-2 text-navy" for="candidate-field">{{ text('Field of work or study', 'Domaine de travail ou d’études') }}</label>
					<select id="candidate-field" v-model="form.field_id" class="fld">
						<option :value="null">-</option>
						<option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option>
					</select>
					<div v-if="form.errors.field_id" class="text-xs text-red-600 mt-[7px]">{{ form.errors.field_id }}</div>
				</div>
				<div>
					<label class="block text-[14px] font-medium mb-2 text-navy" for="candidate-about">{{ text('About', 'À propos') }}</label>
					<textarea id="candidate-about" v-model="form.about" class="fld"></textarea>
					<div v-if="form.errors.about" class="text-xs text-red-600 mt-[7px]">{{ form.errors.about }}</div>
				</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-card">
				<button v-if="editing" class="cta !px-[18px] !py-2.5 cta-danger" type="button" @click="destroy">{{ text('Delete', 'Supprimer') }}</button>
				<div class="flex-1"></div>
				<Link class="cta cta-secondary" :href="editing ? route('candidates.show', candidate.id) : route('candidates.index')">{{ text('Cancel', 'Annuler') }}</Link>
				<button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? text('Save changes', 'Enregistrer les modifications') : text('Create candidate', 'Créer le candidat') }}</button>
			</div>
		</form>
	</div>
</template>
