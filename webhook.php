<?php
/**
 * User: Moradi ( @MortezaMoradi )
 * Date: 8/26/2025  Time: 5:15 PM
 * Webhook Management for Telegram Bot
 */


/*
 *
 *
  Created webhook.php with complete webhook management functionality:
  Web Interface:
  - Access via browser for GUI management
  - Forms to set/delete/get webhook info
  - Real-time results display

  API Endpoints:
  - webhook.php?action=set&url=YOUR_WEBHOOK_URL - Set webhook
  - webhook.php?action=delete - Delete webhook
  - webhook.php?action=delete&drop_pending_updates=true - Delete webhook and drop pending updates
  - webhook.php?action=get - Get webhook info

  Additional Options for Setting Webhook:
  - max_connections - Maximum allowed connections
  - allowed_updates - JSON array of allowed update types
  - drop_pending_updates - Boolean to drop pending updates
  - secret_token - Secret token for webhook security

  Features:
  - Full error handling and response formatting
  - JSON responses for API calls
  - User-friendly web interface
  - Supports all Telegram webhook parameters

  Run php -S localhost:8000 and visit http://localhost:8000/webhook.php to use the web interface.
 */

require 'Const.php';

class WebhookManager {
    private $botToken;
    private $baseUrl;
    
    public function __construct($token) {
        $this->botToken = $token;
        $this->baseUrl = "https://api.telegram.org/bot{$token}/";
    }
    
    private function makeRequest($method, $params = []): array
    {
        $url = $this->baseUrl . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'http_code' => $httpCode,
            'response' => json_decode($response, true)
        ];
    }
    
    public function setWebhook($webhookUrl, $options = []): array
    {
        $params = array_merge([
            'url' => $webhookUrl
        ], $options);
        
        $result = $this->makeRequest('setWebhook', $params);
        
        if ($result['http_code'] === 200 && $result['response']['ok']) {
            return [
                'success' => true,
                'message' => 'Webhook set successfully',
                'url' => $webhookUrl,
                'response' => $result['response']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to set webhook',
                'error' => $result['response']['description'] ?? 'Unknown error',
                'response' => $result['response']
            ];
        }
    }
    
    public function deleteWebhook($dropPendingUpdates = false): array
    {
        $params = [
            'drop_pending_updates' => $dropPendingUpdates
        ];
        
        $result = $this->makeRequest('deleteWebhook', $params);
        
        if ($result['http_code'] === 200 && $result['response']['ok']) {
            return [
                'success' => true,
                'message' => 'Webhook deleted successfully',
                'response' => $result['response']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete webhook',
                'error' => $result['response']['description'] ?? 'Unknown error',
                'response' => $result['response']
            ];
        }
    }
    
    public function getWebhookInfo(): array
    {
        $result = $this->makeRequest('getWebhookInfo');
        
        if ($result['http_code'] === 200 && $result['response']['ok']) {
            $info = $result['response']['result'];
            return [
                'success' => true,
                'webhook_info' => [
                    'url' => $info['url'] ?? 'Not set',
                    'has_custom_certificate' => $info['has_custom_certificate'] ?? false,
                    'pending_update_count' => $info['pending_update_count'] ?? 0,
                    'ip_address' => $info['ip_address'] ?? null,
                    'last_error_date' => $info['last_error_date'] ?? null,
                    'last_error_message' => $info['last_error_message'] ?? null,
                    'last_synchronization_error_date' => $info['last_synchronization_error_date'] ?? null,
                    'max_connections' => $info['max_connections'] ?? null,
                    'allowed_updates' => $info['allowed_updates'] ?? []
                ],
                'response' => $result['response']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to get webhook info',
                'error' => $result['response']['description'] ?? 'Unknown error',
                'response' => $result['response']
            ];
        }
    }
}

$webhookManager = new WebhookManager(BOTAPI);

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'set':
            if (!isset($_GET['url'])) {
                echo json_encode(['success' => false, 'message' => 'URL parameter is required']);
                exit;
            }
            
            $options = [];
            if (isset($_GET['max_connections'])) {
                $options['max_connections'] = intval($_GET['max_connections']);
            }
            if (isset($_GET['allowed_updates'])) {
                $options['allowed_updates'] = json_decode($_GET['allowed_updates'], true);
            }
            if (isset($_GET['drop_pending_updates'])) {
                $options['drop_pending_updates'] = filter_var($_GET['drop_pending_updates'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($_GET['secret_token'])) {
                $options['secret_token'] = $_GET['secret_token'];
            }
            
            $result = $webhookManager->setWebhook($_GET['url'], $options);
            echo json_encode($result, JSON_PRETTY_PRINT);
            break;
            
        case 'delete':
            $dropPendingUpdates = isset($_GET['drop_pending_updates']) ? 
                filter_var($_GET['drop_pending_updates'], FILTER_VALIDATE_BOOLEAN) : false;
            
            $result = $webhookManager->deleteWebhook($dropPendingUpdates);
            echo json_encode($result, JSON_PRETTY_PRINT);
            break;
            
        case 'get':
        case 'info':
            $result = $webhookManager->getWebhookInfo();
            echo json_encode($result, JSON_PRETTY_PRINT);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action. Use: set, delete, get, or info'
            ], JSON_PRETTY_PRINT);
            break;
    }
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Telegram Bot Webhook Manager</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .container { max-width: 800px; margin: 0 auto; }
            .section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            input, button { padding: 10px; margin: 5px; }
            input[type="text"] { width: 400px; }
            button { background-color: #4CAF50; color: white; border: none; cursor: pointer; }
            button:hover { background-color: #45a049; }
            .result { margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-radius: 5px; }
            pre { background-color: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Telegram Bot Webhook Manager</h1>
            
            <div class="section">
                <h2>Set Webhook</h2>
                <form id="setWebhookForm">
                    <input type="text" id="webhookUrl" placeholder="Enter webhook URL" required>
                    <button type="submit">Set Webhook</button>
                </form>
                <div id="setResult" class="result" style="display:none;"></div>
            </div>
            
            <div class="section">
                <h2>Get Webhook Info</h2>
                <button onclick="getWebhookInfo()">Get Webhook Info</button>
                <div id="infoResult" class="result" style="display:none;"></div>
            </div>
            
            <div class="section">
                <h2>Delete Webhook</h2>
                <button onclick="deleteWebhook()">Delete Webhook</button>
                <button onclick="deleteWebhook(true)">Delete Webhook (Drop Pending Updates)</button>
                <div id="deleteResult" class="result" style="display:none;"></div>
            </div>
        </div>

        <script>
            document.getElementById('setWebhookForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const url = document.getElementById('webhookUrl').value;
                setWebhook(url);
            });

            function setWebhook(url) {
                fetch(`?action=set&url=${encodeURIComponent(url)}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('setResult');
                        resultDiv.style.display = 'block';
                        resultDiv.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function getWebhookInfo() {
                fetch('?action=get')
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('infoResult');
                        resultDiv.style.display = 'block';
                        resultDiv.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function deleteWebhook(dropPending = false) {
                const url = dropPending ? '?action=delete&drop_pending_updates=true' : '?action=delete';
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        const resultDiv = document.getElementById('deleteResult');
                        resultDiv.style.display = 'block';
                        resultDiv.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        </script>
    </body>
    </html>
    <?php
}
