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
        try {
            $timestamp = date('Y-m-d H:i:s');
            $entry = "[{$timestamp}] {$message}";
            if ($data !== null) {
                $entry .= "\nDATA: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            $entry .= "\n" . str_repeat('-', 80) . "\n";

            $fh = fopen($this->logFile, 'ab');
            if ($fh) {
                flock($fh, LOCK_EX);
                fwrite($fh, $entry);
                fflush($fh);
                flock($fh, LOCK_UN);
                fclose($fh);
            } else {
                error_log("BOT_LOG_FAIL_OPEN: " . $entry);
            }
        } catch (Throwable $e) {
            error_log("BOT_LOG_EXCEPTION: " . $e->getMessage());
        }
    }
}