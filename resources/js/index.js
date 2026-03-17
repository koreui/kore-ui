import KoreOverlay from './overlay.js';
import KoreFeedback from './feedback.js';
import KoreSelect from './form/select.js';
import KoreInputOtp from './form/input-otp.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('KoreOverlay', KoreOverlay);
    Alpine.data('KoreFeedback', KoreFeedback);
    Alpine.data('KoreSelect', KoreSelect);
    Alpine.data('KoreInputOtp', KoreInputOtp);
});
