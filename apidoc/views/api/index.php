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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        .token-input-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #fff;
            padding: 12px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .token-input-container label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            white-space: nowrap;
        }
        .token-input-container input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            max-width: 500px;
            transition: border-color 0.2s;
        }
        .token-input-container input:focus {
            outline: none;
            border-color: #16A34A;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        .token-input-container button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-set {
            background: #16A34A;
            color: white;
        }
        .btn-set:hover {
            background: #15803D;
        }
        .btn-clear {
            background: #ef4444;
            color: white;
        }
        .btn-clear:hover {
            background: #dc2626;
        }
        body {
            padding-top: 60px;
        }
    </style>
</head>
<body>
    <div class="token-input-container">
        <label>Bearer Token:</label>
        <input type="text" id="bearer-token" placeholder="Enter your Bearer token" 
               value="">
        <button class="btn-set" onclick="setToken()">Set Token</button>
        <button class="btn-clear" onclick="clearToken()">Clear Token</button>
    </div>
    
    <script>
        // Load token from localStorage first
        const savedToken = localStorage.getItem('swagger_token') || '';
        document.getElementById('bearer-token').value = savedToken;
        
        // Intercept fetch to add Bearer token - do this before Scalar loads
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const token = localStorage.getItem('swagger_token');
            if (token && args[0] && typeof args[0] === 'string') {
                // Add token to headers if it's an API request (not the OpenAPI spec)
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
            // Reload to apply token to Scalar
            location.reload();
        };
        
        window.clearToken = function() {
            localStorage.removeItem('swagger_token');
            document.getElementById('bearer-token').value = '';
            location.reload();
        };
    </script>
    
    <script 
        id="api-reference" 
        type="application/json"
        data-configuration='{"theme":"purple","layout":"modern","defaultHttpClient":{"targetKey":"javascript","clientKey":"fetch"},"authentication":{"http":{"scheme":"bearer","bearerFormat":"JWT"}},"hideDownloadButton":false,"searchHotKey":"k","sidebar":{"open":true},"withDefaultFonts":true}'
        data-url="/openapi.json">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference@latest/dist/browser/standalone.js"></script>
</body>
</html>
