<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { ArrowDown, ArrowUp, ChevronDown, Plus, Trash2, X } from '@lucide/vue';
import BulletList from '@/Components/Cv/BulletList.vue';
import TagInput from '@/Components/Cv/TagInput.vue';
import { useText } from '@/composables/useText';

const { text } = useText();

// Edits one section of a CV (the shape of App\Support\Cv) in place.
const cv = defineModel({ type: Object, required: true });
const props = defineProps({ section: { type: String, required: true } });

const label = 'block text-[14px] font-medium text-navy';
const field = 'fld mt-1.5 !py-2.5 !text-[14px]';

const contactHints = computed(() => [text('Email', 'E-mail'), text('Phone', 'Téléphone'), text('City, country', 'Ville, pays'), text('Website or LinkedIn', 'Site web ou LinkedIn')]);
const lists = computed(() => ({
	experience: {
		blank: () => ({ title: '', company: '', location: '', start: '', end: '', current: false, highlights: [] }),
		title: (job) => job.title || text('New role', 'Nouveau poste'),
		meta: (job) => [job.company, dates(job)].filter(Boolean).join(' · '),
		add: text('Add a role', 'Ajouter un poste'),
	},
	education: {
		blank: () => ({ school: '', degree: '', field: '', start: '', end: '' }),
		title: (school) => school.school || text('New school', 'Nouvel établissement'),
		meta: (school) => [[school.degree, school.field].filter(Boolean).join(', '), dates(school)].filter(Boolean).join(' · '),
		add: text('Add a school', 'Ajouter un établissement'),
	},
	projects: {
		blank: () => ({ name: '', summary: '', url: '' }),
		title: (project) => project.name || text('New project', 'Nouveau projet'),
		meta: (project) => project.url || '',
		add: text('Add a project', 'Ajouter un projet'),
	},
}));
const dates = (item) => [item.start, item.current ? text('now', 'aujourd’hui') : item.end].filter(Boolean).join(' – ');
const list = computed(() => lists.value[props.section]);
const items = computed(() => cv.value[props.section] || []);

// One entry is open at a time; the first one starts open.
const open = ref(0);
watch(() => props.section, () => (open.value = 0));
const root = ref(null);
const add = async () => {
	cv.value[props.section] = [...items.value, list.value.blank()];
	open.value = items.value.length - 1;
	await nextTick();
	const cards = root.value?.querySelectorAll('[data-entry]');
	cards?.[cards.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	cards?.[cards.length - 1]?.querySelector('input')?.focus();
};
const remove = (index) => {
	const name = list.value.title(items.value[index]);
	if (!window.confirm(text(`Remove “${name}”?`, `Retirer « ${name} » ?`))) return;
	cv.value[props.section] = items.value.filter((_, at) => at !== index);
	if (open.value >= items.value.length) open.value = items.value.length - 1;
};
const move = (index, by) => {
	const next = [...items.value];
	const [entry] = next.splice(index, 1);
	next.splice(index + by, 0, entry);
	cv.value[props.section] = next;
	if (open.value === index) open.value = index + by;
};

const contact = computed(() => cv.value.contact || []);
const setContact = (index, value) => { cv.value.contact = contact.value.map((line, at) => (at === index ? value : line)); };
const addContact = () => { cv.value.contact = [...contact.value, '']; };
const removeContact = (index) => { cv.value.contact = contact.value.filter((_, at) => at !== index); };
</script>

<template>
	<div ref="root">
		<div v-if="section === 'header'" class="grid gap-5">
			<div class="grid gap-4 @lg:grid-cols-2">
				<label :class="label">{{ text('Full name', 'Nom complet') }}<input v-model="cv.name" dir="auto" :class="field" maxlength="150" autocomplete="name"></label>
				<label :class="label">{{ text('Headline', 'Titre') }}<input v-model="cv.headline" dir="auto" :class="field" maxlength="150" :placeholder="text('Full-stack developer', 'Développeur full-stack')"></label>
			</div>
			<div>
				<p :class="label">{{ text('Contact details', 'Coordonnées') }}</p>
				<p class="text-[12.5px] text-muted mt-0.5">{{ text('One per line, shown at the top of your CV.', 'Une par ligne, affichées en haut de votre CV.') }}</p>
				<div class="grid gap-2 mt-2.5">
					<div v-for="(line, index) in contact" :key="index" class="flex items-center gap-2">
						<input :value="line" dir="auto" class="fld !py-2.5 !text-[14px]" maxlength="255" :placeholder="contactHints[index] || text('Another line', 'Une autre ligne')" :aria-label="text(`Contact line ${index + 1}`, `Ligne de coordonnées ${index + 1}`)" @input="setContact(index, $event.target.value)">
						<button type="button" class="grid place-items-center size-9 shrink-0 rounded-lg text-muted-2 hover:bg-red-50 hover:text-red-700 focus-visible:outline-2 focus-visible:outline-blue-500" :aria-label="text(`Remove contact line ${index + 1}`, `Retirer la ligne de coordonnées ${index + 1}`)" @click="removeContact(index)"><X class="size-4" aria-hidden="true" /></button>
					</div>
				</div>
				<button v-if="contact.length < 8" type="button" class="mt-2 inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[13px] font-medium text-blue-700 hover:bg-blue-50" @click="addContact"><Plus class="size-4" aria-hidden="true" /> {{ text('Add a line', 'Ajouter une ligne') }}</button>
			</div>
		</div>

		<div v-else-if="section === 'summary'">
			<label :class="label" for="cv-summary">{{ text('Summary', 'Résumé') }}</label>
			<textarea id="cv-summary" v-model="cv.summary" dir="auto" class="fld mt-1.5 !text-[14px] leading-relaxed min-h-[220px] field-sizing-content" maxlength="5000"></textarea>
			<p class="text-[12.5px] text-muted mt-1.5 text-right">{{ (cv.summary || '').length }} / 5000</p>
		</div>

		<div v-else-if="list" class="grid gap-3">
			<div v-for="(item, index) in items" :key="index" data-entry class="rounded-card border transition-colors" :class="open === index ? 'border-blue-200 bg-white' : 'border-line bg-paper-2 hover:border-slate-300'">
				<div class="flex items-center gap-1 pr-2">
					<button type="button" class="flex-1 min-w-0 flex items-center gap-3 text-left px-4 py-3.5 rounded-card focus-visible:outline-2 focus-visible:outline-blue-500" :aria-expanded="open === index" @click="open = open === index ? -1 : index">
						<span class="min-w-0 flex-1">
							<span class="block text-[14.5px] font-medium text-ink truncate" dir="auto">{{ list.title(item) }}</span>
							<span v-if="list.meta(item)" class="block text-[12.5px] text-muted truncate mt-0.5" dir="auto">{{ list.meta(item) }}</span>
						</span>
						<ChevronDown class="size-4 shrink-0 text-muted-2 transition-transform" :class="{ 'rotate-180': open === index }" aria-hidden="true" />
					</button>
					<button type="button" class="grid place-items-center size-8 rounded-lg text-muted-2 hover:bg-paper-3 hover:text-ink disabled:opacity-30 disabled:pointer-events-none" :disabled="index === 0" :aria-label="text(`Move ${list.title(item)} up`, `Monter ${list.title(item)}`)" @click="move(index, -1)"><ArrowUp class="size-4" aria-hidden="true" /></button>
					<button type="button" class="grid place-items-center size-8 rounded-lg text-muted-2 hover:bg-paper-3 hover:text-ink disabled:opacity-30 disabled:pointer-events-none" :disabled="index === items.length - 1" :aria-label="text(`Move ${list.title(item)} down`, `Descendre ${list.title(item)}`)" @click="move(index, 1)"><ArrowDown class="size-4" aria-hidden="true" /></button>
					<button type="button" class="grid place-items-center size-8 rounded-lg text-muted-2 hover:bg-red-50 hover:text-red-700" :aria-label="text(`Remove ${list.title(item)}`, `Retirer ${list.title(item)}`)" @click="remove(index)"><Trash2 class="size-4" aria-hidden="true" /></button>
				</div>

				<div v-if="open === index" class="grid gap-4 px-4 pb-5 pt-1 @lg:grid-cols-2">
					<template v-if="section === 'experience'">
						<label :class="label">{{ text('Job title', 'Intitulé du poste') }}<input v-model="item.title" dir="auto" :class="field"></label>
						<label :class="label">{{ text('Company', 'Entreprise') }}<input v-model="item.company" dir="auto" :class="field"></label>
						<label :class="label">{{ text('Location', 'Lieu') }}<input v-model="item.location" dir="auto" :class="field" :placeholder="text('City, country', 'Ville, pays')"></label>
						<label class="flex items-center gap-2 text-[14px] font-medium text-navy @lg:self-end @lg:pb-3"><input v-model="item.current" type="checkbox" class="rounded"> {{ text('I work here now', 'Je travaille ici actuellement') }}</label>
						<label :class="label">{{ text('Start', 'Début') }}<input v-model="item.start" dir="auto" :class="field" placeholder="2024-09"></label>
						<label :class="label">{{ text('End', 'Fin') }}<input v-model="item.end" dir="auto" :class="field" :disabled="item.current" :placeholder="item.current ? text('Now', 'Aujourd’hui') : '2025-06'"></label>
						<div class="@lg:col-span-2">
							<p :class="label">{{ text('What you did', 'Ce que vous avez fait') }}</p>
							<p class="text-[12.5px] text-muted mt-0.5 mb-2.5">{{ text('One point per line. Press Enter to start the next one.', 'Un point par ligne. Appuyez sur Entrée pour passer au suivant.') }}</p>
							<BulletList :model-value="item.highlights || []" @update:model-value="item.highlights = $event" />
						</div>
					</template>
					<template v-else-if="section === 'education'">
						<label class="@lg:col-span-2" :class="label">{{ text('School', 'Établissement') }}<input v-model="item.school" dir="auto" :class="field"></label>
						<label :class="label">{{ text('Degree', 'Diplôme') }}<input v-model="item.degree" dir="auto" :class="field"></label>
						<label :class="label">{{ text('Field of study', 'Domaine d’études') }}<input v-model="item.field" dir="auto" :class="field"></label>
						<label :class="label">{{ text('Start', 'Début') }}<input v-model="item.start" dir="auto" :class="field" placeholder="2021"></label>
						<label :class="label">{{ text('End', 'Fin') }}<input v-model="item.end" dir="auto" :class="field" placeholder="2024"></label>
					</template>
					<template v-else>
						<label :class="label">{{ text('Project name', 'Nom du projet') }}<input v-model="item.name" dir="auto" :class="field"></label>
						<label :class="label">{{ text('Link', 'Lien') }}<input v-model="item.url" dir="auto" :class="field" placeholder="github.com/you/project"></label>
						<label class="@lg:col-span-2" :class="label">{{ text('What it is', 'En quoi ça consiste') }}<textarea v-model="item.summary" dir="auto" class="fld mt-1.5 !text-[14px] leading-relaxed !min-h-[96px] field-sizing-content"></textarea></label>
					</template>
				</div>
			</div>

			<p v-if="!items.length" class="rounded-card border border-dashed border-line px-5 py-8 text-center text-sm text-muted">{{ text('Nothing here yet. This section is left off your CV until you add something.', 'Rien pour l’instant. Cette rubrique ne figure pas sur votre CV tant que vous n’y ajoutez rien.') }}</p>
			<button type="button" class="cta cta-secondary cta-sm justify-self-start" @click="add"><Plus class="size-4" aria-hidden="true" /> {{ list.add }}</button>
		</div>

		<div v-else-if="section === 'skills'">
			<TagInput v-model="cv.skills" :label="text('Skills', 'Compétences')" :placeholder="text('Type a skill and press Enter', 'Saisissez une compétence et appuyez sur Entrée')" />
			<p class="text-[12.5px] text-muted mt-2">{{ text('Paste a comma-separated list to add several at once. Put the ones this role asks for first.', 'Collez une liste séparée par des virgules pour en ajouter plusieurs d’un coup. Placez en premier celles que demande cette offre.') }}</p>
		</div>

		<div v-else-if="section === 'languages'">
			<TagInput v-model="cv.languages" :label="text('Languages', 'Langues')" :placeholder="text('French (native), English (C1)…', 'Français (langue maternelle), anglais (C1)…')" />
			<p class="text-[12.5px] text-muted mt-2">{{ text('Add your level in brackets if you like.', 'Vous pouvez ajouter votre niveau entre parenthèses.') }}</p>
		</div>
	</div>
</template>
