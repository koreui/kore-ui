export default (config = {}) => ({
    autoplay: config.autoplay || false,
    interval: config.interval || 5000,
    loop: config.loop !== undefined ? config.loop : true,
    pauseOnHover: config.pauseOnHover !== undefined ? config.pauseOnHover : true,
    numVisible: config.numVisible || 1,
    gap: config.gap || 16,
    currentIndex: 0,
    totalSlides: 0,
    _timer: null,
    _resizeObserver: null,
    _pointerStartX: 0,
    _pointerCurrentX: 0,
    _isDragging: false,

    get totalPages() {
        return Math.ceil(this.totalSlides / this.numVisible);
    },

    get currentPage() {
        return Math.floor(this.currentIndex / this.numVisible);
    },

    init() {
        this.$nextTick(() => {
            this._countSlides();
            this._setSlideSizes();

            this._resizeObserver = new ResizeObserver(() => {
                this._setSlideSizes();
                this._updateTrackPosition();
            });
            this._resizeObserver.observe(this.$refs.viewport);

            if (this.autoplay) {
                this._startAutoplay();
            }
        });
    },

    destroy() {
        this._stopAutoplay();
        if (this._resizeObserver) {
            this._resizeObserver.disconnect();
        }
    },

    _countSlides() {
        this.totalSlides = this.$refs.track.querySelectorAll('[data-carousel-slide]').length;
    },

    _setSlideSizes() {
        const viewport = this.$refs.viewport;
        if (!viewport) return;

        const viewportWidth = viewport.offsetWidth;
        const totalGap = this.gap * (this.numVisible - 1);
        const slideWidth = (viewportWidth - totalGap) / this.numVisible;

        const slides = this.$refs.track.querySelectorAll('[data-carousel-slide]');
        slides.forEach(slide => {
            slide.style.width = slideWidth + 'px';
            slide.style.minWidth = slideWidth + 'px';
        });
    },

    _updateTrackPosition() {
        const viewport = this.$refs.viewport;
        if (!viewport) return;

        const viewportWidth = viewport.offsetWidth;
        const totalGap = this.gap * (this.numVisible - 1);
        const slideWidth = (viewportWidth - totalGap) / this.numVisible;
        const offset = -(this.currentIndex * (slideWidth + this.gap));

        this.$refs.track.style.transform = `translateX(${offset}px)`;
    },

    next() {
        const maxIndex = this.totalSlides - this.numVisible;
        if (this.currentIndex < maxIndex) {
            this.currentIndex = Math.min(this.currentIndex + this.numVisible, maxIndex);
        } else if (this.loop) {
            this.currentIndex = 0;
        }
        this._updateTrackPosition();
    },

    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex = Math.max(this.currentIndex - this.numVisible, 0);
        } else if (this.loop) {
            this.currentIndex = this.totalSlides - this.numVisible;
        }
        this._updateTrackPosition();
    },

    goTo(index) {
        const maxIndex = this.totalSlides - this.numVisible;
        this.currentIndex = Math.max(0, Math.min(index, maxIndex));
        this._updateTrackPosition();
    },

    _startAutoplay() {
        this._stopAutoplay();
        this._timer = setInterval(() => this.next(), this.interval);
    },

    _stopAutoplay() {
        if (this._timer) {
            clearInterval(this._timer);
            this._timer = null;
        }
    },

    pause() {
        if (this.autoplay) {
            this._stopAutoplay();
        }
    },

    resume() {
        if (this.autoplay) {
            this._startAutoplay();
        }
    },

    onPointerDown(e) {
        this._isDragging = true;
        this._pointerStartX = e.clientX;
        this._pointerCurrentX = e.clientX;
        this.$refs.track.style.transition = 'none';
    },

    onPointerMove(e) {
        if (!this._isDragging) return;
        this._pointerCurrentX = e.clientX;

        const viewport = this.$refs.viewport;
        const viewportWidth = viewport.offsetWidth;
        const totalGap = this.gap * (this.numVisible - 1);
        const slideWidth = (viewportWidth - totalGap) / this.numVisible;
        const baseOffset = -(this.currentIndex * (slideWidth + this.gap));
        const dragOffset = this._pointerCurrentX - this._pointerStartX;

        this.$refs.track.style.transform = `translateX(${baseOffset + dragOffset}px)`;
    },

    onPointerUp() {
        if (!this._isDragging) return;
        this._isDragging = false;
        this.$refs.track.style.transition = '';

        const delta = this._pointerCurrentX - this._pointerStartX;
        const viewport = this.$refs.viewport;
        const threshold = viewport.offsetWidth * 0.2;

        if (Math.abs(delta) > threshold) {
            if (delta < 0) {
                this.next();
            } else {
                this.prev();
            }
        } else {
            this._updateTrackPosition();
        }
    },
});
