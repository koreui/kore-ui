import { defineConfig } from 'vite'

/**
 * El bundle aparte del editor.
 *
 * `emptyOutDir: false` es obligatorio: con el valor por defecto, este build
 * borraría el `dist/` que acaba de dejar el principal y el paquete se publicaría
 * con la mitad del JavaScript.
 */
export default defineConfig({
    build: {
        lib: {
            entry: 'resources/js/editor.js',
            name: 'KoreUiEditor',
            formats: ['iife'],
            fileName: () => 'kore-ui-editor.js',
        },
        outDir: 'dist',
        emptyOutDir: false,
    },
})
