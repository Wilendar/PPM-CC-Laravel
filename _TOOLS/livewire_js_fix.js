// LIVEWIRE SHARED HOSTING FIX - JavaScript Override
// This script fixes Livewire asset loading by intercepting script requests

(function() {
    'use strict';
    
    console.log('🔧 Livewire Shared Hosting Fix - Loading...');
    
    // Override document.createElement to catch script creation
    const originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        const element = originalCreateElement.call(this, tagName);
        
        if (tagName.toLowerCase() === 'script') {
            // Override src setter to fix Livewire URLs
            let originalSrc = element.src;
            Object.defineProperty(element, 'src', {
                get: function() {
                    return originalSrc;
                },
                set: function(value) {
                    // Fix Livewire asset URLs
                    if (value.includes('/vendor/livewire/livewire.min.js')) {
                        console.log('🔧 Fixing Livewire URL:', value);
                        value = value.replace(
                            /\/vendor\/livewire\/livewire\.min\.js(\?.*)?$/,
                            '/public/vendor/livewire/livewire.min.js'
                        );
                        console.log('✅ Fixed Livewire URL:', value);
                    }
                    originalSrc = value;
                    return originalSrc;
                }
            });
        }
        
        return element;
    };
    
    // Also intercept script tags that may already exist
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔍 Checking existing script tags...');
        
        const scripts = document.querySelectorAll('script[src*="livewire.min.js"]');
        scripts.forEach(function(script, index) {
            const originalSrc = script.src;
            
            if (originalSrc.includes('/vendor/livewire/livewire.min.js')) {
                console.log('🔧 Found problematic Livewire script:', originalSrc);
                
                // Create new script with corrected URL
                const newScript = document.createElement('script');
                const fixedSrc = originalSrc.replace(
                    /\/vendor\/livewire\/livewire\.min\.js(\?.*)?$/,
                    '/public/vendor/livewire/livewire.min.js'
                );
                
                // Copy attributes
                Array.from(script.attributes).forEach(attr => {
                    if (attr.name !== 'src') {
                        newScript.setAttribute(attr.name, attr.value);
                    }
                });
                
                newScript.src = fixedSrc;
                
                console.log('✅ Creating fixed Livewire script:', fixedSrc);
                
                // Replace the old script
                script.parentNode.insertBefore(newScript, script);
                script.parentNode.removeChild(script);
                
                console.log('🎉 Livewire script replaced successfully!');
            }
        });
    });
    
    console.log('✅ Livewire Shared Hosting Fix - Ready');
})();
