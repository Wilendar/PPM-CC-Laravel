import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist'

Alpine.plugin(persist)

// Theme store
Alpine.store('theme', {
    dark: Alpine.$persist(false),
    
    toggle() {
        this.dark = !this.dark;
        this.updateDOM();
    },
    
    updateDOM() {
        if (this.dark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
});

// Loading store - CRITICAL FIX for JavaScript errors
Alpine.store('loading', {
    isLoading: false,
    loadingText: 'Loading...',
    
    show(text = 'Loading...') {
        this.isLoading = true;
        this.loadingText = text;
    },
    
    hide() {
        this.isLoading = false;
    }
});

// Notifications store - CRITICAL FIX for JavaScript errors  
Alpine.store('notifications', {
    items: [],
    nextId: 1,
    
    add(message, type = 'info', duration = 5000) {
        const notification = {
            id: this.nextId++,
            message,
            type,
            timestamp: Date.now()
        };
        
        this.items.push(notification);
        
        if (duration > 0) {
            setTimeout(() => {
                this.remove(notification.id);
            }, duration);
        }
        
        return notification;
    },
    
    remove(id) {
        const index = this.items.findIndex(item => item.id === id);
        if (index > -1) {
            this.items.splice(index, 1);
        }
    },
    
    clear() {
        this.items = [];
    },
    
    // Helper methods for different notification types
    success(message, duration = 5000) {
        return this.add(message, 'success', duration);
    },
    
    error(message, duration = 0) { // Errors don't auto-dismiss
        return this.add(message, 'error', duration);
    },
    
    warning(message, duration = 7000) {
        return this.add(message, 'warning', duration);
    },
    
    info(message, duration = 5000) {
        return this.add(message, 'info', duration);
    }
});

// Product search component
Alpine.data('productSearch', () => ({
    search: '',
    suggestions: [],
    loading: false,
    
    async fetchSuggestions() {
        if (this.search.length < 2) {
            this.suggestions = [];
            return;
        }
        
        this.loading = true;
        
        try {
            const response = await fetch('/api/products/search?q=' + encodeURIComponent(this.search));
            const data = await response.json();
            this.suggestions = data.suggestions || [];
        } catch (error) {
            console.error('Search error:', error);
            Alpine.store('notifications').error('Search failed: ' + error.message);
        } finally {
            this.loading = false;
        }
    }
}));

// Global error handler for better debugging
window.addEventListener('error', (event) => {
    console.error('Global JavaScript error:', event.error);
    if (window.Alpine && Alpine.store('notifications')) {
        Alpine.store('notifications').error('An error occurred: ' + event.error.message);
    }
});

// Alpine.js initialization with debugging
document.addEventListener('alpine:init', () => {
    console.log('Alpine.js stores initialized:', {
        loading: Alpine.store('loading'),
        notifications: Alpine.store('notifications'),
        theme: Alpine.store('theme')
    });
});

window.Alpine = Alpine;
Alpine.start();