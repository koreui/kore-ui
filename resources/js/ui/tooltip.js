import { startFloating, stopFloating } from '../utils/floating.js';

export default (config) => ({
    show: false,
    _timer: null,

    open() {
        this._timer = setTimeout(() => {
            this.show = true;
            this.$nextTick(() => {
                this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.tooltip, {
                    placement: config.placement || 'top',
                    offset: 8,
                });
            });
        }, config.delay ?? 200);
    },

    close() {
        clearTimeout(this._timer);
        this.show = false;
        stopFloating(this._floatingCleanup);
        this._floatingCleanup = null;
    },

    destroy() {
        this.close();
    },
});
