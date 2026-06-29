import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useTranslations() {
    const page = usePage();

    const locale = computed(() => page.props.locale || 'en');
    const direction = computed(() => page.props.direction || (locale.value === 'ar' ? 'rtl' : 'ltr'));
    const translations = computed(() => page.props.translations || {});

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

    function statusLabel(status) {
        return t(`common.statuses.${String(status || '').toLowerCase()}`);
    }

    function inputTypeLabel(type) {
        return t(`common.input_types.${String(type || '').toLowerCase()}`);
    }

    return {
        direction,
        inputTypeLabel,
        locale,
        statusLabel,
        t,
    };
}
