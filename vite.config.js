import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            // Self-hosted so the portal renders identically without reaching a
            // third-party font CDN at request time.
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 600, 700, 800],
                }),
                bunny('IBM Plex Mono', {
                    weights: [400, 500],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
