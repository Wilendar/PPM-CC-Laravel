# ====================================================================================
# AUTOMATED LOGIN TESTER FOR PPM-CC-LARAVEL
# ====================================================================================
# Narzędzie do automated browser testing systemu logowania PPM
# Działá jak prawdziwy user - otwiera przeglądarke, loguje sie i raportuje problemy
#
# Author: Claude Code Deployment Specialist
# Version: 1.0
# Date: 2025-09-10
# ====================================================================================

param(
    [string]$TestUrl = "https://ppm.mpptrade.pl/login",
    [string]$Email = "admin@mpptrade.pl", 
    [string]$Password = "Admin123!MPP",
    [switch]$Headless = $false,
    [switch]$FullDebug = $false,
    [string]$OutputDir = "$PSScriptRoot\..\test_results"
)

# ====================================================================================
# CONFIGURATION
# ====================================================================================

$ErrorActionPreference = "Continue"
$ProgressPreference = "Continue"

# Test Results Directory
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$LogFile = "$OutputDir\login_test_$Timestamp.log"
$ScreenshotDir = "$OutputDir\screenshots_$Timestamp"

if (-not (Test-Path $ScreenshotDir)) {
    New-Item -ItemType Directory -Path $ScreenshotDir -Force | Out-Null
}

# ====================================================================================
# LOGGING FUNCTIONS
# ====================================================================================

function Write-TestLog {
    param([string]$Message, [string]$Level = "INFO")
    
    $LogMessage = "[$(Get-Date -Format 'HH:mm:ss')] [$Level] $Message"
    Write-Host $LogMessage -ForegroundColor $(
        switch ($Level) {
            "ERROR" { "Red" }
            "WARN" { "Yellow" }
            "SUCCESS" { "Green" }
            "DEBUG" { "Cyan" }
            default { "White" }
        }
    )
    
    Add-Content -Path $LogFile -Value $LogMessage -Encoding UTF8
}

function Take-Screenshot {
    param([string]$Name, [object]$Driver)
    
    try {
        $ScreenshotPath = "$ScreenshotDir\$Name.png"
        $Screenshot = $Driver.GetScreenshot()
        $Screenshot.SaveAsFile($ScreenshotPath, [OpenQA.Selenium.ScreenshotImageFormat]::Png)
        Write-TestLog "Screenshot saved: $Name.png" "DEBUG"
        return $ScreenshotPath
    }
    catch {
        Write-TestLog "Failed to take screenshot: $_" "WARN"
        return $null
    }
}

# ====================================================================================
# SELENIUM SETUP
# ====================================================================================

function Initialize-WebDriver {
    Write-TestLog "Initializing WebDriver..." "INFO"
    
    try {
        # Try to import Selenium module
        Import-Module Selenium -ErrorAction Stop
        Write-TestLog "Selenium module imported successfully" "SUCCESS"
    }
    catch {
        Write-TestLog "Selenium module not found. Installing..." "WARN"
        try {
            Install-Module Selenium -Force -Scope CurrentUser
            Import-Module Selenium
            Write-TestLog "Selenium installed and imported" "SUCCESS"
        }
        catch {
            Write-TestLog "Failed to install Selenium: $_" "ERROR"
            return $null
        }
    }
    
    try {
        # Initialize Chrome driver
        $ChromeOptions = New-Object OpenQA.Selenium.Chrome.ChromeOptions
        $ChromeOptions.AddArgument("--no-sandbox")
        $ChromeOptions.AddArgument("--disable-dev-shm-usage")
        $ChromeOptions.AddArgument("--disable-web-security")
        $ChromeOptions.AddArgument("--allow-running-insecure-content")
        
        if ($Headless) {
            $ChromeOptions.AddArgument("--headless")
            Write-TestLog "Running in headless mode" "INFO"
        }
        
        if ($FullDebug) {
            $ChromeOptions.AddArgument("--enable-logging")
            $ChromeOptions.AddArgument("--log-level=0")
        }
        
        $Driver = New-Object OpenQA.Selenium.Chrome.ChromeDriver($ChromeOptions)
        $Driver.Manage().Window.Maximize()
        $Driver.Manage().Timeouts().ImplicitWait = [TimeSpan]::FromSeconds(10)
        
        Write-TestLog "Chrome WebDriver initialized successfully" "SUCCESS"
        return $Driver
    }
    catch {
        Write-TestLog "Failed to initialize Chrome driver: $_" "ERROR"
        
        # Try Edge as fallback
        try {
            Write-TestLog "Trying Edge driver as fallback..." "INFO"
            $EdgeOptions = New-Object Microsoft.Edge.SeleniumTools.EdgeOptions
            $EdgeOptions.UseChromium = $true
            
            if ($Headless) {
                $EdgeOptions.AddArgument("--headless")
            }
            
            $Driver = New-Object Microsoft.Edge.SeleniumTools.EdgeDriver($EdgeOptions)
            $Driver.Manage().Window.Maximize()
            $Driver.Manage().Timeouts().ImplicitWait = [TimeSpan]::FromSeconds(10)
            
            Write-TestLog "Edge WebDriver initialized successfully" "SUCCESS"
            return $Driver
        }
        catch {
            Write-TestLog "Failed to initialize Edge driver: $_" "ERROR"
            return $null
        }
    }
}

# ====================================================================================
# JAVASCRIPT ERROR DETECTION
# ====================================================================================

function Get-JavaScriptErrors {
    param([object]$Driver)
    
    try {
        # Get browser logs
        $LogEntries = $Driver.Manage().Logs.GetLog("browser")
        $JavaScriptErrors = @()
        
        foreach ($LogEntry in $LogEntries) {
            if ($LogEntry.Level -eq [OpenQA.Selenium.LogLevel]::Severe -or 
                $LogEntry.Message -like "*error*" -or
                $LogEntry.Message -like "*Uncaught*" -or
                $LogEntry.Message -like "*SyntaxError*") {
                
                $JavaScriptErrors += [PSCustomObject]@{
                    Timestamp = $LogEntry.Timestamp
                    Level = $LogEntry.Level
                    Message = $LogEntry.Message
                }
            }
        }
        
        return $JavaScriptErrors
    }
    catch {
        Write-TestLog "Could not retrieve browser logs: $_" "WARN"
        return @()
    }
}

# ====================================================================================
# NETWORK MONITORING
# ====================================================================================

function Get-NetworkRequests {
    param([object]$Driver)
    
    try {
        # Execute JavaScript to get network requests
        $NetworkScript = @"
            var performance = window.performance || window.webkitPerformance || window.msPerformance || window.mozPerformance;
            if (performance && performance.getEntriesByType) {
                var resources = performance.getEntriesByType('resource');
                return resources.map(function(r) {
                    return {
                        name: r.name,
                        startTime: r.startTime,
                        duration: r.duration,
                        transferSize: r.transferSize || 0,
                        status: 'unknown'
                    };
                });
            }
            return [];
"@
        
        $NetworkData = $Driver.ExecuteScript($NetworkScript)
        return $NetworkData
    }
    catch {
        Write-TestLog "Could not retrieve network data: $_" "WARN"
        return @()
    }
}

# ====================================================================================
# MAIN TEST FUNCTIONS
# ====================================================================================

function Test-LoginPage {
    param([object]$Driver)
    
    Write-TestLog "=== TESTING LOGIN PAGE ===" "INFO"
    
    # Navigate to login page
    Write-TestLog "Navigating to: $TestUrl" "INFO"
    $Driver.Navigate().GoToUrl($TestUrl)
    
    Start-Sleep -Seconds 3
    Take-Screenshot "01_login_page_loaded" $Driver
    
    # Check page title
    $PageTitle = $Driver.Title
    Write-TestLog "Page title: $PageTitle" "INFO"
    
    # Check for JavaScript errors
    $JSErrors = Get-JavaScriptErrors $Driver
    if ($JSErrors.Count -gt 0) {
        Write-TestLog "FOUND $($JSErrors.Count) JAVASCRIPT ERRORS:" "ERROR"
        foreach ($Error in $JSErrors) {
            Write-TestLog "  - $($Error.Message)" "ERROR"
        }
    } else {
        Write-TestLog "No JavaScript errors detected on page load" "SUCCESS"
    }
    
    # Check for key elements
    $Elements = @{
        "Email Field" = "input[type='email'], input[name='email'], #email"
        "Password Field" = "input[type='password'], input[name='password'], #password"  
        "Submit Button" = "button[type='submit'], input[type='submit'], .btn-login"
        "Login Form" = "form"
    }
    
    $ElementStatus = @{}
    foreach ($ElementName in $Elements.Keys) {
        try {
            $Element = $Driver.FindElement([OpenQA.Selenium.By]::CssSelector($Elements[$ElementName]))
            $ElementStatus[$ElementName] = $true
            Write-TestLog "✓ Found: $ElementName" "SUCCESS"
        }
        catch {
            $ElementStatus[$ElementName] = $false
            Write-TestLog "✗ Missing: $ElementName" "ERROR"
        }
    }
    
    return $ElementStatus
}

function Test-FormSubmission {
    param([object]$Driver)
    
    Write-TestLog "=== TESTING FORM SUBMISSION ===" "INFO"
    
    try {
        # Find and fill email field
        Write-TestLog "Finding email field..." "INFO"
        $EmailField = $Driver.FindElement([OpenQA.Selenium.By]::CssSelector("input[type='email'], input[name='email'], #email"))
        $EmailField.Clear()
        $EmailField.SendKeys($Email)
        Write-TestLog "✓ Email field filled: $Email" "SUCCESS"
        
        Start-Sleep -Seconds 1
        Take-Screenshot "02_email_filled" $Driver
        
        # Find and fill password field
        Write-TestLog "Finding password field..." "INFO"
        $PasswordField = $Driver.FindElement([OpenQA.Selenium.By]::CssSelector("input[type='password'], input[name='password'], #password"))
        $PasswordField.Clear()
        $PasswordField.SendKeys($Password)
        Write-TestLog "✓ Password field filled" "SUCCESS"
        
        Start-Sleep -Seconds 1
        Take-Screenshot "03_form_filled" $Driver
        
        # Check for CSRF token
        try {
            $CSRFToken = $Driver.FindElement([OpenQA.Selenium.By]::CssSelector("input[name='_token']"))
            Write-TestLog "✓ CSRF token found: $($CSRFToken.GetAttribute('value').Substring(0,20))..." "SUCCESS"
        }
        catch {
            Write-TestLog "⚠ No CSRF token found" "WARN"
        }
        
        # Submit form
        Write-TestLog "Submitting form..." "INFO"
        
        # Get initial URL for comparison
        $InitialUrl = $Driver.Url
        
        # Try to find and click submit button
        try {
            $SubmitButton = $Driver.FindElement([OpenQA.Selenium.By]::CssSelector("button[type='submit'], input[type='submit'], .btn-login"))
            $SubmitButton.Click()
            Write-TestLog "✓ Submit button clicked" "SUCCESS"
        }
        catch {
            # Fallback: submit form directly
            Write-TestLog "Submit button not found, submitting form directly" "WARN"
            $Form = $Driver.FindElement([OpenQA.Selenium.By]::CssSelector("form"))
            $Form.Submit()
        }
        
        # Wait for response
        Start-Sleep -Seconds 5
        Take-Screenshot "04_after_submit" $Driver
        
        # Check what happened
        $FinalUrl = $Driver.Url
        Write-TestLog "Initial URL: $InitialUrl" "INFO"
        Write-TestLog "Final URL: $FinalUrl" "INFO"
        
        # Check for JavaScript errors after submission
        $PostSubmitErrors = Get-JavaScriptErrors $Driver
        if ($PostSubmitErrors.Count -gt 0) {
            Write-TestLog "JAVASCRIPT ERRORS AFTER SUBMIT:" "ERROR"
            foreach ($Error in $PostSubmitErrors) {
                Write-TestLog "  - $($Error.Message)" "ERROR"
            }
        }
        
        # Analyze result
        if ($FinalUrl -ne $InitialUrl) {
            if ($FinalUrl -like "*/admin*" -or $FinalUrl -like "*/dashboard*") {
                Write-TestLog "✓ LOGIN SUCCESS - Redirected to admin/dashboard" "SUCCESS"
                return "SUCCESS"
            } else {
                Write-TestLog "? LOGIN REDIRECT - Redirected but not to admin: $FinalUrl" "WARN"
                return "REDIRECT"
            }
        } else {
            # Check for error messages
            try {
                $ErrorMessages = $Driver.FindElements([OpenQA.Selenium.By]::CssSelector(".alert-danger, .error, .invalid-feedback"))
                if ($ErrorMessages.Count -gt 0) {
                    Write-TestLog "✗ LOGIN FAILED - Error messages found:" "ERROR"
                    foreach ($ErrorMsg in $ErrorMessages) {
                        if ($ErrorMsg.Displayed) {
                            Write-TestLog "  - $($ErrorMsg.Text)" "ERROR"
                        }
                    }
                    return "FAILED"
                } else {
                    Write-TestLog "? LOGIN UNCLEAR - No redirect, no error messages" "WARN"
                    return "UNCLEAR"
                }
            }
            catch {
                Write-TestLog "? LOGIN UNCLEAR - Could not determine result" "WARN"
                return "UNCLEAR"
            }
        }
    }
    catch {
        Write-TestLog "✗ FORM SUBMISSION FAILED: $_" "ERROR"
        Take-Screenshot "05_submission_error" $Driver
        return "ERROR"
    }
}

# ====================================================================================
# MAIN EXECUTION
# ====================================================================================

function Start-LoginTest {
    Write-TestLog "PPM-CC-LARAVEL AUTOMATED LOGIN TESTER STARTED" "INFO"
    Write-TestLog "=============================================" "INFO"
    Write-TestLog "Test URL: $TestUrl" "INFO"
    Write-TestLog "Email: $Email" "INFO"  
    Write-TestLog "Password: [HIDDEN]" "INFO"
    Write-TestLog "Headless: $Headless" "INFO"
    Write-TestLog "Full Debug: $FullDebug" "INFO"
    Write-TestLog "Output Directory: $OutputDir" "INFO"
    Write-TestLog "=============================================" "INFO"
    
    # Initialize WebDriver
    $Driver = Initialize-WebDriver
    if (-not $Driver) {
        Write-TestLog "✗ FATAL: Could not initialize WebDriver" "ERROR"
        return
    }
    
    try {
        # Test login page
        $ElementStatus = Test-LoginPage $Driver
        
        # Test form submission if page loaded correctly
        if ($ElementStatus["Email Field"] -and $ElementStatus["Password Field"] -and $ElementStatus["Submit Button"]) {
            $LoginResult = Test-FormSubmission $Driver
            Write-TestLog "FINAL LOGIN RESULT: $LoginResult" "INFO"
        } else {
            Write-TestLog "✗ SKIPPING FORM TEST - Required elements missing" "ERROR"
        }
        
        # Get final network data
        $NetworkRequests = Get-NetworkRequests $Driver
        Write-TestLog "Network requests recorded: $($NetworkRequests.Count)" "INFO"
        
        # Generate final report
        Generate-TestReport $ElementStatus $LoginResult $NetworkRequests
        
    }
    finally {
        # Cleanup
        if ($Driver) {
            Write-TestLog "Closing WebDriver..." "INFO"
            $Driver.Quit()
        }
    }
    
    Write-TestLog "=============================================" "INFO"
    Write-TestLog "LOGIN TEST COMPLETED" "INFO"
    Write-TestLog "Check results in: $OutputDir" "INFO"
    Write-TestLog "=============================================" "INFO"
}

function Generate-TestReport {
    param($ElementStatus, $LoginResult, $NetworkRequests)
    
    $ReportPath = "$OutputDir\test_report_$Timestamp.html"
    
    $HTMLReport = @"
<!DOCTYPE html>
<html>
<head>
    <title>PPM Login Test Report - $Timestamp</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warn { color: orange; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>PPM-CC-Laravel Login Test Report</h1>
    <p><strong>Test Time:</strong> $(Get-Date)</p>
    <p><strong>Test URL:</strong> $TestUrl</p>
    <p><strong>Test Email:</strong> $Email</p>
    
    <div class="section">
        <h2>Element Status</h2>
        <table>
            <tr><th>Element</th><th>Status</th></tr>
"@

    foreach ($Element in $ElementStatus.Keys) {
        $Status = if ($ElementStatus[$Element]) { "<span class='success'>✓ Found</span>" } else { "<span class='error'>✗ Missing</span>" }
        $HTMLReport += "<tr><td>$Element</td><td>$Status</td></tr>"
    }
    
    $HTMLReport += @"
        </table>
    </div>
    
    <div class="section">
        <h2>Login Result</h2>
        <p><strong>Result:</strong> <span class="$(if($LoginResult -eq 'SUCCESS'){'success'}else{'error'})">$LoginResult</span></p>
    </div>
    
    <div class="section">
        <h2>Screenshots</h2>
        <p>Screenshots saved in: $ScreenshotDir</p>
    </div>
    
    <div class="section">  
        <h2>Log File</h2>
        <p>Detailed logs: $LogFile</p>
    </div>
    
</body>
</html>
"@

    Set-Content -Path $ReportPath -Value $HTMLReport -Encoding UTF8
    Write-TestLog "HTML report generated: $ReportPath" "SUCCESS"
}

# ====================================================================================
# EXECUTE TEST
# ====================================================================================

Start-LoginTest

Write-Host ""
Write-Host "=== TEST COMPLETED ===" -ForegroundColor Green
Write-Host "Log file: $LogFile" -ForegroundColor Cyan
Write-Host "Screenshots: $ScreenshotDir" -ForegroundColor Cyan
Write-Host "Results directory: $OutputDir" -ForegroundColor Cyan