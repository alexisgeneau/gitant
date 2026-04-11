import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initI18n } from './i18n';

const appName = document.title || 'Gitant';

initI18n().then(() => {
    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        resolve: (name: string) =>
            resolvePageComponent(
                `./Pages/${name}.tsx`,
                import.meta.glob('./Pages/**/*.tsx'),
            ) as any,
        setup({ el, App, props }) {
            const root = createRoot(el);
            root.render(<App {...props} />);
        },
        progress: {
            color: '#4f46e5',
        },
    });
});
