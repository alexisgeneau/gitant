import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import HttpBackend from 'i18next-http-backend';
import LanguageDetector from 'i18next-browser-languagedetector';

export function initI18n(): Promise<typeof i18n> {
    return i18n
        .use(HttpBackend)
        .use(LanguageDetector)
        .use(initReactI18next)
        .init({
            fallbackLng: 'en',
            supportedLngs: ['en', 'fr'],
            ns: ['common'],
            defaultNS: 'common',
            backend: {
                loadPath: '/locales/{{lng}}/{{ns}}.json',
            },
            interpolation: {
                escapeValue: false,
            },
            detection: {
                order: ['htmlTag', 'navigator'],
            },
        });
}

export default i18n;
