<script setup>
import { ArrowRight, ArrowUpRight, CalendarCheck, Check, ChevronRight, Circle, Clock3, Download, Mail, MapPin, Phone, UserRound } from '@lucide/vue';
import { computed, h, ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { appPath } from '@/utils/url';
import AppShell from '@/Layouts/AppShell.vue';
import CompanyLogo from '@/Components/Ui/CompanyLogo.vue';
import { siteName } from '@/brand';
import { useText } from '@/composables/useText';

defineOptions({
	layout: (_h, page) => h(AppShell, { active: 'applications' }, () => page),
});

const props = defineProps({
	application: { type: Object, required: true },
});

const icons = {
	chevron: ChevronRight,
	calendarCheck: CalendarCheck,
	pin: MapPin,
	clock: Clock3,
	external: ArrowUpRight,
	check: Check,
	dot: Circle,
	download: Download,
	arrow: ArrowRight,
	mail: Mail,
	person: UserRound,
	phone: Phone,
};

const { text } = useText();
const page = usePage();
const user = computed(() => page.props.auth?.user);
// Admins are candidates too, so this is about whose application it is.
const isCandidate = computed(() => user.value?.userable_type === 'candidate' && user.value?.userable_id === props.application.candidate?.id);
const isExternal = computed(() => props.application.channel === 'external');
// Legacy boolean statuses map onto the enum introduced with the redesign.
const normalizedStatus = computed(() => {
	const status = props.application.status;
	if (status === true || status === 'accepted') return 'offer';
	if (status === false || status === 'rejected') return 'closed';
	return status || 'submitted';
});

const open = computed(() => !['offer', 'closed'].includes(normalizedStatus.value));

const canReply = computed(
	() => !isExternal.value && !isCandidate.value && (user.value?.userable_type === 'company' || user.value?.is_admin) && open.value
);

const STAGES = computed(() => ({
	submitted: { label: text('Submitted', 'Envoyée'), color: '#b45309', bg: '#fef3c7', border: '#d97706', title: text('Application submitted', 'Candidature envoyée') },
	viewed: { label: text('Viewed', 'Consultée'), color: '#1d4ed8', bg: '#eef4ff', border: '#2563eb', title: text('Viewed by the company', 'Consultée par l’entreprise') },
	review: { label: text('In review', 'En cours d’examen'), color: '#b45309', bg: '#fef3c7', border: '#d97706', title: text('Application in review', 'Candidature en cours d’examen') },
	interview: { label: text('Interview', 'Entretien'), color: '#047857', bg: '#dcfce7', border: '#059669', title: text('Interview stage', 'Étape de l’entretien') },
	offer: { label: text('Offer', 'Proposition'), color: '#6d28d9', bg: '#ede9fe', border: '#7c3aed', title: text('Offer extended', 'Proposition faite') },
	closed: { label: text('Not selected', 'Non retenue'), color: '#5b6677', bg: '#eef2f9', border: '#8a93a3', title: text('Not selected', 'Non retenue') },
}));

const stage = computed(() => {
	const base = STAGES.value[normalizedStatus.value] || STAGES.value.submitted;
	const company = props.application.company.name;
	if (isExternal.value) {
		const label = normalizedStatus.value === 'submitted' ? text('Applied', 'Postulé') : normalizedStatus.value === 'closed' ? text('Closed', 'Clôturée') : base.label;
		return { ...base, title: `${text('Your update', 'Votre mise à jour')} : ${label}`, note: text(`Candidate-reported status. ${siteName()} has not submitted or verified this application.`, `Statut déclaré par le candidat. ${siteName()} n’a ni envoyé ni vérifié cette candidature.`) };
	}
	const notes = {
		submitted: text('Waiting for the company to review this application.', 'En attente de l’examen de cette candidature par l’entreprise.'),
		viewed: text(`${company} has viewed this application.`, `${company} a consulté cette candidature.`),
		interview: text('This application is at the interview stage.', 'Cette candidature en est à l’étape de l’entretien.'),
		offer: text(`${company} wants to move forward with this application.`, `${company} souhaite donner suite à cette candidature.`),
		closed: text('This application was closed.', 'Cette candidature a été clôturée.'),
	};
	return { ...base, note: notes[normalizedStatus.value] || text(`${company} is reviewing this application.`, `${company} examine cette candidature.`) };
});

const counterpart = computed(() =>
	isCandidate.value
		? { ...props.application.company, role: 'Company', roleLabel: text('Company', 'Entreprise') }
		: { ...props.application.candidate, role: 'Applicant', roleLabel: text('Applicant', 'Candidat') }
);

const reviewForm = useForm({
	status: ['viewed', 'review', 'interview'].includes(normalizedStatus.value) ? normalizedStatus.value : '',
});
const confirmingDisqualify = ref(false);
const reply = (status) => {
	if (reviewForm.processing || !status) return;
	reviewForm.status = status;
	reviewForm.post(route('applications.reply', props.application.id), { preserveScroll: true });
};
const withdraw = () => {
	if (confirm(isExternal.value ? text('Remove this private tracking record? This does not withdraw your application from the employer.', 'Supprimer ce suivi privé ? Cela ne retire pas votre candidature auprès de l’employeur.') : text('Withdraw this application? This cannot be undone.', 'Retirer cette candidature ? Cette action est irréversible.'))) {
		router.delete(route('applications.destroy', props.application.id));
	}
};
const trackingForm = useForm({
	status: props.application.status || 'submitted',
	applied_at: props.application.applied_at || '',
	candidate_notes: props.application.candidate_notes || '',
});
const saveTracking = () => trackingForm.patch(route('applications.tracking.update', props.application.id), { preserveScroll: true });
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-wrap mx-auto">
		<div class="flex items-center gap-2 text-[13.5px] text-muted font-medium mb-[22px] [&_svg]:w-3.5 [&_svg]:h-3.5 [&_svg]:text-muted-2">
			<Link class="hover:text-blue-700" :href="route('applications.index')">{{ text('Applications', 'Candidatures') }}</Link>
			<component :is="icons.chevron" aria-hidden="true" />
			<span class="text-ink-700 font-medium">{{ application.opportunity.title }}</span>
		</div>

		<!-- STATUS BANNER -->
		<div class="flex items-center gap-4 bg-white border border-transparent rounded-card px-6 py-[18px] mb-[22px]">
			<div class="w-11 h-11 rounded-card grid place-items-center shrink-0 [&_svg]:w-[22px] [&_svg]:h-[22px]" :style="{ background: stage.bg, color: stage.color }"><component :is="icons.calendarCheck" aria-hidden="true" /></div>
			<div>
				<b class="text-base font-medium block">{{ stage.title }}</b>
				<span class="text-[13.5px] text-muted">{{ stage.note }}</span>
			</div>
			<div class="ml-auto text-right text-[13px] text-muted-2 max-[680px]:hidden">
				<b class="text-[13.5px] text-ink-700 font-body">{{ text('Updated', 'Mise à jour') }} {{ application.updated_at }}</b><br>
				{{ text('Applied', 'Postulée le') }} {{ application.applied_at || application.created_at }}
			</div>
		</div>

		<div class="grid grid-cols-[1fr_350px] gap-6 items-start max-[1080px]:grid-cols-1">
			<!-- LEFT -->
			<div class="flex flex-col gap-4">
				<section class="bg-white border border-transparent rounded-card overflow-hidden px-8 py-[30px] max-[560px]:px-5 max-[560px]:py-6">
					<div class="flex items-start gap-[18px] max-[560px]:flex-col max-[560px]:gap-4">
						<CompanyLogo class="w-[62px] h-[62px] rounded-card text-[22px] shrink-0 max-[560px]:size-12 max-[560px]:text-base" :name="application.company.name" :src="application.company.image" />
						<div class="min-w-0">
							<h1 class="text-[length:clamp(24px,2.6vw,36px)] font-medium leading-[1.15] text-navy break-words">{{ application.opportunity.title }}</h1>
							<p class="text-[15px] text-muted mt-1.5"><b class="text-ink-700 font-medium">{{ application.company.name }}</b> · {{ application.opportunity.field.name }}</p>
						</div>
					</div>
					<div class="flex flex-wrap gap-2.5 mt-[22px] [&_.m]:flex [&_.m]:items-center [&_.m]:gap-[7px] [&_.m]:text-[13.5px] [&_.m]:text-ink-700 [&_.m]:font-medium [&_.m]:bg-paper-2 [&_.m]:border [&_.m]:border-line-2 [&_.m]:px-[13px] [&_.m]:py-2 [&_.m]:rounded-[9px] [&_svg]:w-[15px] [&_svg]:h-[15px] [&_svg]:text-blue-600">
						<span class="m"><component :is="icons.pin" aria-hidden="true" />{{ application.opportunity.city.name }}</span>
						<span class="m"><component :is="icons.clock" aria-hidden="true" />{{ application.opportunity.closing_at ? text(`Closes ${application.opportunity.closing_at}`, `Date limite : ${application.opportunity.closing_at}`) : text('No deadline listed', 'Pas de date limite indiquée') }}</span>
						<Link class="m no-underline" :href="appPath(application.opportunity.public_url) || route('app.opportunities.show', application.opportunity.id)"><component :is="icons.external" aria-hidden="true" />{{ text('View role', 'Voir l’offre') }}</Link>
					</div>
				</section>

				<!-- TIMELINE -->
				<section v-if="!isExternal" class="bg-white border border-transparent rounded-card px-8 max-[560px]:px-5 py-[30px]">
					<h2 class="mb-1.5 text-[20px] leading-[1.35] text-navy">{{ text('Application progress', 'Avancement de la candidature') }}</h2>
					<p class="text-[13.5px] text-muted mb-[18px]">{{ text('Submission date and current application stage.', 'Date d’envoi et étape actuelle de la candidature.') }}</p>
					<div class="relative pl-[34px] before:content-[''] before:absolute before:left-[11px] before:top-1.5 before:bottom-2.5 before:w-0.5 before:bg-line">
						<div class="relative pb-[26px]">
							<span class="absolute -left-[34px] top-0 w-6 h-6 rounded-full bg-blue-600 border-2 border-blue-600 text-white grid place-items-center [&_svg]:w-[13px] [&_svg]:h-[13px]"><component :is="icons.check" aria-hidden="true" /></span>
							<b class="text-[15px] font-medium block">{{ text('Application submitted', 'Candidature envoyée') }}</b>
							<div class="text-[15px] text-subtle mt-1">{{ application.created_at }}</div>
						</div>
						<div v-if="normalizedStatus !== 'submitted'" class="relative">
							<span class="absolute -left-[34px] top-0 w-6 h-6 rounded-full border-2 text-white grid place-items-center [&_svg]:w-[13px] [&_svg]:h-[13px]" :style="{ background: stage.border, borderColor: stage.border }"><component :is="icons.dot" aria-hidden="true" /></span>
							<b class="text-[15px] font-medium block">{{ stage.title }}</b>
							<div class="text-[15px] text-subtle mt-1">{{ text('Updated', 'Mise à jour') }} {{ application.updated_at }}</div>
						</div>
						<div v-else class="relative">
							<span class="absolute -left-[34px] top-0 w-6 h-6 rounded-full bg-white border-2 border-line text-muted-2 grid place-items-center"></span>
							<b class="text-[15px] font-medium block text-muted-2">{{ text('Awaiting review', 'En attente d’examen') }}</b>
							<div class="text-[15px] text-subtle mt-1">{{ text(`Pending review by ${application.company.name}`, `En attente d’examen par ${application.company.name}`) }}</div>
						</div>
					</div>
				</section>

				<!-- SUBMITTED APPLICATION -->
				<section v-if="!isExternal" class="bg-white border border-transparent rounded-card px-8 max-[560px]:px-5 py-[30px]">
					<h2 class="mb-1.5 text-[20px] leading-[1.35] text-navy">{{ isCandidate ? text('Your submitted application', 'Votre candidature envoyée') : text('Submitted application', 'Candidature envoyée') }}</h2>
					<p class="text-[13.5px] text-muted mb-[18px]">{{ text(`The current application details shared with ${application.company.name}.`, `Les informations actuelles de la candidature partagées avec ${application.company.name}.`) }}</p>

					<div v-if="application.cover_letter" class="py-4">
						<div class="text-xs tracking-[.06em] uppercase text-muted-2 font-medium mb-2">{{ text('Cover note', 'Mot d’accompagnement') }}</div>
						<p class="text-[15px] leading-[1.7] text-ink-700 whitespace-pre-line">{{ application.cover_letter }}</p>
					</div>

					<div v-if="application.message" class="py-4 border-t border-line-2">
						<div class="text-xs tracking-[.06em] uppercase text-muted-2 font-medium mb-2">{{ text('Message to the company', 'Message à l’entreprise') }}</div>
						<p class="text-[15px] leading-[1.7] text-ink-700 whitespace-pre-line">{{ application.message }}</p>
					</div>

					<div v-if="application.attachments.length" class="py-4 border-t border-line-2">
						<div class="text-xs tracking-[.06em] uppercase text-muted-2 font-medium mb-2">{{ text('Attachments', 'Pièces jointes') }}</div>
						<div class="flex flex-wrap gap-3 mt-1">
							<a
								v-for="attachment in application.attachments"
								:key="attachment.url || attachment.name"
								class="flex items-center gap-[11px] bg-paper-2 border border-line rounded-card px-[15px] py-3 no-underline hover:border-blue-300 transition-colors"
								:href="attachment.url"
								target="_blank"
								rel="noopener"
							>
								<span class="w-[38px] h-[38px] rounded-[9px] bg-red-100 text-red-600 grid place-items-center shrink-0 text-[10px] font-extrabold">{{ (attachment.name || '').split('.').pop().slice(0, 3).toUpperCase() || 'DOC' }}</span>
								<span><b class="text-[13.5px] text-ink block font-medium">{{ attachment.name }}</b><span v-if="attachment.resume || attachment.size" class="text-xs text-muted">{{ [attachment.resume && text('Resume', 'CV'), attachment.size && `${Math.round(attachment.size / 1024)} ${text('KB', 'Ko')}`].filter(Boolean).join(' · ') }}</span></span>
								<span class="ml-1.5 text-muted-2 [&_svg]:w-[17px] [&_svg]:h-[17px]"><component :is="icons.download" aria-hidden="true" /></span>
							</a>
						</div>
					</div>
				</section>
				<form v-if="isExternal && isCandidate" class="bg-white border border-transparent rounded-card p-8" @submit.prevent="saveTracking">
					<h2 class="mb-2 text-[20px] leading-[1.35] text-navy">{{ text('Your private application tracker', 'Votre suivi privé de candidature') }}</h2>
					<p class="text-sm text-muted mb-5">{{ text(`Record what happened on the employer's website. Notes and updates here are never sent to the employer.`, 'Notez ce qui s’est passé sur le site de l’employeur. Les notes et mises à jour ici ne sont jamais envoyées à l’employeur.') }}</p>
					<p v-for="(error, key) in trackingForm.errors" :key="key" class="text-sm text-red-600 mb-2" role="alert">{{ error }}</p>
					<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
						<label class="text-sm font-medium">{{ text('Your status', 'Votre statut') }}<select v-model="trackingForm.status" class="fld mt-2"><option value="submitted">{{ text('Applied', 'Postulé') }}</option><option value="review">{{ text('In review', 'En cours d’examen') }}</option><option value="interview">{{ text('Interview', 'Entretien') }}</option><option value="offer">{{ text('Offer', 'Proposition') }}</option><option value="closed">{{ text('Closed', 'Clôturée') }}</option></select></label>
						<label class="text-sm font-medium">{{ text('Date applied', 'Date de candidature') }}<input v-model="trackingForm.applied_at" class="fld mt-2" type="date" required></label>
					</div>
					<label class="block text-sm font-medium mt-5">{{ text('Private notes', 'Notes privées') }}<textarea v-model="trackingForm.candidate_notes" class="fld mt-2" maxlength="10000"></textarea></label>
					<button type="submit" class="cta cta-primary mt-4" :disabled="trackingForm.processing">{{ text('Save your update', 'Enregistrer votre mise à jour') }}</button>
					<button type="button" class="cta cta-danger ml-2 mt-4" @click="withdraw">{{ text('Remove tracking record', 'Supprimer le suivi') }}</button>
				</form>
			</div>

			<!-- RIGHT -->
			<div class="flex flex-col gap-4 sticky top-[94px]">
				<section v-if="canReply" class="bg-white border border-transparent rounded-card px-6 py-[22px]">
					<h3 class="mb-4 text-[13px] tracking-caps text-brand uppercase">{{ text('REVIEW', 'EXAMEN') }}</h3>
					<form class="mb-5 pb-5 border-b border-line-2" @submit.prevent="reply(reviewForm.status)">
						<label class="block text-[14px] font-medium text-navy mb-2" for="review-stage">{{ text('Application stage', 'Étape de la candidature') }}</label>
						<select id="review-stage" v-model="reviewForm.status" class="fld" :disabled="reviewForm.processing" :aria-invalid="!!reviewForm.errors.status" :aria-describedby="reviewForm.errors.status ? 'review-stage-error' : undefined">
							<option value="" disabled>{{ text('Select a stage', 'Choisir une étape') }}</option>
							<option value="viewed">{{ text('Viewed', 'Consultée') }}</option>
							<option value="review">{{ text('In review', 'En cours d’examen') }}</option>
							<option value="interview">{{ text('Interview', 'Entretien') }}</option>
						</select>
						<button class="cta w-full mt-2.5 cta-secondary disabled:opacity-50" type="submit" :disabled="reviewForm.processing || !reviewForm.status || reviewForm.status === normalizedStatus">{{ reviewForm.processing ? text('Updating…', 'Mise à jour…') : text('Update stage', 'Mettre à jour l’étape') }}</button>
					</form>
					<p v-if="reviewForm.errors.status" id="review-stage-error" class="text-sm text-red-600 mb-4" role="alert">{{ reviewForm.errors.status }}</p>
					<button class="cta cta-primary w-full disabled:opacity-50 [&_svg]:w-[17px] [&_svg]:h-[17px]" type="button" :disabled="reviewForm.processing" @click="reply('offer')">
						{{ text('Advance to offer', 'Passer à la proposition') }} <component :is="icons.arrow" aria-hidden="true" />
					</button>
					<button class="cta cta-secondary w-full mt-2.5 disabled:opacity-50" type="button" :disabled="reviewForm.processing" :aria-expanded="confirmingDisqualify" @click="confirmingDisqualify = true">
						{{ text('Disqualify', 'Écarter') }}
					</button>
					<div v-if="confirmingDisqualify" class="mt-2.5 rounded-card border border-red-200 bg-red-50 p-4">
						<p class="text-[13.5px] text-red-900" role="alert">{{ text(`Mark this application as not selected? We'll let the applicant know. This can't be undone.`, 'Marquer cette candidature comme non retenue ? Nous en informerons le candidat. Cette action est irréversible.') }}</p>
						<div class="flex gap-2.5 mt-3">
							<button class="cta flex-1 !px-3 !py-2.5 !text-sm cta-secondary" type="button" @click="confirmingDisqualify = false">{{ text('Cancel', 'Annuler') }}</button>
							<button class="cta flex-1 !px-3 !py-2.5 !text-sm cta-danger disabled:opacity-50" type="button" :disabled="reviewForm.processing" @click="reply('closed')">{{ text('Yes, disqualify', 'Oui, écarter') }}</button>
						</div>
					</div>
				</section>

				<section class="bg-white border border-transparent rounded-card px-6 py-[22px]">
					<h3 class="mb-4 text-[13px] tracking-caps text-brand uppercase">{{ text('APPLICATION DETAILS', 'DÉTAILS DE LA CANDIDATURE') }}</h3>
					<div class="flex items-center justify-between py-[11px] text-sm"><span class="text-muted">{{ text('Application ID', 'N° de candidature') }}</span><span class="font-medium text-ink-700">#{{ application.id }}</span></div>
					<div class="flex items-center justify-between py-[11px] text-sm border-t border-line-2"><span class="text-muted">{{ text('Submitted', 'Envoyée le') }}</span><span class="font-medium text-ink-700">{{ application.created_at }}</span></div>
					<div class="flex items-center justify-between py-[11px] text-sm border-t border-line-2"><span class="text-muted">{{ text('Last update', 'Dernière mise à jour') }}</span><span class="font-medium text-ink-700">{{ application.updated_at }}</span></div>
					<div class="flex items-center justify-between py-[11px] text-sm border-t border-line-2"><span class="text-muted">{{ text('Stage', 'Étape') }}</span><span class="font-medium" :style="{ color: stage.color }">{{ stage.label }}</span></div>
				</section>

				<section class="bg-white border border-transparent rounded-card px-6 py-[22px]">
					<h3 class="mb-4 text-[13px] tracking-caps text-brand uppercase">{{ isCandidate ? text('COMPANY', 'ENTREPRISE') : text('APPLICANT', 'CANDIDAT') }}</h3>
					<div class="flex items-center gap-[13px] mb-4">
						<CompanyLogo class="w-[46px] h-[46px] rounded-card text-base" :name="counterpart.name" :src="counterpart.role === 'Company' ? counterpart.image : null" />
						<div><b class="text-base block">{{ counterpart.name }}</b><span class="text-[13px] text-muted">{{ counterpart.roleLabel }}</span></div>
					</div>
					<div v-if="counterpart.email" class="flex items-center gap-3 py-[9px] text-sm text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.mail" aria-hidden="true" />{{ counterpart.email }}</div>
					<div v-if="counterpart.phone_number" class="flex items-center gap-3 py-[9px] text-sm text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.phone" aria-hidden="true" />{{ counterpart.phone_number }}</div>
					<div v-if="counterpart.city?.name" class="flex items-center gap-3 py-[9px] text-sm text-ink-700 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0"><component :is="icons.pin" aria-hidden="true" />{{ counterpart.city.name }}</div>
					<Link v-if="!isCandidate && !isExternal" class="cta cta-secondary w-full mt-[14px]" :href="route('candidates.show', application.candidate.id)">{{ text('View profile', 'Voir le profil') }}</Link>
					<Link v-if="!isExternal && counterpart.user_id" class="cta cta-primary w-full mt-[14px] [&_svg]:w-[17px] [&_svg]:h-[17px]" :href="route('messages.show', counterpart.user_id)">
						{{ isCandidate ? text('Message recruiter', 'Écrire au recruteur') : text('Message applicant', 'Écrire au candidat') }} <component :is="icons.arrow" aria-hidden="true" />
					</Link>
				</section>

				<template v-if="isCandidate && open && !isExternal">
					<Link class="cta cta-secondary w-full" :href="route('applications.edit', application.id)">{{ text('Edit application', 'Modifier la candidature') }}</Link>
					<button class="cta cta-danger w-full" type="button" @click="withdraw">{{ text('Withdraw application', 'Retirer la candidature') }}</button>
				</template>
			</div>
		</div>
	</div>
</template>
