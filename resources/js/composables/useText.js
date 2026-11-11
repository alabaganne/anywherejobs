import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

// text('English', 'Français') picks the wording for the page language; dateLocale is for Intl and toLocale* calls.
export function useText() {
	const page = usePage();
	const fr = computed(() => page.props.locale === 'fr');
	const text = (en, french) => (fr.value ? french : en);
	const dateLocale = computed(() => (fr.value ? 'fr-FR' : 'en-GB'));
	return { fr, text, dateLocale };
}

// Saves the language on the account; the page comes back in it.
export const saveLanguage = (code) =>
	router.post(route('locale.update'), { locale: code }, { preserveScroll: true, preserveState: false, onSuccess: () => (document.documentElement.lang = code) });
