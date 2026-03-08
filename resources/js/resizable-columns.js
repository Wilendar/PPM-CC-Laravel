/**
 * ETAP_06 FAZA 6.5: Resizable Table Columns with localStorage persistence
 * Usage: Add data-resizable-table to table element
 */

document.addEventListener('alpine:init', () => {
    /**
     * Scroll detector for sticky column shadows.
     * Toggles .has-scroll-left / .has-scroll-right on the scroll container.
     */
    Alpine.data('scrollDetector', () => ({
        init() {
            this.$nextTick(() => this.checkScroll());
            this.$el.addEventListener('scroll', () => this.checkScroll(), { passive: true });
            window.addEventListener('resize', () => this.checkScroll(), { passive: true });
        },
        checkScroll() {
            const el = this.$el;
            const hasLeft = el.scrollLeft > 0;
            const hasRight = el.scrollLeft < (el.scrollWidth - el.clientWidth - 1);
            el.classList.toggle('has-scroll-left', hasLeft);
            el.classList.toggle('has-scroll-right', hasRight);
        }
    }));

    Alpine.data('resizableTable', (tableId = 'import-table') => ({
        columnWidths: {},
        isResizing: false,
        currentColumn: null,
        startX: 0,
        startWidth: 0,
        storageKey: `ppm_column_widths_${tableId}`,

        init() {
            this._boundDoResize = this.doResize.bind(this);
            this._boundStopResize = this.stopResize.bind(this);

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
                    // Always clear min-width to allow shrinking
                    th.style.minWidth = '0';
                    if (this.columnWidths[columnId]) {
                        th.style.width = `${this.columnWidths[columnId]}px`;
                    }
                });

                this.updateStickyOffsets();
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

            document.addEventListener('mousemove', this._boundDoResize);
            document.addEventListener('mouseup', this._boundStopResize);
        },

        doResize(e) {
            if (!this.isResizing) return;

            const diff = e.pageX - this.startX;
            const newWidth = Math.max(60, Math.min(500, this.startWidth + diff));

            const th = this.$el.querySelector(`th[data-column-id="${this.currentColumn}"]`);
            if (th) {
                th.style.width = `${newWidth}px`;
                th.style.minWidth = '0';
                this.columnWidths[this.currentColumn] = newWidth;
            }
        },

        stopResize() {
            if (!this.isResizing) return;

            this.isResizing = false;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';

            this.saveWidths();
            this.updateStickyOffsets();

            document.removeEventListener('mousemove', this._boundDoResize);
            document.removeEventListener('mouseup', this._boundStopResize);
        },

        updateStickyOffsets() {
            this.$nextTick(() => {
                const table = this.$el.querySelector('table') || this.$el;

                // Recalculate LEFT sticky offsets
                const stickyLeftThs = table.querySelectorAll('thead th.import-table-sticky-left');
                let leftOffset = 0;
                stickyLeftThs.forEach(th => {
                    th.style.left = leftOffset + 'px';
                    const colIndex = [...th.parentElement.children].indexOf(th);
                    table.querySelectorAll('tbody tr').forEach(tr => {
                        const td = tr.children[colIndex];
                        if (td && td.classList.contains('import-table-sticky-left')) {
                            td.style.left = leftOffset + 'px';
                        }
                    });
                    leftOffset += th.offsetWidth;
                });

                // Recalculate RIGHT sticky offsets (reverse order: last column first)
                const stickyRightThs = [...table.querySelectorAll('thead th.import-table-sticky-right')].reverse();
                let rightOffset = 0;
                stickyRightThs.forEach(th => {
                    th.style.right = rightOffset + 'px';
                    const colIndex = [...th.parentElement.children].indexOf(th);
                    table.querySelectorAll('tbody tr').forEach(tr => {
                        const td = tr.children[colIndex];
                        if (td && td.classList.contains('import-table-sticky-right')) {
                            td.style.right = rightOffset + 'px';
                        }
                    });
                    rightOffset += th.offsetWidth;
                });
            });
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

            // Reset inline sticky offsets back to CSS defaults
            table.querySelectorAll('.import-table-sticky-left, .import-table-sticky-right').forEach(el => {
                el.style.left = '';
                el.style.right = '';
            });
        }
    }));
});
