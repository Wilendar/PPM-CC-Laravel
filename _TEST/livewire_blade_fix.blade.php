{{-- LIVEWIRE SHARED HOSTING FIX - Blade Template Override --}}
{{-- Add this to your main layout before Livewire scripts --}}

<script>
// LIVEWIRE SHARED HOSTING FIX - Inline JavaScript
console.log('🔧 PPM-CC-Laravel: Livewire Shared Hosting Fix Active');

// Method 1: Replace existing script tags on page load
document.addEventListener('DOMContentLoaded', function() {
    const scripts = document.querySelectorAll('script[src*="vendor/livewire/livewire.min.js"]');
    
    scripts.forEach(function(script) {
        if (script.src.includes('/vendor/livewire/livewire.min.js')) {
            console.log('🔧 Fixing Livewire script URL:', script.src);
            
            // Create replacement script
            const newScript = document.createElement('script');
            const fixedUrl = script.src.replace(
                /\/vendor\/livewire\/livewire\.min\.js.*$/,
                '/public/vendor/livewire/livewire.min.js'
            );
            
            // Copy all attributes except src
            for (let attr of script.attributes) {
                if (attr.name !== 'src') {
                    newScript.setAttribute(attr.name, attr.value);
                }
            }
            
            newScript.src = fixedUrl;
            newScript.onload = function() {
                console.log('✅ Fixed Livewire script loaded successfully!');
            };
            newScript.onerror = function() {
                console.error('❌ Failed to load fixed Livewire script');
            };
            
            // Replace script
            script.parentNode.insertBefore(newScript, script);
            script.remove();
            
            console.log('✅ Livewire script URL fixed:', fixedUrl);
        }
    });
});

// Method 2: Directly load working Livewire script
(function() {
    const workingLivewireUrl = '/public/vendor/livewire/livewire.min.js';
    
    // Test if the working URL is accessible
    fetch(workingLivewireUrl, { method: 'HEAD' })
        .then(response => {
            if (response.ok) {
                console.log('✅ Working Livewire URL confirmed:', workingLivewireUrl);
                
                // Only load if Livewire is not already loaded
                if (typeof window.Livewire === 'undefined') {
                    const script = document.createElement('script');
                    script.src = workingLivewireUrl;
                    script.onload = function() {
                        console.log('🎉 Livewire loaded via fixed URL!');
                    };
                    document.head.appendChild(script);
                }
            }
        })
        .catch(error => {
            console.error('❌ Working Livewire URL not accessible:', error);
        });
})();
</script>
