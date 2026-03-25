import { computePosition, flip, shift, offset, autoUpdate } from '@floating-ui/dom';

/**
 * Shared floating positioning for kore-ui components.
 *
 * Usage in an Alpine component:
 *   import { startFloating, stopFloating } from '../utils/floating.js';
 *
 *   openDropdown() {
 *       this.open = true;
 *       this.$nextTick(() => {
 *           this._floatingCleanup = startFloating(this.$refs.trigger, this.$refs.dropdown, {
 *               placement: 'bottom-start',
 *               offset: 4,
 *               sameWidth: true,         // match trigger width
 *               fixedWidth: 380,         // or set a fixed px width
 *               onClose: () => this.close(),
 *           });
 *       });
 *   }
 *
 *   close() {
 *       this.open = false;
 *       stopFloating(this._floatingCleanup);
 *   }
 */

export function startFloating(reference, floating, opts = {}) {
    if (!reference || !floating) return null;

    const placement = opts.placement || 'bottom-start';
    const gap = opts.offset ?? 4;
    const middleware = [offset(gap), flip(), shift({ padding: 8 })];

    floating.style.position = 'fixed';
    // Keep the panel invisible until coordinates are calculated so it never
    // flashes at an incorrect position (e.g. top-left corner of the viewport).
    floating.style.visibility = 'hidden';

    if (opts.fixedWidth) {
        floating.style.width = opts.fixedWidth + 'px';
    }

    let positioned = false;
    const update = () => {
        if (opts.sameWidth) {
            floating.style.width = reference.getBoundingClientRect().width + 'px';
        }
        computePosition(reference, floating, { placement, middleware, strategy: 'fixed' }).then(({ x, y }) => {
            floating.style.left = x + 'px';
            floating.style.top = y + 'px';
            floating.style.bottom = 'auto';
            floating.style.right = 'auto';
            if (!positioned) {
                floating.style.visibility = '';
                positioned = true;
            }
        });
    };

    // autoUpdate handles scroll (all ancestors), resize, DOM mutations
    const cleanup = autoUpdate(reference, floating, update, {
        ancestorScroll: true,
        ancestorResize: true,
        elementResize: true,
        layoutShift: true,
        animationFrame: false,
    });

    return cleanup;
}

export function stopFloating(cleanup) {
    if (typeof cleanup === 'function') {
        cleanup();
    }
}
