export default function KoreFeedback(flash = null, config = {}) {
    return {
        toasts: [],
        swiping: null,
        reducedMotion: false,
        listeners: [],
        config: Object.assign({
            position: 'top-right',
            maxVisible: 5,
            spacing: 'gap-3',
            zIndex: 'z-[60]',
            expandDelay: 150,
            collapseDelay: 300,
            swipeToDismiss: true,
        }, config),

        init() {
            this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Listen for Livewire-dispatched toast events
            this.listeners.push(
                Livewire.on('kore:toast', ({ toast }) => {
                    this.add(toast);
                })
            );

            this.listeners.push(
                Livewire.on('kore:toast-resolve', ({ toast }) => {
                    this.resolve(toast.id, toast);
                })
            );

            // Also listen for native browser events (JS API: window.dispatchEvent)
            const onBrowserToast = (e) => {
                const data = e.detail?.toast ?? e.detail;
                if (data) this.add(data);
            };
            const onBrowserResolve = (e) => {
                const data = e.detail?.toast ?? e.detail;
                if (data?.id) this.resolve(data.id, data);
            };
            window.addEventListener('kore-toast', onBrowserToast);
            window.addEventListener('kore-toast-resolve', onBrowserResolve);
            this.listeners.push(
                () => window.removeEventListener('kore-toast', onBrowserToast),
                () => window.removeEventListener('kore-toast-resolve', onBrowserResolve),
            );

            // Recover flashed toast from session (full page load or SPA navigation)
            if (flash) {
                this.$nextTick(() => this.add(flash));
            }

            // SPA navigation (wire:navigate) — re-check flash after each navigation.
            // Skip the first event when flash was already handled above during init()
            // to avoid processing the same flash twice (double toast bug).
            let skipNext = !!flash;
            const onNavigated = () => {
                if (skipNext) {
                    skipNext = false;
                    return;
                }
                const newFlash = this.$wire?.get('flash');
                if (newFlash) this.add(newFlash);
            };
            document.addEventListener('livewire:navigated', onNavigated);
            this.listeners.push(() => document.removeEventListener('livewire:navigated', onNavigated));
        },

        destroy() {
            this.listeners.forEach(cleanup => {
                if (typeof cleanup === 'function') cleanup();
            });
            this.listeners = [];
        },

        // --- Core ---

        add(toast) {
            // Sole mode — clear all existing toasts
            if (toast.sole) {
                this.toasts = [];
            }

            // Grouping — merge with existing toast of same type+title
            if (this.canGroup(toast)) {
                const existing = this.toasts.find(t =>
                    t.type === toast.type && t.title === toast.title
                );
                if (existing) {
                    existing.count = (existing.count || 1) + 1;
                    existing.timeout = toast.timeout;
                    this.resetTimer(existing.id);
                    return;
                }
            }

            // Initialize runtime props
            toast.count = 1;
            toast._visible = false;
            toast._hovered = false;
            toast._hoverTimer = null;

            this.toasts.push(toast);
        },

        dismiss(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (!toast) return;

            clearTimeout(toast._hoverTimer);

            // Execute close hook if present
            if (toast.hooks?.close && toast.reference) {
                this.executeHook(toast, 'close');
            }

            // Animate out
            toast._visible = false;

            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, this.reducedMotion ? 0 : 300);
        },

        resolve(id, updates) {
            const toast = this.toasts.find(t => t.id === id);
            if (!toast) return;

            // Crossfade icon
            toast.resolving = true;

            setTimeout(() => {
                Object.assign(toast, {
                    type: updates.type,
                    title: updates.title,
                    description: updates.description ?? null,
                    dismissible: true,
                    timeout: updates.timeout ?? this.getDefaultTimeout(updates.type),
                    resolving: false,
                });

                // Auto-expand if now has description
                if (toast.description) {
                    toast.autoExpand = true;
                }

                // Restart timer if auto-dismiss
                if (toast.timeout > 0) {
                    this.$nextTick(() => this.resetTimer(toast.id));
                }
            }, 200);
        },

        // --- Grouping ---

        canGroup(toast) {
            if (toast.noGroup) return false;
            if (toast.sole) return false;
            if (toast.actions?.length) return false;
            if (toast.options?.confirm) return false;
            if (toast.hooks && Object.keys(toast.hooks).length) return false;
            return true;
        },

        // --- Timer ---

        resetTimer(id) {
            const el = document.querySelector(`[data-toast-progress="${id}"]`);
            if (!el) return;
            el.classList.remove('kore-toast-progress');
            void el.offsetWidth; // force reflow
            el.classList.add('kore-toast-progress');
        },

        pauseTimer(id) {
            const el = document.querySelector(`[data-toast-progress="${id}"]`);
            if (el) el.style.animationPlayState = 'paused';
        },

        resumeTimer(id) {
            const el = document.querySelector(`[data-toast-progress="${id}"]`);
            if (el) el.style.animationPlayState = 'running';
        },

        // --- Expand on hover ---

        setHovered(toast, value) {
            clearTimeout(toast._hoverTimer);

            // Los delays evitan que pasar el cursor de largo abra y cierre el toast de golpe.
            const delay = value ? this.config.expandDelay : this.config.collapseDelay;

            toast._hoverTimer = setTimeout(() => {
                toast._hovered = value;
            }, delay);
        },

        isExpanded(toast) {
            // El hover solo puede AÑADIR: nunca colapsa por debajo del estado de reposo
            // (autoExpand), o rozar un toast con el cursor le borraría la descripción.
            return this.hasContent(toast) && (toast._hovered || toast.autoExpand);
        },

        // --- Swipe ---

        startSwipe(e, toast) {
            if (this.reducedMotion) return;
            if (!this.config.swipeToDismiss) return;
            if (toast.options?.confirm) return;
            if (toast.dismissible === false) return;

            this.swiping = { id: toast.id, startX: e.clientX, currentX: 0 };
            e.target.setPointerCapture(e.pointerId);
        },

        moveSwipe(e, toast) {
            if (!this.swiping || this.swiping.id !== toast.id) return;
            const delta = e.clientX - this.swiping.startX;
            this.swiping.currentX = Math.max(-20, Math.min(20, delta));
        },

        endSwipe(e, toast) {
            if (!this.swiping || this.swiping.id !== toast.id) return;
            const delta = e.clientX - this.swiping.startX;
            const direction = this.getSwipeDirection(toast.position);

            if (Math.abs(delta) > 30 && Math.sign(delta) === direction) {
                this.dismiss(toast.id);
            }
            this.swiping = null;
        },

        getSwipeDirection(position) {
            return position.includes('right') ? 1 : -1;
        },

        swipeStyle(id) {
            if (!this.swiping || this.swiping.id !== id) return '';
            return `transform: translateX(${this.swiping.currentX}px); opacity: ${1 - Math.abs(this.swiping.currentX) / 40}`;
        },

        // --- Positions ---

        get activePositions() {
            const positions = new Set();
            for (const toast of this.toasts) {
                positions.add(toast.position || this.config.position);
            }
            return [...positions];
        },

        getContainerClasses(pos) {
            const map = {
                'top-right': 'top-4 right-4 items-end',
                'top-left': 'top-4 left-4 items-start',
                'top-center': 'top-4 left-1/2 -translate-x-1/2 items-center',
                'bottom-right': 'bottom-4 right-4 items-end',
                'bottom-left': 'bottom-4 left-4 items-start',
                'bottom-center': 'bottom-4 left-1/2 -translate-x-1/2 items-center',
            };
            return map[pos] || map['top-right'];
        },

        toastsByPosition(pos) {
            return this.toasts.filter(t => (t.position || this.config.position) === pos);
        },

        visibleToasts(pos) {
            return this.toastsByPosition(pos).slice(0, this.config.maxVisible);
        },

        hiddenCount(pos) {
            return Math.max(0, this.toastsByPosition(pos).length - this.config.maxVisible);
        },

        // --- Helpers ---

        hasContent(toast) {
            return !!(toast.description || toast.actions?.length || toast.options?.confirm);
        },

        getDefaultTimeout(type) {
            const map = {
                success: 5,
                error: 0,
                warning: 8,
                info: 5,
                question: 0,
                loading: 0,
            };
            return map[type] ?? 5;
        },

        // --- Callbacks ---

        executeAction(toast, action) {
            if (toast.reference) {
                const component = Livewire.find(toast.reference);
                if (component) {
                    component.call(action.method, ...action.params);
                }
            }
            this.dismiss(toast.id);
        },

        executeOption(toast, type) {
            const option = toast.options?.[type];
            if (option?.method && toast.reference) {
                const component = Livewire.find(toast.reference);
                if (component) {
                    component.call(option.method, ...option.params);
                }
            }
            this.dismiss(toast.id);
        },

        executeHook(toast, hookName) {
            const hook = toast.hooks?.[hookName];
            if (!hook?.method || !toast.reference) return;

            const component = Livewire.find(toast.reference);
            if (component) {
                component.call(hook.method, ...hook.params);
            }
        },
    };
}
