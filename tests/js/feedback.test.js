import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import KoreFeedback from '../../resources/js/feedback.js';

// KoreFeedback is an Alpine factory: KoreFeedback(flash, config) -> { ...methods }.
// The expand/collapse logic is pure state, so we drive it directly without Alpine
// or a DOM — we just never call init(), which is what reaches for Livewire.
function makeFeedback(config = {}) {
    return KoreFeedback(null, config);
}

function makeToast(props = {}) {
    return { id: 't1', type: 'info', title: 'Title', _hovered: false, _hoverTimer: null, ...props };
}

describe('isExpanded', () => {
    it('keeps a toast with a description expanded at rest', () => {
        const fb = makeFeedback();
        const toast = makeToast({ description: 'Details', autoExpand: true });

        expect(fb.isExpanded(toast)).toBe(true);
    });

    it('stays expanded after the mouse leaves', () => {
        // Regression: the toast used to collapse on mouseleave regardless of autoExpand,
        // so brushing past it with the cursor destroyed the description for good.
        const fb = makeFeedback();
        const toast = makeToast({ description: 'Details', autoExpand: true });

        toast._hovered = true;
        expect(fb.isExpanded(toast)).toBe(true);

        toast._hovered = false;
        expect(fb.isExpanded(toast)).toBe(true);
    });

    it('expands a collapsed toast only while hovered', () => {
        // ->expanded(false): the "peek" pattern — compact header, hover to reveal.
        const fb = makeFeedback();
        const toast = makeToast({ description: 'Details', autoExpand: false });

        expect(fb.isExpanded(toast)).toBe(false);

        toast._hovered = true;
        expect(fb.isExpanded(toast)).toBe(true);

        toast._hovered = false;
        expect(fb.isExpanded(toast)).toBe(false);
    });

    it('never expands a toast with nothing to show', () => {
        const fb = makeFeedback();
        const toast = makeToast({ autoExpand: true, _hovered: true });

        expect(fb.isExpanded(toast)).toBe(false);
    });

    it('expands for actions and confirm options, not just descriptions', () => {
        const fb = makeFeedback();

        expect(fb.isExpanded(makeToast({ actions: [{ label: 'Undo' }], _hovered: true }))).toBe(true);
        expect(fb.isExpanded(makeToast({ options: { confirm: { text: 'Yes' } }, _hovered: true }))).toBe(true);
    });

    it('expands a loading toast once resolve() hands it a description', () => {
        // resolve() flips autoExpand on the live toast object; isExpanded() must see it.
        const fb = makeFeedback();
        const toast = makeToast({ type: 'loading' });

        expect(fb.isExpanded(toast)).toBe(false);

        Object.assign(toast, { type: 'success', description: 'Done', autoExpand: true });
        expect(fb.isExpanded(toast)).toBe(true);
    });
});

describe('setHovered', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('waits expandDelay before expanding', () => {
        const fb = makeFeedback({ expandDelay: 150 });
        const toast = makeToast({ description: 'Details', autoExpand: false });

        fb.setHovered(toast, true);
        vi.advanceTimersByTime(149);
        expect(toast._hovered).toBe(false);

        vi.advanceTimersByTime(1);
        expect(toast._hovered).toBe(true);
    });

    it('waits collapseDelay before collapsing', () => {
        const fb = makeFeedback({ collapseDelay: 300 });
        const toast = makeToast({ description: 'Details', autoExpand: false, _hovered: true });

        fb.setHovered(toast, false);
        vi.advanceTimersByTime(299);
        expect(toast._hovered).toBe(true);

        vi.advanceTimersByTime(1);
        expect(toast._hovered).toBe(false);
    });

    it('cancels the pending timer when the cursor passes straight over', () => {
        // Whole point of the delays: a cursor crossing the toast on its way somewhere
        // else must not snap it open and shut.
        const fb = makeFeedback({ expandDelay: 150, collapseDelay: 300 });
        const toast = makeToast({ description: 'Details', autoExpand: false });

        fb.setHovered(toast, true);
        vi.advanceTimersByTime(100);
        fb.setHovered(toast, false);
        vi.advanceTimersByTime(1000);

        expect(toast._hovered).toBe(false);
    });
});

describe('add — grouping', () => {
    // add() reinicia la barra de progreso al agrupar; en node no hay DOM que buscar.
    beforeEach(() => {
        globalThis.document = { querySelector: () => null };
    });
    afterEach(() => {
        delete globalThis.document;
    });

    it('groups identical toasts into one with a counter', () => {
        const fb = makeFeedback();

        fb.add(makeToast({ id: 'a', type: 'success', title: '¡Listo!', description: 'Cliente guardado' }));
        fb.add(makeToast({ id: 'b', type: 'success', title: '¡Listo!', description: 'Cliente guardado' }));

        expect(fb.toasts).toHaveLength(1);
        expect(fb.toasts[0].count).toBe(2);
    });

    it('keeps toasts apart when only the description differs', () => {
        // Regression: grouping matched type + title only, so the second toast's
        // description was dropped and the user never saw "Cliente eliminado".
        const fb = makeFeedback();

        fb.add(makeToast({ id: 'a', type: 'success', title: '¡Listo!', description: 'Cliente guardado' }));
        fb.add(makeToast({ id: 'b', type: 'success', title: '¡Listo!', description: 'Cliente eliminado' }));

        expect(fb.toasts.map(t => t.description)).toEqual(['Cliente guardado', 'Cliente eliminado']);
        expect(fb.toasts.every(t => t.count === 1)).toBe(true);
    });

    it('treats a missing description and null as the same', () => {
        // PHP sends null; the JS API may omit the key.
        const fb = makeFeedback();

        fb.add(makeToast({ id: 'a', description: null }));
        fb.add(makeToast({ id: 'b' }));

        expect(fb.toasts).toHaveLength(1);
        expect(fb.toasts[0].count).toBe(2);
    });

    it('does not merge into a toast that is being dismissed', () => {
        // The dismissing toast is removed 300ms later; merging into it would take the
        // new toast down with it.
        vi.useFakeTimers();
        const fb = makeFeedback();

        fb.add(makeToast({ id: 'a' }));
        fb.dismiss('a');
        fb.add(makeToast({ id: 'b' }));
        vi.advanceTimersByTime(300);

        expect(fb.toasts.map(t => t.id)).toEqual(['b']);
        vi.useRealTimers();
    });
});
