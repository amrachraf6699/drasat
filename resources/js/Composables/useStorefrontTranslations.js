import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useStorefrontTranslations() {
    const page = usePage();

    const locale = computed(() => page.props.locale || 'en');
    const direction = computed(() => page.props.direction || (locale.value === 'ar' ? 'rtl' : 'ltr'));
    const translations = computed(() => page.props.storefrontTranslations || {});

    function resolve(key) {
        return key.split('.').reduce((value, segment) => {
            return value && typeof value === 'object' ? value[segment] : undefined;
        }, translations.value);
    }

    function t(key, replacements = {}) {
        let text = resolve(key);

        if (typeof text !== 'string') {
            text = key;
        }

        Object.entries(replacements).forEach(([name, value]) => {
            text = text.replaceAll(`:${name}`, value ?? '');
        });

        return text;
    }

    return {
        direction,
        locale,
        t,
    };
}
