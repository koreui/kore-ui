export default (config) => ({
    value: config.value ?? 0,
    displayValue: '0',
    animated: config.animated ?? true,
    _observed: false,

    init() {
        // Un contador que sube durante un segundo es justo lo que
        // `prefers-reduced-motion` pide desactivar, y no lo miraba nadie:
        // medido, con la preferencia activa seguía contando desde cero. El
        // resto de la librería sí la respeta (ver `feedback.js`).
        const sinMovimiento = typeof window !== 'undefined'
            && typeof window.matchMedia === 'function'
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!this.animated || sinMovimiento) {
            this.displayValue = this._formatNumber(this.value);
            return;
        }

        this.displayValue = '0';

        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !this._observed) {
                this._observed = true;
                this._animateCount();
                observer.disconnect();
            }
        }, { threshold: 0.1 });

        observer.observe(this.$el);
    },

    _animateCount() {
        const duration = 1000;
        const start = performance.now();
        const target = this.value;

        const animate = (now) => {
            const elapsed = now - start;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - (1 - progress) * (1 - progress);
            const current = Math.round(eased * target);
            this.displayValue = this._formatNumber(current);
            if (progress < 1) requestAnimationFrame(animate);
        };

        requestAnimationFrame(animate);
    },

    _formatNumber(n) {
        return new Intl.NumberFormat().format(n);
    },
});
