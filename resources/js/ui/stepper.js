export default (config) => ({
    selected: config.selected ?? null,
    linear: config.linear ?? false,
    steps: [],
    variant: config.variant ?? 'horizontal',

    init() {
        this.$nextTick(() => {
            if (!this.selected && this.steps.length > 0) {
                this.selected = this.steps[0].id;
            }
        });
    },

    registerStep(step) {
        if (!this.steps.find(s => s.id === step.id)) {
            this.steps.push(step);
        }
    },

    select(stepId) {
        const step = this.steps.find(s => s.id === stepId);
        if (!step || step.disabled) return;

        if (this.linear) {
            const currentIndex = this.steps.findIndex(s => s.id === this.selected);
            const targetIndex = this.steps.findIndex(s => s.id === stepId);

            // In linear mode, can only go to completed steps or the next step
            if (targetIndex > currentIndex + 1) return;
        }

        this.selected = stepId;
        this._syncWireModel();
    },

    getStepStatus(stepId) {
        const step = this.steps.find(s => s.id === stepId);
        if (!step) return 'pending';

        // Explicit error status always takes priority
        if (step.status === 'error') return 'error';

        const stepIndex = this.steps.findIndex(s => s.id === stepId);
        const selectedIndex = this.steps.findIndex(s => s.id === this.selected);

        if (stepId === this.selected) return 'active';
        if (stepIndex < selectedIndex) return 'complete';
        return 'pending';
    },

    getStepNumber(stepId) {
        return this.steps.findIndex(s => s.id === stepId) + 1;
    },

    next() {
        const currentIndex = this.steps.findIndex(s => s.id === this.selected);
        const nextStep = this.steps[currentIndex + 1];
        if (nextStep && !nextStep.disabled) {
            this.selected = nextStep.id;
            this._syncWireModel();
        }
    },

    previous() {
        const currentIndex = this.steps.findIndex(s => s.id === this.selected);
        const prevStep = this.steps[currentIndex - 1];
        if (prevStep && !prevStep.disabled) {
            this.selected = prevStep.id;
            this._syncWireModel();
        }
    },

    isFirst() {
        return this.selected === this.steps[0]?.id;
    },

    isLast() {
        return this.selected === this.steps[this.steps.length - 1]?.id;
    },

    _syncWireModel() {
        const input = this.$refs.hiddenInput;
        if (input) {
            input.value = this.selected ?? '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    },
});
