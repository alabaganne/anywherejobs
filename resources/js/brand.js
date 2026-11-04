import { usePage } from '@inertiajs/vue3';

// The site name and logo come from config/app.php and config/site.php on the server.
// Templates can use `appName` directly; scripts call siteName().
export const siteName = () => usePage().props.brand?.name ?? '';
export const siteLogo = () => usePage().props.brand?.logo ?? '';
