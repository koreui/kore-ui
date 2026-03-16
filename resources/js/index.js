import KoreOverlay from './overlay.js';
import KoreFeedback from './feedback.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('KoreOverlay', KoreOverlay);
    Alpine.data('KoreFeedback', KoreFeedback);
});
