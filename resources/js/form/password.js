export default (config) => ({
    show: false,
    value: '',

    _rules: [
        { id: 'length', label: `At least ${config.minLength || 8} characters`,
          test: (v) => v.length >= (config.minLength || 8) },
        { id: 'uppercase', label: 'One uppercase letter',
          test: (v) => /[A-Z]/.test(v) },
        { id: 'lowercase', label: 'One lowercase letter',
          test: (v) => /[a-z]/.test(v) },
        { id: 'number', label: 'One number',
          test: (v) => /\d/.test(v) },
        { id: 'special', label: 'One special character',
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
        return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.level];
    },

    get levelColorClass() {
        return ['bg-kore-muted', 'bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500'][this.level];
    },

    get levelTextClass() {
        return ['text-kore-muted-fg', 'text-red-500', 'text-orange-500', 'text-yellow-500', 'text-green-500'][this.level];
    },
});
