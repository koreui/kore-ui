// Los textos llegan del Blade (`kore-ui.form.translations`). Estaban escritos
// aquí, en inglés, y eran los únicos de la librería que no se podían cambiar sin
// recompilar el bundle: ni publicar las vistas servía.
const NIVELES = ['Débil', 'Regular', 'Buena', 'Fuerte'];

const REGLAS = {
    length: 'Al menos :min caracteres',
    uppercase: 'Una letra mayúscula',
    lowercase: 'Una letra minúscula',
    number: 'Un número',
    special: 'Un carácter especial',
};

export default (config = {}) => {
    const minLength = config.minLength || 8;
    const textos = config.textos || {};
    const niveles = textos.niveles || NIVELES;
    const reglas = textos.reglas || {};

    const etiqueta = (id) => reglas[id]
        ?? REGLAS[id].replace(':min', minLength);

    return {
        show: false,
        value: '',

        _rules: [
            { id: 'length', label: etiqueta('length'),
              test: (v) => v.length >= minLength },
            { id: 'uppercase', label: etiqueta('uppercase'),
              test: (v) => /[A-Z]/.test(v) },
            { id: 'lowercase', label: etiqueta('lowercase'),
              test: (v) => /[a-z]/.test(v) },
            { id: 'number', label: etiqueta('number'),
              test: (v) => /\d/.test(v) },
            { id: 'special', label: etiqueta('special'),
              test: (v) => /[^a-zA-Z0-9]/.test(v) },
        ],

        onInput(e) { this.value = e.target.value; },

        get rules() {
            return this._rules.map(r => ({
                id: r.id, label: r.label, passed: r.test(this.value),
            }));
        },

        get passedCount() { return this.rules.filter(r => r.passed).length; },

        get level() {
            const c = this.passedCount;
            if (c === 0) return 0;
            if (c <= 1) return 1;
            if (c <= 2) return 2;
            if (c <= 3) return 3;
            return 4;
        },

        get levelLabel() {
            return ['', ...niveles][this.level];
        },

        get levelColorClass() {
            return ['bg-kore-muted', 'bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'][this.level];
        },

        get levelTextClass() {
            return ['text-kore-muted-fg', 'text-red-500', 'text-orange-500', 'text-yellow-500', 'text-green-500'][this.level];
        },
    };
};
