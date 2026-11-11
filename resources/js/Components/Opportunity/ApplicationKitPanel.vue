<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Copy, Download, PenLine, RefreshCw, Sparkles } from '@lucide/vue';
import axios from 'axios';
import CvPreview from '@/Components/Cv/CvPreview.vue';
import { downloadLetterDocx, letterFileName } from '@/utils/letterDocx';

const props = defineProps({
    opportunity: { type: Object, required: true },
    kits: { type: Array, default: () => [] },
    kitLocales: { type: Array, default: () => [] },
});
const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const text = (en, french) => (fr.value ? french : en);
// The listing's own language first when we can write in it, otherwise the site language.
const languages = computed(() => props.kitLocales.length ? props.kitLocales : [{ code: 'en', label: 'English' }, { code: 'fr', label: 'Français' }]);
const siteLocale = page.props.locale === 'fr' ? 'fr' : 'en';
const locale = ref(languages.value.find((option) => option.original)?.code || siteLocale);
const kits = ref(Object.fromEntries(props.kits.map((kit) => [kit.locale, kit])));
const kit = computed(() => kits.value[locale.value] || null);
const billing = computed(() => page.props.billing || {});
const credits = computed(() => page.props.auth?.user?.credits ?? 0);
const free = computed(() => !billing.value.enabled || billing.value.free);
const tab = ref('cv');
const busy = ref(false);
const error = ref('');
const billingUrl = ref('');
const letter = ref('');
const savedLetter = ref('');
const saveState = ref('');
const copied = ref(false);
const exporting = ref(false);

const failed = (failure) => {
    const status = failure.response?.status;
    billingUrl.value = failure.response?.data?.billing_url || '';
    error.value = status === 429
        ? text('You asked for many drafts in a short time. Please wait a little and try again.', 'Vous avez demandé beaucoup de versions en peu de temps. Patientez un peu puis réessayez.')
        : failure.response?.data?.message || text('We could not start right now. Please try again in a minute.', 'Impossible de commencer pour le moment. Réessayez dans une minute.');
};

const take = (data) => {
    kits.value = { ...kits.value, [data.locale]: { ...kits.value[data.locale], ...data } };
    if (data.locale === locale.value && data.status !== 'queued') {
        letter.value = savedLetter.value = data.cover_letter || '';
    }
};

let timer = null;
const load = async () => {
    clearTimeout(timer);
    if (!kit.value) return;
    try {
        const { data } = await axios.get(route('kits.show', kit.value.id), { headers: { Accept: 'application/json' } });
        take(data.kit);
    } catch { /* try again on the next tick */ }
    if (kit.value?.status === 'queued') timer = setTimeout(load, 3000);
};
watch(locale, () => { error.value = ''; letter.value = savedLetter.value = ''; load(); }, { immediate: true });
onBeforeUnmount(() => { clearTimeout(timer); clearTimeout(saveTimer); });

const start = async () => {
    busy.value = true;
    error.value = '';
    billingUrl.value = '';
    try {
        const { data } = await axios.post(route('opportunities.kit', props.opportunity.id), { locale: locale.value });
        take(data.kit);
        load();
    } catch (failure) {
        failed(failure);
    } finally {
        busy.value = false;
    }
};

const rewrite = async () => {
    if (!window.confirm(text('Write a new CV and letter for this role? Your edits will be replaced.', 'Rédiger un nouveau CV et une nouvelle lettre pour cette offre ? Vos modifications seront remplacées.'))) return;
    error.value = '';
    try {
        const { data } = await axios.post(route('kits.regenerate', kit.value.id));
        take(data.kit);
        load();
    } catch (failure) {
        failed(failure);
    }
};

// Edits save on their own a moment after typing stops.
let saveTimer = null;
const save = async () => {
    if (!kit.value || letter.value === savedLetter.value) return;
    const value = letter.value;
    saveState.value = 'saving';
    try {
        await axios.put(route('kits.update', kit.value.id), { cover_letter: value }, { headers: { Accept: 'application/json' } });
        savedLetter.value = value;
        saveState.value = letter.value === value ? 'saved' : 'saving';
    } catch {
        saveState.value = 'error';
    }
};
const edited = () => {
    saveState.value = 'saving';
    clearTimeout(saveTimer);
    saveTimer = setTimeout(save, 1200);
};

const copy = async () => {
    try {
        await navigator.clipboard.writeText(letter.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        error.value = text('Could not copy. Select the text and copy it instead.', 'Copie impossible. Sélectionnez le texte et copiez-le.');
    }
};

const exportDocx = async () => {
    exporting.value = true;
    try {
        const cv = kit.value.cv || {};
        await downloadLetterDocx({ letter: letter.value, name: cv.name, contact: cv.contact || [], locale: kit.value.locale, fileName: letterFileName(cv.name, props.opportunity.company?.name, kit.value.locale) });
    } catch {
        error.value = text('We could not make the Word file. Please try again.', 'Impossible de créer le fichier Word. Réessayez.');
    } finally {
        exporting.value = false;
    }
};

const applyHere = computed(() => props.opportunity.application_mode === 'internal' && !props.opportunity.application);
// The apply page picks up the letter and this CV.
const useToApply = async () => {
    await save();
    try {
        sessionStorage.setItem(`site.cover-letter.${props.opportunity.id}`, letter.value);
        sessionStorage.setItem(`site.kit-cv.${props.opportunity.id}`, String(kit.value.id));
    } catch { /* storage may be off */ }
    router.visit(route('applications.create', props.opportunity.id));
};
const tabs = computed(() => [{ id: 'cv', label: text('CV', 'CV') }, { id: 'letter', label: text('Cover letter', 'Lettre') }]);
</script>

<template>
    <section id="tailored-cv" class="bg-white border border-line rounded-card p-6 sm:p-8 scroll-mt-24" aria-labelledby="kit-title">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <h2 id="kit-title" class="text-[22px] font-medium flex items-center gap-2"><Sparkles :size="19" class="text-blue-700" aria-hidden="true" />{{ text('Tailored CV and cover letter', 'CV et lettre sur mesure') }}</h2>
                <p class="text-sm text-slate-600 leading-6 mt-2">{{ text('Made for this role from your profile and main resume. Check them, then download them or use them to apply.', 'Rédigés pour cette offre à partir de votre profil et de votre CV principal. Relisez-les, puis téléchargez-les ou utilisez-les pour postuler.') }}</p>
            </div>
            <fieldset class="flex rounded-card bg-paper-2 p-1 text-sm font-medium">
                <legend class="sr-only">{{ text('Language of the CV and letter', 'Langue du CV et de la lettre') }}</legend>
                <label v-for="option in languages" :key="option.code" class="cursor-pointer rounded-md px-3 py-1.5 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="locale === option.code ? 'bg-white text-navy' : 'text-slate-600'" :title="option.original ? text('The language of this listing', 'La langue de cette offre') : null">
                    <input v-model="locale" type="radio" :value="option.code" class="sr-only">{{ option.label }}
                </label>
            </fieldset>
        </div>

        <div v-if="!kit" class="mt-6">
            <button type="button" class="cta cta-primary" :disabled="busy" @click="start"><Sparkles :size="17" aria-hidden="true" />{{ busy ? text('Starting…', 'Démarrage…') : text('Write my CV and cover letter', 'Rédiger mon CV et ma lettre') }}</button>
            <p class="text-xs text-slate-500 leading-5 mt-3">
                <template v-if="!free">{{ text(`Uses 1 credit for both, with 3 rewrites included. You have ${credits}.`, `Utilise 1 crédit pour les deux, 3 réécritures incluses. Il vous en reste ${credits}.`) }}</template>
                {{ text('Better results with a main resume and your experience filled in on', 'Meilleur résultat avec un CV principal et vos expériences remplies sur') }} <Link :href="`${route('profile.show')}#resumes`" class="text-blue-700 underline">{{ text('your profile', 'votre profil') }}</Link>.
            </p>
        </div>

        <div v-else-if="kit.status === 'queued'" class="mt-6 rounded-card bg-paper-2 px-5 py-8 text-center" role="status">
            <RefreshCw class="w-7 h-7 text-blue-700 mx-auto animate-spin" aria-hidden="true" />
            <p class="font-semibold mt-3">{{ text('Writing your CV and cover letter…', 'Rédaction de votre CV et de votre lettre…') }}</p>
            <p class="text-sm text-slate-600 mt-1">{{ text('This usually takes under a minute.', 'Cela prend en général moins d’une minute.') }}</p>
        </div>

        <div v-else-if="kit.status === 'failed' && !kit.cv && !letter" class="mt-6 rounded-card bg-paper-2 px-5 py-8 text-center">
            <p class="font-semibold">{{ kit.error || text('Something went wrong.', 'Une erreur est survenue.') }}</p>
            <button type="button" class="cta cta-primary mt-4" @click="rewrite">{{ text('Try again', 'Réessayer') }}</button>
        </div>

        <div v-else class="mt-6">
            <p class="rounded-card bg-blue-50 px-4 py-3 text-sm text-blue-900">{{ text('Read them through and fix anything that is not exactly right before you send them.', 'Relisez-les et corrigez ce qui n’est pas tout à fait juste avant de les envoyer.') }}</p>
            <div class="mt-5 inline-flex rounded-card bg-paper-2 p-1 text-sm font-medium" role="tablist">
                <button v-for="item in tabs" :key="item.id" type="button" role="tab" :aria-selected="tab === item.id" class="rounded-md px-4 py-1.5 focus-ring" :class="tab === item.id ? 'bg-white text-navy' : 'text-slate-600'" @click="tab = item.id">{{ item.label }}</button>
            </div>

            <div v-show="tab === 'cv'" role="tabpanel" class="mt-4">
                <div v-if="kit.cv && kit.style" class="max-h-[640px] overflow-y-auto rounded-card bg-paper-2 p-4 [scrollbar-width:thin]">
                    <CvPreview :cv="kit.cv" :style-options="kit.style" :locale="kit.locale" />
                </div>
                <p v-else class="rounded-card bg-paper-2 px-5 py-8 text-center text-sm text-slate-600">{{ text('Loading your CV…', 'Chargement de votre CV…') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <Link :href="route('kits.show', kit.id)" class="cta cta-primary !py-2.5"><PenLine :size="17" aria-hidden="true" />{{ text('Edit CV', 'Modifier le CV') }}</Link>
                    <a v-if="kit.cv" :href="route('kits.cv', kit.id)" class="cta cta-secondary !py-2.5"><Download :size="17" aria-hidden="true" />PDF</a>
                    <button v-if="applyHere" type="button" class="cta cta-secondary !py-2.5" @click="useToApply">{{ text('Use them to apply', 'Les utiliser pour postuler') }}</button>
                </div>
            </div>

            <div v-show="tab === 'letter'" role="tabpanel" class="mt-4">
                <label class="sr-only" for="cover-letter-text">{{ text('Cover letter', 'Lettre de motivation') }}</label>
                <textarea id="cover-letter-text" v-model="letter" :lang="kit.locale" dir="auto" rows="16" maxlength="20000" class="w-full rounded-card border-line p-4 text-[15px] leading-7 text-slate-800 focus:border-brand focus:ring-brand" @input="edited" @blur="save" />
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <button type="button" class="cta cta-primary !py-2.5" @click="copy"><Copy :size="17" aria-hidden="true" />{{ copied ? text('Copied', 'Copiée') : text('Copy', 'Copier') }}</button>
                    <button type="button" class="cta cta-secondary !py-2.5" :disabled="exporting || !letter.trim()" @click="exportDocx"><Download :size="17" aria-hidden="true" />{{ text('Word (.docx)', 'Word (.docx)') }}</button>
                    <button v-if="applyHere" type="button" class="cta cta-secondary !py-2.5" @click="useToApply">{{ text('Use them to apply', 'Les utiliser pour postuler') }}</button>
                    <span class="text-sm text-slate-500" role="status">{{ { saving: text('Saving…', 'Enregistrement…'), saved: text('Saved', 'Enregistrée'), error: text('Not saved. Check your connection.', 'Non enregistrée. Vérifiez votre connexion.') }[saveState] }}</span>
                </div>
            </div>

            <div class="mt-5 border-t border-line pt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold">
                <button type="button" class="inline-flex items-center gap-1.5 text-blue-700" @click="rewrite"><RefreshCw :size="15" aria-hidden="true" />{{ text('Write them again', 'Les réécrire') }}</button>
                <span v-if="kit.rewrites_left !== null && kit.rewrites_left !== undefined" class="font-normal text-slate-500">{{ text(`${kit.rewrites_left} rewrite(s) left`, `${kit.rewrites_left} réécriture(s) restante(s)`) }}</span>
            </div>
        </div>

        <p v-if="error" class="text-sm text-red-700 mt-4" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-semibold">{{ text('Get credits', 'Obtenir des crédits') }}</a></p>
    </section>
</template>
