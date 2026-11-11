<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown, CircleUserRound, Languages, LogOut, Menu, Plus, Search, Settings, X } from '@lucide/vue';
import CreditsMenu from '@/Components/CreditsMenu.vue';
import LanguageMenu from '@/Shared/LanguageMenu.vue';
import { saveLanguage, useText } from '@/composables/useText';

defineProps({ navigationOpen: { type: Boolean, default: false } });
defineEmits(['notifications', 'navigation']);

const page = usePage();
const { fr, text } = useText();
const user = computed(() => page.props.auth?.user);
const isCompany = computed(() => user.value?.userable_type === 'company');
const unread = computed(() => user.value?.notifications?.unread_count || 0);
const showCredits = computed(() => page.props.billing?.enabled && user.value?.credits !== null && user.value?.credits !== undefined);
const roleLabel = computed(() => {
	if (user.value?.is_admin) return 'Admin';
	if (isCompany.value) return text('Company', 'Entreprise');
	return text('Candidate', 'Candidat');
});
const initials = computed(() =>
	(user.value?.name || '')
		.split(' ')
		.map((w) => w[0])
		.filter(Boolean)
		.slice(0, 2)
		.join('')
		.toUpperCase()
);

const search = ref(new URLSearchParams(page.url.split('?')[1] || '').get('search') || '');
const submitSearch = () => {
	router.get(route('app.opportunities.index'), search.value.trim() ? { search: search.value.trim() } : {});
};
const searchInput = ref(null);
// Phones get a short placeholder so it isn't cut off.
const narrow = ref(false);
const clearSearch = () => {
	search.value = '';
	searchInput.value?.focus();
};
// "/" jumps to the search box from anywhere outside a text field.
const focusSearch = (event) => {
	if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey) return;
	if (event.target.isContentEditable || event.target.closest?.('input, textarea, select')) return;
	event.preventDefault();
	searchInput.value?.focus();
};

const menu = ref(null);
const menuOpen = ref(false);
const closeMenu = (event) => {
	if (menu.value && !menu.value.contains(event.target)) menuOpen.value = false;
};
onMounted(() => {
	narrow.value = window.matchMedia('(max-width: 560px)').matches;
	document.addEventListener('click', closeMenu);
	document.addEventListener('keydown', focusSearch);
});
onBeforeUnmount(() => {
	document.removeEventListener('click', closeMenu);
	document.removeEventListener('keydown', focusSearch);
});

const logout = () => router.post(route('logout'));
const menuItem = 'flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm cursor-pointer transition-colors focus-visible:outline-2 focus-visible:outline-brand';
</script>

<template>
	<header class="h-[72px] shrink-0 bg-white border-b border-line flex items-center gap-3 px-6 max-[680px]:px-4 sticky top-0 z-20">
		<button class="icon-btn hidden max-[900px]:grid -ml-1.5" type="button" :aria-label="text('Open navigation', 'Ouvrir le menu')" aria-controls="workspace-navigation" :aria-expanded="navigationOpen" @click="$emit('navigation')">
			<Menu class="size-5" />
		</button>

		<form class="flex-1 min-w-0 max-w-[520px]" role="search" @submit.prevent="submitSearch">
			<label class="group flex items-center gap-2.5 h-11 pl-3.5 pr-2 rounded-card border border-slate-300 bg-white cursor-text transition-colors hover:border-slate-400 focus-within:border-brand focus-within:hover:border-brand">
				<Search class="size-[18px] shrink-0 text-muted-2 transition-colors group-focus-within:text-brand" aria-hidden="true" />
				<input
					ref="searchInput"
					v-model="search"
					:aria-label="text('Search roles', 'Rechercher des offres')"
					aria-keyshortcuts="/"
					class="min-w-0 w-full border-0 bg-transparent p-0 outline-none focus:ring-0 text-[14.5px] text-ink placeholder:text-muted-2 [&::-webkit-search-cancel-button]:appearance-none"
					type="search"
					enterkeyhint="search"
					:placeholder="narrow ? text('Search', 'Rechercher') : text('Search roles, companies, skills…', 'Rechercher une offre, une entreprise, une compétence…')"
				>
				<button v-if="search" type="button" class="grid place-items-center size-6 shrink-0 rounded-md text-muted-2 transition-colors hover:bg-pale hover:text-ink focus-visible:outline-2 focus-visible:outline-brand" :aria-label="text('Clear search', 'Effacer la recherche')" @click="clearSearch">
					<X class="size-4" aria-hidden="true" />
				</button>
				<kbd v-else class="grid place-items-center min-w-[22px] h-[22px] px-1.5 shrink-0 rounded-md border border-line bg-pale font-sans text-[11.5px] font-medium text-muted group-focus-within:hidden max-[680px]:hidden" :title="text('Press / to search', 'Appuyez sur / pour rechercher')" aria-hidden="true">/</kbd>
			</label>
		</form>

		<div class="ml-auto flex items-center gap-1.5 shrink-0">
			<Link v-if="isCompany" class="cta cta-primary !py-0 h-11 !px-5 !text-[13.5px] max-[680px]:!px-0 max-[680px]:w-11" :href="route('opportunities.create')" :aria-label="text('Post role', 'Publier une offre')">
				<Plus class="size-4" aria-hidden="true" /><span class="max-[680px]:hidden">{{ text('Post role', 'Publier une offre') }}</span>
			</Link>

			<LanguageMenu account filled class="max-[680px]:hidden" />

			<CreditsMenu v-if="showCredits" />

			<button v-if="user?.notifications" class="icon-btn" type="button" :aria-label="unread ? text(`Notifications, ${unread} unread`, `Notifications, ${unread} non lues`) : 'Notifications'" @click="$emit('notifications')">
				<Bell class="size-5" />
				<span v-if="unread" class="absolute top-1.5 right-1.5 min-w-4 h-4 px-1 rounded-full bg-brand text-white text-[10px] font-medium grid place-items-center ring-2 ring-white">{{ unread > 9 ? '9+' : unread }}</span>
			</button>

			<div ref="menu" class="relative" @keydown.esc="menuOpen = false">
				<button
					class="flex items-center gap-2.5 h-11 pl-1 pr-2 rounded-card transition-colors hover:bg-pale focus-visible:outline-2 focus-visible:outline-brand"
					type="button"
					aria-haspopup="menu"
					:aria-expanded="menuOpen"
					:aria-label="text('Account menu', 'Menu du compte')"
					@click="menuOpen = !menuOpen"
				>
					<span class="size-9 rounded-card grid place-items-center text-white font-medium text-[13px] bg-brand">{{ initials }}</span>
					<span class="text-left leading-tight max-[760px]:hidden">
						<b class="block text-[14px] font-medium max-w-[140px] truncate">{{ user?.name }}</b>
						<span class="block text-xs text-muted">{{ roleLabel }}</span>
					</span>
					<ChevronDown class="size-4 text-muted-2 max-[760px]:hidden" aria-hidden="true" />
				</button>

				<Transition enter-active-class="transition duration-100" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition duration-75" leave-to-class="opacity-0">
					<div v-if="menuOpen" class="absolute right-0 top-[calc(100%+8px)] w-60 rounded-card border border-line bg-white p-1.5 z-30" role="menu">
						<div class="px-3 py-2.5 border-b border-line-2 mb-1.5">
							<b class="block text-sm font-medium truncate">{{ user?.name }}</b>
							<span class="block text-xs text-muted truncate">{{ user?.email }}</span>
						</div>
						<Link :class="[menuItem, 'text-ink-700 hover:bg-pale hover:text-ink']" role="menuitem" :href="route('profile.show')" @click="menuOpen = false"><CircleUserRound class="size-4 text-muted-2" />{{ text('Profile', 'Profil') }}</Link>
						<Link :class="[menuItem, 'text-ink-700 hover:bg-pale hover:text-ink']" role="menuitem" :href="route('settings')" @click="menuOpen = false"><Settings class="size-4 text-muted-2" />{{ text('Settings', 'Paramètres') }}</Link>
						<div class="hidden max-[680px]:flex items-center justify-between gap-3 px-3 py-2 text-sm text-ink-700">
							<span class="flex items-center gap-2.5"><Languages class="size-4 text-muted-2" aria-hidden="true" />{{ text('Language', 'Langue') }}</span>
							<span class="flex gap-1">
								<button v-for="code in ['en', 'fr']" :key="code" type="button" class="rounded-lg px-2.5 py-1 text-[13px] font-medium uppercase transition-colors focus-visible:outline-2 focus-visible:outline-brand" :class="(fr ? 'fr' : 'en') === code ? 'bg-brand text-white' : 'bg-pale text-navy hover:bg-blue-100'" :aria-pressed="(fr ? 'fr' : 'en') === code" :lang="code" @click="(fr ? 'fr' : 'en') !== code && saveLanguage(code)">{{ code }}</button>
							</span>
						</div>
						<div class="h-px bg-line-2 my-1.5"></div>
						<button :class="[menuItem, 'text-red-700 hover:bg-red-50 hover:text-red-800']" role="menuitem" type="button" @click="logout"><LogOut class="size-4 text-red-600" />{{ text('Log out', 'Se déconnecter') }}</button>
					</div>
				</Transition>
			</div>
		</div>
	</header>
</template>
