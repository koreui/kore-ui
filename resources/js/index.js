import KoreOverlay from './overlay.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('KoreOverlay', KoreOverlay);
});
