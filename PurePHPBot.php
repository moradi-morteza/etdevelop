<?php

class PurePHPBot {
    private $token;
    private $logger;
    
    public function __construct($token) {
        require_once 'BotLogger.php';
        $this->token = $token;
        $this->logger = new BotLogger();
    }
    
    private function apiRequest($method, $parameters = []) {
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";
        
        // Log outgoing request
        $this->logger->log("SEND REQUEST TO BOT SERVER : $url", $parameters);
        
        $postData = http_build_query($parameters);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $this->logger->log("RECEIVED RESPONSE FROM BOT SERVER", json_decode($response, true));
        
        return json_decode($response, true);
    }
    
    private function sendFile($chat_id, $file_path, $method, $file_param, $caption = '') {
        if (!file_exists($file_path)) {
            $this->logger->log(strtoupper($file_param) . " FILE NOT FOUND : $file_path");
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";

        $postData = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            $file_param => new CURLFile($file_path)
        ];

        // Log outgoing request
        $this->logger->log("SEND REQUEST TO BOT SERVER : $url", $postData);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $this->logger->log("RECEIVED RESPONSE FROM BOT SERVER", json_decode($response, true));
        
        return json_decode($response, true);
    }
    
    public function sendMessage($chat_id, $text) {
        return $this->apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text
        ]);
    }
    
    public function sendPhoto($chat_id, $photo_path, $caption = '') {
        return $this->sendFile($chat_id, $photo_path, 'sendPhoto', 'photo', $caption);
    }
    
    public function sendVideo($chat_id, $video_path, $caption = '') {
        return $this->sendFile($chat_id, $video_path, 'sendVideo', 'video', $caption);
    }
    
    public function sendDocument($chat_id, $document_path, $caption = '') {
        return $this->sendFile($chat_id, $document_path, 'sendDocument', 'document', $caption);
    }
    
    public function sendAudio($chat_id, $audio_path, $caption = '') {
        return $this->sendFile($chat_id, $audio_path, 'sendAudio', 'audio', $caption);
    }
    
    public function processUpdate(): void
    {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        if (!$update) {
            $this->logger->log("INVALID UPDATE RECEIVED : ".$input);
            return;
        }
        
        $this->logger->log("RAW UPDATE RECEIVED", $update);
        
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }
    }
    
    private function handleMessage($message): void
    {
        $chat_id = $message['chat']['id'];
        $user = $message['from'];
        $text = $message['text'] ?? null;
        
        $this->logger->log("MESSAGE RECEIVED", $message);
        
        if ($text) {
            switch (strtolower($text)) {
                case 'hi':
                    $this->sendMessage($chat_id, 'hello');
                    break;
                    
                case 'image':
                    $this->handleFileCommand($chat_id, $user['id'], 'image', 'jpg', 'image/jpeg', 'sendPhoto');
                    break;
                    
                case 'video':
                    $this->handleFileCommand($chat_id, $user['id'], 'video', 'mp4', 'video/mp4', 'sendVideo');
                    break;
                    
                case 'pdf':
                    $this->handleFileCommand($chat_id, $user['id'], 'pdf', 'pdf', 'application/pdf', 'sendDocument');
                    break;
                    
                case 'sound':
                    $this->handleFileCommand($chat_id, $user['id'], 'sound', 'wav', 'audio/wav', 'sendAudio');
                    break;
            }
        }
        
        if (isset($message['document'])) {
            $this->handleReceivedFile($chat_id, $user['id'], $message['document'], 'document');
        }
        
        if (isset($message['photo'])) {
            $largestPhoto = end($message['photo']);
            $this->handleReceivedFile($chat_id, $user['id'], $largestPhoto, 'photo', count($message['photo']));
        }
        
        if (isset($message['video'])) {
            $this->handleReceivedFile($chat_id, $user['id'], $message['video'], 'video');
        }
        
        if (isset($message['audio'])) {
            $this->handleReceivedFile($chat_id, $user['id'], $message['audio'], 'audio');
        }
    }
    
    private function handleFileCommand($chat_id, $user_id, $type, $extension, $mimeType, $method): void
    {
        $filePath = __DIR__ . "/files/{$type}.{$extension}";
        
        if (file_exists($filePath)) {
            $caption = "THIS IS CAPTION";
            $this->$method($chat_id, $filePath, $caption);
        } else {
            $this->sendMessage($chat_id, ucfirst($type) . ' file not found.');
        }
    }
    
    private function handleReceivedFile($chat_id, $user_id, $file, $type, $photo_count = null): void
    {
        if ($type === 'document') {
            $fileDetails = [
                'file_id' => $file['file_id'],
                'file_unique_id' => $file['file_unique_id'],
                'file_name' => $file['file_name'] ?? null,
                'mime_type' => $file['mime_type'] ?? null,
                'file_size' => $file['file_size'] ?? null,
                'thumbnail' => $file['thumbnail'] ?? null,
                'type' => $type
            ];
        } elseif ($type === 'photo') {
            $fileDetails = [
                'file_id' => $file['file_id'],
                'file_unique_id' => $file['file_unique_id'],
                'width' => $file['width'],
                'height' => $file['height'],
                'file_size' => $file['file_size'] ?? null,
                'type' => $type
            ];
        } elseif ($type === 'video') {
            $fileDetails = [
                'file_id' => $file['file_id'],
                'file_unique_id' => $file['file_unique_id'],
                'width' => $file['width'],
                'height' => $file['height'],
                'duration' => $file['duration'],
                'mime_type' => $file['mime_type'] ?? null,
                'file_size' => $file['file_size'] ?? null,
                'thumbnail' => $file['thumbnail'] ?? null,
                'type' => $type
            ];
        } elseif ($type === 'audio') {
            $fileDetails = [
                'file_id' => $file['file_id'],
                'file_unique_id' => $file['file_unique_id'],
                'duration' => $file['duration'],
                'performer' => $file['performer'] ?? null,
                'title' => $file['title'] ?? null,
                'file_name' => $file['file_name'] ?? null,
                'mime_type' => $file['mime_type'] ?? null,
                'file_size' => $file['file_size'] ?? null,
                'thumbnail' => $file['thumbnail'] ?? null,
                'type' => $type
            ];
        }
        
        // Save file to storage folder
        $this->saveFileToStorage($fileDetails, $type);
        
        $this->sendMessage($chat_id, json_encode($fileDetails, JSON_PRETTY_PRINT));
    }
    
    private function saveFileToStorage($fileDetails, $type): void
    {
        try {
            // Get file info from Telegram API
            $fileResponse = $this->apiRequest('getFile', ['file_id' => $fileDetails['file_id']]);
            
            if ($fileResponse && isset($fileResponse['result']['file_path'])) {
                $filePath = $fileResponse['result']['file_path'];
                $fileUrl = "https://api.telegram.org/file/bot{$this->token}/" . $filePath;
                
                // Determine filename
                $fileName = $fileDetails['file_name'] ?? null;
                if (!$fileName) {
                    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                    $fileName = $fileDetails['file_unique_id'] . '.' . $extension;
                }
                
                // Create storage path
                $storageDir = __DIR__ . '/storage';
                if (!is_dir($storageDir)) {
                    mkdir($storageDir, 0755, true);
                }
                
                $localPath = $storageDir . '/' . $fileName;
                
                // Download file
                $this->logger->log("download from $fileUrl" );
                $fileContent = file_get_contents($fileUrl);
                if ($fileContent !== false) {
                    file_put_contents($localPath, $fileContent);
                    $this->logger->log(strtoupper($type) . " SAVED TO STORAGE", [
                        'original_name' => $fileName,
                        'local_path' => $localPath,
                        'file_size' => filesize($localPath)
                    ]);
                } else {
                    $this->logger->log("FAILED TO DOWNLOAD " . strtoupper($type), [
                        'file_id' => $fileDetails['file_id'],
                        'file_url' => $fileUrl
                    ]);
                }
            }
        } catch (Exception $e) {
            $this->logger->log("ERROR SAVING " . strtoupper($type) . " TO STORAGE", [
                'error' => $e->getMessage(),
                'file_id' => $fileDetails['file_id']
            ]);
        }
    }
    
    public function run(): void
    {
        $this->logger->log("PURE PHP BOT STARTED - Webhook mode activated");
        $this->processUpdate();
    }
}