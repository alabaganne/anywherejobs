<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowUpRight, Copy, Download, FileText, Mail, PenLine, RefreshCw } from '@lucide/vue';
import axios from 'axios';
import CvPreview from '@/Components/Cv/CvPreview.vue';
import { downloadLetterDocx, letterFileName } from '@/utils/letterDocx';

// The tailored CV and cover letter for this role: each one is written on its own, from the profile and main resume.
const props = defineProps({
    opportunity: { type: Object, required: true },
    kits: { type: Array, default: () => [] },
    kitLocales: { type: Array, default: () => [] },
    themes: { type: Array, default: () => [] },
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
const busy = ref(null);
const error = ref('');
const billingUrl = ref('');
const letter = ref('');
const savedLetter = ref('');
const saveState = ref('');
const copied = ref(false);
const exporting = ref(false);

const parts = computed(() => ({
    cv: { title: text('CV', 'CV'), name: text('your CV', 'votre CV'), icon: FileText, write: text('Write my CV', 'Rédiger mon CV') },
    letter: { title: text('Cover letter', 'Lettre'), name: text('your cover letter', 'votre lettre'), icon: Mail, write: text('Write my cover letter', 'Rédiger ma lettre') },
}));
const writing = (part) => kit.value?.status === 'queued' && [part, 'both'].includes(kit.value.writing || 'both');
const written = (part) => part === 'cv' ? !!kit.value?.cv : !!letter.value.trim() || !!kit.value?.cover_letter;
const queued = computed(() => kit.value?.status === 'queued');
const started = computed(() => !!kit.value);

const failed = (failure) => {
    const status = failure.response?.status;
    billingUrl.value = failure.response?.data?.billing_url || '';
    error.value = status === 429
        ? text('You asked for many drafts in a short time. Please wait a little and try again.', 'Vous avez demandé beaucoup de versions en peu de temps. Patientez un peu puis réessayez.')
        : failure.response?.data?.message || text('We could not start right now. Please try again in a minute.', 'Impossible de commencer pour le moment. Réessayez dans une minute.');
};

const take = (data) => {
    kits.value = { ...kits.value, [data.locale]: { ...kits.value[data.locale], ...data } };
    if (data.locale === locale.value && data.status !== 'queued' && 'cover_letter' in data && letter.value === savedLetter.value) {
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
// The full editor opens in another tab; what is saved there shows here when you come back.
const refresh = () => { if (document.visibilityState === 'visible') load(); };
onMounted(() => document.addEventListener('visibilitychange', refresh));
onBeforeUnmount(() => { clearTimeout(timer); clearTimeout(saveTimer); document.removeEventListener('visibilitychange', refresh); });

const themeSaving = ref(false);
const setTheme = async (template) => {
    const current = kit.value;
    if (!current?.style || current.style.template === template) return;
    const keep = (style) => { kits.value = { ...kits.value, [current.locale]: { ...kits.value[current.locale], style } }; };
    keep({ ...current.style, template });
    themeSaving.value = true;
    try {
        await axios.put(route('kits.update', current.id), { style: { template } }, { headers: { Accept: 'application/json' } });
    } catch {
        keep(current.style);
        error.value = text('We could not change the theme. Please try again.', 'Impossible de changer le thème. Réessayez.');
    } finally {
        themeSaving.value = false;
    }
};

const start = async (part) => {
    busy.value = part;
    tab.value = part;
    error.value = '';
    billingUrl.value = '';
    try {
        const { data } = await axios.post(route('opportunities.kit', props.opportunity.id), { locale: locale.value, part });
        take(data.kit);
        load();
    } catch (failure) {
        failed(failure);
    } finally {
        busy.value = null;
    }
};

const rewrite = async (part) => {
    const question = part === 'cv'
        ? text('Write a new CV for this role? Your edits to the CV will be replaced.', 'Rédiger un nouveau CV pour cette offre ? Vos modifications du CV seront remplacées.')
        : text('Write a new letter for this role? Your edits to the letter will be replaced.', 'Rédiger une nouvelle lettre pour cette offre ? Vos modifications de la lettre seront remplacées.');
    if (!window.confirm(question)) return;
    error.value = '';
    try {
        await save();
        const { data } = await axios.post(route('kits.regenerate', kit.value.id), { part });
        if (part === 'letter') letter.value = savedLetter.value;
        take(data.kit);
        load();
    } catch (failure) {
        failed(failure);
    }
};

// Edits save on their own a moment after typing stops.
let saveTimer = null;
const save = async () => {
    clearTimeout(saveTimer);
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
        const sender = kit.value.sender || kit.value.cv || {};
        await downloadLetterDocx({ letter: letter.value, name: sender.name, contact: sender.contact || [], locale: kit.value.locale, fileName: letterFileName(sender.name, props.opportunity.company?.name, kit.value.locale) });
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
        if (letter.value.trim()) sessionStorage.setItem(`site.cover-letter.${props.opportunity.id}`, letter.value);
        if (kit.value.cv) sessionStorage.setItem(`site.kit-cv.${props.opportunity.id}`, String(kit.value.id));
    } catch { /* storage may be off */ }
    router.visit(route('applications.create', props.opportunity.id));
};
const applyLabel = computed(() => written('cv') && written('letter') ? text('Use them to apply', 'Les utiliser pour postuler') : text('Use it to apply', 'L’utiliser pour postuler'));
const waitNote = (part) => part === 'cv'
    ? text('Your letter is being written. You can write the CV once it is done.', 'Votre lettre est en cours de rédaction. Vous pourrez rédiger le CV ensuite.')
    : text('Your CV is being written. You can write the letter once it is done.', 'Votre CV est en cours de rédaction. Vous pourrez rédiger la lettre ensuite.');
</script>

<template>
    <section id="tailored-cv" class="bg-white border border-line rounded-card p-6 sm:p-8 scroll-mt-24" aria-labelledby="kit-title">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-xl">
                <h2 id="kit-title" class="text-[22px] font-medium text-navy">{{ text('Tailored CV and cover letter', 'CV et lettre sur mesure') }}</h2>
                <p class="text-sm text-slate-600 leading-6 mt-2">{{ text('Written for this role from your profile and main resume. Write one or both, check them, then download them or use them to apply.', 'Rédigés pour cette offre à partir de votre profil et de votre CV principal. Rédigez l’un ou les deux, relisez-les, puis téléchargez-les ou utilisez-les pour postuler.') }}</p>
            </div>
            <fieldset class="flex rounded-card bg-paper-2 p-1 text-sm font-medium">
                <legend class="sr-only">{{ text('Language of the CV and letter', 'Langue du CV et de la lettre') }}</legend>
                <label v-for="option in languages" :key="option.code" class="cursor-pointer rounded-md px-3 py-1.5 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand" :class="locale === option.code ? 'bg-white text-navy' : 'text-slate-600'" :title="option.original ? text('The language of this listing', 'La langue de cette offre') : null">
                    <input v-model="locale" type="radio" :value="option.code" class="sr-only">{{ option.label }}
                </label>
            </fieldset>
        </div>

        <!-- Nothing written yet: one button for each. -->
        <div v-if="!started" class="mt-6">
            <div class="flex flex-wrap gap-3">
                <button v-for="(part, id) in parts" :key="id" type="button" class="cta" :class="id === 'cv' ? 'cta-primary' : 'cta-secondary'" :disabled="!!busy" @click="start(id)"><component :is="part.icon" :size="17" aria-hidden="true" />{{ busy === id ? text('Starting…', 'Démarrage…') : part.write }}</button>
            </div>
            <p class="text-xs text-slate-500 leading-5 mt-3">
                <template v-if="!free">{{ text(`1 credit covers your CV and letter for this role, with a few rewrites. You have ${credits}.`, `1 crédit couvre votre CV et votre lettre pour cette offre, avec quelques réécritures. Il vous en reste ${credits}.`) }}</template>
                {{ text('Better results with a main resume and your experience filled in on', 'Meilleur résultat avec un CV principal et vos expériences remplies sur') }} <Link :href="`${route('profile.show')}#resumes`" class="text-blue-700 underline">{{ text('your profile', 'votre profil') }}</Link>.
            </p>
        </div>

        <div v-else class="mt-6">
            <div class="inline-flex rounded-card bg-paper-2 p-1 text-sm font-medium" role="tablist">
                <button v-for="(part, id) in parts" :key="id" type="button" role="tab" :aria-selected="tab === id" class="inline-flex items-center gap-2 rounded-md px-4 py-1.5 focus-ring" :class="tab === id ? 'bg-white text-navy' : 'text-slate-600'" @click="tab = id">
                    {{ part.title }}<RefreshCw v-if="writing(id)" :size="13" class="animate-spin text-blue-700" aria-hidden="true" />
                </button>
            </div>

            <div v-for="(part, id) in parts" v-show="tab === id" :key="id" role="tabpanel" class="mt-4">
                <div v-if="writing(id)" class="rounded-card bg-paper-2 px-5 py-8 text-center" role="status">
                    <RefreshCw class="w-7 h-7 text-blue-700 mx-auto animate-spin" aria-hidden="true" />
                    <p class="font-semibold mt-3">{{ id === 'cv' ? text('Writing your CV…', 'Rédaction de votre CV…') : text('Writing your cover letter…', 'Rédaction de votre lettre…') }}</p>
                    <p class="text-sm text-slate-600 mt-1">{{ text('This usually takes under a minute.', 'Cela prend en général moins d’une minute.') }}</p>
                </div>

                <div v-else-if="!written(id)" class="rounded-card bg-paper-2 px-5 py-8 text-center">
                    <p v-if="kit.error" class="text-sm font-medium text-red-700 mb-3" role="alert">{{ kit.error }}</p>
                    <p class="text-sm text-slate-600">{{ id === 'cv' ? text('No CV for this role yet.', 'Pas encore de CV pour cette offre.') : text('No cover letter for this role yet.', 'Pas encore de lettre pour cette offre.') }}</p>
                    <button type="button" class="cta cta-primary mt-4" :disabled="!!busy || queued" @click="start(id)"><component :is="part.icon" :size="17" aria-hidden="true" />{{ busy === id ? text('Starting…', 'Démarrage…') : part.write }}</button>
                    <p v-if="queued" class="text-xs text-slate-500 mt-3">{{ waitNote(id) }}</p>
                </div>

                <template v-else-if="id === 'cv'">
                    <label v-if="themes.length && kit.style" class="mb-3 flex flex-wrap items-center gap-2 text-sm font-medium text-navy">{{ text('Theme', 'Thème') }}
                        <select :value="kit.style.template" class="rounded-card border-0 bg-paper-2 py-1.5 ps-3 pe-9 text-sm text-navy focus:ring-2 focus:ring-brand" :disabled="themeSaving" @change="setTheme($event.target.value)">
                            <option v-for="theme in themes" :key="theme.id" :value="theme.id">{{ theme.name }} · {{ theme.family }}</option>
                        </select>
                        <span v-if="themeSaving" class="font-normal text-slate-500" role="status">{{ text('Saving…', 'Enregistrement…') }}</span>
                    </label>
                    <div v-if="kit.cv && kit.style" class="max-h-[640px] overflow-y-auto rounded-card bg-paper-2 p-4 [scrollbar-width:thin]">
                        <CvPreview :cv="kit.cv" :style-options="kit.style" :locale="kit.locale" />
                    </div>
                    <p v-else class="rounded-card bg-paper-2 px-5 py-8 text-center text-sm text-slate-600">{{ text('Loading your CV…', 'Chargement de votre CV…') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <a :href="route('kits.show', kit.id)" target="_blank" rel="noopener" class="cta cta-primary !py-2.5"><PenLine :size="17" aria-hidden="true" />{{ text('More edits', 'Plus de modifications') }}<ArrowUpRight :size="16" aria-hidden="true" /><span class="sr-only">{{ text('(opens in a new tab)', '(s’ouvre dans un nouvel onglet)') }}</span></a>
                        <a :href="route('kits.cv', kit.id)" class="cta cta-secondary !py-2.5" :class="themeSaving && 'pointer-events-none opacity-60'"><Download :size="17" aria-hidden="true" />PDF</a>
                        <button v-if="applyHere" type="button" class="cta cta-secondary !py-2.5" @click="useToApply">{{ applyLabel }}</button>
                    </div>
                </template>

                <template v-else>
                    <label class="sr-only" for="cover-letter-text">{{ text('Cover letter', 'Lettre de motivation') }}</label>
                    <textarea id="cover-letter-text" v-model="letter" :lang="kit.locale" dir="auto" rows="16" maxlength="20000" class="w-full rounded-card border-line p-4 text-[15px] leading-7 text-slate-800 focus:border-brand focus:ring-brand" @input="edited" @blur="save" />
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <button type="button" class="cta cta-primary !py-2.5" @click="copy"><Copy :size="17" aria-hidden="true" />{{ copied ? text('Copied', 'Copiée') : text('Copy', 'Copier') }}</button>
                        <button type="button" class="cta cta-secondary !py-2.5" :disabled="exporting || !letter.trim()" @click="exportDocx"><Download :size="17" aria-hidden="true" />{{ text('Word (.docx)', 'Word (.docx)') }}</button>
                        <button v-if="applyHere" type="button" class="cta cta-secondary !py-2.5" @click="useToApply">{{ applyLabel }}</button>
                        <span class="text-sm text-slate-500" role="status">{{ { saving: text('Saving…', 'Enregistrement…'), saved: text('Saved', 'Enregistrée'), error: text('Not saved. Check your connection.', 'Non enregistrée. Vérifiez votre connexion.') }[saveState] }}</span>
                    </div>
                </template>

                <div v-if="written(id) && !writing(id)" class="mt-5 border-t border-line pt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <p class="text-slate-600">{{ text('Read it through and fix anything that is not exactly right before you send it.', 'Relisez-le et corrigez ce qui n’est pas tout à fait juste avant de l’envoyer.') }}</p>
                    <button type="button" class="inline-flex items-center gap-1.5 font-semibold text-blue-700 disabled:opacity-50" :disabled="queued" @click="rewrite(id)"><RefreshCw :size="15" aria-hidden="true" />{{ text('Write it again', 'Le réécrire') }}</button>
                    <span v-if="kit.rewrites_left !== null && kit.rewrites_left !== undefined" class="text-slate-500">{{ text(`${kit.rewrites_left} rewrite(s) left`, `${kit.rewrites_left} réécriture(s) restante(s)`) }}</span>
                </div>
            </div>
        </div>

        <p v-if="error" class="text-sm text-red-700 mt-4" role="alert">{{ error }} <a v-if="billingUrl" :href="billingUrl" class="underline font-semibold">{{ text('Get credits', 'Obtenir des crédits') }}</a></p>
    </section>
</template>
