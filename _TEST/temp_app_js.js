import './bootstrap';
import Alpine from 'alpinejs';

// Import Alpine.js plugins for enhanced functionality
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
import collapse from '@alpinejs/collapse'

// Configure Alpine.js plugins
Alpine.plugin(persist)
Alpine.plugin(focus)  
Alpine.plugin(collapse)

// Global Alpine.js stores for PPM application
Alpine.store('theme', {
    dark: Alpine.$persist(false),
    
    toggle() {
        this.dark = !this.dark;
        this.updateDOM();
    },
    
    set(isDark) {
        this.dark = isDark;
        this.updateDOM();
    },
    
    updateDOM() {
        if (this.dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
    
    init() {
        this.updateDOM();
        
        // Listen for system theme changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', (e) => {
            if (!localStorage.getItem('_x_theme_dark')) {
                this.set(e.matches);
            }
        });
    }
});

Alpine.store('notifications', {
    items: [],
    
    add(notification) {
        const id = Date.now();
        this.items.push({
            id,
            type: notification.type || 'info',
            message: notification.message,
            timeout: notification.timeout || 5000
        });
        
        if (notification.timeout !== false) {
            setTimeout(() => this.remove(id), notification.timeout || 5000);
        }
        
        return id;
    },
    
    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
    },
    
    clear() {
        this.items = [];
    }
});

Alpine.store('loading', {
    states: {},
    
    start(key = 'default') {
        this.states[key] = true;
    },
    
    stop(key = 'default') {
        this.states[key] = false;
    },
    
    is(key = 'default') {
        return this.states[key] || false;
    }
});

// Global Alpine.js directives for PPM
Alpine.directive('tooltip', (el, { expression }, { evaluate }) => {
    const content = evaluate(expression);
    
    let tooltip = null;
    
    function showTooltip() {
        if (tooltip) return;
        
        tooltip = document.createElement('div');
        tooltip.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg pointer-events-none';
        tooltip.textContent = content;
        document.body.appendChild(tooltip);
        
        const rect = el.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
        
        setTimeout(() => {
            if (tooltip) tooltip.classList.add('opacity-100');
        }, 10);
    }
    
    function hideTooltip() {
        if (tooltip) {
            document.body.removeChild(tooltip);
            tooltip = null;
        }
    }
    
    el.addEventListener('mouseenter', showTooltip);
    el.addEventListener('mouseleave', hideTooltip);
    el.addEventListener('focus', showTooltip);
    el.addEventListener('blur', hideTooltip);
});

// Utility functions for PPM components
Alpine.data('productSearch', () => ({
    search: '',
    suggestions: [],
    selectedIndex: -1,
    showSuggestions: false,
    loading: false,
    
    async fetchSuggestions() {
        if (this.search.length < 2) {
            this.suggestions = [];
            this.showSuggestions = false;
            return;
        }
        
        this.loading = true;
        
        try {
            const response = await fetch(`/api/products/search?q=${encodeURIComponent(this.search)}&suggestions=true`);
            const data = await response.json();
            
            this.suggestions = data.suggestions || [];
            this.showSuggestions = this.suggestions.length > 0;
            this.selectedIndex = -1;
        } catch (error) {
            console.error('Search suggestions error:', error);
        } finally {
            this.loading = false;
        }
    },
    
    selectNext() {
        if (this.selectedIndex < this.suggestions.length - 1) {
            this.selectedIndex++;
        }
    },
    
    selectPrevious() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
        }
    },
    
    selectCurrent() {
        if (this.selectedIndex >= 0 && this.suggestions[this.selectedIndex]) {
            this.selectSuggestion(this.suggestions[this.selectedIndex]);
        }
    },
    
    selectSuggestion(suggestion) {
        this.search = suggestion.name;
        this.suggestions = [];
        this.showSuggestions = false;
        this.selectedIndex = -1;
        
        // Trigger Livewire search
        this.$wire.search = this.search;
    }
}));

Alpine.data('categoryTree', () => ({
    selectedCategories: [],
    expandedCategories: [],
    
    isExpanded(categoryId) {
        return this.expandedCategories.includes(categoryId);
    },
    
    toggleExpand(categoryId) {
        if (this.isExpanded(categoryId)) {
            this.expandedCategories = this.expandedCategories.filter(id => id !== categoryId);
        } else {
            this.expandedCategories.push(categoryId);
        }
    },
    
    isSelected(categoryId) {
        return this.selectedCategories.includes(categoryId);
    },
    
    toggleSelection(categoryId) {
        if (this.isSelected(categoryId)) {
            this.selectedCategories = this.selectedCategories.filter(id => id !== categoryId);
        } else {
            this.selectedCategories.push(categoryId);
        }
        
        // Sync with Livewire
        if (this.$wire.selectedCategories !== undefined) {
            this.$wire.selectedCategories = this.selectedCategories;
        }
    }
}));

Alpine.data('imageUploader', () => ({
    images: [],
    uploading: false,
    isDragOver: false,
    maxFiles: 10,
    maxSize: 2048000, // 2MB
    
    init() {
        // Listen for file input changes
        this.$watch('images', () => {
            if (this.$wire && this.$wire.images !== undefined) {
                this.$wire.images = this.images;
            }
        });
    },
    
    handleDrop(event) {
        this.isDragOver = false;
        const files = Array.from(event.dataTransfer.files);
        this.handleFiles(files);
    },
    
    handleFiles(files) {
        if (this.images.length + files.length > this.maxFiles) {
            this.$store.notifications.add({
                type: 'error',
                message: `Maksymalnie ${this.maxFiles} zdjęć.`
            });
            return;
        }
        
        this.uploading = true;
        
        files.forEach(file => {
            if (!file.type.startsWith('image/')) {
                this.$store.notifications.add({
                    type: 'error',
                    message: `${file.name} nie jest obrazem.`
                });
                return;
            }
            
            if (file.size > this.maxSize) {
                this.$store.notifications.add({
                    type: 'error',
                    message: `${file.name} jest za duży (max 2MB).`
                });
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                this.images.push({
                    file: file,
                    preview: e.target.result,
                    name: file.name,
                    size: file.size
                });
            };
            reader.readAsDataURL(file);
        });
        
        setTimeout(() => {
            this.uploading = false;
        }, 500);
    },
    
    removeImage(index) {
        this.images.splice(index, 1);
    },
    
    moveImage(from, to) {
        const image = this.images.splice(from, 1)[0];
        this.images.splice(to, 0, image);
    }
}));

Alpine.data('bulkActions', () => ({
    selectedItems: [],
    selectAll: false,
    
    init() {
        this.$watch('selectAll', (value) => {
            if (value) {
                this.selectedItems = this.getAllItemIds();
            } else {
                this.selectedItems = [];
            }
        });
        
        this.$watch('selectedItems', (value) => {
            const allIds = this.getAllItemIds();
            this.selectAll = allIds.length > 0 && value.length === allIds.length;
        });
    },
    
    getAllItemIds() {
        // This should be overridden by the component using this data
        return [];
    },
    
    toggleItem(itemId) {
        if (this.selectedItems.includes(itemId)) {
            this.selectedItems = this.selectedItems.filter(id => id !== itemId);
        } else {
            this.selectedItems.push(itemId);
        }
    },
    
    hasSelection() {
        return this.selectedItems.length > 0;
    },
    
    getSelectionCount() {
        return this.selectedItems.length;
    }
}));

// Livewire integration helpers
Alpine.data('livewireLoading', () => ({
    isLoading(target = null) {
        if (target) {
            return this.$wire.__instance.effects.loading.includes(target);
        }
        return this.$wire.__instance.effects.loading.length > 0;
    }
}));

// Global event listeners
document.addEventListener('alpine:init', () => {
    // Initialize theme store
    Alpine.store('theme').init();
    
    // Global keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to close modals/dropdowns
        if (e.key === 'Escape') {
            Alpine.store('notifications').clear();
        }
    });
    
    // Enhanced error handling for Livewire
    window.addEventListener('livewire:exception', (event) => {
        Alpine.store('notifications').add({
            type: 'error',
            message: 'Wystąpił błąd. Spróbuj ponownie.',
            timeout: 10000
        });
    });
    
    // Success handling for Livewire
    window.addEventListener('livewire:success', (event) => {
        if (event.detail.message) {
            Alpine.store('notifications').add({
                type: 'success',
                message: event.detail.message
            });
        }
    });
});

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();