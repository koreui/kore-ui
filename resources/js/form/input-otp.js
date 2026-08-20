export default (config) => ({
    digits: Array(config.length).fill(''),

    init() {
        // Populate from existing value if present
        const val = this.$refs.hiddenInput?.value ?? '';
        if (val) this._pintar(val);

        this._observarServidor();
    },

    /**
     * Refleja en las casillas lo que el servidor ponga en la propiedad.
     *
     * Era el único componente de formulario sin este puente: la sincronización
     * iba solo de cliente a servidor. Un `$this->reset('codigo')` tras un código
     * incorrecto —el caso normal de un OTP— cambiaba la propiedad y dejaba los
     * seis dígitos escritos en pantalla. Ahora que la raíz va con `wire:ignore`
     * es además la única vía de vuelta, porque el morph ya no la toca.
     */
    _observarServidor() {
        const input = this.$refs.hiddenInput;
        if (! input || ! this.$wire) return;

        const modelo = input.getAttribute('wire:model.live')
            || input.getAttribute('wire:model.blur')
            || input.getAttribute('wire:model.defer')
            || input.getAttribute('wire:model');
        if (! modelo) return;

        this.$wire.$watch(modelo, (valor) => {
            const texto = (valor === null || valor === undefined) ? '' : String(valor);
            if (texto === this.digits.join('')) return;

            this.digits = Array(config.length).fill('');
            this._pintar(texto);
            for (let i = 0; i < config.length; i++) {
                const casilla = this.getInput(i);
                if (casilla) casilla.value = this.digits[i];
            }
            if (input.value !== texto) input.value = texto;
        });
    },

    /** Reparte una cadena entre las casillas, sin tocar el DOM. */
    _pintar(texto) {
        texto.split('').forEach((ch, i) => {
            if (i < config.length) this.digits[i] = ch;
        });
    },

    onInput(index, e) {
        const val = e.target.value;
        // Take only the last character if multiple typed
        this.digits[index] = val.slice(-1);
        e.target.value = this.digits[index];

        if (config.numeric && !/^\d*$/.test(this.digits[index])) {
            this.digits[index] = '';
            e.target.value = '';
            return;
        }

        this.sync();

        // Auto-advance to next input
        if (this.digits[index] && index < config.length - 1) {
            this.focusInput(index + 1);
        }
    },

    onKeydown(index, e) {
        switch (e.key) {
            case 'Backspace':
                e.preventDefault();
                if (this.digits[index]) {
                    this.digits[index] = '';
                    this.getInput(index).value = '';
                } else if (index > 0) {
                    this.digits[index - 1] = '';
                    this.getInput(index - 1).value = '';
                    this.focusInput(index - 1);
                }
                this.sync();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                if (index > 0) this.focusInput(index - 1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                if (index < config.length - 1) this.focusInput(index + 1);
                break;
            case 'Delete':
                e.preventDefault();
                this.digits[index] = '';
                this.getInput(index).value = '';
                this.sync();
                break;
        }
    },

    onPaste(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const chars = text.replace(/\s/g, '').split('');

        chars.forEach((ch, i) => {
            if (i >= config.length) return;
            if (config.numeric && !/^\d$/.test(ch)) return;
            this.digits[i] = ch;
            this.getInput(i).value = ch;
        });

        this.sync();

        // Focus the next empty input or the last one
        const nextEmpty = this.digits.findIndex(d => !d);
        this.focusInput(nextEmpty >= 0 ? nextEmpty : config.length - 1);
    },

    getInput(index) {
        return this.$refs[`digit${index}`];
    },

    focusInput(index) {
        const input = this.getInput(index);
        if (input) {
            input.focus();
            input.select();
        }
    },

    sync() {
        const value = this.digits.join('');
        if (this.$refs.hiddenInput) {
            this.$refs.hiddenInput.value = value;
            this.$refs.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },
});
