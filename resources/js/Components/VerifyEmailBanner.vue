<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { MailCheck } from '@lucide/vue';
import { useText } from '@/composables/useText';

const { text } = useText();

const page = usePage();
const user = computed(() => page.props.auth?.user);
const show = computed(() => user.value && user.value.email_verified === false);
const sending = ref(false);
const sent = ref(false);

const resend = () => {
	if (sending.value) return;
	sending.value = true;
	router.post(route('verification.send'), {}, {
		preserveScroll: true,
		onSuccess: () => { sent.value = true; },
		onFinish: () => { sending.value = false; },
	});
};
</script>

<template>
	<div v-if="show" class="flex flex-wrap items-center gap-x-4 gap-y-2 bg-blue-600 text-white px-6 py-3 text-[14px]" role="status">
		<MailCheck :size="18" aria-hidden="true" class="shrink-0" />
		<p class="flex-1 min-w-[220px]">
			<b class="font-medium">{{ text('Confirm your email.', 'Confirmez votre e-mail.') }}</b>
			{{ text(`We sent a link to ${user.email}. You need it to apply, message employers and get alerts.`, `Nous avons envoyé un lien à ${user.email}. Il est nécessaire pour postuler, écrire aux employeurs et recevoir des alertes.`) }}
		</p>
		<span v-if="sent" class="font-medium">{{ text('New link sent.', 'Nouveau lien envoyé.') }}</span>
		<button v-else type="button" class="font-medium underline underline-offset-4 decoration-white/60 hover:decoration-white focus-visible:outline-2 focus-visible:outline-white focus-visible:outline-offset-2 rounded" :disabled="sending" @click="resend">
			{{ sending ? text('Sending…', 'Envoi…') : text('Send a new link', 'Envoyer un nouveau lien') }}
		</button>
	</div>
</template>
