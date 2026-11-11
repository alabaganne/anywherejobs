<script setup>
import { h, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.reports' }, () => page) });
defineProps({ reports: Array });
const page = usePage();
const { text, dateLocale } = useText();

const notes = ref({});
const resolve = (report, status) => router.put(route('imports.reports.resolve', report.id), { status, notes: notes.value[report.id] || '' }, { preserveScroll: true });
const date = (value) => value ? new Date(value).toLocaleDateString(dateLocale.value, { day: 'numeric', month: 'short', year: 'numeric' }) : '';
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
        <PageHeader :title="text('Reports', 'Signalements')" :description="text('Problems people reported on listings. Fix the listing if needed, then resolve or dismiss the report.', 'Les problèmes signalés sur des offres. Corrigez l’offre si besoin, puis résolvez ou écartez le signalement.')" />

        <div v-if="Object.keys(page.props.errors || {}).length" class="bg-red-50 border border-red-200 text-red-600 rounded-card p-4 mb-5 text-[14px]" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <div class="bg-white border border-transparent rounded-card overflow-hidden">
            <article v-for="report in reports" :key="report.id" class="px-7 pt-6 pb-1 max-[560px]:px-5 last:border-b-0">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-medium text-[15.5px]">{{ report.title }}</h2>
                        <p class="text-[13px] text-muted mt-0.5"><span class="capitalize">{{ String(report.reason).replaceAll('_', ' ') }}</span> · {{ date(report.created_at) }}</p>
                    </div>
                    <Link class="cta cta-secondary cta-sm" :href="route('imports.opportunities.show', report.opportunity_id)">{{ text('Moderate listing', 'Modérer l’offre') }}</Link>
                </div>
                <p v-if="report.details" class="text-[14px] leading-6 whitespace-pre-wrap mt-3 rounded-card bg-paper-2 p-4">{{ report.details }}</p>
                <label class="block text-[14px] font-medium text-navy mt-3">{{ text('What you did', 'Ce que vous avez fait') }}<textarea v-model="notes[report.id]" class="fld mt-1.5 !min-h-[64px]" rows="2" :placeholder="text('At least 5 characters', '5 caractères minimum')"></textarea></label>
                <div class="flex gap-2 mt-3">
                    <button type="button" class="cta cta-primary cta-sm" @click="resolve(report, 'resolved')">{{ text('Resolve', 'Résoudre') }}</button>
                    <button type="button" class="cta cta-secondary cta-sm" @click="resolve(report, 'dismissed')">{{ text('Dismiss', 'Écarter') }}</button>
                </div>
            </article>
            <div v-if="!reports.length" class="px-[22px] py-8 text-[14px] text-muted">{{ text('No open reports.', 'Aucun signalement ouvert.') }}</div>
        </div>
    </div>
</template>
