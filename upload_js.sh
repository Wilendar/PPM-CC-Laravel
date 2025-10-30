#!/bin/bash

# Upload JS content
cat > resources/js/app.js << 'EOFJS'
import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist'
import focus from '@alpinejs/focus'
import collapse from '@alpinejs/collapse'

Alpine.plugin(persist)
Alpine.plugin(focus)  
Alpine.plugin(collapse)

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
    
    selectSuggestion(suggestion) {
        this.search = suggestion.name;
        this.suggestions = [];
        this.showSuggestions = false;
        this.selectedIndex = -1;
        
        if (this.$wire) {
            this.$wire.search = this.search;
        }
    }
}));

document.addEventListener('alpine:init', () => {
    Alpine.store('theme').init();
    
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.focus();
            }
        }
    });
});

window.Alpine = Alpine;
Alpine.start();
EOFJS