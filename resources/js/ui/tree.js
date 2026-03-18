export default (config = {}) => ({
    nodes: config.nodes || [],
    selectable: config.selectable || false,
    selectionMode: config.selectionMode || 'single',
    expandedKeys: new Set(config.expandedKeys || []),
    selectedKeys: new Set(config.selectedKeys || []),
    filter: config.filter || false,
    filterText: '',

    get flatNodes() {
        const result = [];
        const walk = (nodes, level, parentVisible) => {
            nodes.forEach(node => {
                const hasChildren = node.children && node.children.length > 0;
                const matchesFilter = this._matchesFilter(node);
                const visible = parentVisible && matchesFilter;

                result.push({ node, level, hasChildren, visible });

                if (hasChildren) {
                    const childrenVisible = visible && this.expandedKeys.has(node.key);
                    walk(node.children, level + 1, childrenVisible);
                }
            });
        };
        walk(this.nodes, 0, true);
        return result;
    },

    _matchesFilter(node) {
        if (!this.filterText) return true;
        const text = this.filterText.toLowerCase();

        if (node.label && node.label.toLowerCase().includes(text)) return true;

        if (node.children) {
            return node.children.some(child => this._matchesFilter(child));
        }
        return false;
    },

    isExpanded(key) {
        return this.expandedKeys.has(key);
    },

    isSelected(key) {
        return this.selectedKeys.has(key);
    },

    toggleExpand(key) {
        if (this.expandedKeys.has(key)) {
            this.expandedKeys.delete(key);
        } else {
            this.expandedKeys.add(key);
        }
        this.expandedKeys = new Set(this.expandedKeys);
    },

    toggleSelect(key) {
        if (!this.selectable) return;

        if (this.selectionMode === 'single') {
            if (this.selectedKeys.has(key)) {
                this.selectedKeys = new Set();
            } else {
                this.selectedKeys = new Set([key]);
            }
        } else {
            if (this.selectedKeys.has(key)) {
                this.selectedKeys.delete(key);
            } else {
                this.selectedKeys.add(key);
            }
            this.selectedKeys = new Set(this.selectedKeys);
        }

        this.$dispatch('tree-selection-change', {
            selectedKeys: Array.from(this.selectedKeys),
        });
    },

    onNodeClick(node) {
        if (this.selectable) {
            this.toggleSelect(node.key);
        }
        if (node.children && node.children.length > 0) {
            this.toggleExpand(node.key);
        }
    },

    isVisible(node) {
        return this._matchesFilter(node);
    },

    expandAll() {
        const keys = [];
        const collect = (nodes) => {
            nodes.forEach(node => {
                if (node.children && node.children.length > 0) {
                    keys.push(node.key);
                    collect(node.children);
                }
            });
        };
        collect(this.nodes);
        this.expandedKeys = new Set(keys);
    },

    collapseAll() {
        this.expandedKeys = new Set();
    },
});
