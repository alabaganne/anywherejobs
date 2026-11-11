<script setup>
import { ChevronRight } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.sources' }, () => page) });
const props = defineProps({ source: { type: Object, default: null } });

const editing = computed(() => !!props.source);
const { text } = useText();
const settings = { employer_name: '', job_path_prefix: '', authoritative_employer: false, scope: 'all' };
const defaults = { key: '', name: '', provider: 'jsonld', url: '', allowed_domains: [], permission_status: 'pending', permission_notes: '', publication_mode: 'review', enabled: false, company_id: null, cadence_hours: 24, request_delay_ms: 1000, max_requests: 100 };
const form = useForm({
    ...Object.fromEntries(Object.keys(defaults).map((key) => [key, props.source?.[key] ?? defaults[key]])),
    settings: { ...settings, ...(props.source?.settings || {}) },
});
const domainText = ref((props.source?.allowed_domains || []).join(', '));

const submit = () => {
    form.allowed_domains = [...new Set(domainText.value.split(/[\s,]+/).map((part) => part.trim().toLowerCase()).filter(Boolean))];
    editing.value ? form.put(route('imports.sources.update', props.source.id)) : form.post(route('imports.sources.store'));
};
</script>

<template>
    <div class="max-w-[960px] mx-auto px-5 md:px-10 py-9">
        <div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
            <Link class="hover:text-blue-700" :href="route('imports.sources.index')">{{ text('Sources', 'Sources') }}</Link>
            <ChevronRight aria-hidden="true" />
            <span class="text-ink-700 font-medium">{{ editing ? source.name : text('New source', 'Nouvelle source') }}</span>
        </div>
        <h1 class="mb-6 text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? text('Edit source', 'Modifier la source') : text('Add a source', 'Ajouter une source') }}</h1>
        <div v-if="Object.keys(form.errors).length" class="bg-red-50 border border-red-200 text-red-700 rounded-card p-4 mb-6" role="alert"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
        <form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
            <div class="p-6 space-y-4">
                <div class="grid md:grid-cols-3 gap-4">
                    <label class="text-sm font-medium">{{ text('Name', 'Nom') }}<input v-model="form.name" class="fld mt-2" required></label><label class="text-sm font-medium">{{ text('Stable source key', 'Clé de source stable') }}<input v-model="form.key" class="fld mt-2" required placeholder="company-greenhouse"></label><label class="text-sm font-medium">{{ text('Provider', 'Fournisseur') }}<select v-model="form.provider" class="fld mt-2"><option value="jsonld">{{ text('Public pages with JSON-LD', 'Pages publiques avec JSON-LD') }}</option><option value="greenhouse">Greenhouse</option><option value="lever">Lever</option><option value="ashby">Ashby</option><option value="workable">Workable</option><option value="recruitee">Recruitee</option><option value="himalayas">Himalayas ({{ text('job leads', 'pistes d’emploi') }})</option><option value="remoteok">Remote OK ({{ text('job leads', 'pistes d’emploi') }})</option><option value="remotive">Remotive ({{ text('job leads', 'pistes d’emploi') }})</option><option value="wwr">We Work Remotely RSS ({{ text('job leads', 'pistes d’emploi') }})</option><option value="hn">Hacker News Who is hiring ({{ text('job leads', 'pistes d’emploi') }})</option><option value="pages">{{ text('Job pages linked from board posts (AI check)', 'Pages d’offres liées dans les messages des forums (vérifiées par l’IA)') }}</option></select></label>
                    <label class="text-sm font-medium md:col-span-2">{{ text('Feed, board API, or sitemap URL', 'URL du flux, de l’API du site d’offres ou du sitemap') }}<input v-model="form.url" class="fld mt-2" type="url" required></label><label class="text-sm font-medium">{{ text('Allowed domains', 'Domaines autorisés') }}<input v-model="domainText" class="fld mt-2" :required="form.provider !== 'pages'" :placeholder="form.provider === 'pages' ? text('Any site: the links in the posts', 'N’importe quel site : les liens des messages') : 'boards-api.greenhouse.io, example.com'"></label>
                    <label class="text-sm font-medium">{{ text('Reuse permission', 'Autorisation de réutilisation') }}<select v-model="form.permission_status" class="fld mt-2"><option value="pending">{{ text('Pending review', 'En attente de vérification') }}</option><option value="approved">{{ text('Approved for the intended reuse', 'Approuvée pour la réutilisation prévue') }}</option><option value="revoked">{{ text('Revoked', 'Révoquée') }}</option></select></label><label class="text-sm font-medium">{{ text('Publication', 'Publication') }}<select v-model="form.publication_mode" class="fld mt-2"><option value="review">{{ text('Review every new role', 'Valider chaque nouvelle offre') }}</option><option value="automatic">{{ text('Publish validated roles automatically', 'Publier automatiquement les offres validées') }}</option><option value="leads">{{ text('Job leads only, never published', 'Pistes d’emploi seulement, jamais publiées') }}</option></select></label><label class="text-sm font-medium">{{ text('Verified company ID, optional', 'ID de l’entreprise vérifiée, facultatif') }}<input v-model.number="form.company_id" class="fld mt-2" type="number" min="1"></label>
                    <label class="text-sm font-medium md:col-span-3">{{ text('Permission evidence and review notes', 'Preuve d’autorisation et notes de vérification') }}<textarea v-model="form.permission_notes" class="fld mt-2" rows="3" :placeholder="text('Record the agreement, source URL, scope of reuse, and review date. Robots access alone does not grant republication rights.', 'Notez l’accord, l’URL de la source, l’étendue de la réutilisation et la date de vérification. L’accès autorisé aux robots ne donne pas à lui seul le droit de republier.')"></textarea></label>
                    <label class="text-sm font-medium">{{ text('Cadence, hours', 'Fréquence, en heures') }}<input v-model.number="form.cadence_hours" class="fld mt-2" type="number" min="1" max="168" required></label><label class="text-sm font-medium">{{ text('Delay between requests, milliseconds', 'Délai entre les requêtes, en millisecondes') }}<input v-model.number="form.request_delay_ms" class="fld mt-2" type="number" min="500" max="60000" required></label><label class="text-sm font-medium">{{ text('Maximum requests per run', 'Nombre maximal de requêtes par exécution') }}<input v-model.number="form.max_requests" class="fld mt-2" type="number" min="1" max="1000" required></label>
                    <label class="text-sm font-medium">{{ text('Employer name supplied by this board', 'Nom de l’employeur fourni par ce site d’offres') }}<input v-model="form.settings.employer_name" class="fld mt-2"></label><label class="text-sm font-medium">{{ text('Job URL path prefix, optional', 'Préfixe du chemin des URL d’offres, facultatif') }}<input v-model="form.settings.job_path_prefix" class="fld mt-2" placeholder="/careers/jobs/"></label>
                </div>
                <label class="flex gap-2 items-start text-sm"><input v-model="form.settings.authoritative_employer" type="checkbox" class="mt-1"> {{ text(`This is the employer's authoritative board. Confirmed closure takes precedence over aggregator copies.`, 'C’est le site d’offres officiel de l’employeur. Une fermeture confirmée prévaut sur les copies des agrégateurs.') }}</label>
                <label class="flex gap-2 items-center text-sm"><input v-model="form.enabled" type="checkbox"> {{ text('Enable scheduled imports once reuse permission is approved', 'Activer les imports planifiés une fois l’autorisation de réutilisation approuvée') }}</label>
            </div>
            <div class="px-6 py-4 border-t border-line-2 flex items-center justify-end gap-2.5">
                <Link class="cta cta-secondary" :href="route('imports.sources.index')">{{ text('Cancel', 'Annuler') }}</Link>
                <button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? text('Save changes', 'Enregistrer les modifications') : text('Add source', 'Ajouter la source') }}</button>
            </div>
        </form>
    </div>
</template>
