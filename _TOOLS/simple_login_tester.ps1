# ====================================================================================
# SIMPLE LOGIN TESTER FOR PPM-CC-LARAVEL
# ====================================================================================
# HTTP-based testing tool który testuje login flow bez potrzeby przeglądarki
# Bezpośrednie testowanie HTTP requests, JavaScript assets i responses
#
# Author: Claude Code Deployment Specialist
# Version: 1.0
# Date: 2025-09-10
# ====================================================================================

param(
    [string]$BaseUrl = "https://ppm.mpptrade.pl",
    [string]$Email = "admin@mpptrade.pl",
    [string]$Password = "Admin123!MPP",
    [switch]$DetailedOutput = $true
)

$ErrorActionPreference = "Continue"
$OutputDir = "$PSScriptRoot\..\test_results"

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$LogFile = "$OutputDir\simple_login_test_$Timestamp.log"

# ====================================================================================
# LOGGING FUNCTIONS
# ====================================================================================

function Write-TestLog {
    param([string]$Message, [string]$Level = "INFO")
    
    $LogMessage = "[$(Get-Date -Format 'HH:mm:ss')] [$Level] $Message"
    $Color = switch ($Level) {
        "ERROR" { "Red" }
        "WARN" { "Yellow" }
        "SUCCESS" { "Green" }
        "DEBUG" { "Cyan" }
        default { "White" }
    }
    
    Write-Host $LogMessage -ForegroundColor $Color
    Add-Content -Path $LogFile -Value $LogMessage -Encoding UTF8
}

# ====================================================================================
# TESTING FUNCTIONS
# ====================================================================================

function Test-LoginPageAccess {
    Write-TestLog "=== TESTING LOGIN PAGE ACCESS ===" "INFO"
    
    try {
        $LoginUrl = "$BaseUrl/login"
        Write-TestLog "Testing: $LoginUrl" "INFO"
        
        $Response = Invoke-WebRequest -Uri $LoginUrl -UseBasicParsing -TimeoutSec 15
        
        Write-TestLog "HTTP Status: $($Response.StatusCode)" $(if($Response.StatusCode -eq 200){"SUCCESS"}else{"ERROR"})
        Write-TestLog "Content-Type: $($Response.Headers['Content-Type'])" "DEBUG"
        Write-TestLog "Content Length: $($Response.Content.Length) bytes" "DEBUG"
        
        # Check for key elements in HTML
        $Content = $Response.Content
        
        $Checks = @{
            "Email Input" = 'input.*type=[\"\x27]email[\"\x27]|input.*name=[\"\x27]email[\"\x27]'
            "Password Input" = 'input.*type=[\"\x27]password[\"\x27]|input.*name=[\"\x27]password[\"\x27]'
            "CSRF Token" = 'input.*name=[\"\x27]_token[\"\x27]'
            "Login Form" = '<form.*action.*login'
            "Vite Assets" = '@vite\(|vite\('
            "Livewire" = 'livewire|Livewire'
            "Alpine.js" = 'alpine|Alpine'
        }
        
        $Results = @{}
        foreach ($Check in $Checks.Keys) {
            if ($Content -match $Checks[$Check]) {
                $Results[$Check] = $true
                Write-TestLog "✓ Found: $Check" "SUCCESS"
            } else {
                $Results[$Check] = $false
                Write-TestLog "✗ Missing: $Check" "ERROR"
            }
        }
        
        return $Results
        
    } catch {
        Write-TestLog "Login page test failed: $_" "ERROR"
        return $null
    }
}

function Test-JavaScriptAssets {
    Write-TestLog "=== TESTING JAVASCRIPT ASSETS ===" "INFO"
    
    $Assets = @{
        "Livewire JS" = "$BaseUrl/livewire/livewire.min.js"
        "Vite App JS" = "$BaseUrl/build/assets/app-*.js"
        "Vite App CSS" = "$BaseUrl/build/assets/app-*.css"
        "Vite Manifest" = "$BaseUrl/build/manifest.json"
    }
    
    $Results = @{}
    
    foreach ($Asset in $Assets.Keys) {
        $AssetUrl = $Assets[$Asset]
        Write-TestLog "Testing: $AssetUrl" "INFO"
        
        try {
            if ($AssetUrl -like "*-**") {
                # Handle Vite assets with hashes - need to get actual filename
                Write-TestLog "Vite asset test requires manifest lookup - skipping direct test" "WARN"
                $Results[$Asset] = "UNKNOWN"
                continue
            }
            
            $Response = Invoke-WebRequest -Uri $AssetUrl -UseBasicParsing -TimeoutSec 10
            
            if ($Response.StatusCode -eq 200) {
                $ContentType = $Response.Headers['Content-Type']
                $Size = $Response.Content.Length
                
                Write-TestLog "✓ ${Asset}: $($Response.StatusCode) ($Size bytes, $ContentType)" "SUCCESS"
                
                # Check if it's actually JavaScript/CSS content
                if ($Asset -like "*JS*" -and $Response.Content -like "<*") {
                    Write-TestLog "⚠ WARNING: JS asset returns HTML (likely 404 page)" "WARN"
                    $Results[$Asset] = "HTML_ERROR"
                } elseif ($Asset -like "*CSS*" -and $Response.Content -like "<*") {
                    Write-TestLog "⚠ WARNING: CSS asset returns HTML (likely 404 page)" "WARN"
                    $Results[$Asset] = "HTML_ERROR"
                } else {
                    $Results[$Asset] = "SUCCESS"
                }
                
                # Show first line for debugging
                if ($DetailedOutput) {
                    $FirstLine = ($Response.Content -split "`n")[0].Substring(0, [Math]::Min(100, $Response.Content.Length))
                    Write-TestLog "First 100 chars: $FirstLine" "DEBUG"
                }
                
            } else {
                Write-TestLog "✗ ${Asset}: $($Response.StatusCode)" "ERROR"
                $Results[$Asset] = "HTTP_ERROR"
            }
            
        } catch {
            Write-TestLog "✗ ${Asset}: FAILED - $_" "ERROR"
            $Results[$Asset] = "EXCEPTION"
        }
    }
    
    return $Results
}

function Test-LoginFormSubmission {
    Write-TestLog "=== TESTING LOGIN FORM SUBMISSION ===" "INFO"
    
    try {
        # First get the login page to extract CSRF token
        $LoginUrl = "$BaseUrl/login"
        $LoginPage = Invoke-WebRequest -Uri $LoginUrl -SessionVariable WebSession
        
        if ($LoginPage.StatusCode -ne 200) {
            Write-TestLog "Could not access login page for CSRF token" "ERROR"
            return $null
        }
        
        # Extract CSRF token
        if ($LoginPage.Content -match 'name=[\"\x27]_token[\"\x27].*?value=[\"\x27]([^\"\x27]+)[\"\x27]') {
            $CsrfToken = $Matches[1]
            Write-TestLog "✓ CSRF Token extracted: $($CsrfToken.Substring(0,20))..." "SUCCESS"
        } else {
            Write-TestLog "✗ Could not extract CSRF token" "ERROR"
            return $null
        }
        
        # Prepare form data
        $FormData = @{
            email = $Email
            password = $Password
            _token = $CsrfToken
        }
        
        Write-TestLog "Submitting login form..." "INFO"
        Write-TestLog "Email: $Email" "INFO"
        Write-TestLog "Password: [HIDDEN]" "INFO"
        Write-TestLog "CSRF Token: $($CsrfToken.Substring(0,20))..." "INFO"
        
        # Submit login form
        $LoginResponse = Invoke-WebRequest -Uri $LoginUrl -Method POST -Body $FormData -WebSession $WebSession -MaximumRedirection 0 -ErrorAction SilentlyContinue
        
        Write-TestLog "Login Response Status: $($LoginResponse.StatusCode)" "INFO"
        
        # Analyze response
        if ($LoginResponse.StatusCode -eq 302) {
            $RedirectLocation = $LoginResponse.Headers.Location
            Write-TestLog "✓ LOGIN REDIRECT: $RedirectLocation" "SUCCESS"
            
            if ($RedirectLocation -like "*/admin*" -or $RedirectLocation -like "*/dashboard*") {
                Write-TestLog "✓ REDIRECT TO ADMIN - LOGIN LIKELY SUCCESSFUL" "SUCCESS"
                return "SUCCESS"
            } else {
                Write-TestLog "? REDIRECT ELSEWHERE - May be successful: $RedirectLocation" "WARN"
                return "REDIRECT_OTHER"
            }
            
        } elseif ($LoginResponse.StatusCode -eq 200) {
            # Stayed on same page - check for errors
            if ($LoginResponse.Content -like "*error*" -or $LoginResponse.Content -like "*invalid*") {
                Write-TestLog "✗ LOGIN FAILED - Error messages in response" "ERROR"
                return "FAILED_WITH_ERRORS"
            } else {
                Write-TestLog "? LOGIN UNCLEAR - 200 response but no redirect" "WARN"
                return "UNCLEAR"
            }
            
        } elseif ($LoginResponse.StatusCode -eq 419) {
            Write-TestLog "✗ LOGIN FAILED - CSRF Token Mismatch (419)" "ERROR"
            return "CSRF_ERROR"
            
        } else {
            Write-TestLog "✗ LOGIN FAILED - HTTP $($LoginResponse.StatusCode)" "ERROR"
            return "HTTP_ERROR"
        }
        
    } catch {
        Write-TestLog "Login form submission failed: $_" "ERROR"
        return "EXCEPTION"
    }
}

function Test-PostLoginAccess {
    Write-TestLog "=== TESTING POST-LOGIN ACCESS ===" "INFO"
    
    $AdminUrls = @(
        "$BaseUrl/admin",
        "$BaseUrl/dashboard", 
        "$BaseUrl/admin/dashboard"
    )
    
    $Results = @{}
    
    foreach ($AdminUrl in $AdminUrls) {
        try {
            Write-TestLog "Testing: $AdminUrl" "INFO"
            $Response = Invoke-WebRequest -Uri $AdminUrl -UseBasicParsing -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            if ($Response.StatusCode -eq 200) {
                Write-TestLog "✓ ${AdminUrl}: Accessible (200)" "SUCCESS"
                $Results[$AdminUrl] = "ACCESSIBLE"
            } elseif ($Response.StatusCode -eq 302) {
                Write-TestLog "? ${AdminUrl}: Redirect (302)" "WARN"
                $Results[$AdminUrl] = "REDIRECT"
            } elseif ($Response.StatusCode -eq 401) {
                Write-TestLog "✓ ${AdminUrl}: Requires Authentication (401) - EXPECTED" "SUCCESS"
                $Results[$AdminUrl] = "AUTH_REQUIRED"
            } else {
                Write-TestLog "? ${AdminUrl}: $($Response.StatusCode)" "WARN"
                $Results[$AdminUrl] = "OTHER"
            }
            
        } catch {
            Write-TestLog "✗ ${AdminUrl}: FAILED - $_" "ERROR"
            $Results[$AdminUrl] = "EXCEPTION"
        }
    }
    
    return $Results
}

function Generate-TestReport {
    param($PageTest, $AssetTest, $LoginTest, $AdminTest)
    
    Write-TestLog "=== GENERATING TEST REPORT ===" "INFO"
    
    $ReportPath = "$OutputDir\simple_test_report_$Timestamp.json"
    
    $Report = @{
        timestamp = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
        base_url = $BaseUrl
        test_email = $Email
        results = @{
            page_access = $PageTest
            javascript_assets = $AssetTest
            login_submission = $LoginTest
            admin_access = $AdminTest
        }
        summary = @{
            page_accessible = if($PageTest -and $PageTest.Values -contains $true) { $true } else { $false }
            assets_loading = if($AssetTest -and ($AssetTest.Values -contains "SUCCESS")) { $true } else { $false }
            livewire_issue = if($AssetTest -and $AssetTest["Livewire JS"] -eq "HTML_ERROR") { $true } else { $false }
            login_functional = if($LoginTest -eq "SUCCESS" -or $LoginTest -eq "REDIRECT_OTHER") { $true } else { $false }
        }
    }
    
    $Report | ConvertTo-Json -Depth 5 | Set-Content -Path $ReportPath -Encoding UTF8
    Write-TestLog "Report saved: $ReportPath" "SUCCESS"
    
    return $Report
}

# ====================================================================================
# MAIN EXECUTION
# ====================================================================================

function Start-SimpleLoginTest {
    Write-TestLog "PPM-CC-LARAVEL SIMPLE LOGIN TESTER STARTED" "INFO"
    Write-TestLog "=============================================" "INFO"
    Write-TestLog "Base URL: $BaseUrl" "INFO"
    Write-TestLog "Test Email: $Email" "INFO"
    Write-TestLog "Detailed Output: $DetailedOutput" "INFO"
    Write-TestLog "=============================================" "INFO"
    
    # Test login page access
    $PageTest = Test-LoginPageAccess
    
    # Test JavaScript assets
    $AssetTest = Test-JavaScriptAssets
    
    # Test login form submission
    $LoginTest = Test-LoginFormSubmission
    
    # Test admin access
    $AdminTest = Test-PostLoginAccess
    
    # Generate report
    $Report = Generate-TestReport $PageTest $AssetTest $LoginTest $AdminTest
    
    # Summary
    Write-TestLog "=============================================" "INFO"
    Write-TestLog "TEST SUMMARY" "INFO"
    Write-TestLog "=============================================" "INFO"
    
    if ($Report.summary.page_accessible) {
        Write-TestLog "✓ Login page accessible" "SUCCESS"
    } else {
        Write-TestLog "✗ Login page has issues" "ERROR"
    }
    
    if ($Report.summary.livewire_issue) {
        Write-TestLog "✗ LIVEWIRE.JS RETURNS HTML - THIS IS THE MAIN ISSUE!" "ERROR"
        Write-TestLog "  This causes JavaScript 'expected expression, got <' error" "ERROR"
    } else {
        Write-TestLog "✓ Livewire.js appears to load correctly" "SUCCESS"
    }
    
    if ($Report.summary.login_functional) {
        Write-TestLog "✓ Login submission appears functional" "SUCCESS"
    } else {
        Write-TestLog "✗ Login submission has issues: $LoginTest" "ERROR"
    }
    
    Write-TestLog "=============================================" "INFO"
    Write-TestLog "Log file: $LogFile" "INFO"
    Write-TestLog "Report file: $OutputDir\simple_test_report_$Timestamp.json" "INFO"
    Write-TestLog "=============================================" "INFO"
}

# ====================================================================================
# EXECUTE TEST
# ====================================================================================

Start-SimpleLoginTest

Write-Host ""
Write-Host "=== SIMPLE LOGIN TEST COMPLETED ===" -ForegroundColor Green
Write-Host "Check results above and in log files" -ForegroundColor Cyan