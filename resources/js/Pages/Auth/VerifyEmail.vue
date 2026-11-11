<template>
	<div class="mb-6 flex size-12 items-center justify-center rounded-card bg-blue-50 text-brand"><icon name="mail" class="size-[23px]" /></div>
	<auth-heading :kicker="text('Almost there', 'Presque fini')" :description="text('Follow the link in your verification email to confirm your address and get started.', 'Ouvrez le lien reçu par e-mail pour confirmer votre adresse et commencer.')">{{ text('Check your inbox.', 'Consultez votre boîte mail.') }}</auth-heading>
	<auth-feedback :status="verificationLinkSent ? text('A new verification link is on its way. Check your inbox and spam folder.', 'Un nouveau lien de vérification arrive. Regardez votre boîte de réception et vos spams.') : ''" :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<p :class="classes.hint">{{ text('Can’t find the email? Check your spam folder, or send a new link below.', 'Vous ne trouvez pas l’e-mail ? Regardez dans vos spams, ou envoyez un nouveau lien ci-dessous.') }}</p>
		<breeze-button class="mt-7" :disabled="form.processing">{{ form.processing ? text('Sending email…', 'Envoi de l’e-mail…') : text('Resend verification email', 'Renvoyer l’e-mail de vérification') }}<icon name="arrow-right" /></breeze-button>
	</form>
	<div :class="classes.switchRow"><Link :href="route('logout')" method="post" as="button" :class="classes.link">{{ text('Log out', 'Se déconnecter') }}</Link></div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import AuthHeading from '@/Components/Auth/Heading.vue';
import * as classes from '@/Components/Auth/classes';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
import { useText } from '@/composables/useText';
export default {
	layout: BreezeGuestLayout,
	setup: () => useText(),

	components: {
		BreezeButton, AuthFeedback, AuthHeading,
	},

	props: {
		status: String
	},

	data() {
		return {
			classes: { ...classes },
			form: useForm()
		}
	},

	methods: {
		submit() {
			this.form.post(this.route('verification.send'))
		},
	},

	computed: {
		verificationLinkSent() {
			return this.status === 'verification-link-sent';
		}
	}
}
</script>
