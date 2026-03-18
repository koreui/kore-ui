export default (config = {}) => ({
    open: false,
    direction: config.direction || 'up',

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    },
});
