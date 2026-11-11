<script setup>
import { h, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import { useText } from '@/composables/useText';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'imports.claims' }, () => page) });
defineProps({ claims: Array });
const page = usePage();
const { text, dateLocale } = useText();

const notes = ref({});
const review = (claim, status) => router.put(route('imports.claims.review', claim.id), { status, review_notes: notes.value[claim.id] || '' }, { preserveScroll: true });
const date = (value) => value ? new Date(value).toLocaleDateString(dateLocale.value, { day: 'numeric', month: 'short', year: 'numeric' }) : '';
</script>

<template>
    <div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
        <PageHeader :title="text('Employer claims', 'Demandes d’employeurs')" :description="text('People asking to manage a company page. Approve only after checking they really work there.', 'Des personnes demandent à gérer la page d’une entreprise. N’approuvez qu’après avoir vérifié qu’elles y travaillent vraiment.')" />

        <div v-if="Object.keys(page.props.errors || {}).length" class="bg-red-50 border border-red-200 text-red-600 rounded-card p-4 mb-5 text-[14px]" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <div class="bg-white border border-transparent rounded-card overflow-hidden">
            <article v-for="claim in claims" :key="claim.id" class="px-7 pt-6 pb-1 max-[560px]:px-5 last:border-b-0">
                <h2 class="font-medium text-[15.5px]">{{ claim.company_name || `${text('Company', 'Entreprise')} ${claim.company_id}` }}</h2>
                <p class="text-[13px] text-muted mt-0.5">{{ claim.claimant_name }} · {{ claim.claimant_email }} · {{ date(claim.created_at) }}</p>
                <p class="text-[14px] leading-6 whitespace-pre-wrap mt-3 rounded-card bg-paper-2 p-4">{{ claim.evidence }}</p>
                <label class="block text-[14px] font-medium text-navy mt-3">{{ text('How you checked it', 'Comment vous l’avez vérifié') }}<textarea v-model="notes[claim.id]" class="fld mt-1.5 !min-h-[64px]" rows="2" :placeholder="text('Independent proof that this person can speak for the employer (at least 20 characters)', 'Une preuve indépendante que cette personne peut parler au nom de l’employeur (20 caractères minimum)')"></textarea></label>
                <div class="flex gap-2 mt-3">
                    <button type="button" class="cta cta-primary cta-sm" @click="review(claim, 'approved')">{{ text('Approve claim', 'Approuver la demande') }}</button>
                    <button type="button" class="cta cta-danger cta-sm" @click="review(claim, 'rejected')">{{ text('Reject claim', 'Rejeter la demande') }}</button>
                </div>
            </article>
            <div v-if="!claims.length" class="px-[22px] py-8 text-[14px] text-muted">{{ text('No claims waiting.', 'Aucune demande en attente.') }}</div>
        </div>
    </div>
</template>
