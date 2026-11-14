<script setup>
import { h } from 'vue';
import { router } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import Pagination from '@/Components/Ui/Pagination.vue';
import StatusPill from '@/Components/Ui/StatusPill.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.runs' }, () => page) });
defineProps({ runs: Object, deliveryHealth: Array, uncertainDeliveries: Array, scheduledTasks: Array });
const { text, dateLocale } = useText();
const date = (value) => value ? new Date(value).toLocaleString(dateLocale.value, { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
        <PageHeader :title="text('Runs & health', 'Exécutions et état')" :description="text('Recent imports and how alert emails are going out.', 'Les imports récents et l’envoi des e-mails d’alerte.')" />

        <section class="my-6 rounded-card bg-white p-5">
            <h2 class="text-xl mb-4">{{ text('Scheduled tasks', 'Tâches planifiées') }}</h2>
            <div v-for="task in scheduledTasks" :key="task.name" class="flex items-center justify-between gap-4 py-3 border-b border-line-2">
                <span>{{ task.name }}</span>
                <div class="flex gap-4">
                    <button type="button" :aria-pressed="task.enabled" @click="router.post('/app/admin/imports/schedule/' + task.name, { enabled: !task.enabled }, { preserveScroll: true })">{{ task.enabled ? text('Automatic: on', 'Automatique : activé') : text('Automatic: off', 'Automatique : désactivé') }}</button>
                    <button type="button" @click="router.post('/app/admin/imports/schedule/' + task.name + '/run', {}, { preserveScroll: true })">{{ text('Run now', 'Exécuter') }}</button>
                </div>
            </div>
        </section>

        <h2 class="mb-3 text-[20px] leading-[1.35] text-navy">{{ text('Recent runs', 'Exécutions récentes') }}</h2>
        <div class="bg-white border border-transparent rounded-card overflow-x-auto">
            <table class="w-full min-w-[760px] text-left">
                <thead class="bg-paper-2 border-b border-line-2 [&_th]:px-[18px] [&_th]:py-3 [&_th]:text-[11.5px] [&_th]:tracking-[.08em] [&_th]:uppercase [&_th]:text-muted-2 [&_th]:font-medium">
                    <tr><th scope="col">{{ text('Source', 'Source') }}</th><th scope="col">{{ text('Status', 'Statut') }}</th><th scope="col" class="text-right">{{ text('Found', 'Trouvées') }}</th><th scope="col" class="text-right">{{ text('Read', 'Lues') }}</th><th scope="col" class="text-right">{{ text('Published', 'Publiées') }}</th><th scope="col">{{ text('Started', 'Début') }}</th></tr>
                </thead>
                <tbody>
                    <tr v-for="run in runs.data" :key="run.id" class="border-b border-line-2 last:border-b-0 align-top text-[13.5px] [&_td]:px-[18px] [&_td]:py-3.5">
                        <td class="font-medium">{{ run.import_source?.name }}<p v-if="run.error" class="font-normal text-[12.5px] text-red-600 mt-1 max-w-[340px]">{{ run.error }}</p></td>
                        <td><StatusPill set="import" :status="run.status === 'completed' && !run.complete ? 'partial' : run.status" /></td>
                        <td class="text-right tabular-nums">{{ run.discovered }}</td>
                        <td class="text-right tabular-nums">{{ run.processed }}</td>
                        <td class="text-right tabular-nums">{{ run.published }}</td>
                        <td class="text-muted whitespace-nowrap">{{ date(run.started_at) }}</td>
                    </tr>
                    <tr v-if="!runs.data.length"><td colspan="6" class="px-[18px] py-8 text-[14px] text-muted">{{ text('No runs yet.', 'Aucune exécution pour le moment.') }}</td></tr>
                </tbody>
            </table>
        </div>
        <Pagination :meta="runs" />

        <h2 class="mt-10 mb-3 text-[20px] leading-[1.35] text-navy">{{ text('Alert emails', 'E-mails d’alerte') }}</h2>
        <div class="flex flex-wrap gap-2.5">
            <div v-for="state in deliveryHealth" :key="state.status" class="bg-white border border-transparent rounded-card px-4 py-3 min-w-[120px]">
                <p class="text-[12px] uppercase tracking-[.08em] text-muted-2 font-medium">{{ state.status }}</p>
                <p class="text-[22px] font-medium tabular-nums mt-0.5">{{ state.total }}</p>
            </div>
            <p v-if="!deliveryHealth.length" class="text-[14px] text-muted">{{ text('No alert emails sent yet.', 'Aucun e-mail d’alerte envoyé pour le moment.') }}</p>
        </div>
        <article v-for="batch in uncertainDeliveries" :key="batch.batch_id" class="mt-3 rounded-card bg-amber-100 text-amber-800 p-4 text-[14px]">
            <p class="font-medium">{{ text(`Batch ${batch.batch_id} may not have been sent`, `Le lot ${batch.batch_id} n’a peut-être pas été envoyé`) }}</p>
            <p v-if="batch.last_error" class="mt-1">{{ batch.last_error }}</p>
            <p class="mt-2">{{ text('Check the mail provider, then run', 'Vérifiez le fournisseur d’e-mail, puis lancez') }} <code class="rounded-md bg-pale px-1.5 py-0.5 text-[13px] text-navy">php artisan opportunities:review-digest {{ batch.batch_id }}</code> {{ text('with the outcome. Retries for this search are paused until then.', 'avec le résultat. Les nouveaux essais pour cette recherche sont en pause d’ici là.') }}</p>
        </article>
    </div>
</template>
