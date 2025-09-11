# PowerShell Script - JAVASCRIPT LIVEWIRE URL OVERRIDE
# FILE: javascript_livewire_fix.ps1  
# PURPOSE: Override Livewire JS loading via client-side JavaScript

param(
    [switch]$Test = $false
)

$Host.UI.RawUI.WindowTitle = "PPM-CC-Laravel - JavaScript Livewire Fix"

Write-Host "🎯 JAVASCRIPT LIVEWIRE URL OVERRIDE" -ForegroundColor Green
Write-Host "Final approach: Client-side JavaScript fix for Livewire loading" -ForegroundColor Yellow
Write-Host ""

function Create-JavaScriptFix {
    Write-Host "📝 Creating JavaScript Livewire URL override..." -ForegroundColor Yellow
    
    # Create JavaScript that intercepts and fixes Livewire loading
    $JavaScriptFix = @"
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
"@

    $JavaScriptFix | Out-File -FilePath "livewire_js_fix.js" -Encoding UTF8
    Write-Host "✅ JavaScript fix created" -ForegroundColor Green
}

function Create-BladeTemplate {
    Write-Host "📝 Creating Blade template with JavaScript fix..." -ForegroundColor Yellow
    
    $BladeTemplate = @"
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
"@

    $BladeTemplate | Out-File -FilePath "livewire_blade_fix.blade.php" -Encoding UTF8
    Write-Host "✅ Blade template fix created" -ForegroundColor Green
}

function Test-JavaScriptSolution {
    Write-Host "🧪 Testing JavaScript solution approach..." -ForegroundColor Yellow
    
    # Test if the working URL is accessible  
    $WorkingUrl = "https://ppm.mpptrade.pl/public/vendor/livewire/livewire.min.js"
    
    try {
        $response = Invoke-WebRequest -Uri $WorkingUrl -UseBasicParsing
        
        if ($response.StatusCode -eq 200 -and $response.Headers['Content-Type'] -like "*javascript*") {
            Write-Host "✅ CONFIRMED: Working URL is accessible" -ForegroundColor Green
            Write-Host "URL: $WorkingUrl" -ForegroundColor Cyan
            Write-Host "Content-Type: $($response.Headers['Content-Type'])" -ForegroundColor Cyan
            Write-Host "Content-Length: $($response.Content.Length) bytes" -ForegroundColor Cyan
            
            return $true
        } else {
            Write-Host "❌ Working URL is not accessible" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "❌ Error testing working URL: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Give-ImplementationInstructions {
    Write-Host ""
    Write-Host "📋 IMPLEMENTATION INSTRUCTIONS:" -ForegroundColor Yellow
    Write-Host "================================" -ForegroundColor Gray
    Write-Host ""
    Write-Host "STEP 1: Add JavaScript fix to your main layout" -ForegroundColor Green
    Write-Host "File: resources/views/layouts/app.blade.php" -ForegroundColor Cyan
    Write-Host "Add BEFORE the closing </head> tag:" -ForegroundColor White
    Write-Host ""
    Write-Host "@include('partials.livewire-fix')" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "STEP 2: Create the partial view" -ForegroundColor Green
    Write-Host "File: resources/views/partials/livewire-fix.blade.php" -ForegroundColor Cyan
    Write-Host "Content: Use the generated livewire_blade_fix.blade.php" -ForegroundColor White
    Write-Host ""
    Write-Host "STEP 3: Test in browser" -ForegroundColor Green
    Write-Host "1. Clear browser cache" -ForegroundColor White
    Write-Host "2. Go to: https://ppm.mpptrade.pl/login" -ForegroundColor Cyan
    Write-Host "3. Open Developer Tools Console" -ForegroundColor White
    Write-Host "4. Look for messages: '🔧 PPM-CC-Laravel: Livewire Shared Hosting Fix Active'" -ForegroundColor White
    Write-Host "5. Try login: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "FILES CREATED:" -ForegroundColor Yellow
    Write-Host "- livewire_js_fix.js (standalone JavaScript fix)" -ForegroundColor Gray
    Write-Host "- livewire_blade_fix.blade.php (Blade template)" -ForegroundColor Gray
}

# Main execution
try {
    Write-Host "🚀 Creating JavaScript Livewire fix solution..." -ForegroundColor Green
    
    # Step 1: Create JavaScript fix
    Create-JavaScriptFix
    
    # Step 2: Create Blade template
    Create-BladeTemplate
    
    # Step 3: Test solution viability
    $canWork = Test-JavaScriptSolution
    
    # Step 4: Give implementation instructions
    Give-ImplementationInstructions
    
    if ($canWork) {
        Write-Host ""
        Write-Host "🎉 JAVASCRIPT FIX SOLUTION READY!" -ForegroundColor Green
        Write-Host "This approach bypasses server routing issues by fixing URLs client-side." -ForegroundColor Yellow
    } else {
        Write-Host ""
        Write-Host "⚠️ JavaScript fix created but working URL may not be accessible." -ForegroundColor Red
        Write-Host "Double-check that /public/vendor/livewire/livewire.min.js works." -ForegroundColor Yellow
    }
    
} catch {
    Write-Host "💥 Script failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

if ($Test) {
    Test-JavaScriptSolution
}