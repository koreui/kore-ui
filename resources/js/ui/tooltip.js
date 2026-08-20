import { startFloating, stopFloating } from '../utils/floating.js';

/** Lo que puede recibir el foco dentro del disparador, en orden de preferencia. */
const ENFOCABLE = 'button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

export default (config) => ({
    show: false,

    _timer: null,

    // Declarada aquí, aunque se asigne en open(): lo que un componente no declara, Alpine lo
    // escribe en el x-data ANCESTRO más externo — compartido con el resto de la página. Y
    // `_floatingCleanup` lo usan seis componentes, así que se pisarían entre ellos.
    // Ver tests/js/alpine-scope.test.js.
    _floatingCleanup: null,

    init() {
        this.$nextTick(() => this._describir());
    },

    /**
     * Conecta el control con el texto del tooltip.
     *
     * Sin esto el tooltip no existía para un lector de pantalla: el panel está
     * teleportado a `<body>` —lejos del control, y sin `id`— y nadie apuntaba a
     * él. Medido: `aria-describedby` a `null` en el disparador, y el `<div
     * role="tooltip">` colgando de `<body>` sin identificar.
     *
     * El atributo va sobre el control que el CONSUMIDOR puso en el slot, no
     * sobre el envoltorio: un `div` sin rol ni tabindex nunca se anuncia, así
     * que ahí el atributo no serviría de nada. Y como el HTML del servidor no lo
     * trae, un morph puede llevárselo: por eso se vuelve a poner al abrir, que
     * es cuando de verdad hace falta.
     *
     * Apunta al `<span class="sr-only">` del propio componente, NO al panel
     * teleportado. Este código no toca `$refs.tooltip` a propósito: pedirlo
     * mientras Livewire montaba una tabla con veinticinco tooltips dejaba uno de
     * los paneles sin ámbito de Alpine.
     */
    _describir() {
        if (! config.descripcionId || ! this.$refs.trigger) return;

        const control = this.$refs.trigger.querySelector(ENFOCABLE) ?? this.$refs.trigger.firstElementChild;
        if (control && control.getAttribute('aria-describedby') !== config.descripcionId) {
            control.setAttribute('aria-describedby', config.descripcionId);
        }
    },

    open() {
        this._describir();

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
