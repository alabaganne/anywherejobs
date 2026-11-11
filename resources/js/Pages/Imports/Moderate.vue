<script setup>
import { computed, h, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ChevronRight, ExternalLink } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.review' }, () => page) });
const props = defineProps({ opportunity: Object, sources: Array, reports: Array });
const page = usePage();
const { text } = useText();

const facts = { applicant_scope: ['anywhere', 'listed', 'local', 'unknown'], remote_eligibility: ['eligible', 'restricted', 'unknown'], relocation: ['yes', 'no', 'unknown'], visa_sponsorship: ['yes', 'no', 'unknown'], kind: ['internship', 'job'] };
const labels = computed(() => ({
    title: text('Title', 'Titre'),
    description: text('Description', 'Description'),
    kind: text('Internship or job', 'Stage ou emploi'),
    location: text('Location', 'Lieu'),
    country_code: text('Country code', 'Code pays'),
    applicant_scope: text('Who can apply', 'Qui peut postuler'),
    remote_eligibility: text('Remote from Tunisia', 'À distance depuis la Tunisie'),
    relocation: text('Relocation', 'Relocalisation'),
    visa_sponsorship: text('Visa sponsorship', 'Parrainage de visa'),
}));
const moderation = useForm({ action: 'suppress', reason: '', corrections: {} });
const field = ref('applicant_scope');
const value = ref(props.opportunity.applicant_scope);
const options = computed(() => facts[field.value] || null);
const pickField = () => { value.value = props.opportunity[field.value] ?? ''; };
const moderate = () => {
    moderation.corrections = moderation.action === 'correct' ? { [field.value]: value.value } : {};
    moderation.put(route('imports.opportunities.moderate', props.opportunity.id), { preserveScroll: true, onSuccess: () => moderation.reset('reason') });
};

const merge = useForm({ survivor_id: '', reason: '' });
const submitMerge = () => merge.post(route('imports.opportunities.merge', props.opportunity.id), { preserveScroll: true });
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
        <PageHeader :title="opportunity.title" :document-title="text(`Moderate ${opportunity.title}`, `Modérer ${opportunity.title}`)">
            <template #before>
                <div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-3 [&_svg]:size-3.5">
                    <Link class="hover:text-blue-700" :href="route('imports.review')">{{ text('Review queue', 'File de validation') }}</Link>
                    <ChevronRight aria-hidden="true" />
                    <span class="text-ink-700 font-medium">{{ text('Listing', 'Offre') }} {{ opportunity.id }}</span>
                </div>
            </template>
            <template #description>{{ opportunity.company }} · {{ opportunity.kind === 'job' ? text('job', 'emploi') : opportunity.kind === 'internship' ? text('internship', 'stage') : opportunity.kind }} · {{ opportunity.location || text('No location', 'Pas de lieu') }}</template>
            <template #actions>
                <a v-if="opportunity.publication_status === 'published'" class="cta cta-secondary border border-line" :href="opportunity.public_url">{{ text('Open page', 'Ouvrir la page') }} <ExternalLink class="size-4" aria-hidden="true" /></a>
            </template>
        </PageHeader>

        <div v-if="Object.keys(page.props.errors || {}).length" class="bg-red-50 border border-red-200 text-red-600 rounded-card p-4 mb-5 text-[14px]" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <div class="flex flex-wrap gap-2 mb-6">
            <StatusPill set="import" :status="opportunity.publication_status" />
            <StatusPill set="import" :status="opportunity.status === 'closed' ? 'closed' : 'open'" />
            <span class="inline-flex items-center text-[14px] font-medium px-[11px] py-[5px] rounded-full bg-paper-3 text-navy">{{ opportunity.origin === 'imported' ? text('Imported', 'Importée') : text('Posted by the company', 'Publiée par l’entreprise') }}</span>
        </div>
        <p v-if="opportunity.merged_into_id" class="rounded-card bg-white text-blue-700 px-6 py-4 text-[15px] mb-6">{{ text('This listing was merged into', 'Cette offre a été fusionnée avec') }} <Link class="font-medium underline" :href="route('imports.opportunities.show', opportunity.merged_into_id)">{{ text('listing', 'l’offre') }} {{ opportunity.merged_into_id }}</Link>.</p>

        <div class="grid lg:grid-cols-[minmax(0,1fr)_340px] gap-5 items-start">
            <div class="space-y-5">
                <form class="bg-white border border-transparent rounded-card p-6 space-y-4" @submit.prevent="moderate">
                    <h2 class="text-[20px] leading-[1.35] text-navy">{{ text('Hide, close or correct', 'Masquer, fermer ou corriger') }}</h2>
                    <label class="block text-[14px] font-medium text-navy">{{ text('Action', 'Action') }}
                        <select v-model="moderation.action" class="fld mt-1.5">
                            <option value="suppress">{{ text('Hide from the site', 'Masquer sur le site') }}</option>
                            <option value="close">{{ text('Mark as closed', 'Marquer comme fermée') }}</option>
                            <option value="restore">{{ text('Show a hidden listing again', 'Réafficher une offre masquée') }}</option>
                            <option value="correct">{{ text('Correct a fact', 'Corriger une information') }}</option>
                        </select>
                    </label>
                    <div v-if="moderation.action === 'correct'" class="grid sm:grid-cols-2 gap-4">
                        <label class="block text-[14px] font-medium text-navy">{{ text('Fact', 'Information') }}
                            <select v-model="field" class="fld mt-1.5" @change="pickField"><option v-for="(label, key) in labels" :key="key" :value="key">{{ label }}</option></select>
                        </label>
                        <label class="block text-[14px] font-medium text-navy" :class="{ 'sm:col-span-2': !options }">{{ text('Correct value', 'Valeur correcte') }}
                            <select v-if="options" v-model="value" class="fld mt-1.5"><option v-for="option in options" :key="option">{{ option }}</option></select>
                            <textarea v-else v-model="value" class="fld mt-1.5" :rows="field === 'description' ? 10 : 2" required></textarea>
                        </label>
                    </div>
                    <label class="block text-[14px] font-medium text-navy">{{ text('Reason and evidence', 'Motif et preuves') }}<textarea v-model="moderation.reason" class="fld mt-1.5" required minlength="5" :placeholder="text('What you checked and why', 'Ce que vous avez vérifié et pourquoi')"></textarea></label>
                    <button class="cta cta-primary" :disabled="moderation.processing">{{ text('Save', 'Enregistrer') }}</button>
                </form>

                <form v-if="!opportunity.merged_into_id" class="bg-white border border-transparent rounded-card p-6 space-y-4" @submit.prevent="submitMerge">
                    <div>
                        <h2 class="text-[20px] leading-[1.35] text-navy">{{ text('Merge into another listing', 'Fusionner avec une autre offre') }}</h2>
                        <p class="text-[14px] text-muted mt-1">{{ text('For the same role posted twice by the same company. This listing is hidden and its link, saves and applications move to the one you keep. Keep the listing the company posted itself.', 'Pour la même offre publiée deux fois par la même entreprise. Cette offre est masquée et son lien, ses sauvegardes et ses candidatures passent à celle que vous gardez. Gardez l’offre publiée par l’entreprise elle-même.') }}</p>
                    </div>
                    <label class="block text-[14px] font-medium text-navy">{{ text('Listing to keep (ID)', 'Offre à garder (ID)') }}<input v-model.number="merge.survivor_id" class="fld mt-1.5 max-w-[200px]" type="number" min="1" required></label>
                    <label class="block text-[14px] font-medium text-navy">{{ text('Why they are the same role', 'Pourquoi c’est la même offre') }}<textarea v-model="merge.reason" class="fld mt-1.5" required minlength="10"></textarea></label>
                    <button class="cta cta-secondary border border-line" :disabled="merge.processing">{{ text('Merge listings', 'Fusionner les offres') }}</button>
                </form>
            </div>

            <aside class="space-y-5">
                <section class="bg-white border border-transparent rounded-card p-5">
                    <h2 class="text-[16px] font-medium mb-3">{{ text('Facts', 'Informations') }}</h2>
                    <dl class="text-[13.5px] space-y-2">
                        <div v-for="key in ['applicant_scope', 'remote_eligibility', 'relocation', 'visa_sponsorship', 'country_code']" :key="key" class="flex justify-between gap-3"><dt class="text-muted">{{ labels[key] }}</dt><dd class="font-medium">{{ opportunity[key] || '—' }}</dd></div>
                    </dl>
                </section>
                <section class="bg-white border border-transparent rounded-card p-5">
                    <h2 class="text-[16px] font-medium mb-3">{{ text('Sources', 'Sources') }}</h2>
                    <ul class="space-y-3 text-[13.5px]">
                        <li v-for="item in sources" :key="item.id">
                            <div class="flex items-center justify-between gap-2"><span class="font-medium truncate">{{ item.source_name }}</span><StatusPill set="import" :status="item.state" /></div>
                            <a class="text-blue-700 break-all text-[12.5px]" :href="item.source_url" target="_blank" rel="noopener noreferrer">{{ item.source_url }}</a>
                        </li>
                        <li v-if="!sources.length" class="text-muted">{{ text(`Posted on ${appName}, no outside source.`, `Publiée sur ${appName}, sans source externe.`) }}</li>
                    </ul>
                </section>
                <section v-if="reports.length" class="bg-white border border-transparent rounded-card p-5">
                    <h2 class="text-[16px] font-medium mb-3">{{ text('Open reports', 'Signalements ouverts') }}</h2>
                    <ul class="space-y-3 text-[13.5px]">
                        <li v-for="report in reports" :key="report.id"><span class="font-medium capitalize">{{ String(report.reason).replaceAll('_', ' ') }}</span><p class="text-muted mt-0.5 whitespace-pre-wrap">{{ report.details }}</p></li>
                    </ul>
                    <Link class="inline-block mt-3 text-[13px] font-medium text-blue-700" :href="route('imports.reports')">{{ text('Resolve on Reports', 'Résoudre dans Signalements') }}</Link>
                </section>
            </aside>
        </div>
    </div>
</template>
