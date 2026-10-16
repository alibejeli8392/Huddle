import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Prefetch can cache a page (including auth.user) from a previous session.
// Flush when the signed-in user changes so Home never resurfaces another account.
let lastAuthUserId = undefined;

router.on('navigate', (event) => {
    const userId = event.detail.page.props.auth?.user?.id ?? null;

    if (lastAuthUserId !== undefined && lastAuthUserId !== userId) {
        router.flushAll();
    }

    lastAuthUserId = userId;
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#000000',
    },
});
