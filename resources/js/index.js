import KoreOverlay from './overlay.js';
import KoreFeedback from './feedback.js';
import KoreSelect from './form/select.js';
import KoreInputOtp from './form/input-otp.js';
import KoreDatePicker from './form/datepicker.js';
import KoreUpload from './form/upload.js';
import KoreTimePicker from './form/time-picker.js';
import KoreTheme from './theme.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('KoreOverlay', KoreOverlay);
    Alpine.data('KoreFeedback', KoreFeedback);
    Alpine.data('KoreSelect', KoreSelect);
    Alpine.data('KoreInputOtp', KoreInputOtp);
    Alpine.data('KoreDatePicker', KoreDatePicker);
    Alpine.data('KoreUpload', KoreUpload);
    Alpine.data('KoreTimePicker', KoreTimePicker);
    Alpine.store('koreTheme', KoreTheme);
});
