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

if (file_exists($logFile)) {
    $fileExists = true;
    $logContent = file_get_contents($logFile);
    $logLines = array_filter(explode("\n", $logContent));
    $logLines = array_reverse($logLines); // Show newest first
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
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                <h1 class="text-2xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Bot Log Viewer
                </h1>
                <p class="text-blue-100 mt-1">
                    <?php if ($fileExists): ?>
                        File: <?= htmlspecialchars($logFile) ?> | Size: <?= number_format(filesize($logFile)) ?> bytes | Last modified: <?= date('Y-m-d H:i:s', filemtime($logFile)) ?>
                    <?php else: ?>
                        Log file not found
                    <?php endif; ?>
                </p>
            </div>

            <div class="p-6">
                <?php if (!empty($message)): ?>
                    <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="text-green-800"><?= htmlspecialchars($message) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($fileExists): ?>
                    <?php if (empty($logContent)): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-yellow-800">Log file exists but is empty.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mb-4 flex justify-between items-center">
                            <span class="text-sm text-gray-600"><?= count($logLines) ?> log entries</span>
                            <div class="flex gap-2">
                                <button onclick="location.reload()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm transition duration-200">
                                    Refresh
                                </button>
                                <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to clear the log file? This action cannot be undone.')">
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm transition duration-200">
                                        Clear Log
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="bg-gray-900 rounded-lg overflow-hidden">
                            <div class="max-h-96 overflow-y-auto">
                                <pre class="text-green-400 font-mono text-sm p-4 whitespace-pre-wrap"><?php
                                    foreach ($logLines as $index => $line) {
                                        $line = htmlspecialchars($line);
                                        
                                        // Add line numbers and color coding
                                        $lineNumber = sprintf('%04d', $index + 1);
                                        
                                        // Simple color coding based on content
                                        $class = 'text-green-400';
                                        if (stripos($line, 'error') !== false) {
                                            $class = 'text-red-400';
                                        } elseif (stripos($line, 'warning') !== false) {
                                            $class = 'text-yellow-400';
                                        } elseif (stripos($line, 'info') !== false) {
                                            $class = 'text-blue-400';
                                        }
                                        
                                        echo "<span class='text-gray-500 mr-2'>$lineNumber</span><span class='$class'>$line</span>\n";
                                    }
                                ?></pre>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-xs text-gray-500 flex justify-between">
                            <span>Showing newest entries first</span>
                            <span>Auto-refresh: <button onclick="toggleAutoRefresh()" id="autoRefreshBtn" class="text-blue-500 hover:underline">Enable</button></span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Log File Not Found</h3>
                                <p class="text-sm text-red-700 mt-1">The file <code class="bg-red-100 px-1 rounded"><?= htmlspecialchars($logFile) ?></code> does not exist in the current directory.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
    </script>
</body>
</html>