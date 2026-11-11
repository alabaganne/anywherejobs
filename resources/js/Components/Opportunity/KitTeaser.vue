<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Check, Lock } from '@lucide/vue';

// What visitors see in place of the tailored CV and letter panel: a locked sketch and a sign-up button.
defineProps({
    signupUrl: { type: String, required: true },
    loginUrl: { type: String, required: true },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => (fr.value ? french : en);
const billing = computed(() => page.props.billing || {});
const perks = computed(() => [
    text('A CV aimed at this role', 'Un CV pensé pour cette offre'),
    text('A cover letter', 'Une lettre de motivation'),
    text('PDF and Word downloads', 'Téléchargement PDF et Word'),
]);
const note = computed(() => billing.value.enabled && billing.value.signup
    ? text(`It’s free to start: you get ${billing.value.signup} credits when you sign up, and one covers the CV and letter for a role.`, `C’est gratuit pour commencer : vous recevez ${billing.value.signup} crédits à l’inscription, et un seul couvre le CV et la lettre d’une offre.`)
    : text('It’s free. Sign up, fill in your profile and write them in a minute.', 'C’est gratuit. Inscrivez-vous, remplissez votre profil et rédigez-les en une minute.'));
</script>

<template>
    <section id="tailored-cv" class="bg-white border border-line rounded-card p-6 sm:p-8 scroll-mt-24" aria-labelledby="kit-teaser-title">
        <h2 id="kit-teaser-title" class="text-[22px] font-medium text-navy">{{ text('Tailored CV and cover letter', 'CV et lettre sur mesure') }}</h2>
        <p class="text-sm text-slate-600 leading-6 mt-2 max-w-xl">{{ text('Our AI writes a CV and a cover letter for this role from your profile and resume. You check and edit them before you send anything.', 'Notre IA rédige un CV et une lettre pour cette offre à partir de votre profil et de votre CV. Vous les relisez et les modifiez avant d’envoyer quoi que ce soit.') }}</p>
        <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-navy">
            <li v-for="perk in perks" :key="perk" class="inline-flex items-center gap-1.5"><Check :size="16" class="text-brand" aria-hidden="true" />{{ perk }}</li>
        </ul>

        <div class="relative mt-6 grid min-h-[380px] place-items-center overflow-hidden rounded-card bg-paper-2 p-5">
            <!-- A sketch of a CV and a letter, blurred under the lock. -->
            <div class="absolute inset-0 grid gap-4 p-5 sm:grid-cols-2 blur-[1.5px] select-none" aria-hidden="true">
                <div class="rounded-md bg-white p-5 overflow-hidden">
                    <div class="mx-auto h-3 w-2/5 rounded bg-navy/80" />
                    <div class="mx-auto mt-2 h-2 w-3/5 rounded bg-slate-300" />
                    <div class="mt-5 h-2 w-1/4 rounded bg-brand/70" />
                    <div class="mt-1.5 h-px bg-brand/40" />
                    <div v-for="width in ['w-full', 'w-11/12', 'w-4/5']" :key="width" class="mt-2 h-1.5 rounded bg-slate-300" :class="width" />
                    <div class="mt-4 h-2 w-1/4 rounded bg-brand/70" />
                    <div class="mt-1.5 h-px bg-brand/40" />
                    <div class="mt-2 flex justify-between"><div class="h-2 w-2/5 rounded bg-slate-400" /><div class="h-2 w-1/5 rounded bg-slate-300" /></div>
                    <div v-for="width in ['w-11/12', 'w-full', 'w-3/4', 'w-5/6', 'w-full', 'w-2/3']" :key="width" class="mt-2 ml-3 h-1.5 rounded bg-slate-300" :class="width" />
                </div>
                <div class="rounded-md bg-white p-5 overflow-hidden">
                    <div class="h-2 w-1/4 rounded bg-slate-300" />
                    <div class="mt-5 h-2 w-2/5 rounded bg-slate-400" />
                    <div v-for="(width, index) in ['w-full', 'w-11/12', 'w-full', 'w-2/3', 'w-full', 'w-5/6', 'w-full', 'w-3/4', 'w-11/12', 'w-1/2']" :key="index" class="h-1.5 rounded bg-slate-300" :class="[width, index === 4 || index === 8 ? 'mt-4' : 'mt-2']" />
                    <div class="mt-5 h-2 w-1/5 rounded bg-slate-400" />
                </div>
            </div>

            <div class="absolute inset-0 bg-navy/10" aria-hidden="true" />
            <div class="relative text-center">
                <div class="max-w-sm rounded-card border border-line bg-white px-5 py-6 sm:px-6">
                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-brand text-white"><Lock :size="22" aria-hidden="true" /></span>
                    <p class="mt-4 text-[19px] font-medium text-navy">{{ text('Sign up to write yours', 'Inscrivez-vous pour rédiger les vôtres') }}</p>
                    <p class="mt-1.5 text-sm text-slate-600 leading-6">{{ note }}</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-3">
                        <Link :href="signupUrl" class="cta cta-primary">{{ text('Sign up free', 'Inscription gratuite') }}</Link>
                        <Link :href="loginUrl" class="cta cta-secondary">{{ text('Log in', 'Se connecter') }}</Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
