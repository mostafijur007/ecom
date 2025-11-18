<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>E-Commerce API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css">
    <link rel="icon" type="image/png" href="https://petstore.swagger.io/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://petstore.swagger.io/favicon-16x16.png" sizes="16x16" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .topbar {
            background-color: #2c3e50;
            padding: 15px 0;
        }

        .topbar-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1460px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .topbar-wrapper .link {
            color: #fff;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .topbar-wrapper .link:hover {
            text-decoration: underline;
        }

        .info {
            background-color: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            margin: 20px;
            max-width: 1420px;
            margin-left: auto;
            margin-right: auto;
        }

        .info h2 {
            color: #2c3e50;
            margin-top: 0;
        }

        .info .test-accounts {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }

        .info .test-accounts h3 {
            margin-top: 0;
            color: #27ae60;
        }

        .info .test-accounts ul {
            list-style: none;
            padding: 0;
        }

        .info .test-accounts li {
            padding: 5px 0;
            font-family: 'Courier New', monospace;
        }

        .swagger-ui .topbar {
            display: none;
        }
    </style>
</head>

<body>
    <div class="topbar">
        <div class="topbar-wrapper">
            <a class="link" href="/">
                <span>🛒 E-Commerce API Documentation</span>
            </a>
            <div style="color: white;">
                <span>Version 1.0.0</span>
            </div>
        </div>
    </div>

    <div class="info">
        <h2>📚 Welcome to the E-Commerce API Documentation</h2>
        <p>
            This is an interactive API documentation for the E-Commerce JWT Authentication system.
            You can test all endpoints directly from this interface.
        </p>

        <div class="test-accounts">
            <h3>🔑 Test Accounts</h3>
            <p><strong>Use these accounts to test the API:</strong></p>
            <ul>
                <li><strong>Admin:</strong> admin@example.com / password123</li>
                <li><strong>Vendor:</strong> vendor@example.com / password123</li>
                <li><strong>Customer:</strong> customer@example.com / password123</li>
            </ul>
        </div>

        <div style="margin-top: 15px;">
            <h3>🔐 How to Test Protected Endpoints</h3>
            <ol>
                <li>Use the <strong>POST /auth/login</strong> endpoint to get an access token</li>
                <li>Click the <strong>"Authorize"</strong> button (green lock icon) at the top right</li>
                <li>Enter: <code>Bearer {your_access_token}</code></li>
                <li>Click <strong>"Authorize"</strong> and then <strong>"Close"</strong></li>
                <li>Now you can test all protected endpoints!</li>
            </ol>
        </div>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "/api-docs",
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
                persistAuthorization: true,
                displayRequestDuration: true,
                filter: true,
                tryItOutEnabled: true,
                syntaxHighlight: {
                    activate: true,
                    theme: "monokai"
                }
            });

            window.ui = ui;
        };
    </script>
</body>
</html>
