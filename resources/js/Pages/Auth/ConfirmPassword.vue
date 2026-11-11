<template>
	<auth-heading :kicker="text('One quick check', 'Une petite vérification')" :description="text('Confirm your password to continue to this secure area of your account.', 'Confirmez votre mot de passe pour accéder à cette partie sécurisée de votre compte.')"><template v-if="fr">C’est bien<br />vous ?</template><template v-else>Make sure<br />it’s you.</template></auth-heading>
	<auth-feedback :errors="form.errors" />
	<form @submit.prevent="submit" :aria-busy="form.processing">
		<auth-field id="password" :label="text('Password', 'Mot de passe')" type="password" v-model="form.password" :error="form.errors.password" required autocomplete="current-password" autofocus />
		<breeze-button class="mt-7" :disabled="form.processing">{{ form.processing ? text('Confirming…', 'Confirmation…') : text('Confirm password', 'Confirmer le mot de passe') }}<icon name="arrow-right" /></breeze-button>
	</form>
	<p :class="classes.switchRow"><Link :href="route('dashboard')" :class="classes.link">{{ text('Back to your dashboard', 'Retour au tableau de bord') }}</Link></p>
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

	data() {
		return {
			classes: { ...classes },
			form: useForm({
				password: '',
			})
		}
	},

	methods: {
		submit() {
			this.form.post(this.route('password.confirm'), {
				onFinish: () => this.form.reset(),
			})
		}
	}
}
</script>
