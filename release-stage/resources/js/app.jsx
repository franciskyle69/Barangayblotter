import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx');
        const path = `./Pages/${name}.jsx`;
        if (!(path in pages)) throw new Error(`Page not found: ${name}`);
        return pages[path]();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
