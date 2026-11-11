<script setup>
import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';
import { FileUser } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'kits' }, () => page) });
defineProps({ kits: { type: Array, default: () => [] } });
const { text } = useText();
const status = computed(() => ({ queued: text('Being written', 'En cours de rédaction'), ready: text('Ready', 'Prêt'), failed: text('Failed', 'Échec') }));
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader :title="text('CV & letters', 'CV et lettres')" :description="text('The CVs and cover letters written for the roles you picked. Open one to edit it, download it or use it to apply.', 'Les CV et lettres de motivation rédigés pour les offres que vous avez choisies. Ouvrez-en un pour le modifier, le télécharger ou l’utiliser pour postuler.')">
			<template #actions><Link :href="route('app.opportunities.index')" class="cta cta-secondary h-11 !py-0">{{ text('Browse roles', 'Parcourir les offres') }}</Link></template>
		</PageHeader>
		<div v-if="!kits.length" class="bg-white border border-transparent rounded-card px-6 py-10 text-center">
			<FileUser class="w-9 h-9 text-blue-600 mx-auto" aria-hidden="true" />
			<p class="text-lg font-medium mt-3">{{ text('Nothing here yet', 'Rien ici pour le moment') }}</p>
			<p class="text-sm text-muted mt-1 max-w-md mx-auto">{{ text('Open any listing and press “Write my CV” or “Write my cover letter”. Add your resume on your profile page first for the best result.', 'Ouvrez une offre et appuyez sur « Rédiger mon CV » ou « Rédiger ma lettre de motivation ». Pour un meilleur résultat, ajoutez d’abord votre CV sur votre page de profil.') }}</p>
		</div>
		<ul v-else class="bg-white border border-transparent rounded-card divide-y divide-line-2">
			<li v-for="kit in kits" :key="kit.id">
				<Link :href="route('kits.show', kit.id)" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-6 py-4 max-[680px]:px-4 hover:bg-paper-2 transition-colors">
					<div class="min-w-0 grow basis-[220px]">
						<b class="block font-medium truncate">{{ kit.role?.title || text('Role removed', 'Offre supprimée') }}</b>
						<span class="text-sm text-muted">{{ [kit.role?.company, kit.language, kit.updated_label].filter(Boolean).join(' · ') }}</span>
					</div>
					<span class="flex shrink-0 flex-wrap items-center gap-1.5 text-xs font-medium">
						<span v-if="kit.has_cv" class="rounded-full bg-paper-3 px-2.5 py-1 text-ink-700">CV</span>
						<span v-if="kit.has_letter" class="rounded-full bg-paper-3 px-2.5 py-1 text-ink-700">{{ text('Letter', 'Lettre') }}</span>
						<span v-if="kit.status !== 'ready'" class="rounded-full px-2.5 py-1" :class="kit.status === 'failed' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800'">{{ status[kit.status] }}</span>
					</span>
				</Link>
			</li>
		</ul>
	</div>
</template>
