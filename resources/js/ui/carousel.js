/**
 * Selector de lo que un carril NO debe secuestrar: si el gesto empieza sobre un
 * control, el arrastre no arranca y el navegador hace su trabajo —dar el foco y
 * disparar el clic—.
 */
const CONTROLES = 'a[href], button, input, select, textarea, [contenteditable], [tabindex]:not([tabindex="-1"])';

export default (config = {}) => ({
    autoplay: config.autoplay || false,
    interval: config.interval || 5000,
    loop: config.loop !== undefined ? config.loop : true,
    pauseOnHover: config.pauseOnHover !== undefined ? config.pauseOnHover : true,
    numVisible: config.numVisible || 1,
    gap: config.gap || 16,
    currentIndex: 0,
    totalSlides: 0,

    /** Pausa que ha pedido el usuario, distinta de la que provoca el puntero. */
    parado: false,

    _timer: null,
    _resizeObserver: null,
    _morphObserver: null,
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
            this._montar();

            this._resizeObserver = new ResizeObserver(() => {
                this._setSlideSizes();
                this._updateTrackPosition();
            });
            this._resizeObserver.observe(this.$refs.viewport);

            // El ancho de cada diapositiva y la posición del carril los escribe
            // ESTE JavaScript como estilo en línea, y nada de eso existe en el
            // HTML que emite el servidor: el morph de Livewire los borraba en
            // cuanto cualquier otra cosa de la página hablaba con el servidor.
            // Medido: las diapositivas pasaban de 768 px a unos 50 —el ancho de
            // su contenido— y la siguiente pulsación de «siguiente» desplazaba
            // el carril con la cuenta vieja, dejando la vista EN BLANCO.
            //
            // Se vuelve a montar en cuanto los estilos desaparecen, igual que
            // las barras del splitter. Reaplicarlos dispara este mismo
            // observador, pero entonces la condición ya no se cumple y no hay
            // bucle. De paso resuelve el otro fallo: las diapositivas que el
            // servidor añade después se cuentan, cosa que `init()` no podía
            // hacer porque solo corre una vez.
            this._morphObserver = new MutationObserver(() => {
                // Durante un arrastre el `transform` lo escribe este mismo
                // componente en cada movimiento del puntero, y preguntarle al
                // observador si le han borrado los estilos no tiene sentido.
                // Medido: 33 mutaciones en un arrastre de treinta pasos, una
                // comprobación completa cada una. Lo que quede pendiente se
                // recoge al soltar.
                if (this._isDragging) return;

                if (this._necesitaRemontaje()) this._montar();
            });
            this._morphObserver.observe(this.$refs.track, {
                childList: true,
                subtree: true,
                attributeFilter: ['style'],
            });

            if (this.autoplay) this._startAutoplay();
        });
    },

    destroy() {
        this._stopAutoplay();
        this._resizeObserver?.disconnect();
        this._resizeObserver = null;
        this._morphObserver?.disconnect();
        this._morphObserver = null;
    },

    _slides() {
        return [...(this.$refs.track?.querySelectorAll('[data-carousel-slide]') ?? [])];
    },

    /** ¿El morph se ha llevado los tamaños, o han cambiado las diapositivas? */
    _necesitaRemontaje() {
        const slides = this._slides();
        if (slides.length !== this.totalSlides) return true;
        if (slides.some((s) => ! s.style.width)) return true;

        // El morph también se lleva el `inert` y el `aria-hidden`, por el mismo
        // motivo: no están en el HTML del servidor.
        //
        // La excepción del foco tiene que valer también AQUÍ. Sin ella, una
        // diapositiva que sale de vista con el foco dentro se queda sin `inert`
        // a propósito, esta comprobación lo lee como «falta reponerlo», remonta,
        // el remontaje escribe estilos, los estilos disparan este observador y
        // el ciclo no para nunca.
        return slides.some((s, i) => s.inert === this._deberiaVerse(s, i));
    },

    _montar() {
        this._countSlides();
        this._setSlideSizes();
        this._updateTrackPosition();
    },

    /**
     * Lo que está fuera de la ventana no participa.
     *
     * Las diapositivas ocultas siguen en el DOM —solo las recorta un
     * `overflow-hidden`—, así que sin esto un botón dentro de la tercera
     * diapositiva se podía enfocar con el tabulador aunque nadie lo viera: el
     * foco desaparecía de la pantalla y la página no desplazaba a ninguna parte,
     * porque el carril se mueve con `transform`. Medido: cuatro de cinco botones
     * fuera de la ventana y todos con `tabindex` 0.
     *
     * `inert` los saca de una vez del tabulador, del árbol de accesibilidad y de
     * los eventos de puntero, que es exactamente lo que hace falta.
     */
    _actualizarVisibilidad() {
        this._slides().forEach((slide, i) => {
            const visible = this._deberiaVerse(slide, i);

            slide.inert = ! visible;
            slide.setAttribute('aria-hidden', visible ? 'false' : 'true');
        });
    },

    /**
     * ¿Esta diapositiva participa?
     *
     * Las de la ventana, sí. Y también una que se haya quedado fuera con el foco
     * dentro: sacarla del árbol de accesibilidad mandaría el foco a `<body>` de
     * golpe, que es peor que el problema que `inert` resuelve. Vuelve a su sitio
     * en cuanto el foco sale.
     */
    _deberiaVerse(slide, i) {
        if (i >= this.currentIndex && i < this.currentIndex + this.numVisible) return true;

        return slide.contains(document.activeElement);
    },

    _countSlides() {
        this.totalSlides = this._slides().length;

        // Si el servidor se lleva diapositivas, el índice puede quedar fuera de
        // rango y el carril se iría a un hueco vacío.
        const maxIndex = Math.max(0, this.totalSlides - this.numVisible);
        if (this.currentIndex > maxIndex) this.currentIndex = maxIndex;
    },

    _slideWidth() {
        const viewport = this.$refs.viewport;
        if (! viewport) return 0;

        const totalGap = this.gap * (this.numVisible - 1);
        return (viewport.offsetWidth - totalGap) / this.numVisible;
    },

    _setSlideSizes() {
        const viewport = this.$refs.viewport;
        if (! viewport) return;

        const slideWidth = this._slideWidth();
        const slides = this._slides();

        slides.forEach((slide, i) => {
            slide.style.width = slideWidth + 'px';
            slide.style.minWidth = slideWidth + 'px';

            // La posición de cada diapositiva solo se sabe aquí: el componente
            // anónimo de Blade no conoce ni su índice ni cuántas hay.
            slide.setAttribute('aria-label', `${i + 1} / ${slides.length}`);
        });
    },

    _updateTrackPosition() {
        if (! this.$refs.track || ! this.$refs.viewport) return;

        const offset = -(this.currentIndex * (this._slideWidth() + this.gap));
        this.$refs.track.style.transform = `translateX(${offset}px)`;

        this._actualizarVisibilidad();
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
            this.currentIndex = Math.max(0, this.totalSlides - this.numVisible);
        }
        this._updateTrackPosition();
    },

    goTo(index) {
        const maxIndex = Math.max(0, this.totalSlides - this.numVisible);
        this.currentIndex = Math.max(0, Math.min(index, maxIndex));
        this._updateTrackPosition();
    },

    /**
     * Teclas de un carrusel: flechas para moverse entre diapositivas.
     *
     * No se tocan las que el usuario escribe: dentro de un campo de texto la
     * flecha mueve el cursor, y robársela deja el campo inservible.
     */
    onKeydown(e) {
        if (e.target.matches('input, textarea, select, [contenteditable]')) return;
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;

        e.preventDefault();
        e.key === 'ArrowRight' ? this.next() : this.prev();
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
        if (this.autoplay) this._stopAutoplay();
    },

    resume() {
        // Una pausa pedida a mano manda sobre la del puntero: si no, sacar el
        // ratón del carrusel volvía a arrancarlo.
        if (this.autoplay && ! this.parado) this._startAutoplay();
    },

    /**
     * Parar y reanudar a voluntad.
     *
     * WCAG 2.2.2 pide un mecanismo para detener cualquier movimiento automático
     * que dure más de cinco segundos. `pauseOnHover` solo sirve con ratón, así
     * que quien navegue con teclado o toque no tenía ninguno.
     */
    toggleParado() {
        this.parado = ! this.parado;
        this.parado ? this._stopAutoplay() : this._startAutoplay();
    },

    onPointerDown(e) {
        // Un botón o un enlace dentro de una diapositiva es lo primero: el
        // `preventDefault` que llevaba el atributo se lo tragaba TODO, incluido
        // el foco. Medido: al pulsar un botón dentro de una diapositiva el foco
        // se quedaba en `<body>`.
        if (e.target.closest(CONTROLES)) return;

        // Fuera de un control sí hace falta, o el gesto selecciona el texto de
        // la diapositiva y arrastra sus imágenes.
        e.preventDefault();

        this._isDragging = true;
        this._pointerStartX = e.clientX;
        this._pointerCurrentX = e.clientX;
        this.$refs.track.style.transition = 'none';
    },

    onPointerMove(e) {
        if (! this._isDragging) return;
        this._pointerCurrentX = e.clientX;

        const baseOffset = -(this.currentIndex * (this._slideWidth() + this.gap));
        const dragOffset = this._pointerCurrentX - this._pointerStartX;

        this.$refs.track.style.transform = `translateX(${baseOffset + dragOffset}px)`;
    },

    onPointerUp() {
        if (! this._isDragging) return;
        this._isDragging = false;
        this.$refs.track.style.transition = '';

        const delta = this._pointerCurrentX - this._pointerStartX;
        const threshold = this.$refs.viewport.offsetWidth * 0.2;

        if (Math.abs(delta) > threshold) {
            delta < 0 ? this.next() : this.prev();
        } else {
            this._updateTrackPosition();
        }

        // Un morph que llegara en mitad del arrastre no habría disparado nada.
        if (this._necesitaRemontaje()) this._montar();
    },
});
