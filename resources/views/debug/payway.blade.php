<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayWay Debug Dashboard</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .status-good { color: #28a745; }
        .status-bad { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            border-left: 4px solid #007bff;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        .log-viewer {
            max-height: 400px;
            overflow-y: auto;
            background: #1e1e1e;
            color: #fff;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .log-entry {
            margin-bottom: 5px;
            padding: 2px 0;
        }
        .log-error { color: #ff6b6b; }
        .log-warning { color: #feca57; }
        .log-info { color: #48dbfb; }
        .log-debug { color: #a4b0be; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PayWay Debug Dashboard</h1>
        
        <div class="grid">
            <div class="card">
                <h2>Quick Tests</h2>
                <a href="/debug-payway/config" class="btn" target="_blank">Check Configuration</a>
                <a href="/debug-payway/service" class="btn" target="_blank">Test Service</a>
                <button onclick="clearLogs()" class="btn btn-danger">Clear Logs</button>
                <button onclick="refreshLogs()" class="btn">Refresh Logs</button>
            </div>
            
            <div class="card">
                <h2>Configuration Status</h2>
                <div id="config-status">Loading...</div>
            </div>
        </div>
        
        <div class="card">
            <h2>Test Payment Flow</h2>
            <form id="test-payment-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <input type="text" id="test-amount" placeholder="Amount (e.g., 1.00)" value="1.00">
                    <select id="test-currency">
                        <option value="USD">USD</option>
                        <option value="KHR">KHR</option>
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <input type="text" id="test-firstname" placeholder="First Name" value="Debug">
                    <input type="text" id="test-lastname" placeholder="Last Name" value="Test">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <input type="email" id="test-email" placeholder="Email" value="debug@test.com">
                    <input type="text" id="test-phone" placeholder="Phone" value="123456789">
                </div>
                <button type="submit" class="btn">Test Payment Creation</button>
            </form>
            <div id="test-result" style="margin-top: 15px;"></div>
        </div>
        
        <div class="card">
            <h2>Recent Logs</h2>
            <div id="log-viewer" class="log-viewer">
                Loading logs...
            </div>
        </div>
        
        <div class="card">
            <h2>Environment Information</h2>
            <pre id="env-info">
App Environment: {{ config('app.env') }}
App Debug: {{ config('app.debug') ? 'true' : 'false' }}
PayWay Base URL: {{ config('payway.base_url') }}
PayWay Sandbox Mode: {{ config('payway.sandbox_mode') ? 'true' : 'false' }}
            </pre>
        </div>
    </div>

    <script>
        // Load configuration status
        fetch('/debug-payway/config')
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('config-status');
                if (data.error) {
                    statusDiv.innerHTML = `<pre class="error">${JSON.stringify(data, null, 2)}</pre>`;
                } else {
                    let html = '<ul>';
                    html += `<li>Base URL: <span class="${data.base_url ? 'status-good' : 'status-bad'}">${data.base_url || 'NOT SET'}</span></li>`;
                    html += `<li>Merchant ID: <span class="${data.merchant_id !== 'NOT SET' ? 'status-good' : 'status-bad'}">${data.merchant_id}</span></li>`;
                    html += `<li>Secret Key: <span class="${data.secret_key !== 'NOT SET' ? 'status-good' : 'status-bad'}">${data.secret_key}</span></li>`;
                    html += `<li>Sandbox Mode: <span class="${data.sandbox_mode ? 'status-warning' : 'status-good'}">${data.sandbox_mode ? 'ON' : 'OFF'}</span></li>`;
                    html += '</ul>';
                    statusDiv.innerHTML = html;
                }
            })
            .catch(error => {
                document.getElementById('config-status').innerHTML = `<pre class="error">Error loading config: ${error.message}</pre>`;
            });

        // Test payment form
        document.getElementById('test-payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = '<p>Testing payment creation...</p>';
            
            const testData = {
                amount: document.getElementById('test-amount').value,
                currency: document.getElementById('test-currency').value,
                firstname: document.getElementById('test-firstname').value,
                lastname: document.getElementById('test-lastname').value,
                email: document.getElementById('test-email').value,
                phone: document.getElementById('test-phone').value
            };
            
            fetch('/debug-payway/service', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(testData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    resultDiv.innerHTML = `<pre class="error">${JSON.stringify(data, null, 2)}</pre>`;
                } else {
                    resultDiv.innerHTML = `<pre class="success">${JSON.stringify(data, null, 2)}</pre>`;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<pre class="error">Request failed: ${error.message}</pre>`;
            });
        });

        // Simulate log loading (you can implement actual log reading)
        function loadLogs() {
            const logViewer = document.getElementById('log-viewer');
            logViewer.innerHTML = `
                <div class="log-entry log-info">[${new Date().toISOString()}] INFO: PayWay debug dashboard loaded</div>
                <div class="log-entry log-warning">[${new Date().toISOString()}] WARNING: This is a debug interface - do not use in production</div>
                <div class="log-entry log-info">[${new Date().toISOString()}] INFO: Use browser network tab to see actual API calls</div>
            `;
        }

        function clearLogs() {
            document.getElementById('log-viewer').innerHTML = '<div class="log-entry log-info">Logs cleared</div>';
        }

        function refreshLogs() {
            loadLogs();
        }

        // Load initial logs
        loadLogs();
    </script>
</body>
</html>
