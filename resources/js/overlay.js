import { lockScroll, unlockScroll } from './utils/scroll-lock.js';

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
        swipe: null,
        swipeTranslateY: 0,

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

            this._setupTeleportGuard();
        },

        destroy() {
            this.listeners.forEach(cleanup => {
                if (typeof cleanup === 'function') cleanup();
            });
            this.listeners = [];
            this._teardownTeleportGuard();
        },

        // --- Teleport inert guard ---
        // x-trap.inert marks every new body child as inert via a MutationObserver.
        // Components that use x-teleport="body" get caught by that observer even
        // though they are semantically part of the overlay content.
        // This guard watches for [data-kore-teleport] elements being added to body
        // and counters the inert marking with a per-element attribute observer.

        _setupTeleportGuard() {
            this._teleportObserver = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType !== Node.ELEMENT_NODE) return;
                        if (!node.hasAttribute('data-kore-teleport')) return;

                        const guard = new MutationObserver(() => {
                            node.removeAttribute('inert');
                        });
                        guard.observe(node, { attributes: true, attributeFilter: ['inert'] });
                        node._koreInertGuard = guard;
                    });

                    mutation.removedNodes.forEach((node) => {
                        if (node._koreInertGuard) {
                            node._koreInertGuard.disconnect();
                            delete node._koreInertGuard;
                        }
                    });
                });
            });

            this._teleportObserver.observe(document.body, { childList: true });

            // focus-trap's checkFocusIn handler (capture phase) snaps focus back
            // to the overlay whenever focus moves to an element outside the trap —
            // including elements teleported to <body> via x-teleport. This guard is
            // registered during page init (before any overlay opens), so it fires
            // *before* focus-trap's own capture listener and can stop propagation,
            // allowing the teleported element to keep focus.
            this._focusinGuard = (event) => {
                let el = event.target;
                while (el) {
                    if (el.hasAttribute && el.hasAttribute('data-kore-teleport')) {
                        event.stopImmediatePropagation();
                        return;
                    }
                    el = el.parentElement;
                }
            };
            document.addEventListener('focusin', this._focusinGuard, true);
        },

        _teardownTeleportGuard() {
            this._teleportObserver?.disconnect();
            this._teleportObserver = null;
            if (this._focusinGuard) {
                document.removeEventListener('focusin', this._focusinGuard, true);
                this._focusinGuard = null;
            }
        },

        activate(id, skip = false) {
            if (this.transitioning) return;

            if (this.current === null) {
                // First overlay — show immediately
                this.current = id;
                this.show = true;
                this.contentVisible = true;
                this.updateAttributes(id);
                this.lockScroll();
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

            // Click-away respects the stack (only closes topmost overlay)
            this.closeOverlay(false);
        },

        toggle(show) {
            if (show) {
                this.show = true;
                this.lockScroll();
            } else {
                this.contentVisible = false;

                setTimeout(() => {
                    this.show = false;
                    this.current = null;
                    this.stack = [];
                    this.unlockScroll();
                    this.$wire.resetState();
                }, 300);
            }
        },

        // El lock es un recurso global compartido con el drawer del sidebar:
        // utils/scroll-lock.js cuenta dueños para que el primero en cerrarse no
        // devuelva el scroll al body mientras el otro sigue abierto.
        lockScroll() {
            lockScroll(this);
        },

        unlockScroll() {
            unlockScroll(this);
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
                    ? 'h-full items-stretch justify-start'
                    : 'h-full items-stretch justify-end';
            }

            if (type === 'bottom-sheet') {
                return 'h-full items-end justify-center';
            }

            if (type === 'fullscreen') {
                return 'h-full items-stretch';
            }

            // modal, confirm — h-full (base in blade) gives a definite height.
            // Vertical centering via my-auto on each panel (OverlayDefaults).
            // items-start prevents stretch; my-auto safely centres small modals
            // and top-aligns tall ones so the header stays reachable.
            return 'min-h-dvh items-start justify-center p-4 text-center sm:py-8';
        },

        // --- Bottom-sheet swipe-to-close ---

        isBottomSheet() {
            if (!this.current) return false;
            const attrs = this.attr(this.current);
            return attrs?.type === 'bottom-sheet';
        },

        startSwipe(e) {
            if (!this.isBottomSheet()) return;
            this.swipe = { startY: e.clientY };
            this.swipeTranslateY = 0;
            e.target.setPointerCapture(e.pointerId);
        },

        moveSwipe(e) {
            if (!this.swipe) return;
            const delta = e.clientY - this.swipe.startY;
            // Only allow dragging downward
            this.swipeTranslateY = Math.max(0, delta);
        },

        endSwipe(e) {
            if (!this.swipe) return;
            const threshold = 80;

            if (this.swipeTranslateY > threshold) {
                // Past threshold — close
                this.swipeTranslateY = 0;
                this.swipe = null;
                this.closeOverlay(false);
            } else {
                // Spring back
                this.swipeTranslateY = 0;
                this.swipe = null;
            }
        },

        getSwipeStyle() {
            if (!this.swipeTranslateY) return '';
            return `transform: translateY(${this.swipeTranslateY}px); transition: none;`;
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
