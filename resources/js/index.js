import KoreOverlay from './overlay.js';
import KoreFeedback from './feedback.js';
import KoreSelect from './form/select.js';
import KoreInputOtp from './form/input-otp.js';
import KoreDatePicker from './form/datepicker.js';
import KoreUpload from './form/upload.js';
import KoreTimePicker from './form/time-picker.js';
import KoreRating from './form/rating.js';
import KoreRange from './form/range.js';
import KoreTagInput from './form/tag-input.js';
import KoreColorPicker from './form/color-picker.js';
import KoreMaskable from './form/maskable.js';
import KoreTheme from './theme.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('KoreOverlay', KoreOverlay);
    Alpine.data('KoreFeedback', KoreFeedback);
    Alpine.data('KoreSelect', KoreSelect);
    Alpine.data('KoreInputOtp', KoreInputOtp);
    Alpine.data('KoreDatePicker', KoreDatePicker);
    Alpine.data('KoreUpload', KoreUpload);
    Alpine.data('KoreTimePicker', KoreTimePicker);
    Alpine.data('KoreRating', KoreRating);
    Alpine.data('KoreRange', KoreRange);
    Alpine.data('KoreTagInput', KoreTagInput);
    Alpine.data('KoreColorPicker', KoreColorPicker);
    Alpine.data('KoreMaskable', KoreMaskable);
    Alpine.store('koreTheme', KoreTheme);
});
