<script setup>
import { computed, h, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.review' }, () => page) });
const props = defineProps({ items: Object, counts: Object, sources: Array, filters: Object });
const page = usePage();
const { text, dateLocale } = useText();

const states = computed(() => [
    { key: 'review', label: text('Waiting for review', 'En attente de validation') },
    { key: 'pending', label: text('Not read yet', 'Pas encore lue') },
    { key: 'published', label: text('Published', 'Publiée') },
    { key: 'waiting', label: text('Waiting for company check', 'En attente de la vérification de l’entreprise') },
    { key: 'unmatched', label: text('No profile wants it', 'Aucun profil n’en veut') },
    { key: 'lead', label: text('Job leads only', 'Pistes d’emploi seulement') },
    { key: 'rejected', label: text('Rejected', 'Rejetée') },
    { key: 'closed', label: text('Closed', 'Fermée') },
    { key: 'all', label: text('All', 'Toutes') },
].map((state) => ({ ...state, count: state.key === 'all' ? Object.values(props.counts).reduce((sum, n) => sum + Number(n), 0) : Number(props.counts[state.key] || 0) })));
const visit = (changes) => router.get(route('imports.review'), { ...props.filters, page: undefined, ...changes }, { preserveScroll: true, preserveState: true });

const publish = (item) => router.post(route('imports.items.publish', item.id), {}, { preserveScroll: true });
const rejection = useForm({ reason: '' });
const rejectingId = ref(null);
const reject = () => rejection.post(route('imports.items.reject', rejectingId.value), { preserveScroll: true, onSuccess: () => { rejectingId.value = null; rejection.reset(); } });
const date = (value) => value ? new Date(value).toLocaleString(dateLocale.value, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : text('Not yet', 'Pas encore');
const kindLabel = (kind) => kind === 'job' ? text('job', 'emploi') : kind === 'internship' ? text('internship', 'stage') : kind;
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
        <PageHeader :title="text('Review queue', 'File de validation')" :description="text('Listings found on the sources. Open one to see its page, then approve it, reject it or trust its source.', 'Les offres trouvées sur les sources. Ouvrez-en une pour voir sa page, puis approuvez-la, rejetez-la ou faites confiance à sa source.')" />

        <div v-if="Object.keys(page.props.errors || {}).length" class="bg-red-50 border border-red-200 text-red-600 rounded-card p-4 mb-5 text-[14px]" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <nav class="flex flex-wrap gap-2" :aria-label="text('Filter by state', 'Filtrer par état')">
                <button
                    v-for="state in states"
                    :key="state.key"
                    type="button"
                    class="chip"
                    :class="{ 'is-active': filters.state === state.key }"
                    :aria-pressed="filters.state === state.key"
                    @click="visit({ state: state.key })"
                >{{ state.label }} <span class="opacity-70 tabular-nums">{{ state.count }}</span></button>
            </nav>
            <label class="w-full max-w-[260px]">
                <span class="sr-only">{{ text('Source', 'Source') }}</span>
                <select class="fld !py-2.5 bg-white" :value="filters.source_id || ''" @change="visit({ source_id: $event.target.value || undefined })">
                    <option value="">{{ text('All sources', 'Toutes les sources') }}</option>
                    <option v-for="source in sources" :key="source.id" :value="source.id">{{ source.name }}</option>
                </select>
            </label>
        </div>

        <div class="bg-white border border-transparent rounded-card overflow-hidden">
            <article v-for="item in items.data" :key="item.id" class="px-7 pt-6 pb-1 max-[560px]:px-5 last:border-b-0">
                <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="font-medium text-[15.5px]"><Link v-if="item.normalized_data" :href="route('imports.items.show', { item: item.id, ...filters })" class="hover:text-blue-700">{{ item.normalized_data.title }}</Link><template v-else>{{ item.opportunity?.title || text('Not read yet', 'Pas encore lue') }}</template></h2>
                            <StatusPill set="import" :status="item.state" />
                        </div>
                        <p class="text-[13px] text-muted mt-1">
                            <template v-if="item.normalized_data">{{ item.normalized_data.employer }} · {{ kindLabel(item.normalized_data.kind) }} · {{ item.normalized_data.location || text('No location', 'Pas de lieu') }} · </template>
                            {{ item.source_name }} · {{ text('checked', 'vérifiée le') }} {{ date(item.last_verified_at) }}
                        </p>
                        <p v-if="item.normalized_data?.page_check" class="text-[13px] text-ink-700 mt-1.5">{{ text('AI check:', 'Vérification par l’IA :') }} {{ item.normalized_data.page_check.reason }}</p>
                        <p v-if="item.error" class="text-[13px] text-red-600 mt-1.5">{{ item.error }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Link v-if="item.normalized_data" class="cta cta-secondary cta-sm" :href="route('imports.items.show', { item: item.id, ...filters })">{{ text('View page', 'Voir la page') }}</Link>
                        <button v-if="item.normalized_data && !['closed', 'published'].includes(item.state)" type="button" class="cta cta-primary cta-sm" @click="publish(item)">{{ text('Approve', 'Approuver') }}</button>
                        <button v-if="item.state !== 'rejected'" type="button" class="cta cta-danger cta-sm" :aria-expanded="rejectingId === item.id" @click="rejectingId = rejectingId === item.id ? null : item.id">{{ text('Reject', 'Rejeter') }}</button>
                        <Link v-if="item.opportunity" class="cta cta-secondary cta-sm" :href="route('imports.opportunities.show', item.opportunity.id)">{{ text('Moderate', 'Modérer') }}</Link>
                        <a v-if="item.opportunity?.publication_status === 'published'" class="text-[13px] font-medium text-ink-700 px-2 py-[7px] hover:text-blue-700" :href="item.opportunity.public_url">{{ text('Open page', 'Ouvrir la page') }}</a>
                        <a v-if="item.post_url" class="inline-flex items-center gap-1 text-[13px] font-medium text-ink-700 px-2 py-[7px] hover:text-blue-700" :href="item.post_url" target="_blank" rel="noopener noreferrer">{{ text('Post', 'Message') }} <ExternalLink class="size-3.5" aria-hidden="true" /></a>
                        <a class="inline-flex items-center gap-1 text-[13px] font-medium text-ink-700 px-2 py-[7px] hover:text-blue-700" :href="item.normalized_data?.page_check?.page_url || item.source_url" target="_blank" rel="noopener noreferrer">{{ item.post_url ? text('Job page', 'Page de l’offre') : text('Original', 'Original') }} <ExternalLink class="size-3.5" aria-hidden="true" /></a>
                    </div>
                </div>
                <form v-if="rejectingId === item.id" class="mt-3 flex flex-wrap items-end gap-3" @submit.prevent="reject">
                    <label class="grow text-[14px] font-medium text-navy">{{ text('Why reject it?', 'Pourquoi la rejeter ?') }}<input v-model="rejection.reason" class="fld mt-1.5 !py-2.5" minlength="5" required></label>
                    <button class="cta !py-2.5 cta-danger" :disabled="rejection.processing">{{ text('Reject listing', 'Rejeter l’offre') }}</button>
                </form>
                <details v-if="item.normalized_data" class="mt-3 group">
                    <summary class="text-[13px] font-medium text-blue-700 cursor-pointer">{{ text('Facts and description', 'Informations et description') }}</summary>
                    <div class="mt-3 rounded-card bg-paper-2 p-4">
                        <p class="text-[12.5px] text-muted"><template v-if="item.normalized_data.quality">{{ text('Quality:', 'Qualité :') }} {{ item.normalized_data.quality }}<template v-if="item.normalized_data.quality_reason"> ({{ item.normalized_data.quality_reason }})</template> · </template>{{ text('Who can apply:', 'Qui peut postuler :') }} {{ item.normalized_data.applicant_scope || text('unknown', 'inconnu') }} · {{ text('Relocation:', 'Relocalisation :') }} {{ item.normalized_data.relocation }} · {{ text('Visa:', 'Visa :') }} {{ item.normalized_data.visa_sponsorship }}<template v-if="item.normalized_data.field"> · {{ text('Field:', 'Domaine :') }} {{ item.normalized_data.field }}</template></p>
                        <p class="whitespace-pre-wrap text-[14px] leading-6 mt-3 max-h-[420px] overflow-auto">{{ item.normalized_data.description }}</p>
                    </div>
                </details>
            </article>
            <div v-if="!items.data.length" class="px-[22px] py-8 text-[14px] text-muted">{{ filters.state === 'review' ? text('Nothing is waiting for review.', 'Rien n’attend de validation.') : text('No listings match.', 'Aucune offre ne correspond.') }}</div>
        </div>

        <Pagination :meta="items" />
    </div>
</template>
