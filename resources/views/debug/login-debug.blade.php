<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>PPM Login Debug - Real-time Monitoring</title>
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <style>
        /* Debug Console Styles */
        .debug-console {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 300px;
            background: #000;
            color: #0f0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-y: scroll;
            z-index: 9999;
            border-top: 3px solid #0f0;
            padding: 10px;
        }
        
        .debug-log {
            margin: 2px 0;
        }
        
        .debug-error {
            color: #ff4444;
        }
        
        .debug-warning {
            color: #ffaa00;
        }
        
        .debug-success {
            color: #44ff44;
        }
        
        .debug-info {
            color: #00aaff;
        }
        
        .debug-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 10000;
        }
        
        .debug-btn {
            background: #333;
            color: #fff;
            border: 1px solid #555;
            padding: 5px 10px;
            margin: 2px;
            cursor: pointer;
            border-radius: 3px;
        }
        
        .debug-status {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 10000;
            background: rgba(0,0,0,0.8);
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
        }
        
        .main-content {
            margin-bottom: 320px;
        }
        
        /* Enhanced form styling for debug */
        .debug-form-container {
            border: 2px solid #007bff;
            border-radius: 10px;
            padding: 20px;
            margin: 20px;
            background: rgba(0,123,255,0.05);
        }
        
        .debug-field {
            position: relative;
        }
        
        .debug-field::after {
            content: "✓";
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #28a745;
            font-weight: bold;
            display: none;
        }
        
        .debug-field.filled::after {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Debug Status Panel -->
    <div class="debug-status" id="debug-status">
        <div><strong>PPM LOGIN DEBUG</strong></div>
        <div>Status: <span id="debug-status-text">Initializing...</span></div>
        <div>JS Errors: <span id="debug-js-errors">0</span></div>
        <div>Network Requests: <span id="debug-network-count">0</span></div>
        <div>Time: <span id="debug-time">00:00</span></div>
    </div>
    
    <!-- Debug Controls -->
    <div class="debug-controls">
        <button class="debug-btn" onclick="debugClearLogs()">Clear Logs</button>
        <button class="debug-btn" onclick="debugExportLogs()">Export Logs</button>
        <button class="debug-btn" onclick="debugToggleConsole()">Toggle Console</button>
        <button class="debug-btn" onclick="debugTestConnectivity()">Test Connection</button>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="w-full sm:max-w-md px-6 py-4">
                <!-- PPM Logo/Header -->
                <div class="text-center mb-6">
                    <div class="text-4xl font-bold text-gray-800 mb-2">🔒 PPM DEBUG</div>
                    <div class="text-lg text-gray-600">Prestashop Product Manager</div>
                    <div class="text-sm text-red-600 font-semibold mt-2">DEBUG MODE - Real-time Monitoring</div>
                </div>
                
                <!-- Debug Login Form -->
                <div class="debug-form-container">
                    <form method="POST" action="{{ route('login') }}" id="debug-login-form">
                        @csrf
                        
                        <!-- Email Address -->
                        <div class="debug-field">
                            <label for="email" class="block font-medium text-sm text-gray-700">Email</label>
                            <input id="email" 
                                   class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" 
                                   type="email" 
                                   name="email" 
                                   value="admin@mpptrade.pl"
                                   required 
                                   autofocus 
                                   autocomplete="username"
                                   onchange="debugFieldChange(this)" />
                            @error('email')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <!-- Password -->
                        <div class="mt-4 debug-field">
                            <label for="password" class="block font-medium text-sm text-gray-700">Password</label>
                            <input id="password" 
                                   class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                   type="password"
                                   name="password"
                                   value="Admin123!MPP"
                                   required 
                                   autocomplete="current-password"
                                   onchange="debugFieldChange(this)" />
                            @error('password')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <!-- Remember Me -->
                        <div class="block mt-4">
                            <label for="remember_me" class="flex items-center">
                                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="remember">
                                <span class="ml-2 text-sm text-gray-600">Remember me</span>
                            </label>
                        </div>
                        
                        <!-- Debug Info -->
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                            <div class="text-xs text-blue-800">
                                <div><strong>CSRF Token:</strong> <span id="debug-csrf-token">{{ csrf_token() }}</span></div>
                                <div><strong>Session ID:</strong> <span id="debug-session-id">{{ session()->getId() }}</span></div>
                                <div><strong>Form Action:</strong> <span id="debug-form-action">{{ route('login') }}</span></div>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="flex items-center justify-end mt-6">
                            <button type="submit" 
                                    class="ml-3 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition ease-in-out duration-150"
                                    id="debug-submit-btn">
                                <span class="mr-2">🚀</span>
                                LOGIN (DEBUG MODE)
                            </button>
                        </div>
                    </form>
                    
                    <!-- Debug Test Buttons -->
                    <div class="mt-6 border-t pt-4">
                        <div class="text-sm font-medium text-gray-700 mb-2">Debug Tests:</div>
                        <div class="space-x-2">
                            <button onclick="debugTestJavaScript()" class="debug-btn">Test JS</button>
                            <button onclick="debugTestAjax()" class="debug-btn">Test AJAX</button>
                            <button onclick="debugTestLivewire()" class="debug-btn">Test Livewire</button>
                            <button onclick="debugTestAlpine()" class="debug-btn">Test Alpine</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Debug Console -->
    <div class="debug-console" id="debug-console">
        <div class="debug-log debug-success">[DEBUG] PPM Login Debug Console Initialized</div>
        <div class="debug-log debug-info">[INFO] Monitoring JavaScript errors, network requests, and form interactions</div>
        <div class="debug-log debug-info">[INFO] CSRF Token: {{ csrf_token() }}</div>
        <div class="debug-log debug-info">[INFO] Session ID: {{ session()->getId() }}</div>
    </div>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Debug JavaScript -->
    <script>
        // Debug Console Management
        let debugLogs = [];
        let debugStartTime = Date.now();
        let debugJsErrors = 0;
        let debugNetworkRequests = 0;
        
        function debugLog(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = {
                timestamp: timestamp,
                type: type,
                message: message
            };
            
            debugLogs.push(logEntry);
            
            // Add to console
            const console = document.getElementById('debug-console');
            const logDiv = document.createElement('div');
            logDiv.className = `debug-log debug-${type}`;
            logDiv.innerHTML = `[${timestamp}] [${type.toUpperCase()}] ${message}`;
            console.appendChild(logDiv);
            console.scrollTop = console.scrollHeight;
            
            // Update status
            updateDebugStatus();
        }
        
        function updateDebugStatus() {
            const elapsed = Math.floor((Date.now() - debugStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            
            document.getElementById('debug-time').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('debug-js-errors').textContent = debugJsErrors;
            document.getElementById('debug-network-count').textContent = debugNetworkRequests;
        }
        
        function debugClearLogs() {
            debugLogs = [];
            document.getElementById('debug-console').innerHTML = 
                '<div class="debug-log debug-success">[DEBUG] Console cleared</div>';
            debugJsErrors = 0;
            debugNetworkRequests = 0;
            updateDebugStatus();
        }
        
        function debugExportLogs() {
            const logData = {
                timestamp: new Date().toISOString(),
                session_id: '{{ session()->getId() }}',
                csrf_token: '{{ csrf_token() }}',
                url: window.location.href,
                user_agent: navigator.userAgent,
                logs: debugLogs,
                form_data: {
                    email: document.getElementById('email').value,
                    password_filled: document.getElementById('password').value ? true : false
                }
            };
            
            const blob = new Blob([JSON.stringify(logData, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `ppm_debug_logs_${Date.now()}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            
            debugLog('Debug logs exported to JSON file', 'success');
        }
        
        function debugToggleConsole() {
            const console = document.getElementById('debug-console');
            console.style.display = console.style.display === 'none' ? 'block' : 'none';
        }
        
        // Field change tracking
        function debugFieldChange(field) {
            field.parentElement.classList.add('filled');
            debugLog(`Field changed: ${field.name} = ${field.name === 'password' ? '[HIDDEN]' : field.value}`, 'info');
        }
        
        // JavaScript Error Detection
        window.onerror = function(msg, url, line, col, error) {
            debugJsErrors++;
            debugLog(`JavaScript Error: ${msg} at ${url}:${line}:${col}`, 'error');
            return false;
        };
        
        window.addEventListener('unhandledrejection', function(event) {
            debugJsErrors++;
            debugLog(`Unhandled Promise Rejection: ${event.reason}`, 'error');
        });
        
        // Network Request Monitoring
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            debugNetworkRequests++;
            debugLog(`Fetch request: ${args[0]}`, 'info');
            
            return originalFetch.apply(this, args)
                .then(response => {
                    debugLog(`Fetch response: ${response.status} ${response.statusText}`, 
                             response.ok ? 'success' : 'error');
                    return response;
                })
                .catch(error => {
                    debugLog(`Fetch error: ${error.message}`, 'error');
                    throw error;
                });
        };
        
        // XMLHttpRequest Monitoring
        const originalXHR = window.XMLHttpRequest;
        window.XMLHttpRequest = function() {
            const xhr = new originalXHR();
            const originalOpen = xhr.open;
            const originalSend = xhr.send;
            
            xhr.open = function(method, url, ...args) {
                debugNetworkRequests++;
                debugLog(`XHR ${method}: ${url}`, 'info');
                return originalOpen.apply(this, [method, url, ...args]);
            };
            
            xhr.addEventListener('load', function() {
                debugLog(`XHR response: ${this.status} ${this.statusText}`, 
                         this.status < 400 ? 'success' : 'error');
            });
            
            xhr.addEventListener('error', function() {
                debugLog(`XHR error: Network error`, 'error');
            });
            
            return xhr;
        };
        
        // Form Submission Monitoring
        document.getElementById('debug-login-form').addEventListener('submit', function(e) {
            debugLog('Form submission started', 'info');
            debugLog(`Email: ${document.getElementById('email').value}`, 'info');
            debugLog('Password: [HIDDEN]', 'info');
            debugLog(`CSRF Token: ${document.querySelector('input[name="_token"]').value}`, 'info');
            
            // Allow form to submit
            // e.preventDefault(); // Uncomment to prevent actual submission for testing
        });
        
        // Debug Test Functions
        function debugTestJavaScript() {
            try {
                debugLog('Testing JavaScript execution...', 'info');
                eval('console.log("JavaScript test successful")');
                debugLog('JavaScript test: PASSED', 'success');
            } catch (error) {
                debugLog(`JavaScript test: FAILED - ${error.message}`, 'error');
            }
        }
        
        function debugTestAjax() {
            debugLog('Testing AJAX connectivity...', 'info');
            fetch('/login', {
                method: 'HEAD',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                debugLog(`AJAX test: ${response.status} ${response.statusText}`, 
                         response.ok ? 'success' : 'warning');
            })
            .catch(error => {
                debugLog(`AJAX test: FAILED - ${error.message}`, 'error');
            });
        }
        
        function debugTestLivewire() {
            debugLog('Testing Livewire availability...', 'info');
            if (typeof window.Livewire !== 'undefined') {
                debugLog('Livewire test: PASSED - Livewire is loaded', 'success');
                debugLog(`Livewire version: ${window.Livewire.version || 'unknown'}`, 'info');
            } else {
                debugLog('Livewire test: FAILED - Livewire not found', 'error');
            }
        }
        
        function debugTestAlpine() {
            debugLog('Testing Alpine.js availability...', 'info');
            if (typeof window.Alpine !== 'undefined') {
                debugLog('Alpine test: PASSED - Alpine.js is loaded', 'success');
                debugLog(`Alpine version: ${window.Alpine.version || 'unknown'}`, 'info');
            } else {
                debugLog('Alpine test: FAILED - Alpine.js not found', 'error');
            }
        }
        
        function debugTestConnectivity() {
            debugLog('Testing server connectivity...', 'info');
            
            fetch('/login', { method: 'HEAD' })
                .then(response => {
                    debugLog(`Server connectivity: ${response.status}`, 'success');
                })
                .catch(error => {
                    debugLog(`Server connectivity: FAILED - ${error.message}`, 'error');
                });
                
            fetch('/livewire/livewire.min.js', { method: 'HEAD' })
                .then(response => {
                    debugLog(`Livewire JS: ${response.status}`, response.ok ? 'success' : 'error');
                })
                .catch(error => {
                    debugLog(`Livewire JS: FAILED - ${error.message}`, 'error');
                });
        }
        
        // Initialize debug status
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('DOM loaded, initializing debug monitoring...', 'info');
            document.getElementById('debug-status-text').textContent = 'Ready';
            updateDebugStatus();
            
            // Auto-test on load
            setTimeout(() => {
                debugTestConnectivity();
                debugTestJavaScript();
                debugTestLivewire();
                debugTestAlpine();
            }, 1000);
        });
        
        // Update time every second
        setInterval(updateDebugStatus, 1000);
    </script>
</body>
</html>