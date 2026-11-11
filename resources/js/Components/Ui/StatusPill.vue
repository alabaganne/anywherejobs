<script setup>
import { computed } from 'vue';
import { statusPill } from '@/utils/status';
import { useText } from '@/composables/useText';

const { fr } = useText();

const props = defineProps({
	status: { type: String, default: null },
	// "import" uses the admin Imports colours and labels.
	set: { type: String, default: 'application' },
});

const pill = computed(() => statusPill(props.status, props.set, fr.value ? 'fr' : 'en'));
</script>

<template>
	<span
		class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-[5px] rounded-md whitespace-nowrap"
		:style="{ color: pill.text, background: pill.bg }"
	>
		<span class="w-1.5 h-1.5 rounded-full" :style="{ background: pill.dot }"></span>
		{{ pill.label }}
	</span>
</template>
