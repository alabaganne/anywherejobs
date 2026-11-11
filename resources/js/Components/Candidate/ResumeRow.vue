<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CornerDownRight, Download, Ellipsis, FilePenLine, FileText, Languages, Pencil, Star, Trash2 } from '@lucide/vue';
import Menu from '@/Components/Ui/Menu.vue';
import { menuItem } from '@/utils/menu';
import { useText } from '@/composables/useText';

// One resume on the profile page: one button for what you most likely want, the rest in a menu.
const props = defineProps({
	resume: { type: Object, required: true },
	nested: { type: Boolean, default: false },
	busy: { type: String, default: null },
	aiOff: { type: String, default: '' },
	paid: { type: Boolean, default: false },
});
const emit = defineEmits(['ai', 'main', 'remove']);

const { text } = useText();
const kind = computed(() => ({ uploaded: text('Uploaded PDF', 'PDF importé'), profile: text('From your profile', 'Depuis votre profil'), translation: text('Translation', 'Traduction'), copy: text('Editable copy', 'Copie modifiable') }));
const ready = computed(() => props.resume.status === 'ready');
const status = computed(() => ({
	queued: props.resume.source === 'copy' ? text('Being read…', 'Lecture en cours…') : text('Being translated…', 'Traduction en cours…'),
	draft: text('Draft', 'Brouillon'),
	failed: props.resume.source === 'copy' ? text('Reading failed', 'Lecture échouée') : text('Translation failed', 'Traduction échouée'),
})[props.resume.status]);
const details = computed(() => [props.resume.language?.label, kind.value[props.resume.source], text(`updated ${props.resume.updated_label}`, `mis à jour ${props.resume.updated_label}`)].filter(Boolean).join(' · '));
const primary = computed(() => {
	const resume = props.resume;
	if (resume.status === 'draft') return { label: text('Finish', 'Terminer'), icon: Pencil, href: route('resumes.show', resume.id) };
	if (resume.editable && ['ready', 'failed'].includes(resume.status)) return { label: text('Open', 'Ouvrir'), icon: Pencil, href: route('resumes.show', resume.id) };
	if (ready.value && resume.has_file) return { label: text('Download', 'Télécharger'), icon: Download, href: route('resumes.download', resume.id), file: true };
	return null;
});
const starting = computed(() => props.busy?.startsWith(`${props.resume.id}-`));
const aiItems = computed(() => ready.value && (props.resume.targets.length || props.resume.source === 'uploaded'));
</script>

<template>
	<div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
		<div class="flex items-center gap-3 min-w-0 grow basis-[240px]">
			<CornerDownRight v-if="nested" class="size-4 ml-3 shrink-0 text-muted-2" aria-hidden="true" />
			<span v-else class="size-10 rounded-card grid place-items-center shrink-0 bg-blue-50 text-blue-700"><FileText class="size-5" aria-hidden="true" /></span>
			<div class="min-w-0">
				<p class="font-medium text-[14.5px] text-navy truncate">{{ resume.name }}</p>
				<p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12.5px] text-muted">
					<span>{{ details }}</span>
					<span v-if="status" class="rounded-full px-2 py-0.5 font-medium" :class="resume.status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800'">{{ status }}</span>
				</p>
			</div>
		</div>
		<div class="flex items-center gap-2 ml-auto">
			<span v-if="starting" class="text-[13px] text-muted" role="status">{{ text('Starting…', 'Démarrage…') }}</span>
			<component :is="primary.file ? 'a' : Link" v-else-if="primary" :href="primary.href" class="cta cta-secondary cta-sm"><component :is="primary.icon" class="size-4" aria-hidden="true" />{{ primary.label }}</component>
			<Menu width="w-72">
				<template #button="{ open, toggle }">
					<button type="button" class="icon-btn size-9 border border-line bg-white" :aria-label="text(`More for ${resume.name}`, `Plus d’actions pour ${resume.name}`)" aria-haspopup="menu" :aria-expanded="open" @click="toggle"><Ellipsis class="size-5" aria-hidden="true" /></button>
				</template>
				<a v-if="ready && resume.has_file && !primary?.file" :class="menuItem" role="menuitem" :href="route('resumes.download', resume.id)"><Download class="size-4 text-muted-2" />{{ text('Download PDF', 'Télécharger le PDF') }}</a>
				<button v-if="ready && !resume.is_main" :class="menuItem" role="menuitem" type="button" @click="emit('main')"><Star class="size-4 text-muted-2" />{{ text('Make it my main resume', 'En faire mon CV principal') }}</button>
				<template v-if="aiItems">
					<div class="h-px bg-line-2 my-1.5" />
					<button v-for="target in resume.targets" :key="target.code" :class="menuItem" role="menuitem" type="button" :disabled="!!aiOff || !!busy" @click="emit('ai', target)"><Languages class="size-4 text-muted-2" />{{ text(`Translate to ${target.label}`, `Traduire en ${target.label}`) }}<span v-if="paid" class="ml-auto text-xs font-normal text-muted">{{ text('1 credit', '1 crédit') }}</span></button>
					<button v-if="resume.source === 'uploaded'" :class="menuItem" role="menuitem" type="button" :disabled="!!aiOff || !!busy" @click="emit('ai', null)"><FilePenLine class="size-4 text-muted-2" />{{ text('Turn into an editable copy', 'Créer une copie modifiable') }}<span v-if="paid" class="ml-auto text-xs font-normal text-muted">{{ text('1 credit', '1 crédit') }}</span></button>
					<p v-if="aiOff" class="px-3 pt-1 pb-1.5 text-xs text-muted">{{ aiOff }}</p>
				</template>
				<div class="h-px bg-line-2 my-1.5" />
				<button :class="menuItem" class="!text-red-700 hover:!bg-red-50" role="menuitem" type="button" @click="emit('remove')"><Trash2 class="size-4 text-red-600" />{{ text('Delete', 'Supprimer') }}</button>
			</Menu>
		</div>
	</div>
</template>
