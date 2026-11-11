<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'skills' }, () => page),
});

const props = defineProps({
	skill: { type: Object, default: null },
});

const icons = {
	chevron: ChevronRight,
};

const editing = computed(() => !!props.skill);
const { text } = useText();
const usage = computed(() => (props.skill?.opportunities_count || 0) + (props.skill?.candidates_count || 0));

const form = useForm({
	name: props.skill?.name || '',
});

const submit = () => {
	if (editing.value) {
		form.put(route('skills.update', props.skill.id));
	} else {
		form.post(route('skills.store'));
	}
};

const destroy = () => {
	const note = usage.value ? text(` It will be removed from ${usage.value} ${usage.value === 1 ? 'role or profile' : 'roles and profiles'}.`, ` Elle sera retirée de ${usage.value} ${usage.value === 1 ? 'offre ou profil' : 'offres et profils'}.`) : '';
	if (confirm(text('Delete this skill?', 'Supprimer cette compétence ?') + note)) {
		router.delete(route('skills.destroy', props.skill.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[640px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('skills.index')">{{ text('Skills', 'Compétences') }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ editing ? skill.name : text('New skill', 'Nouvelle compétence') }}</span>
		</div>

		<div class="mb-6">
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? text('Edit skill', 'Modifier la compétence') : text('Add a skill', 'Ajouter une compétence') }}</h1>
			<p v-if="editing" class="text-muted text-[15px] mt-1.5">{{ text(`Used by ${skill.opportunities_count} ${skill.opportunities_count === 1 ? 'role' : 'roles'} and ${skill.candidates_count} ${skill.candidates_count === 1 ? 'candidate' : 'candidates'}.`, `Utilisée par ${skill.opportunities_count} ${skill.opportunities_count > 1 ? 'offres' : 'offre'} et ${skill.candidates_count} ${skill.candidates_count > 1 ? 'candidats' : 'candidat'}.`) }}</p>
		</div>

		<form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<label class="block text-[14px] font-medium mb-2 text-navy" for="name">{{ text('Name', 'Nom') }}</label>
				<input id="name" v-model="form.name" class="fld" type="text" maxlength="80" :placeholder="text('e.g. Laravel', 'p. ex. Laravel')">
				<div v-if="form.errors.name" class="text-xs text-red-600 mt-[7px]">{{ form.errors.name }}</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-card">
				<button v-if="editing" class="cta !px-[18px] !py-2.5 cta-danger" type="button" @click="destroy">{{ text('Delete', 'Supprimer') }}</button>
				<div class="flex-1"></div>
				<Link class="cta cta-secondary" :href="route('skills.index')">{{ text('Cancel', 'Annuler') }}</Link>
				<button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? text('Save changes', 'Enregistrer les modifications') : text('Create skill', 'Créer la compétence') }}</button>
			</div>
		</form>
	</div>
</template>
