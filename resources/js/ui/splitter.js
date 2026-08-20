export default (config = {}) => {
    // Keep ALL drag state outside Alpine reactivity to avoid Proxy issues
    let panels = [];
    let gutterEls = [];
    let dragging = null;
    let startPos = 0;
    let startSizes = [];
    let resizeObserver = null;
    let morphObserver = null;

    const orientation = config.orientation || 'horizontal';
    const gutterSize = config.gutterSize || 8;
    const stateKey = config.stateKey || null;
    const resizeLabel = config.resizeLabel || 'Resize panels';
    const isHorizontal = orientation === 'horizontal';

    function collectPanels(el) {
        const panelEls = el.querySelectorAll(':scope > [data-splitter-panel]');
        const count = panelEls.length;
        const defaultSize = count > 0 ? (100 / count) : 100;

        panels = Array.from(panelEls).map((panelEl) => ({
            el: panelEl,
            size: parseFloat(panelEl.dataset.panelSize) || defaultSize,
            min: parseFloat(panelEl.dataset.panelMin) || 0,
            max: parseFloat(panelEl.dataset.panelMax) || 100,
        }));
    }

    function applyPanelSizes(containerEl) {
        const rect = containerEl.getBoundingClientRect();
        const containerSize = isHorizontal ? rect.width : rect.height;

        if (containerSize === 0) return;

        const totalGutterSize = gutterSize * (panels.length - 1);
        const available = containerSize - totalGutterSize;

        panels.forEach((panel) => {
            const px = Math.round((panel.size / 100) * available);
            panel.el.style.flex = '0 0 ' + px + 'px';
        });

        // Cada barra anuncia el tamaño del panel que tiene delante, que es lo
        // que mueven sus flechas.
        gutterEls.forEach((gutter, i) => {
            gutter.setAttribute('aria-valuenow', String(Math.round(panels[i]?.size ?? 0)));
        });
    }

    function onPointerMove(e) {
        if (dragging === null) return;

        const containerEl = gutterEls[dragging]?.parentElement;
        if (!containerEl) return;

        const rect = containerEl.getBoundingClientRect();
        const containerSize = isHorizontal ? rect.width : rect.height;
        const totalGutter = gutterSize * (panels.length - 1);
        const available = containerSize - totalGutter;

        if (available <= 0) return;

        const currentPos = isHorizontal ? e.clientX : e.clientY;
        const delta = currentPos - startPos;
        const deltaPercent = (delta / available) * 100;

        const i = dragging;
        let size1 = startSizes[i] + deltaPercent;
        let size2 = startSizes[i + 1] - deltaPercent;

        const p1 = panels[i];
        const p2 = panels[i + 1];

        // Clamp
        if (size1 < p1.min) { size2 += (size1 - p1.min); size1 = p1.min; }
        if (size1 > p1.max) { size2 += (size1 - p1.max); size1 = p1.max; }
        if (size2 < p2.min) { size1 += (size2 - p2.min); size2 = p2.min; }
        if (size2 > p2.max) { size1 += (size2 - p2.max); size2 = p2.max; }

        p1.size = size1;
        p2.size = size2;
        applyPanelSizes(containerEl);
    }

    function onPointerUp(e) {
        const idx = dragging;
        dragging = null;

        if (idx !== null && gutterEls[idx]) {
            gutterEls[idx].releasePointerCapture(e.pointerId);
            gutterEls[idx].style.background = 'var(--kore-border)';
            gutterEls[idx].style.opacity = '';
        }

        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        saveState();
    }

    function onPointerDown(e, index) {
        e.preventDefault();
        dragging = index;
        startPos = isHorizontal ? e.clientX : e.clientY;
        startSizes = panels.map(p => p.size);

        // Capture pointer to this gutter for reliable tracking
        gutterEls[index].setPointerCapture(e.pointerId);

        gutterEls[index].style.background = 'var(--kore-primary)';
        gutterEls[index].style.opacity = '0.7';

        document.body.style.cursor = isHorizontal ? 'col-resize' : 'row-resize';
        document.body.style.userSelect = 'none';
    }

    function onKeydown(e, index) {
        const step = 2;
        let handled = false;

        if (isHorizontal) {
            if (e.key === 'ArrowLeft') { resize(index, -step); handled = true; }
            if (e.key === 'ArrowRight') { resize(index, step); handled = true; }
        } else {
            if (e.key === 'ArrowUp') { resize(index, -step); handled = true; }
            if (e.key === 'ArrowDown') { resize(index, step); handled = true; }
        }

        if (handled) {
            e.preventDefault();
            saveState();
        }
    }

    function resize(index, delta) {
        const p1 = panels[index];
        const p2 = panels[index + 1];

        let s1 = p1.size + delta;
        let s2 = p2.size - delta;

        s1 = Math.max(p1.min, Math.min(p1.max, s1));
        s2 = Math.max(p2.min, Math.min(p2.max, s2));

        p1.size = s1;
        p2.size = s2;

        const containerEl = gutterEls[0]?.parentElement;
        if (containerEl) applyPanelSizes(containerEl);
    }

    function insertGutters(containerEl) {
        for (let i = 0; i < panels.length - 1; i++) {
            const gutter = document.createElement('div');

            gutter.style.flex = '0 0 ' + gutterSize + 'px';
            gutter.style.background = 'var(--kore-border)';
            gutter.style.transition = 'background 150ms, opacity 150ms';
            gutter.style.cursor = isHorizontal ? 'col-resize' : 'row-resize';
            gutter.style.touchAction = 'none';

            gutter.setAttribute('role', 'separator');
            gutter.setAttribute('aria-orientation', orientation);
            gutter.setAttribute('tabindex', '0');
            // Un `separator` enfocable es un «window splitter»: sin nombre y sin
            // valores, un lector de pantalla lo anuncia como «separador» y no
            // dice hacia dónde se está moviendo al pulsar las flechas.
            gutter.setAttribute('aria-label', resizeLabel);
            gutter.setAttribute('aria-valuemin', '0');
            gutter.setAttribute('aria-valuemax', '100');

            gutter.addEventListener('mouseenter', () => {
                if (dragging === null) {
                    gutter.style.background = 'var(--kore-primary)';
                    gutter.style.opacity = '0.5';
                }
            });
            gutter.addEventListener('mouseleave', () => {
                if (dragging === null) {
                    gutter.style.background = 'var(--kore-border)';
                    gutter.style.opacity = '';
                }
            });

            // Pointer events on gutter itself (with capture)
            const gutterIndex = i;
            gutter.addEventListener('pointerdown', (e) => onPointerDown(e, gutterIndex));
            gutter.addEventListener('pointermove', onPointerMove);
            gutter.addEventListener('pointerup', onPointerUp);
            gutter.addEventListener('pointercancel', onPointerUp);
            gutter.addEventListener('keydown', (e) => onKeydown(e, gutterIndex));

            panels[i].el.after(gutter);
            gutterEls.push(gutter);
        }
    }

    function saveState() {
        if (!stateKey) return;
        try {
            localStorage.setItem('kore-splitter-' + stateKey, JSON.stringify(panels.map(p => p.size)));
        } catch (e) { /* silent */ }
    }

    function restoreState() {
        if (!stateKey) return;
        try {
            const saved = localStorage.getItem('kore-splitter-' + stateKey);
            if (saved) {
                const sizes = JSON.parse(saved);
                if (sizes.length === panels.length) {
                    sizes.forEach((s, i) => { panels[i].size = s; });
                }
            }
        } catch (e) { /* silent */ }
    }

    /**
     * Monta (o vuelve a montar) las barras y los tamaños.
     *
     * `conservarTamanos` mantiene lo que el usuario haya arrastrado: al
     * remontar tras un morph, `collectPanels` vuelve a leer los `data-panel-size`
     * del servidor y el panel saltaría a su tamaño inicial.
     */
    function montar(el, conservarTamanos = false) {
        const previos = conservarTamanos ? panels.map((p) => p.size) : [];

        gutterEls.forEach((g) => g.remove());
        gutterEls = [];

        collectPanels(el);

        if (previos.length === panels.length) {
            panels.forEach((p, i) => { p.size = previos[i]; });
        } else {
            restoreState();
        }

        insertGutters(el);
        applyPanelSizes(el);
    }

    return {
        init() {
            const el = this.$el;

            this.$nextTick(() => montar(el));

            resizeObserver = new ResizeObserver(() => {
                if (panels.length > 0) applyPanelSizes(el);
            });
            resizeObserver.observe(el);

            // Las barras las crea este JavaScript, así que NO están en el HTML
            // que emite el servidor: el morph de Livewire las veía como nodos
            // sobrantes y las borraba. Con ellas se iba el layout entero —los
            // paneles colapsaban a su tamaño mínimo— en cuanto cualquier cosa
            // de la página hablaba con el servidor. Se vuelven a montar en
            // cuanto desaparecen, conservando lo que el usuario hubiera
            // arrastrado. Reinsertarlas dispara este mismo observador, pero la
            // condición ya no se cumple y no hay bucle.
            morphObserver = new MutationObserver(() => {
                if (gutterEls.length > 0 && !el.contains(gutterEls[0])) {
                    montar(el, true);
                }
            });
            morphObserver.observe(el, { childList: true });
        },

        destroy() {
            gutterEls.forEach(g => g.remove());
            gutterEls = [];
            if (resizeObserver) {
                resizeObserver.disconnect();
                resizeObserver = null;
            }
            if (morphObserver) {
                morphObserver.disconnect();
                morphObserver = null;
            }
        },
    };
};
