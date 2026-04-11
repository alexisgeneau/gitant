import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import createServer from '@inertiajs/react/server';
import ReactDOMServer from 'react-dom/server';
import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

import enCommon from '../../public/locales/en/common.json';
import frCommon from '../../public/locales/fr/common.json';

// SSR-safe i18n: bundle translations directly — no HTTP backend needed
if (!i18n.isInitialized) {
    i18n.use(initReactI18next).init({
        fallbackLng: 'en',
        supportedLngs: ['en', 'fr'],
        ns: ['common'],
        defaultNS: 'common',
        resources: {
            en: { common: enCommon },
            fr: { common: frCommon },
        },
        interpolation: { escapeValue: false },
    });
}

const appName = 'Gitant';

createServer((page) =>
    createInertiaApp({
        page,
        title: (title) => `${title} - ${appName}`,
        render: ReactDOMServer.renderToString,
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        resolve: (name: string) =>
            resolvePageComponent(
                `./Pages/${name}.tsx`,
                import.meta.glob('./Pages/**/*.tsx'),
            ) as any,
        setup({ App, props }) {
            return <App {...props} />;
        },
    }),
);
