import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        // JS unit tests for the Alpine plugins live here. They exercise the pure
        // logic of the factory objects (formatting, parsing, clamping, fuzzy
        // matching…) without a real Alpine/DOM runtime.
        include: ['tests/js/**/*.test.js'],
        environment: 'node',
    },
});
