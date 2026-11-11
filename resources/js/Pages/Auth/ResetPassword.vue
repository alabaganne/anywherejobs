<template>
	<auth-heading :kicker="text('A fresh start', 'Un nouveau départ')" :description="text(`Choose a new password for your ${appName} account.`, `Choisissez un nouveau mot de passe pour votre compte ${appName}.`)"><template v-if="fr">Nouveau mot de passe.<br />Nouveau départ.</template><template v-else>A new password.<br />A fresh start.</template></auth-heading>
	<auth-feedback :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<div :class="classes.fields">
			<auth-field id="email" :label="text('Email address', 'Adresse e-mail')" type="email" v-model="form.email" :error="form.errors.email" required autofocus autocomplete="username" />
			<auth-field id="password" :label="text('New password', 'Nouveau mot de passe')" type="password" v-model="form.password" :error="form.errors.password" :hint="text('Use at least 8 characters.', 'Au moins 8 caractères.')" minlength="8" required autocomplete="new-password" />
			<auth-field id="password_confirmation" :label="text('Confirm new password', 'Confirmez le nouveau mot de passe')" type="password" v-model="form.password_confirmation" :error="form.errors.password_confirmation" required autocomplete="new-password" />
		</div>
		<breeze-button class="mt-7" :disabled="form.processing">{{ form.processing ? text('Updating password…', 'Mise à jour…') : text('Reset password', 'Changer le mot de passe') }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p :class="classes.switchRow"><Link :href="route('login')" :class="classes.link">{{ text('Back to log in', 'Retour à la connexion') }}</Link></p>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AuthField from '@/Components/Auth/Field.vue';
import AuthFeedback from '@/Components/Auth/Feedback.vue';
import AuthHeading from '@/Components/Auth/Heading.vue';
import * as classes from '@/Components/Auth/classes';
import BreezeButton from '@/Components/Breeze/Button.vue';
import BreezeGuestLayout from '@/Layouts/Guest.vue';
import { useText } from '@/composables/useText';
export default {
	layout: BreezeGuestLayout,
	setup: () => useText(),

	components: { AuthHeading, AuthField, AuthFeedback, BreezeButton },

	props: {
		email: String,
		token: String,
	},

	data() {
		return {
			classes: { ...classes },
			form: useForm({
				token: this.token,
				email: this.email,
				password: '',
				password_confirmation: '',
			})
		}
	},

	methods: {
		submit() {
			this.form.post(this.route('password.update'), {
				onFinish: () => this.form.reset('password', 'password_confirmation'),
			})
		}
	}
}
</script>
