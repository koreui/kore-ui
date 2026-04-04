import { defineConfig } from 'vite'

export default defineConfig({
    build: {
        lib: {
            entry: 'resources/js/index.js',
            name: 'KoreUi',
            formats: ['iife'],
            fileName: () => 'kore-ui.js',
        },
        outDir: 'dist',
        emptyOutDir: true,
    },
})
