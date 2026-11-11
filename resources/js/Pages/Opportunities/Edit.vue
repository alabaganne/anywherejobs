<script setup>
import { ArrowRight, ChevronRight } from '@lucide/vue';
import { computed, h, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { appPath } from '@/utils/url';
import { useText } from '@/composables/useText';
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({ layout: (_h, page) => h(AppShell, { active: 'opportunities' }, () => page) });
const props = defineProps({ fields: Array, cities: { type: Array, default: () => [] }, skills: { type: Array, default: () => [] }, opportunity: { type: Object, default: null }, aiReview: { type: Boolean, default: false } });
const { text } = useText();
const MAX_SKILLS = 12;
const editing = computed(() => !!props.opportunity);
const role = computed(() => props.opportunity || {});
const formData = (role) => ({
	title: role.title || '', description: role.description || '', kind: role.kind || 'internship',
	field_id: role.field_id || null, city_id: role.city_id || null, country_code: role.country_code || '', location: role.location || '',
	closing_at: role.closing_at ? String(role.closing_at).slice(0, 10) : '', work_type: role.work_type || 'onsite',
	duration_weeks: role.duration_weeks || null, term: role.term || '', program_type: role.program_type || '',
	start_date: role.start_date ? String(role.start_date).slice(0, 10) : '', employment_types: role.employment_types || [],
	seniority: role.seniority || '', language: role.language || '',
	pay_min: role.pay_min == null ? null : Math.round(Number(role.pay_min)), pay_max: role.pay_max == null ? null : Math.round(Number(role.pay_max)), pay_currency: role.pay_currency || '', pay_unit: role.pay_unit || '',
	compensation_status: role.compensation_status || 'unspecified', remote_scope: role.remote_scope || '',
	applicant_scope: role.applicant_scope || 'unknown', relocation: role.relocation || 'unknown', visa_sponsorship: role.visa_sponsorship || 'unknown',
	eligible_countries: role.eligible_countries || [], excluded_countries: role.excluded_countries || [],
	status: role.status || 'open', publication_status: role.publication_status || 'published', attachment_files: [],
	remove_attachments: false, skills: (role.skills || []).map((skill) => skill.id),
});
const form = useForm(formData(role.value));
const eligibleCountries = ref(form.eligible_countries.join(', '));
const excludedCountries = ref(form.excluded_countries.join(', '));
watch(() => role.value.id, () => {
	form.defaults(formData(role.value));
	form.reset();
	eligibleCountries.value = form.eligible_countries.join(', ');
	excludedCountries.value = form.excluded_countries.join(', ');
});
const countries = (value) => [...new Set(value.split(',').map((part) => part.trim().toUpperCase()).filter(Boolean))];
const skillSearch = ref('');
// Picked skills first, then the most used ones; a search looks through all of them.
const shownSkills = computed(() => {
	const term = skillSearch.value.trim().toLowerCase();
	const picked = props.skills.filter((skill) => form.skills.includes(skill.id));
	const rest = props.skills.filter((skill) => !form.skills.includes(skill.id) && (!term || skill.name.toLowerCase().includes(term)));
	return [...picked, ...(term ? rest : rest.slice(0, 30))];
});
// File uploads drop empty arrays, so an empty string clears every skill.
const payload = (data) => ({ ...data, skills: data.skills.length ? data.skills : '' });
const submit = () => {
	form.eligible_countries = countries(eligibleCountries.value);
	form.excluded_countries = countries(excludedCountries.value);
	if (form.kind === 'job') { form.duration_weeks = null; form.term = ''; form.program_type = ''; }
	const options = { onSuccess: () => form.reset('remove_attachments') };
	if (editing.value) form.transform((data) => ({ ...payload(data), _method: 'put' })).post(route('opportunities.update', role.value.id), options);
	else form.transform(payload).post(route('opportunities.store'), options);
};
const employmentTypes = computed(() => ({
	FULL_TIME: text('Full-time', 'Temps plein'), PART_TIME: text('Part-time', 'Temps partiel'), CONTRACTOR: text('Contract / freelance', 'Contrat / freelance'),
	TEMPORARY: text('Temporary', 'Temporaire'), INTERN: text('Internship', 'Stage'), VOLUNTEER: text('Volunteer', 'Bénévolat'), OTHER: text('Other', 'Autre'),
}));
const levelLabels = computed(() => ({
	entry: text('entry', 'débutant'), graduate: text('graduate', 'jeune diplômé'), mid: text('mid', 'confirmé'), senior: text('senior', 'senior'), lead: text('lead', 'responsable technique'), executive: text('executive', 'direction'),
}));
const unitLabels = computed(() => ({ hour: text('hour', 'heure'), day: text('day', 'jour'), week: text('week', 'semaine'), month: text('month', 'mois'), year: text('year', 'an') }));
</script>

<template>
	<div class="px-8 max-[680px]:px-5 pt-8 pb-14 w-full max-w-[1080px] mx-auto">
		<div class="flex items-center gap-2 text-sm text-muted mb-6"><Link :href="route('app.opportunities.index')">{{ text('Roles', 'Offres') }}</Link><ChevronRight :size="14" /><span>{{ editing ? text('Edit role', 'Modifier l’offre') : text('Post a role', 'Publier une offre') }}</span></div>
		<div class="mb-6"><span class="block text-xs font-medium tracking-widest uppercase text-blue-600 mb-2">{{ text('Hire students and professionals', 'Recrutez des étudiants et des professionnels') }}</span><h1 class="text-[length:clamp(28px,2.6vw,36px)] font-medium leading-[1.15] text-navy">{{ editing ? text('Edit role', 'Modifier l’offre') : text('Post a role', 'Publier une offre') }}</h1><p class="text-muted mt-2">{{ text('Share clear responsibilities, requirements, compensation, and where candidates can work.', 'Décrivez clairement les missions, les exigences, la rémunération et les lieux de travail possibles.') }}</p></div>
		<form class="bg-white border border-transparent rounded-card" @submit.prevent="submit">
			<div class="px-8 max-[560px]:px-5 py-7 space-y-5">
				<div v-if="Object.keys(form.errors).length" class="rounded-card bg-red-50 text-red-700 p-4" role="alert"><p v-for="(error, key) in form.errors" :key="key" class="text-sm">{{ error }}</p></div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-medium">{{ text('Role type', 'Type d’offre') }}<select v-model="form.kind" class="fld mt-2"><option value="internship">{{ text('Internship', 'Stage') }}</option><option value="job">{{ text('Job', 'Emploi') }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Title', 'Titre') }}<input v-model="form.title" class="fld mt-2" required maxlength="255" :placeholder="text('e.g. Software engineer', 'ex. Ingénieur logiciel')"></label>
					<label class="text-sm font-medium">{{ text('Field', 'Domaine') }}<select v-model="form.field_id" class="fld mt-2"><option :value="null">{{ text('Not specified', 'Non précisé') }}</option><option v-for="field in fields" :key="field.id" :value="field.id">{{ field.name }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Experience level', 'Niveau d’expérience') }}<select v-model="form.seniority" class="fld mt-2"><option value="">{{ text('Not specified', 'Non précisé') }}</option><option v-for="level in ['entry', 'graduate', 'mid', 'senior', 'lead', 'executive']" :key="level" :value="level">{{ levelLabels[level] }}</option></select></label>
				</div>
				<fieldset><legend class="text-sm font-medium mb-2">{{ text('Employment type', 'Type de contrat') }}</legend><div class="flex flex-wrap gap-x-6 gap-y-3"><label v-for="(label, value) in employmentTypes" :key="value" class="text-sm flex gap-2 items-center"><input v-model="form.employment_types" type="checkbox" :value="value"> {{ label }}</label></div></fieldset>
				<fieldset v-if="skills.length">
					<legend class="text-sm font-medium">{{ text('Skills', 'Compétences') }}</legend>
					<p class="text-xs text-muted mt-1 mb-3">{{ text(`Pick up to ${MAX_SKILLS} skills candidates should have. ${form.skills.length} of ${MAX_SKILLS} picked.`, `Choisissez jusqu’à ${MAX_SKILLS} compétences que les candidats doivent avoir. ${form.skills.length} sur ${MAX_SKILLS} choisies.`) }}<template v-if="aiReview"> {{ text('After you save, AI reads the description and adds matching skills and any details you left empty, like experience or who can apply. You can change them anytime.', 'Après l’enregistrement, l’IA lit la description et ajoute les compétences correspondantes ainsi que les informations laissées vides, comme l’expérience ou qui peut postuler. Vous pouvez les modifier à tout moment.') }}</template></p>
					<input v-model="skillSearch" class="fld mb-3 max-w-sm" type="search" :placeholder="text(`Search ${skills.length} skills`, `Rechercher parmi ${skills.length} compétences`)" :aria-label="text('Search skills', 'Rechercher des compétences')">
					<div class="flex flex-wrap gap-2 max-h-[232px] overflow-y-auto">
						<label
							v-for="skill in shownSkills"
							:key="skill.id"
							class="inline-flex items-center text-[13px] font-medium px-3 py-[7px] rounded-[9px] border cursor-pointer select-none transition-colors has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-blue-500 has-[:disabled]:opacity-50 has-[:disabled]:cursor-not-allowed"
							:class="form.skills.includes(skill.id) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-line text-ink-700 hover:border-blue-300'"
						><input v-model="form.skills" type="checkbox" class="sr-only" :value="skill.id" :disabled="!form.skills.includes(skill.id) && form.skills.length >= MAX_SKILLS">{{ skill.name }}</label>
						<p v-if="!shownSkills.length" class="text-sm text-muted">{{ text('No skills match.', 'Aucune compétence ne correspond.') }}</p>
					</div>
				</fieldset>
				<h2 class="pt-3 text-[20px] leading-[1.35] text-navy">{{ text('Location and work arrangement', 'Lieu et organisation du travail') }}</h2>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-medium">{{ text('Work arrangement', 'Mode de travail') }}<select v-model="form.work_type" class="fld mt-2"><option value="onsite">{{ text('On-site', 'Sur site') }}</option><option value="hybrid">{{ text('Hybrid', 'Hybride') }}</option><option value="remote">{{ text('Fully remote', '100 % à distance') }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Country code', 'Code pays') }}<input v-model="form.country_code" class="fld mt-2" maxlength="2" placeholder="FR, US, TN..."></label>
					<label class="text-sm font-medium">{{ text('Location', 'Lieu') }}<input v-model="form.location" class="fld mt-2" maxlength="255" :placeholder="text('City, region, or office location', 'Ville, région ou lieu du bureau')"></label>
					<label class="text-sm font-medium">{{ text('City, optional', 'Ville (facultatif)') }}<select v-model="form.city_id" class="fld mt-2"><option :value="null">{{ text('Not specified', 'Non précisé') }}</option><option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Eligible applicant countries', 'Pays des candidats admis') }}<input v-model="eligibleCountries" class="fld mt-2" placeholder="FR, DE, US"><span class="text-xs font-normal text-muted">{{ text('Country codes separated by commas. Leave empty if unspecified.', 'Codes pays séparés par des virgules. Laissez vide si non précisé.') }}</span></label>
					<label class="text-sm font-medium">{{ text('Excluded applicant countries', 'Pays des candidats exclus') }}<input v-model="excludedCountries" class="fld mt-2" :placeholder="text('Country codes, if any', 'Codes pays, le cas échéant')"></label>
					<label v-if="form.work_type === 'remote'" class="text-sm font-medium">{{ text('Remote scope', 'Périmètre du télétravail') }}<input v-model="form.remote_scope" class="fld mt-2" maxlength="255" :placeholder="text('e.g. Worldwide, or only specific countries', 'ex. Monde entier, ou certains pays seulement')"></label>
					<label class="text-sm font-medium">{{ text('Who can apply?', 'Qui peut postuler ?') }}<select v-model="form.applicant_scope" class="fld mt-2"><option value="unknown">{{ text('Not specified', 'Non précisé') }}</option><option value="anywhere">{{ text('Anyone, from any country', 'Tout le monde, de tous les pays') }}</option><option value="listed">{{ text('Only people in the eligible countries', 'Seulement les personnes des pays admis') }}</option><option value="local">{{ text('Only people already allowed to work there', 'Seulement les personnes déjà autorisées à y travailler') }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Relocation assistance', 'Aide au déménagement') }}<select v-model="form.relocation" class="fld mt-2"><option value="unknown">{{ text('Not specified', 'Non précisé') }}</option><option value="yes">{{ text('Provided', 'Proposée') }}</option><option value="no">{{ text('Not provided', 'Non proposée') }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Visa sponsorship', 'Parrainage de visa') }}<select v-model="form.visa_sponsorship" class="fld mt-2"><option value="unknown">{{ text('Not specified', 'Non précisé') }}</option><option value="yes">{{ text('Provided', 'Proposé') }}</option><option value="no">{{ text('Not provided', 'Non proposé') }}</option></select></label>
				</div>
				<p class="text-sm text-muted">{{ text('Only confirm who can apply, relocation, or sponsorship that your team can provide.', 'Ne confirmez que ce que votre équipe peut offrir : qui peut postuler, déménagement ou parrainage.') }}</p>
				<h2 class="pt-3 text-[20px] leading-[1.35] text-navy">{{ text('Timing', 'Calendrier') }}</h2>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-medium">{{ text('Application deadline, optional', 'Date limite de candidature (facultatif)') }}<input v-model="form.closing_at" class="fld mt-2" type="date"></label>
					<label class="text-sm font-medium">{{ text('Start date, optional', 'Date de début (facultatif)') }}<input v-model="form.start_date" class="fld mt-2" type="date"></label>
					<template v-if="form.kind === 'internship'">
						<label class="text-sm font-medium">{{ text('Programme', 'Programme') }}<select v-model="form.program_type" class="fld mt-2"><option value="">{{ text('Not specified', 'Non précisé') }}</option><option value="pfe">PFE</option><option value="summer">{{ text('Summer internship', 'Stage d’été') }}</option><option value="apprenticeship">{{ text('Apprenticeship', 'Alternance') }}</option><option value="graduate">{{ text('Graduate programme', 'Programme jeunes diplômés') }}</option><option value="other">{{ text('Other internship', 'Autre stage') }}</option></select></label>
						<label class="text-sm font-medium">{{ text('Duration in weeks', 'Durée en semaines') }}<input v-model.number="form.duration_weeks" class="fld mt-2" type="number" min="1" max="104"></label>
						<label class="text-sm font-medium">{{ text('Term', 'Période') }}<input v-model="form.term" class="fld mt-2" maxlength="255" :placeholder="text('e.g. Summer 2027', 'ex. Été 2027')"></label>
					</template>
				</div>
				<h2 class="pt-3 text-[20px] leading-[1.35] text-navy">{{ text('Compensation', 'Rémunération') }}</h2>
				<p v-if="role.pay_amount != null && role.pay_min == null" class="text-sm text-muted">{{ text(`Legacy amount: ${Math.round(Number(role.pay_amount))} per ${role.pay_unit || 'unspecified period'}. Currency was not recorded. Enter a confirmed range and currency to update it.`, `Ancien montant : ${Math.round(Number(role.pay_amount))} par ${unitLabels[role.pay_unit] || role.pay_unit || 'période non précisée'}. La devise n’a pas été enregistrée. Saisissez une fourchette et une devise confirmées pour le mettre à jour.`) }}</p>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-medium">{{ text('Compensation status', 'Statut de la rémunération') }}<select v-model="form.compensation_status" class="fld mt-2"><option value="unspecified">{{ text('Not specified', 'Non précisé') }}</option><option value="paid">{{ text('Paid', 'Rémunéré') }}</option><option value="unpaid">{{ text('Unpaid', 'Non rémunéré') }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Currency code', 'Code devise') }}<input v-model="form.pay_currency" class="fld mt-2" maxlength="3" placeholder="TND, EUR, USD"></label>
					<label class="text-sm font-medium">{{ text('Minimum', 'Minimum') }}<input v-model.number="form.pay_min" class="fld mt-2" type="number" min="0" step="1"></label>
					<label class="text-sm font-medium">{{ text('Maximum', 'Maximum') }}<input v-model.number="form.pay_max" class="fld mt-2" type="number" min="0" step="1"></label>
					<label class="text-sm font-medium">{{ text('Pay period', 'Période de paie') }}<select v-model="form.pay_unit" class="fld mt-2"><option value="">{{ text('Not specified', 'Non précisé') }}</option><option v-for="unit in ['hour','day','week','month','year']" :key="unit" :value="unit">{{ unitLabels[unit] }}</option></select></label>
				</div>
				<label class="block text-sm font-medium">Description<textarea v-model="form.description" class="fld mt-2 !min-h-[220px]" rows="12" required :placeholder="text('Responsibilities, requirements, benefits, and application instructions', 'Missions, exigences, avantages et marche à suivre pour postuler')"></textarea></label>
				<label class="block text-sm font-medium">{{ text('Description language code', 'Code de langue de la description') }}<input v-model="form.language" class="fld mt-2 max-w-xs" maxlength="10" placeholder="en, fr, ar..."></label>
				<label class="block text-sm font-medium">{{ text('Attachments', 'Pièces jointes') }}<input class="fld mt-2" type="file" multiple accept=".pdf,.doc,.docx,.txt" @change="form.attachment_files = Array.from($event.target.files || [])"><span class="text-xs font-normal text-muted">{{ text('Public role documents. Up to 10 files, 10 MB each.', 'Documents publics de l’offre. 10 fichiers au plus, 10 Mo chacun.') }}</span></label>
				<div v-if="role.attachments?.length" class="rounded-card border border-line-2 bg-paper-2 p-4">
					<p class="text-xs font-medium text-muted mb-2">{{ text('Current files', 'Fichiers actuels') }}</p>
					<ul class="text-sm space-y-1"><li v-for="file in role.attachments" :key="file.url"><a class="text-blue-700 font-medium" :href="file.url" target="_blank" rel="noopener">{{ file.name }}</a></li></ul>
					<label class="mt-3 text-sm flex gap-2 items-center"><input v-model="form.remove_attachments" type="checkbox"> {{ text('Remove current files', 'Supprimer les fichiers actuels') }}</label>
					<p class="text-xs text-muted mt-1">{{ text('New files replace the current ones.', 'Les nouveaux fichiers remplacent les actuels.') }}</p>
				</div>
				<div class="grid grid-cols-2 gap-4 max-[560px]:grid-cols-1">
					<label class="text-sm font-medium">{{ text('Publication', 'Publication') }}<select v-model="form.publication_status" class="fld mt-2"><option value="published">{{ text('Published', 'Publiée') }}</option><option value="draft">{{ text('Draft', 'Brouillon') }}</option></select></label>
					<label class="text-sm font-medium">{{ text('Availability', 'Disponibilité') }}<select v-model="form.status" class="fld mt-2"><option value="open">{{ text('Open', 'Ouverte') }}</option><option value="closed">{{ text('Closed', 'Fermée') }}</option></select></label>
				</div>
			</div>
			<div class="px-8 max-[560px]:px-5 py-4 border-t border-line-2 flex justify-end gap-3"><Link class="cta cta-secondary" :href="editing && role.publication_status === 'published' ? appPath(role.public_url) || route('app.opportunities.show', role.id) : route('dashboard')">{{ text('Cancel', 'Annuler') }}</Link><button class="cta cta-primary" type="submit" :disabled="form.processing">{{ editing ? text('Save changes', 'Enregistrer les modifications') : form.publication_status === 'draft' ? text('Save draft', 'Enregistrer le brouillon') : text('Publish role', 'Publier l’offre') }} <ArrowRight :size="17" /></button></div>
		</form>
	</div>
</template>
