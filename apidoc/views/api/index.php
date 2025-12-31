<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub API Documentation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .token-input-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            padding: 10px 20px;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .token-input-container label {
            font-weight: 500;
            color: #e0e0e0;
            font-size: 13px;
            white-space: nowrap;
        }
        
        .token-input-container input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            font-size: 13px;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.05);
            color: #e0e0e0;
            font-family: 'SF Mono', Monaco, monospace;
        }
        
        .token-input-container input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.08);
        }
        
        .token-input-container button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 12px;
        }
        
        .btn-set {
            background: #6366f1;
            color: white;
        }
        
        .btn-set:hover {
            background: #4f46e5;
        }
        
        .btn-clear {
            background: transparent;
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-clear:hover {
            background: rgba(239, 68, 68, 0.1);
        }
        
        body {
            padding-top: 50px;
        }
    </style>
</head>
<body>
    <div class="token-input-container">
        <label>Bearer Token:</label>
        <input type="text" id="bearer-token" placeholder="Enter JWT token" 
               value="">
        <button class="btn-set" onclick="setToken()">Set</button>
        <button class="btn-clear" onclick="clearToken()">Clear</button>
    </div>
    
    <script>
        const savedToken = localStorage.getItem('swagger_token') || '';
        if (document.getElementById('bearer-token')) {
            document.getElementById('bearer-token').value = savedToken;
        }
        
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const token = localStorage.getItem('swagger_token');
            if (token && args[0] && typeof args[0] === 'string') {
                if (!args[0].includes('/openapi.json')) {
                    if (!args[1]) args[1] = {};
                    if (!args[1].headers) args[1].headers = {};
                    if (typeof args[1].headers === 'object' && !(args[1].headers instanceof Headers)) {
                        args[1].headers['Authorization'] = 'Bearer ' + token;
                    }
                }
            }
            return originalFetch.apply(this, args);
        };
        
        window.setToken = function() {
            const token = document.getElementById('bearer-token').value;
            localStorage.setItem('swagger_token', token);
        };
        
        window.clearToken = function() {
            localStorage.removeItem('swagger_token');
            document.getElementById('bearer-token').value = '';
        };
    </script>
    
    <script 
        id="api-reference" 
        type="application/json"
        data-configuration='{"theme":"purple","layout":"modern","defaultHttpClient":{"targetKey":"javascript","clientKey":"fetch"},"authentication":{"http":{"scheme":"bearer","bearerFormat":"JWT"}},"hideDownloadButton":false,"searchHotKey":"k","sidebar":{"open":true,"grouped":true},"darkMode":true,"withDefaultFonts":true}'
        data-url="/openapi.json">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@latest/dist/browser/standalone.js"></script>
</body>
</html>
