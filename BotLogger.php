<?php

class BotLogger {
    private $logFile;
    
    public function __construct($logFile = 'storage/bot.log') {
        $this->logFile = $logFile;
        // Ensure storage directory exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    public function log($message, $data = null): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            $logEntry .= "\nDATA: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n" . str_repeat('-', 80) . "\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}