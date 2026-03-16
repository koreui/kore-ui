export default function KoreOverlay() {
    return {
        show: false,
        contentVisible: false,
        current: null,
        stack: [],
        overlaySize: null,
        backdropBlur: false,
        positionClasses: '',
        listeners: [],
        transitioning: false,

        init() {
            this.listeners.push(
                Livewire.on('kore:overlay-changed', ({ id }) => {
                    this.activate(id);
                })
            );

            this.listeners.push(
                Livewire.on('kore:close', (params = {}) => {
                    this.closeOverlay(
                        params.force ?? false,
                        params.skipPrevious ?? 0,
                        params.destroySkipped ?? false
                    );
                })
            );
        },

        destroy() {
            this.listeners.forEach(cleanup => {
                if (typeof cleanup === 'function') cleanup();
            });
            this.listeners = [];
        },

        activate(id, skip = false) {
            if (this.transitioning) return;

            if (this.current === null) {
                // First overlay — show immediately
                this.current = id;
                this.show = true;
                this.contentVisible = true;
                this.updateAttributes(id);
                document.body.classList.add('overflow-y-hidden');
                this.$nextTick(() => {
                    setTimeout(() => this.focusFirst(), 50);
                });
            } else {
                // Stacking — transition out then in
                if (!skip) {
                    this.stack.push(this.current);
                }

                this.transitioning = true;
                this.contentVisible = false;

                setTimeout(() => {
                    this.current = id;
                    this.contentVisible = true;
                    this.updateAttributes(id);
                    this.transitioning = false;

                    this.$nextTick(() => {
                        setTimeout(() => this.focusFirst(), 50);
                    });
                }, 300);
            }
        },

        closeOverlay(force = false, skipPrevious = 0, destroySkipped = false) {
            const currentId = this.current;

            if (!currentId) return;

            const attrs = this.attr(currentId);

            // Dispatch close event if configured
            if (attrs?.dispatchesCloseEvent) {
                Livewire.dispatch('kore:closed', { id: currentId });
            }

            // Destroy component if configured
            if (attrs?.destroysOnClose) {
                this.$wire.destroyOverlay(currentId);
            }

            // Skip previous overlays if requested
            for (let i = 0; i < skipPrevious; i++) {
                const skippedId = this.stack.pop();
                if (skippedId && destroySkipped) {
                    this.$wire.destroyOverlay(skippedId);
                }
            }

            // Navigate back or close all
            const previousId = this.stack.pop();

            if (previousId && !force) {
                this.activate(previousId, true);
            } else {
                this.toggle(false);
            }
        },

        closeOnEscape() {
            if (!this.current) return;

            const attrs = this.attr(this.current);
            if (!attrs?.closesOnEscape) return;

            // Dispatch before-close event for cancellation
            const params = { id: this.current, trigger: 'escape', closing: true };
            const componentName = this.getComponentName(this.current);
            if (componentName) {
                Livewire.dispatchTo(componentName, 'kore:before-close', params);
            }
            if (!params.closing) return;

            if (attrs.escapeClosesAll) {
                this.closeOverlay(true);
            } else {
                this.closeOverlay(false);
            }
        },

        closeOnClickAway() {
            if (!this.current) return;

            const attrs = this.attr(this.current);
            if (!attrs?.closesOnClickAway) return;

            // Dispatch before-close event for cancellation
            const params = { id: this.current, trigger: 'click-away', closing: true };
            const componentName = this.getComponentName(this.current);
            if (componentName) {
                Livewire.dispatchTo(componentName, 'kore:before-close', params);
            }
            if (!params.closing) return;

            // Click-away respects the stack (not forced) — fix over wire-elements
            this.closeOverlay(false);
        },

        toggle(show) {
            if (show) {
                this.show = true;
                document.body.classList.add('overflow-y-hidden');
            } else {
                this.contentVisible = false;
                document.body.classList.remove('overflow-y-hidden');

                setTimeout(() => {
                    this.show = false;
                    this.current = null;
                    this.stack = [];
                    this.$wire.resetState();
                }, 300);
            }
        },

        updateAttributes(id) {
            const attrs = this.attr(id);
            if (!attrs) return;

            this.overlaySize = attrs.sizeClass ?? '';
            this.backdropBlur = attrs.backdropBlur ?? false;

            // Set position classes based on type
            const type = attrs.type ?? 'modal';
            const position = attrs.position ?? 'center';

            this.positionClasses = this.getPositionClasses(type, position);
        },

        getPositionClasses(type, position) {
            if (type === 'drawer') {
                return position === 'left'
                    ? 'items-stretch justify-start'
                    : 'items-stretch justify-end';
            }

            if (type === 'bottom-sheet') {
                return 'items-end justify-center p-4 sm:p-0';
            }

            if (type === 'fullscreen') {
                return 'items-stretch';
            }

            // modal, confirm — centered
            return 'items-end justify-center p-4 text-center sm:items-center sm:p-0';
        },

        attr(id) {
            const overlays = this.$wire.get('overlays');
            return overlays?.[id]?.overlayAttributes ?? null;
        },

        getComponentName(id) {
            const overlays = this.$wire.get('overlays');
            return overlays?.[id]?.name ?? null;
        },

        focusFirst() {
            const el = this.$el.querySelector('[autofocus]');
            if (el) el.focus();
        },
    };
}
