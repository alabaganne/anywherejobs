<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Gift, Lock } from '@lucide/vue';
import AppShell from '@/Layouts/AppShell.vue';
import PageHeader from '@/Components/Ui/PageHeader.vue';
import { siteName } from '@/brand';
import { useText } from '@/composables/useText';

defineOptions({ layout: AppShell });
const { fr, text } = useText();
const props = defineProps({ balance: Number, drafts_per_credit: Number, beta: Boolean, beta_credits: Number, gateways: Array, packs: Array, history: Array });
const names = computed(() => ({
	konnect: text('Konnect (cards, e-DINAR, wallet · TND)', 'Konnect (cartes, e-DINAR, portefeuille · TND)'),
	flouci: text('Flouci (wallet, cards · TND)', 'Flouci (portefeuille, cartes · TND)'),
	lemonsqueezy: text('International card or PayPal (USD)', 'Carte internationale ou PayPal (USD)'),
}));
const currencyOf = { konnect: 'TND', flouci: 'TND', lemonsqueezy: 'USD' };
const reasons = computed(() => ({
	welcome: text('Free credits', 'Crédits gratuits'),
	monthly: text('Free credits for the month', 'Crédits gratuits du mois'),
	beta: text('Free beta credits', 'Crédits gratuits de la bêta'),
	purchase: text('Pack bought', 'Pack acheté'),
	kit: text('CV and letter for a role', 'CV et lettre pour une offre'),
	resume: text('Resume translation', 'Traduction de CV'),
	resume_read: text('Profile filled from a resume', 'Profil rempli à partir d’un CV'),
	refund: text('Given back', 'Rendu'),
	grant: text(`Added by ${siteName()}`, `Ajouté par ${siteName()}`),
}));
const plural = computed(() => (fr.value ? props.balance > 1 : props.balance !== 1));
const gateway = ref(props.gateways[0] || null);
const busy = ref(null);
const price = (pack) => {
	const currency = currencyOf[gateway.value];
	const amount = pack.prices[currency];
	if (!amount) return null;
	return currency === 'TND' ? `${(amount / 1000).toFixed(3)} TND` : `$${(amount / 100).toFixed(2)}`;
};
const buy = (pack) => {
	busy.value = pack.key;
	router.post(route('billing.checkout'), { pack: pack.key, gateway: gateway.value }, { onFinish: () => (busy.value = null) });
};
const claiming = ref(false);
const claim = () => {
	claiming.value = true;
	router.post(route('billing.beta'), {}, { preserveScroll: true, onFinish: () => (claiming.value = false) });
};
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<PageHeader :title="text('Credits', 'Crédits')" :description="text(`Credits pay for the AI tools. One credit writes a CV and cover letter for one role, translates a resume or makes an uploaded PDF editable (each with ${drafts_per_credit - 1} rewrites included), or fills your profile from a resume. You get 5 free credits every month. Browsing, applying, and making resumes from your profile stay free.`, `Les crédits servent aux outils d’IA. Un crédit rédige un CV et une lettre de motivation pour une offre, traduit un CV ou rend modifiable un PDF importé (chacun avec ${drafts_per_credit - 1} réécritures incluses), ou remplit votre profil à partir d’un CV. Vous recevez 5 crédits gratuits chaque mois. Parcourir les offres, postuler et créer des CV à partir de votre profil restent gratuits.`)" />

		<div class="grid gap-5 mb-12" :class="beta ? 'md:grid-cols-[minmax(0,1fr)_minmax(0,2.2fr)]' : ''">
			<div class="bg-white border border-transparent rounded-card px-7 py-6 flex flex-col justify-center">
				<p class="text-sm text-muted">{{ text('Your balance', 'Votre solde') }}</p>
				<p class="text-3xl font-medium mt-1">{{ balance }} {{ text(`credit${plural ? 's' : ''}`, `crédit${plural ? 's' : ''}`) }}</p>
			</div>

			<section v-if="beta" class="bg-white rounded-card px-7 py-6 flex items-center justify-between gap-6 flex-wrap" aria-labelledby="beta-title">
				<div class="min-w-0 flex-1 basis-[300px]">
					<p class="flex items-center gap-2.5">
						<span class="rounded-full bg-navy-800 text-white text-[11px] font-medium px-2 py-0.5">{{ text('Beta', 'Bêta') }}</span>
						<span id="beta-title" class="text-[17px] font-medium text-ink">{{ text('Free credits during the beta', 'Crédits gratuits pendant la bêta') }}</span>
					</p>
					<p class="text-sm leading-relaxed text-ink-700 mt-2.5">{{ text(`${appName} is in beta and payments are still being built. Until they open, get ${beta_credits} free credits whenever you need more.`, `${appName} est en bêta et les paiements sont encore en construction. D’ici leur ouverture, recevez ${beta_credits} crédits gratuits dès que vous en avez besoin.`) }}</p>
				</div>
				<button class="cta cta-primary !text-sm !py-3 !px-5 disabled:opacity-60" type="button" :disabled="claiming" @click="claim">
					<Gift class="size-4" aria-hidden="true" />{{ claiming ? text('Adding…', 'Ajout…') : text(`Get ${beta_credits} free credits`, `Recevoir ${beta_credits} crédits gratuits`) }}
				</button>
			</section>
		</div>

		<section id="packs" class="mb-12 scroll-mt-20" aria-labelledby="packs-title">
			<h2 id="packs-title" class="mb-4 text-[20px] leading-[1.35] text-navy">{{ text('Buy credits', 'Acheter des crédits') }}</h2>

			<div v-if="!beta && (!gateways.length || !packs.length)" class="rounded-card bg-white px-5 py-4 text-[15px] text-navy">{{ text('Packs are not on sale yet.', 'Les packs ne sont pas encore en vente.') }}</div>
			<div v-else class="relative" :class="beta ? 'rounded-card bg-white overflow-hidden' : ''">
				<!-- During the beta the packs are shown greyed out under a lock. -->
				<div :class="beta ? 'p-7 max-[680px]:p-5 grayscale opacity-40 pointer-events-none select-none' : ''" :inert="beta || null" :aria-hidden="beta || null">
					<fieldset class="mb-6">
						<legend class="text-sm font-medium mb-3">{{ text('Pay with', 'Payer avec') }}</legend>
						<div class="flex flex-wrap gap-x-6 gap-y-3 text-sm"><label v-for="name in gateways" :key="name" class="flex items-center gap-2"><input v-model="gateway" type="radio" :value="name" :disabled="beta"> {{ names[name] }}</label></div>
					</fieldset>
					<div class="grid sm:grid-cols-3 gap-5">
						<article v-for="pack in packs" :key="pack.key" class="rounded-card p-7 flex flex-col" :class="beta ? 'bg-pale' : 'bg-white'">
							<p class="text-2xl font-medium">{{ pack.credits }} {{ text('credits', 'crédits') }}</p>
							<p class="text-muted text-sm mt-1.5">{{ text(`${pack.credits} credits for the AI tools`, `${pack.credits} crédits pour les outils d’IA`) }}</p>
							<p class="font-medium text-lg mt-5">{{ price(pack) || (beta ? text('Price coming soon', 'Prix bientôt disponible') : text('Not available with this method', 'Non disponible avec ce moyen de paiement')) }}</p>
							<button class="cta cta-primary mt-6 disabled:opacity-60" type="button" :disabled="beta || !price(pack) || busy" @click="buy(pack)">{{ busy === pack.key ? text('Opening…', 'Ouverture…') : text('Buy', 'Acheter') }}</button>
						</article>
					</div>
				</div>

				<div v-if="beta" class="absolute inset-0 bg-paper-3/70 grid place-items-center p-6 max-sm:content-start max-sm:pt-24">
					<div class="bg-white border border-line rounded-card px-8 py-8 max-[680px]:px-6 max-w-md text-center">
						<span class="mx-auto size-14 rounded-card bg-paper-2 border border-line grid place-items-center text-ink-700"><Lock class="size-6" aria-hidden="true" /></span>
						<p class="text-lg font-medium text-ink mt-5">{{ text('Payments are coming soon', 'Les paiements arrivent bientôt') }}</p>
						<p class="text-sm leading-relaxed text-muted mt-2">{{ text('We’re still setting up card and wallet payments. Packs open after the beta; until then, your credits are free.', 'Nous mettons encore en place les paiements par carte et par portefeuille. Les packs ouvriront après la bêta ; d’ici là, vos crédits sont gratuits.') }}</p>
					</div>
				</div>
			</div>
		</section>

		<h2 class="mb-4 text-[20px] leading-[1.35] text-navy">{{ text('History', 'Historique') }}</h2>
		<ul class="bg-white border border-transparent rounded-card divide-y divide-line-2">
			<li v-for="(row, index) in history" :key="index" class="flex justify-between gap-4 px-7 py-4 text-sm">
				<span>{{ reasons[row.reason] || row.reason }}<span v-if="row.note" class="text-muted"> · {{ row.note }}</span></span>
				<span class="font-medium" :class="row.amount > 0 ? 'text-emerald-700' : 'text-ink-700'">{{ row.amount > 0 ? '+' : '' }}{{ row.amount }}</span>
			</li>
			<li v-if="!history.length" class="px-7 py-4 text-sm text-muted">{{ text('Nothing yet.', 'Rien pour le moment.') }}</li>
		</ul>
	</div>
</template>
