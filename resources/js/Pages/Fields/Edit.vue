<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'fields' }, () => page),
});

const props = defineProps({
	field: { type: Object, default: null },
});

const icons = {
	chevron: ChevronRight,
};

const editing = computed(() => !!props.field);
const { text } = useText();

const form = useForm({
	name: props.field?.name || '',
});

const submit = () => {
	if (editing.value) {
		form.put(route('fields.update', props.field.id));
	} else {
		form.post(route('fields.store'));
	}
};

const destroy = () => {
	if (confirm(text('Delete this field? Internships and students referencing it may break.', 'Supprimer ce domaine ? Les stages et les candidats qui l’utilisent risquent de ne plus fonctionner.'))) {
		router.delete(route('fields.destroy', props.field.id));
	}
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[640px] mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('fields.index')">{{ text('Fields', 'Domaines') }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ editing ? field.name : text('New field', 'Nouveau domaine') }}</span>
		</div>

		<div class="mb-6">
			<h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? text('Edit field', 'Modifier le domaine') : text('Add a field of study', 'Ajouter un domaine d’études') }}</h1>
		</div>

		<form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-[26px]">
				<label class="block text-[14px] font-medium mb-2 text-navy" for="name">{{ text('Name', 'Nom') }}</label>
				<input id="name" v-model="form.name" class="fld" type="text" :placeholder="text('e.g. Computer Science', 'p. ex. Informatique')">
				<div v-if="form.errors.name" class="text-xs text-red-600 mt-[7px]">{{ form.errors.name }}</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex items-center gap-2.5 bg-white rounded-b-card">
				<button v-if="editing" class="cta !px-[18px] !py-2.5 cta-danger" type="button" @click="destroy">{{ text('Delete', 'Supprimer') }}</button>
				<div class="flex-1"></div>
				<Link class="cta cta-secondary" :href="route('fields.index')">{{ text('Cancel', 'Annuler') }}</Link>
				<button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? text('Save changes', 'Enregistrer les modifications') : text('Create field', 'Créer le domaine') }}</button>
			</div>
		</form>
	</div>
</template>
