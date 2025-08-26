<?php
$logFile = 'bot.log';
$logContent = '';
$fileExists = false;
$message = '';

// Handle clear log action
if (isset($_POST['action']) && $_POST['action'] === 'clear' && file_exists($logFile)) {
    if (file_put_contents($logFile, '') !== false) {
        $message = 'Log file cleared successfully!';
    } else {
        $message = 'Error: Could not clear log file.';
    }
}

// Pagination settings
$entriesPerPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

if (file_exists($logFile)) {
    $fileExists = true;
    $logContent = file_get_contents($logFile);
    // Split by the separator line to get log entries
    $logEntries = explode(str_repeat('-', 80), $logContent);
    $logEntries = array_filter($logEntries, function($entry) {
        return !empty(trim($entry));
    });
    $logEntries = array_reverse($logEntries); // Show newest first
    
    // Pagination calculation
    $totalEntries = count($logEntries);
    $totalPages = ceil($totalEntries / $entriesPerPage);
    $offset = ($currentPage - 1) * $entriesPerPage;
    $currentEntries = array_slice($logEntries, $offset, $entriesPerPage);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bot Log Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'mono': ['Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-6">
        <!-- Header Card -->
        <div class="bg-white rounded-xl shadow-lg mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white flex items-center">
                    <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Bot Log Viewer
                </h1>
                <?php if ($fileExists): ?>
                    <div class="text-blue-100 mt-2 flex flex-wrap gap-4 text-sm">
                        <span>📁 <?= htmlspecialchars($logFile) ?></span>
                        <span>📊 <?= number_format(filesize($logFile)) ?> bytes</span>
                        <span>🕒 <?= date('Y-m-d H:i:s', filemtime($logFile)) ?></span>
                        <span>📄 <?= $totalEntries ?> entries</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Controls -->
            <div class="p-4 bg-gray-50 border-t">
                <div class="flex flex-wrap justify-between items-center gap-3">
                    <div class="flex gap-2">
                        <button onclick="location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                        <?php if ($fileExists && $totalEntries > 0): ?>
                            <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to clear the log file? This action cannot be undone.')">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition duration-200 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Clear Log
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <span>Auto-refresh:</span>
                        <button onclick="toggleAutoRefresh()" id="autoRefreshBtn" class="text-blue-500 hover:text-blue-700 font-medium">Enable</button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
                <div class="flex">
                    <svg class="w-5 h-5 text-green-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-green-800 font-medium"><?= htmlspecialchars($message) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($fileExists): ?>
            <?php if (empty($logContent)): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                    <svg class="w-12 h-12 text-yellow-400 mx-auto mb-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-yellow-800 mb-1">No Log Entries</h3>
                    <p class="text-yellow-700">The log file exists but contains no entries yet.</p>
                </div>
            <?php else: ?>
                <!-- Log Entries -->
                <div class="space-y-4 mb-6">
                    <?php foreach ($currentEntries as $index => $entry): ?>
                        <?php 
                            $entry = trim($entry);
                            if (empty($entry)) continue;
                            
                            // Extract timestamp and message
                            preg_match('/^\[([^\]]+)\]\s*(.*)$/s', $entry, $matches);
                            $timestamp = $matches[1] ?? 'Unknown';
                            $restOfEntry = $matches[2] ?? $entry;
                            
                            // Extract the main message before DATA:
                            if (strpos($restOfEntry, "\nDATA:") !== false) {
                                $parts = explode("\nDATA:", $restOfEntry, 2);
                                $mainMessage = trim($parts[0]);
                                $rawJsonData = isset($parts[1]) ? trim($parts[1]) : '';
                                
                                // Try to format JSON better
                                if (!empty($rawJsonData)) {
                                    // Try to decode and re-encode for better formatting
                                    $decoded = json_decode($rawJsonData, true);
                                    if ($decoded !== null) {
                                        $jsonData = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                    } else {
                                        $jsonData = $rawJsonData;
                                    }
                                } else {
                                    $jsonData = '';
                                }
                            } else {
                                $mainMessage = trim($restOfEntry);
                                $jsonData = '';
                            }
                            
                            // Create a unique time-based ID
                            $timeId = strtotime($timestamp);
                            $uniqueId = $timeId . '-' . $index;
                            
                            // Enhanced color coding and icons
                            $config = [
                                'bg' => 'bg-white',
                                'border' => 'border-gray-200',
                                'text' => 'text-gray-800',
                                'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>',
                                'iconColor' => 'text-gray-500'
                            ];
                            
                            if (stripos($mainMessage, 'error') !== false) {
                                $config = array_merge($config, [
                                    'bg' => 'bg-red-50',
                                    'border' => 'border-red-300',
                                    'text' => 'text-red-800',
                                    'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                                    'iconColor' => 'text-red-500'
                                ]);
                            } elseif (stripos($mainMessage, 'warning') !== false) {
                                $config = array_merge($config, [
                                    'bg' => 'bg-yellow-50',
                                    'border' => 'border-yellow-300',
                                    'text' => 'text-yellow-800',
                                    'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                                    'iconColor' => 'text-yellow-500'
                                ]);
                            } elseif (stripos($mainMessage, 'started') !== false || stripos($mainMessage, 'success') !== false || stripos($mainMessage, 'sent') !== false) {
                                $config = array_merge($config, [
                                    'bg' => 'bg-green-50',
                                    'border' => 'border-green-300',
                                    'text' => 'text-green-800',
                                    'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                                    'iconColor' => 'text-green-500'
                                ]);
                            } elseif (stripos($mainMessage, 'received') !== false || stripos($mainMessage, 'command') !== false || stripos($mainMessage, 'update') !== false) {
                                $config = array_merge($config, [
                                    'bg' => 'bg-blue-50',
                                    'border' => 'border-blue-300',
                                    'text' => 'text-blue-800',
                                    'icon' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>',
                                    'iconColor' => 'text-blue-500'
                                ]);
                            }
                            
                            // Format timestamp for display
                            $displayTime = date('M j, Y', strtotime($timestamp));
                            $displayTimeDetail = date('H:i:s', strtotime($timestamp));
                        ?>
                        <div class="<?= $config['bg'] ?> <?= $config['border'] ?> border-2 rounded-xl shadow-sm hover:shadow-md transition-all duration-200">
                            <!-- Card Header -->
                            <div class="px-6 py-4 border-b border-gray-100">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="<?= $config['iconColor'] ?> flex-shrink-0">
                                            <?= $config['icon'] ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                                    <?= $displayTime ?>
                                                </span>
                                                <span class="text-xs text-gray-400">
                                                    <?= $displayTimeDetail ?>
                                                </span>
                                            </div>
                                            <h3 class="<?= $config['text'] ?> font-semibold text-lg mt-1">
                                                <?= htmlspecialchars($mainMessage) ?>
                                            </h3>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($jsonData)): ?>
                                        <button onclick="copyToClipboard('json-<?= $uniqueId ?>')" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-medium transition duration-200 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path>
                                            </svg>
                                            Copy JSON
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- JSON Data -->
                            <?php if (!empty($jsonData)): ?>
                                <div class="px-6 py-4">
                                    <div class="border-t border-gray-100 pt-4">
                                        <button onclick="toggleJson('json-container-<?= $uniqueId ?>')" class="flex items-center text-sm text-gray-600 hover:text-gray-800 mb-3">
                                            <svg id="json-icon-<?= $uniqueId ?>" class="w-4 h-4 mr-2 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                            <span class="font-medium">View JSON Data</span>
                                        </button>
                                        <div id="json-container-<?= $uniqueId ?>" class="hidden">
                                            <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto max-h-96 overflow-y-auto">
                                                <pre id="json-<?= $uniqueId ?>" class="text-green-400 font-mono text-xs whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($jsonData) ?></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="bg-white rounded-xl shadow-sm p-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                Showing <?= ($offset + 1) ?> to <?= min($offset + $entriesPerPage, $totalEntries) ?> of <?= $totalEntries ?> entries
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <?php if ($currentPage > 1): ?>
                                    <a href="?page=<?= $currentPage - 1 ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition duration-200">
                                        ← Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $currentPage + 2);
                                ?>
                                
                                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                    <a href="?page=<?= $i ?>" class="<?= $i == $currentPage ? 'bg-blue-500 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' ?> px-3 py-2 rounded-lg text-sm font-medium transition duration-200">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?page=<?= $currentPage + 1 ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition duration-200">
                                        Next →
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                <svg class="w-12 h-12 text-red-400 mx-auto mb-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <h3 class="text-lg font-semibold text-red-800 mb-1">Log File Not Found</h3>
                <p class="text-red-700">The file <code class="bg-red-100 px-2 py-1 rounded"><?= htmlspecialchars($logFile) ?></code> does not exist in the current directory.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let autoRefreshInterval = null;
        
        function toggleAutoRefresh() {
            const btn = document.getElementById('autoRefreshBtn');
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                btn.textContent = 'Enable';
                btn.classList.remove('text-red-500');
                btn.classList.add('text-blue-500');
            } else {
                autoRefreshInterval = setInterval(() => {
                    location.reload();
                }, 5000);
                btn.textContent = 'Disable';
                btn.classList.remove('text-blue-500');
                btn.classList.add('text-red-500');
            }
        }
        
        function toggleJson(containerId) {
            const container = document.getElementById(containerId);
            const icon = document.getElementById(containerId.replace('json-container-', 'json-icon-'));
            
            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                container.classList.add('hidden');
                icon.style.transform = 'rotate(0deg)';
            }
        }
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Copied!';
                button.classList.remove('bg-gray-100', 'hover:bg-gray-200', 'text-gray-700');
                button.classList.add('bg-green-100', 'text-green-800');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-100', 'text-green-800');
                    button.classList.add('bg-gray-100', 'hover:bg-gray-200', 'text-gray-700');
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                alert('Failed to copy to clipboard');
            });
        }
    </script>
</body>
</html>