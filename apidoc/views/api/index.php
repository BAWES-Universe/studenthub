<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '/openapi.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                validatorUrl: null,
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: 'list',
                filter: true,
                showExtensions: true,
                showCommonExtensions: true,
                tryItOutEnabled: true,
                requestInterceptor: (request) => {
                    // Add Bearer token if available
                    const token = localStorage.getItem('swagger_token');
                    if (token) {
                        request.headers['Authorization'] = 'Bearer ' + token;
                    }
                    return request;
                },
                onComplete: () => {
                    // Add token input field
                    const authContainer = document.querySelector('.auth-container');
                    if (!authContainer) {
                        const tokenInput = document.createElement('div');
                        tokenInput.className = 'auth-container';
                        tokenInput.style.cssText = 'padding: 20px; background: #fafafa; border-bottom: 1px solid #ddd;';
                        tokenInput.innerHTML = `
                            <label style="display: block; margin-bottom: 10px; font-weight: bold;">Bearer Token:</label>
                            <input type="text" id="bearer-token" placeholder="Enter your Bearer token" 
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
                                   value="${localStorage.getItem('swagger_token') || ''}">
                            <button onclick="setToken()" style="margin-top: 10px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                Set Token
                            </button>
                            <button onclick="clearToken()" style="margin-top: 10px; margin-left: 10px; padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                Clear Token
                            </button>
                        `;
                        document.querySelector('#swagger-ui').insertBefore(tokenInput, document.querySelector('#swagger-ui').firstChild);
                    }
                }
            });

            window.setToken = function() {
                const token = document.getElementById('bearer-token').value;
                localStorage.setItem('swagger_token', token);
                location.reload();
            };

            window.clearToken = function() {
                localStorage.removeItem('swagger_token');
                document.getElementById('bearer-token').value = '';
            };
        };
    </script>
</body>
</html>

