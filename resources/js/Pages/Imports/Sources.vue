<script setup>
import { computed, h, onMounted, ref, watch } from 'vue';
import { Link, router, useForm, usePoll } from '@inertiajs/vue3';
import { Download, Pause, Play, Power, RefreshCw, Search } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import { appPath } from '@/utils/url';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.sources' }, () => page) });
const props = defineProps({ sources: Array, fetch: Object });
const { text, dateLocale } = useText();

const fetchForm = useForm({ count: 10 });
const startFetch = () => fetchForm.post(route('imports.fetch'), { preserveScroll: true });
// While a fetch runs, the page asks for its progress every 5 seconds.
const poll = usePoll(5000, { only: ['fetch', 'sources'] }, { autoStart: false });
onMounted(() => watch(() => props.fetch?.running, (running) => running ? poll.start() : poll.stop(), { immediate: true }));
const newListings = (n) => text(`${n} new ${n === 1 ? 'listing' : 'listings'}`, `${n} ${n > 1 ? 'nouvelles offres' : 'nouvelle offre'}`);
const sourcesCount = (n) => text(`${n} ${n === 1 ? 'source' : 'sources'}`, `${n} ${n > 1 ? 'sources' : 'source'}`);

const search = ref('');
const filter = ref('all');
const filters = computed(() => [
    { key: 'all', label: text('All', 'Toutes'), test: () => true },
    { key: 'enabled', label: text('Enabled', 'Activées'), test: (source) => source.enabled },
    { key: 'paused', label: text('Paused', 'En pause'), test: (source) => !source.enabled },
    { key: 'permission', label: text('Needs permission', 'Autorisation requise'), test: (source) => source.permission_status !== 'approved' },
    { key: 'errors', label: text('With errors', 'Avec erreurs'), test: (source) => !!source.last_error },
].map((item) => ({ ...item, count: props.sources.filter(item.test).length })));
const shown = computed(() => {
    const words = search.value.trim().toLowerCase();
    const test = filters.value.find((item) => item.key === filter.value).test;
    return props.sources.filter((source) => test(source) && (!words || `${source.name} ${source.key} ${source.url}`.toLowerCase().includes(words)));
});

const runnable = (source) => source.enabled && source.permission_status === 'approved';
const toggle = (source) => router.put(route('imports.sources.update', source.id), { ...source, enabled: !source.enabled }, { preserveScroll: true });
const queue = (source, recheck = false) => router.post(route('imports.sources.run', source.id), { recheck }, { preserveScroll: true });
const date = (value) => value ? new Date(value).toLocaleString(dateLocale.value, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : text('Never', 'Jamais');
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
        <PageHeader :title="text('Sources', 'Sources')" :description="text(`The job boards and employer pages ${appName} reads every day.`, `Les sites d’offres et les pages d’employeurs que ${appName} lit chaque jour.`)">
            <template #actions>
                <form class="flex items-center gap-2" @submit.prevent="startFetch">
                    <label class="sr-only" for="fetch-count">{{ text('How many new listings to add', 'Combien de nouvelles offres ajouter') }}</label>
                    <select id="fetch-count" v-model="fetchForm.count" class="fld !w-auto !py-[11px] bg-white" :disabled="fetch?.running">
                        <option v-for="n in [10, 25, 50, 100]" :key="n" :value="n">{{ n }} {{ text('listings', 'offres') }}</option>
                    </select>
                    <button class="cta cta-secondary disabled:opacity-50 disabled:pointer-events-none" type="submit" :disabled="fetchForm.processing || fetch?.running">
                        <Download class="size-4" aria-hidden="true" />{{ text('Fetch new listings', 'Récupérer les nouvelles offres') }}
                    </button>
                </form>
                <Link class="cta cta-primary" :href="route('imports.sources.create')">{{ text('Add a source', 'Ajouter une source') }}</Link>
            </template>
        </PageHeader>

        <p v-if="fetchForm.errors.count" class="text-[13.5px] text-red-600 -mt-4 mb-5">{{ fetchForm.errors.count }}</p>
        <section v-if="fetch" class="rounded-card bg-white px-6 py-5 mb-6 text-[14px]" aria-live="polite">
            <p class="font-medium flex items-center gap-2">
                <RefreshCw v-if="fetch.running" class="size-4 text-blue-600 animate-spin" aria-hidden="true" />
                <template v-if="fetch.running">{{ text(`Fetching new listings: ${fetch.added.length} of ${fetch.count} added`, `Récupération des nouvelles offres : ${fetch.added.length} sur ${fetch.count} ajoutées`) }}</template>
                <template v-else-if="fetch.added.length">{{ text(`${newListings(fetch.added.length)} added`, `${newListings(fetch.added.length)} ajoutée${fetch.added.length > 1 ? 's' : ''}`) }}</template>
                <template v-else>{{ text('No new listings found', 'Aucune nouvelle offre trouvée') }}</template>
            </p>
            <p class="text-muted mt-1">
                {{ text(`${fetch.read} of ${sourcesCount(fetch.sources)} read`, `${fetch.read} source${fetch.read > 1 ? 's' : ''} lue${fetch.read > 1 ? 's' : ''} sur ${fetch.sources}`) }}<template v-if="fetch.failed">{{ text(`, ${fetch.failed} could not be read (see`, `, ${fetch.failed} n’${fetch.failed > 1 ? 'ont' : 'a'} pas pu être lue${fetch.failed > 1 ? 's' : ''} (voir`) }} <Link class="text-blue-700 font-medium" :href="route('imports.runs')">{{ text('Runs & health', 'Exécutions et état') }}</Link>)</template>.
                {{ text(`Started by ${fetch.by} on ${date(fetch.started_at)}`, `Lancée par ${fetch.by} le ${date(fetch.started_at)}`) }}<template v-if="fetch.finished_at">{{ text(`, finished ${date(fetch.finished_at)}`, `, terminée le ${date(fetch.finished_at)}`) }}</template>.
                <template v-if="fetch.stopped"> {{ text('It stopped before the end; start it again to go on.', 'Elle s’est arrêtée avant la fin ; relancez-la pour continuer.') }}</template>
            </p>
            <p v-if="!fetch.running" class="text-muted mt-1">{{ text(`Only IT listings posted in the last ${fetch.days} days that a profile wants are added, never one already on ${appName}. Listings waiting for review or a company check show in the`, `Seules les offres informatiques publiées ces ${fetch.days} derniers jours et voulues par un profil sont ajoutées, jamais une offre déjà sur ${appName}. Les offres en attente de validation ou de vérification de l’entreprise apparaissent dans la`) }} <Link class="text-blue-700 font-medium" :href="route('imports.review', { state: 'all' })">{{ text('review queue', 'file de validation') }}</Link>.</p>
            <ul v-if="fetch.added.length" class="mt-3 grid gap-1.5">
                <li v-for="role in fetch.added" :key="role.id">
                    <Link class="font-medium hover:text-blue-700" :href="appPath(role.public_url)">{{ role.title }}</Link>
                    <span class="text-muted"> · {{ role.company }} · {{ role.kind === 'job' ? text('Job', 'Emploi') : text('Internship', 'Stage') }}</span>
                </li>
            </ul>
        </section>

        <div class="flex flex-wrap items-center gap-3 mb-5">
            <label class="relative w-full max-w-[320px]">
                <span class="sr-only">{{ text('Search sources', 'Rechercher des sources') }}</span>
                <Search class="size-4 text-muted-2 absolute left-3.5 top-1/2 -translate-y-1/2" aria-hidden="true" />
                <input v-model="search" class="fld !py-2.5 !pl-10 bg-white" type="search" :placeholder="text('Search by name or link', 'Rechercher par nom ou lien')">
            </label>
            <nav class="flex flex-wrap gap-2" :aria-label="text('Filter sources', 'Filtrer les sources')">
                <button
                    v-for="item in filters"
                    :key="item.key"
                    type="button"
                    class="chip"
                    :class="{ 'is-active': filter === item.key }"
                    :aria-pressed="filter === item.key"
                    @click="filter = item.key"
                >{{ item.label }} <span class="opacity-70 tabular-nums">{{ item.count }}</span></button>
            </nav>
        </div>

        <div class="bg-white border border-transparent rounded-card overflow-x-auto">
            <table class="w-full min-w-[860px] text-left">
                <thead class="bg-paper-2 border-b border-line-2 [&_th]:px-[18px] [&_th]:py-3 [&_th]:text-[11.5px] [&_th]:tracking-[.08em] [&_th]:uppercase [&_th]:text-muted-2 [&_th]:font-medium">
                    <tr><th scope="col">{{ text('Source', 'Source') }}</th><th scope="col">{{ text('Permission', 'Autorisation') }}</th><th scope="col">{{ text('Status', 'Statut') }}</th><th scope="col">{{ text('Listings', 'Offres') }}</th><th scope="col">{{ text('Last full read', 'Dernière lecture complète') }}</th><th scope="col"><span class="sr-only">{{ text('Actions', 'Actions') }}</span></th></tr>
                </thead>
                <tbody>
                    <tr v-for="source in shown" :key="source.id" class="border-b border-line-2 last:border-b-0 align-top [&_td]:px-[18px] [&_td]:py-4">
                        <td class="max-w-[360px]">
                            <Link class="font-medium text-[15px] hover:text-blue-700" :href="route('imports.sources.edit', source.id)">{{ source.name }}</Link>
                            <p class="text-[12.5px] text-muted mt-0.5"><span class="capitalize">{{ source.provider === 'jsonld' ? text('Job pages', 'Pages d’offres') : source.provider }}</span> · <span class="break-all">{{ source.url }}</span></p>
                            <p v-if="source.last_error" class="text-[12.5px] text-red-600 mt-1.5">{{ source.last_error }}</p>
                        </td>
                        <td><StatusPill set="import" :status="source.permission_status" /></td>
                        <td>
                            <StatusPill set="import" :status="source.enabled ? 'enabled' : 'paused'" />
                            <p class="text-[12.5px] text-muted mt-1.5">{{ source.publication_mode === 'leads' ? text('Job leads only', 'Pistes d’emploi seulement') : text('Publishes on its own', 'Publie seule') }}</p>
                        </td>
                        <td class="text-[13.5px] tabular-nums whitespace-nowrap">
                            {{ source.items_count }} {{ text('found', source.items_count > 1 ? 'trouvées' : 'trouvée') }}
                            <Link v-if="source.pending_count" class="block text-blue-700 font-medium mt-0.5" :href="route('imports.review', { source_id: source.id, state: 'all' })">{{ source.pending_count }} {{ text('waiting', source.pending_count > 1 ? 'en attente' : 'en attente') }}</Link>
                        </td>
                        <td class="text-[13.5px] text-muted whitespace-nowrap">{{ date(source.last_complete_at) }}<span class="block text-[12.5px]">{{ text('every', 'toutes les') }} {{ source.cadence_hours }} h</span></td>
                        <td>
                            <div class="flex items-center justify-end gap-1.5">
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!runnable(source)" :aria-label="text(`Run import for ${source.name}`, `Lancer l’import pour ${source.name}`)" :title="text('Run import', 'Lancer l’import')" @click="queue(source)"><Play class="size-4" aria-hidden="true" /></button>
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!runnable(source)" :aria-label="text(`Recheck ${source.name}`, `Revérifier ${source.name}`)" :title="text('Recheck every listing', 'Revérifier chaque offre')" @click="queue(source, true)"><RefreshCw class="size-4" aria-hidden="true" /></button>
                                <button class="icon-btn !size-9 disabled:opacity-35 disabled:pointer-events-none" type="button" :disabled="!source.enabled && source.permission_status !== 'approved'" :aria-label="`${source.enabled ? text('Pause', 'Mettre en pause') : text('Enable', 'Activer')} ${source.name}`" :title="source.enabled ? text('Pause', 'Mettre en pause') : text('Enable', 'Activer')" @click="toggle(source)">
                                    <component :is="source.enabled ? Pause : Power" class="size-4" aria-hidden="true" />
                                </button>
                                <Link class="cta cta-secondary cta-sm" :href="route('imports.sources.edit', source.id)">{{ text('Edit', 'Modifier') }}</Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!shown.length"><td colspan="6" class="px-[18px] py-8 text-[14px] text-muted">{{ sources.length ? text('No sources match.', 'Aucune source ne correspond.') : text('No sources yet. Add a job board or employer page to start.', 'Aucune source pour le moment. Ajoutez un site d’offres ou une page d’employeur pour commencer.') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
