<template>
	<span class="inline-flex shrink-0 flex-col" :class="stacked ? 'items-center gap-2 text-center' : 'items-start gap-1'">
		<img
			v-if="wordmark"
			:src="dark ? $page.props.brand?.wordmark_white : $page.props.brand?.wordmark"
			:alt="label || $page.props.brand?.name"
			:width="Math.round(height * WORDMARK_RATIO)"
			:height="height"
			:style="{ width: 'auto', height: `${height}px` }"
			class="block shrink-0"
		/>
		<img
			v-else
			:src="$page.props.brand?.logo"
			:alt="label"
			:width="height"
			:height="height"
			:style="{ width: `${height}px`, height: `${height}px` }"
			class="block shrink-0 object-contain"
		/>
		<span v-if="tagline" class="text-[11px] font-semibold uppercase tracking-[0.14em]" :class="dark ? 'text-blue-200' : 'text-gray-500'">{{ tagline }}</span>
	</span>
</template>

<script>
const SIZES = { xs: 36, sm: 40, md: 48, lg: 56 };
// Width over height of public/brand/wordmark.svg.
const WORDMARK_RATIO = 574 / 120;

export default {
	props: {
		// The files are set in config/site.php (brand). Leave the label empty when the name is shown as text next to the icon.
		label: {
			type: String,
			default: '',
		},
		tagline: {
			type: String,
			default: '',
		},
		size: {
			type: String,
			default: 'md',
		},
		// Shows the name in script instead of the square icon. Its alt text is the site name.
		wordmark: {
			type: Boolean,
			default: false,
		},
		// On a navy or cobalt background: the white wordmark and a light tagline.
		dark: {
			type: Boolean,
			default: false,
		},
		stacked: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return { WORDMARK_RATIO };
	},
	computed: {
		height() {
			return SIZES[this.size] || SIZES.md;
		},
	},
};
</script>
