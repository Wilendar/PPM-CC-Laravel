/**
 * ETAP_06 FAZA 6.5: Resizable Table Columns with localStorage persistence
 * Usage: Add data-resizable-table to table element
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('resizableTable', (tableId = 'import-table') => ({
        columnWidths: {},
        isResizing: false,
        currentColumn: null,
        startX: 0,
        startWidth: 0,
        storageKey: `ppm_column_widths_${tableId}`,

        init() {
            this.loadWidths();
            this.applyWidths();

            // Re-apply after Livewire updates
            document.addEventListener('livewire:navigated', () => this.applyWidths());

            // Handle Livewire refresh
            Livewire.hook('morph.updated', ({ el }) => {
                if (el.closest('[data-resizable-table]')) {
                    this.applyWidths();
                }
            });
        },

        loadWidths() {
            try {
                const saved = localStorage.getItem(this.storageKey);
                if (saved) {
                    this.columnWidths = JSON.parse(saved);
                }
            } catch (e) {
                console.warn('Failed to load column widths:', e);
                this.columnWidths = {};
            }
        },

        saveWidths() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(this.columnWidths));
            } catch (e) {
                console.warn('Failed to save column widths:', e);
            }
        },

        applyWidths() {
            this.$nextTick(() => {
                const table = this.$el.querySelector('table') || this.$el;
                const headers = table.querySelectorAll('th[data-column-id]');

                headers.forEach(th => {
                    const columnId = th.dataset.columnId;
                    if (this.columnWidths[columnId]) {
                        th.style.width = `${this.columnWidths[columnId]}px`;
                        th.style.minWidth = `${this.columnWidths[columnId]}px`;
                    }
                });
            });
        },

        startResize(e, columnId) {
            e.preventDefault();
            this.isResizing = true;
            this.currentColumn = columnId;
            this.startX = e.pageX;

            const th = e.target.closest('th');
            this.startWidth = th.offsetWidth;

            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';

            document.addEventListener('mousemove', this.doResize.bind(this));
            document.addEventListener('mouseup', this.stopResize.bind(this));
        },

        doResize(e) {
            if (!this.isResizing) return;

            const diff = e.pageX - this.startX;
            const newWidth = Math.max(50, Math.min(500, this.startWidth + diff));

            const th = this.$el.querySelector(`th[data-column-id="${this.currentColumn}"]`);
            if (th) {
                th.style.width = `${newWidth}px`;
                th.style.minWidth = `${newWidth}px`;
                this.columnWidths[this.currentColumn] = newWidth;
            }
        },

        stopResize() {
            if (!this.isResizing) return;

            this.isResizing = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';

            this.saveWidths();

            document.removeEventListener('mousemove', this.doResize.bind(this));
            document.removeEventListener('mouseup', this.stopResize.bind(this));
        },

        resetWidths() {
            this.columnWidths = {};
            localStorage.removeItem(this.storageKey);

            const table = this.$el.querySelector('table') || this.$el;
            const headers = table.querySelectorAll('th[data-column-id]');

            headers.forEach(th => {
                th.style.width = '';
                th.style.minWidth = '';
            });
        }
    }));
});
