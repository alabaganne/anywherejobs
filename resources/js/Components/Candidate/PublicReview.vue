<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useText } from '@/composables/useText';

const props = defineProps({
	candidate: { type: Object, required: true },
	compact: { type: Boolean, default: false },
});

const { text } = useText();
const pills = computed(() => ({
	pending: { label: text('Waiting for review', 'En attente de validation'), text: '#b45309', bg: '#fef3c7' },
	approved: { label: text('Public', 'Public'), text: '#047857', bg: '#dcfce7' },
	declined: { label: text('Declined', 'Refusé'), text: '#5b6677', bg: '#eef2f9' },
}));
const pill = computed(() => pills.value[props.candidate.public_status] || null);
const busy = ref(false);

const review = (decision) =>
	router.post(route('candidates.public.review', props.candidate.id), { decision }, {
		preserveScroll: true,
		onStart: () => (busy.value = true),
		onFinish: () => (busy.value = false),
	});
</script>

<template>
	<div v-if="pill" class="flex flex-wrap items-center gap-2.5">
		<span class="text-xs font-medium px-[11px] py-[5px] rounded-full whitespace-nowrap" :style="{ color: pill.text, background: pill.bg }">{{ pill.label }}</span>
		<a class="text-[13px] font-medium text-blue-700 hover:underline" :href="candidate.public_url" target="_blank" rel="noopener">{{ candidate.public_status === 'approved' ? text('Open page', 'Ouvrir la page') : text('Preview', 'Aperçu') }}</a>
		<div class="flex gap-2" :class="compact ? 'w-full' : 'ml-auto'">
			<button v-if="candidate.public_status !== 'approved'" type="button" class="cta cta-primary !px-3.5 !py-2 !text-[13.5px]" :class="{ 'flex-1': compact }" :disabled="busy" @click="review('approve')">{{ text('Approve', 'Approuver') }}</button>
			<button v-if="candidate.public_status !== 'declined'" type="button" class="cta cta-secondary !px-3.5 !py-2 !text-[13.5px]" :class="{ 'flex-1': compact }" :disabled="busy" @click="review('decline')">{{ candidate.public_status === 'approved' ? text('Remove from site', 'Retirer du site') : text('Decline', 'Refuser') }}</button>
		</div>
	</div>
</template>
