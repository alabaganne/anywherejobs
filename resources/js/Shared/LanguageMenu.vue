<script setup>
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import Flag from '@/Shared/Flag.vue';
import { saveLanguage } from '@/composables/useText';

// Shows the current language and links to this page in the other one. The button takes the text colour around it.
// With `account` the choice is saved on the account instead (signed-in pages); `filled` is the ice button of the workspace top bar.
const props = defineProps({ account: { type: Boolean, default: false }, filled: { type: Boolean, default: false } });
const translatedPaths = ['/', '/about', '/contact', '/privacy', '/terms', '/blog', '/opportunities', '/opportunities/internships', '/opportunities/jobs', '/companies', '/candidates', '/login', '/register', '/forgot-password'];
const page = usePage();
const id = useId();
const current = computed(() => (page.props.locale === 'fr' ? 'fr' : 'en'));
const fr = computed(() => current.value === 'fr');
const options = computed(() => {
	if (props.account) return [{ code: 'en', label: 'English' }, { code: 'fr', label: 'Français' }];
	const here = page.url.split(/[?#]/)[0];
	let en;
	let french;
	if (page.props.translation) {
		[en, french] = fr.value ? [page.props.translation, here] : [here, page.props.translation];
	} else {
		const path = here.replace(/^\/fr(?=\/|$)/, '') || '/';
		if (!translatedPaths.includes(path)) return [];
		const suffix = page.url.slice(here.length);
		en = path + suffix;
		french = (path === '/' ? '/fr' : `/fr${path}`) + suffix;
	}
	return [
		{ code: 'en', label: 'English', href: en },
		{ code: 'fr', label: 'Français', href: french },
	];
});

const menu = ref(null);
const clickOutside = (event) => {
	if (menu.value && !menu.value.contains(event.target)) menu.value.open = false;
};
const focusOut = (event) => {
	if (event.relatedTarget && !menu.value.contains(event.relatedTarget)) menu.value.open = false;
};
const dismiss = () => {
	if (!menu.value?.open) return;
	menu.value.open = false;
	menu.value.querySelector('summary')?.focus();
};
const choose = (code) => {
	menu.value.open = false;
	if (code !== current.value) saveLanguage(code);
};
const outlined = 'h-10 border border-current/30 pr-[11px] pl-1.5 text-[13px] font-semibold tracking-[.06em] transition-[border-color,background-color] duration-180 group-open:border-current group-open:bg-current/10 hover:border-current hover:bg-current/10 focus-visible:outline-current';
const ice = 'h-11 bg-pale pr-3 pl-2.5 text-sm font-medium text-navy transition-colors group-open:bg-blue-100 hover:bg-blue-100';
onMounted(() => document.addEventListener('click', clickOutside));
onBeforeUnmount(() => document.removeEventListener('click', clickOutside));
</script>

<template>
	<details v-if="options.length" ref="menu" class="group relative" @keydown.esc.stop.prevent="dismiss" @focusout="focusOut">
		<summary :aria-label="fr ? 'Langue : Français' : 'Language: English'" :aria-controls="id" class="focus-ring flex cursor-pointer list-none items-center gap-2 rounded-card [&::-webkit-details-marker]:hidden" :class="filled ? ice : outlined">
			<Flag :code="current" class="size-6 outline-current/25" />
			<span>{{ fr ? 'FR' : 'EN' }}</span>
			<ChevronDown class="h-3.5 w-3.5 opacity-70 transition-transform group-open:rotate-180" aria-hidden="true" />
		</summary>
		<nav :id="id" :aria-label="fr ? 'Choisir la langue' : 'Choose language'" class="absolute top-[calc(100%+12px)] right-0 z-30 w-[196px] origin-top-right animate-menu-pop rounded-card border border-line bg-white p-1.5 text-navy before:absolute before:-top-[7px] before:right-[22px] before:size-3 before:rotate-45 before:border-t before:border-l before:border-line before:bg-white motion-reduce:animate-none">
			<component :is="account ? 'button' : 'a'" v-for="language in options" :key="language.code" :type="account ? 'button' : undefined" :href="language.href" :lang="language.code" :hreflang="account ? undefined : language.code" :aria-current="language.code === current ? 'true' : undefined" :class="['group/option focus-ring relative flex min-h-[46px] w-full cursor-pointer items-center gap-3 rounded-card px-2.5 py-2 text-left text-[14px] font-semibold transition-colors duration-150', language.code === current ? 'bg-blue-50 text-brand-dark' : 'text-gray-700 hover:bg-pale hover:text-navy']" @click="account && choose(language.code)">
				<Flag :code="language.code" class="size-[26px] outline-navy/12 transition-transform duration-200 ease-[cubic-bezier(.2,.7,.2,1)] group-hover/option:scale-108 group-hover/option:-rotate-8 motion-reduce:transition-none" />
				<span class="flex-1">{{ language.label }}</span>
				<span v-if="language.code === current" class="grid size-5 place-items-center rounded-full bg-brand text-white"><Icon name="check" class="h-3 w-3" /></span>
			</component>
		</nav>
	</details>
</template>
