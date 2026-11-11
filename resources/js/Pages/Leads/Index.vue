<script setup>
import { computed, h, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { EyeOff, Mail, Plus, Upload } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import LeadCard from '@/Components/Leads/LeadCard.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'leads' }, () => page) });
const props = defineProps({
	filters: Object, day: String, leads: Object, skips: Object, hidden: Array,
	counts: Object, reasons: Object, statuses: Array, canSuggest: Boolean, gmail: Object,
});

const { text, dateLocale } = useText();
const statusLabels = computed(() => ({
	to_apply: text('To apply', 'À postuler'),
	opened: text('Opened', 'Ouverte'),
	applied: text('Applied', 'Candidature envoyée'),
	interviewing: text('Interviewing', 'En entretien'),
	offer: text('Offer', 'Proposition'),
	rejected: text('Rejected', 'Refusée'),
	skipped: text('Skipped', 'Passée'),
}));
const reasonLabels = computed(() => ({
	repost: text('Repost', 'Republication'),
	ineligible: text('Not open to Tunisia', 'Fermée à la Tunisie'),
	seniority: text('Level', 'Niveau'),
	years: text('Too many years', 'Trop d’années'),
	duplicate: text('Same job', 'Même offre'),
	applied: text('Already applied', 'Déjà postulé'),
	cooldown: text('Applied there lately', 'Candidature récente là-bas'),
	low_score: text('Low score', 'Score faible'),
	cap: text('Over the daily limit', 'Limite quotidienne dépassée'),
}));
const total = computed(() => Object.values(props.counts || {}).reduce((sum, n) => sum + Number(n), 0));
const skipped = computed(() => Object.values(props.reasons || {}).reduce((sum, n) => sum + Number(n), 0));
const dayLabel = computed(() => props.day ? new Date(`${props.day}T12:00:00`).toLocaleDateString(dateLocale.value, { weekday: 'long', day: 'numeric', month: 'long' }) : null);
const tabs = computed(() => [
	{ key: 'today', label: text('Today', 'Aujourd’hui'), count: props.filters.tab === 'today' ? props.leads?.data.length : null },
	{ key: 'all', label: text('All leads', 'Toutes les pistes'), count: total.value },
	{ key: 'skipped', label: text('Left out', 'Écartées'), count: skipped.value },
]);

const search = ref(props.filters.q || '');
const visit = (changes) => router.get(route('leads.index'), { ...props.filters, page: undefined, ...changes }, { preserveScroll: true, preserveState: true });
const select = (key, value) => visit({ [key]: value || undefined });

const adding = ref(false);
const form = useForm({ company: '', title: '', url: '', status: 'applied', applied_at: new Date().toISOString().slice(0, 10), notes: '' });
const add = () => form.post(route('leads.store'), { preserveScroll: true, onSuccess: () => { form.reset(); adding.value = false; } });

const gmailRead = computed(() => props.gmail?.synced_at ? text('Last read ', 'Dernière lecture : ') + `${new Date(props.gmail.synced_at).toLocaleString(dateLocale.value, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}.` : text('Not read yet.', 'Pas encore lu.'));
const readGmail = () => router.post(route('leads.gmail.sync'), {}, { preserveScroll: true });
const disconnectGmail = () => { if (confirm(text('Disconnect Gmail? Leads already added stay.', 'Déconnecter Gmail ? Les pistes déjà ajoutées restent.'))) router.delete(route('leads.gmail.disconnect'), { preserveScroll: true }); };

const upload = useForm({ file: null });
const importFile = (event) => {
	upload.file = event.target.files[0];
	if (upload.file) upload.post(route('leads.import'), { preserveScroll: true, forceFormData: true, onFinish: () => { event.target.value = ''; } });
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader :title="text('Job leads', 'Pistes d’emploi')" :description="text('New software roles you can take from Tunisia, picked every morning at 07:00. A job never shows twice, and roles at companies you applied to stay hidden for 60 days.', 'De nouvelles offres de développement logiciel que vous pouvez occuper depuis la Tunisie, choisies chaque matin à 07:00. Une offre n’apparaît jamais deux fois, et les offres des entreprises où vous avez postulé restent masquées pendant 60 jours.')">
			<template #actions>
				<label class="cta cta-secondary !text-[14px] !px-4 !py-2.5" :class="{ 'opacity-60': upload.processing }">
					<Upload class="size-4" aria-hidden="true" />{{ upload.processing ? text('Importing…', 'Import en cours…') : text('Import', 'Importer') }}
					<input type="file" accept=".json,.csv,.txt" class="sr-only" :disabled="upload.processing" @change="importFile">
				</label>
				<button type="button" class="cta cta-primary !text-[14px] !px-4 !py-2.5" :aria-expanded="adding" @click="adding = !adding"><Plus class="size-4" aria-hidden="true" />{{ text('Add a job', 'Ajouter une offre') }}</button>
			</template>
		</PageHeader>
		<p v-if="upload.errors.file" class="-mt-4 mb-5 text-[13.5px] text-red-600" role="alert">{{ upload.errors.file }}</p>
		<p class="-mt-4 text-[13px] text-muted">{{ text('Import takes jobs.json from your old tracker, a Tsenta or LinkedIn export (Job Applications.csv), or any CSV or JSON with company and role columns.', 'L’import accepte le fichier jobs.json de votre ancien suivi, un export Tsenta ou LinkedIn (Job Applications.csv), ou tout fichier CSV ou JSON avec des colonnes entreprise et poste.') }}</p>
		<div class="mt-2 mb-6 flex items-start gap-2 text-[13px]">
			<Mail class="size-4 mt-0.5 shrink-0 text-muted" aria-hidden="true" />
			<div class="flex flex-wrap gap-x-3 gap-y-1">
			<template v-if="gmail.connected">
				<span class="text-ink-700">{{ text(`Gmail connected, read only (${gmail.email}).`, `Gmail connecté, en lecture seule (${gmail.email}).`) }} {{ gmailRead }}</span>
				<button type="button" class="font-medium text-blue-700 hover:underline" @click="readGmail">{{ text('Read now', 'Lire maintenant') }}</button>
				<button type="button" class="font-medium text-ink-700 hover:underline" @click="disconnectGmail">{{ text('Disconnect', 'Déconnecter') }}</button>
			</template>
			<template v-else-if="gmail.ready">
				<span class="text-muted">{{ text(`Connect Gmail to add applications from confirmation emails. ${appName} only reads them.`, `Connectez Gmail pour ajouter des candidatures à partir des e-mails de confirmation. ${appName} ne fait que les lire.`) }}</span>
				<a :href="route('leads.gmail.connect')" class="font-medium text-blue-700 hover:underline">{{ text('Connect Gmail', 'Connecter Gmail') }}</a>
			</template>
			<span v-else class="text-muted">{{ text('Gmail can add applications from confirmation emails once GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET are set.', 'Gmail pourra ajouter des candidatures à partir des e-mails de confirmation une fois GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET définis.') }}</span>
			</div>
		</div>

		<form v-if="adding" class="mb-6 bg-white border border-transparent rounded-card p-5 grid gap-3 sm:grid-cols-2" @submit.prevent="add">
			<label class="text-[14px] font-medium text-navy">{{ text('Company', 'Entreprise') }}<input v-model="form.company" class="fld mt-1.5" required maxlength="191"></label>
			<label class="text-[14px] font-medium text-navy">{{ text('Role', 'Poste') }}<input v-model="form.title" class="fld mt-1.5" required maxlength="255"></label>
			<label class="text-[14px] font-medium text-navy sm:col-span-2">{{ text('Link to the posting', 'Lien vers l’offre') }}<input v-model="form.url" type="url" class="fld mt-1.5" placeholder="https://"></label>
			<label class="text-[14px] font-medium text-navy">{{ text('Status', 'Statut') }}
				<select v-model="form.status" class="fld mt-1.5"><option v-for="status in statuses" :key="status" :value="status">{{ statusLabels[status] }}</option></select>
			</label>
			<label class="text-[14px] font-medium text-navy">{{ text('Applied on', 'Postulé le') }}<input v-model="form.applied_at" type="date" class="fld mt-1.5"></label>
			<label class="text-[14px] font-medium text-navy sm:col-span-2">{{ text('Notes', 'Notes') }}<textarea v-model="form.notes" class="fld mt-1.5" rows="2" /></label>
			<p v-if="Object.keys(form.errors).length" class="sm:col-span-2 text-[13px] text-red-600">{{ Object.values(form.errors)[0] }}</p>
			<div class="sm:col-span-2 flex gap-2"><button class="cta cta-primary" :disabled="form.processing">{{ text('Save', 'Enregistrer') }}</button><button type="button" class="cta cta-secondary" @click="adding = false">{{ text('Cancel', 'Annuler') }}</button></div>
		</form>

		<nav class="flex flex-wrap gap-2 mb-4" :aria-label="text('Job lead lists', 'Listes de pistes d’emploi')">
			<button v-for="tab in tabs" :key="tab.key" type="button" class="rounded-card px-3.5 py-2 text-[14px] font-medium transition-colors"
				:class="filters.tab === tab.key ? 'bg-navy-900 text-white' : 'bg-white border border-line text-ink-700 hover:border-blue-300'"
				:aria-current="filters.tab === tab.key ? 'page' : undefined" @click="visit({ tab: tab.key, status: undefined, reason: undefined })">
				{{ tab.label }}<span v-if="tab.count !== null && tab.count !== undefined" class="ml-1.5 opacity-70">{{ tab.count }}</span>
			</button>
		</nav>

		<form class="flex flex-wrap gap-2.5 mb-5" role="search" @submit.prevent="visit({ q: search || undefined })">
			<input v-model="search" type="search" class="fld !w-auto grow min-w-[200px] !py-2.5 bg-white" :placeholder="text('Search company, role or notes', 'Rechercher une entreprise, un poste ou des notes')" :aria-label="text('Search job leads', 'Rechercher des pistes d’emploi')">
			<template v-if="filters.tab !== 'skipped'">
				<select class="fld !w-auto !py-2.5 bg-white" :value="filters.eligibility || ''" :aria-label="text('Eligibility', 'Admissibilité')" @change="select('eligibility', $event.target.value)">
					<option value="">{{ text('Any eligibility', 'Toute admissibilité') }}</option><option value="remote">{{ text('Remote from Tunisia', 'À distance depuis la Tunisie') }}</option><option value="visa">{{ text('Visa or relocation', 'Visa ou relocalisation') }}</option><option value="unclear">{{ text('Check eligibility', 'À vérifier') }}</option><option value="excluded">{{ text('Not open to Tunisia', 'Fermée à la Tunisie') }}</option>
				</select>
				<select class="fld !w-auto !py-2.5 bg-white" :value="filters.stage || ''" :aria-label="text('Company stage', 'Type d’entreprise')" @change="select('stage', $event.target.value)">
					<option value="">{{ text('Startups and established', 'Start-ups et entreprises établies') }}</option><option value="startup">{{ text('Startups', 'Start-ups') }}</option><option value="established">{{ text('Established companies', 'Entreprises établies') }}</option>
				</select>
				<select class="fld !w-auto !py-2.5 bg-white" :value="filters.status || ''" :aria-label="text('Status', 'Statut')" @change="select('status', $event.target.value)">
					<option value="">{{ text('Any status', 'Tout statut') }}</option><option v-for="status in statuses" :key="status" :value="status">{{ statusLabels[status] }} ({{ counts[status] || 0 }})</option>
				</select>
			</template>
			<select v-else class="fld !w-auto !py-2.5 bg-white" :value="filters.reason || ''" :aria-label="text('Reason', 'Motif')" @change="select('reason', $event.target.value)">
				<option value="">{{ text('Any reason', 'Tout motif') }}</option><option v-for="(label, key) in reasonLabels" :key="key" :value="key">{{ label }} ({{ reasons[key] || 0 }})</option>
			</select>
			<button class="cta cta-secondary !py-2.5 !px-4 !text-[14px]">{{ text('Search', 'Rechercher') }}</button>
		</form>

		<template v-if="filters.tab !== 'skipped'">
			<p v-if="filters.tab === 'today' && dayLabel" class="mb-3 text-[13.5px] text-muted">{{ text('Picked on', 'Choisies le') }} {{ dayLabel }}</p>
			<div class="grid gap-4">
				<LeadCard v-for="lead in leads.data" :key="lead.id" :lead="lead" :statuses="statuses" :can-suggest="canSuggest" />
			</div>
			<div v-if="!leads.data.length" class="bg-white border border-transparent rounded-card px-6 py-10 text-center text-[14.5px] text-muted">
				{{ filters.tab === 'today' ? text('No leads yet. The daily run picks new roles every morning at 07:00 Tunis time.', 'Aucune piste pour le moment. L’exécution quotidienne choisit de nouvelles offres chaque matin à 07:00, heure de Tunis.') : text('No leads match.', 'Aucune piste ne correspond.') }}
			</div>
			<Pagination v-if="filters.tab === 'all'" :meta="leads" />

			<section v-if="filters.tab === 'today' && hidden.length" class="mt-8" aria-labelledby="hidden-title">
				<h2 id="hidden-title" class="flex items-center gap-2 text-[20px] leading-[1.35] text-navy"><EyeOff class="size-4.5" aria-hidden="true" />{{ text('Hidden today', 'Masquées aujourd’hui') }}</h2>
				<p class="text-[13.5px] text-muted mt-1 mb-3">{{ text('New roles at companies you already applied to.', 'Nouvelles offres d’entreprises où vous avez déjà postulé.') }}</p>
				<ul class="bg-white border border-transparent rounded-card divide-y divide-line-2">
					<li v-for="skip in hidden" :key="skip.id" class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 text-[14px]">
						<span class="rounded-md bg-amber-50 px-2 py-0.5 text-[12px] font-medium text-amber-900">{{ text('Already applied', 'Déjà postulé') }}</span>
						<span class="font-medium">{{ skip.company_name }}</span>
						<a v-if="skip.url" :href="skip.url" target="_blank" rel="noopener noreferrer" class="text-ink-700 hover:text-blue-700">{{ skip.title }}</a>
						<span v-else class="text-ink-700">{{ skip.title }}</span>
						<span class="text-[13px] text-muted basis-full">{{ skip.detail }}</span>
					</li>
				</ul>
			</section>
		</template>

		<template v-else>
			<ul class="bg-white border border-transparent rounded-card divide-y divide-line-2">
				<li v-for="skip in skips.data" :key="skip.id" class="px-5 py-3.5 text-[14px]">
					<div class="flex flex-wrap items-center gap-x-3 gap-y-1">
						<span class="rounded-md bg-paper-2 px-2 py-0.5 text-[12px] font-medium text-ink-700">{{ reasonLabels[skip.reason] || skip.reason }}</span>
						<span class="font-medium">{{ skip.company_name }}</span>
						<a v-if="skip.url" :href="skip.url" target="_blank" rel="noopener noreferrer" class="text-ink-700 hover:text-blue-700">{{ skip.title }}</a>
						<span v-else class="text-ink-700">{{ skip.title }}</span>
						<span class="ml-auto text-[12.5px] text-muted">{{ skip.run_date }}</span>
					</div>
					<p v-if="skip.detail" class="mt-1 text-[13px] text-muted" dir="auto">{{ skip.detail }}</p>
				</li>
				<li v-if="!skips.data.length" class="px-5 py-8 text-[14px] text-muted">{{ text('Nothing was left out yet.', 'Rien n’a encore été écarté.') }}</li>
			</ul>
			<Pagination :meta="skips" />
		</template>
	</div>
</template>
