<?php
/**
 * User: Moradi ( @MortezaMoradi )
 * Date: 8/26/2025  Time: 5:15 PM
 * Combined Telegram bot with two implementations: Pure PHP or Nutgram
 * Set BOT_TYPE in Const.php: 'nutgram' or 'purephp'
 */

require 'Const.php';
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;
// Configuration - Set BOT_TYPE in your Const.php file
// Options: 'nutgram' or 'purephp'
$botType = defined('BOT_TYPE') ? BOT_TYPE : 'nutgram';

class BotLogger {
    private $logFile;
    
    public function __construct($logFile = 'bot.log') {
        $this->logFile = $logFile;
    }
    
    public function log($message, $data = null): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            $logEntry .= "\nDATA: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n" . str_repeat('-', 80) . "\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function logUpdate($update): void
    {
        if (is_object($update) && method_exists($update, 'toArray')) {
            $this->log("RAW UPDATE RECEIVED", $update->toArray());
        } else {
            $this->log("RAW UPDATE RECEIVED", $update);
        }
    }
    
    public function logMessage($bot): void
    {
        $message = $bot->message();
        $chat = $bot->chat();
        $user = $bot->user();
        
        $messageData = [
            'message_id' => $message->message_id ?? null,
            'date' => $message->date ?? null,
            'date_formatted' => isset($message->date) ? date('Y-m-d H:i:s', $message->date) : null,
            'text' => $message->text ?? null,
            'caption' => $message->caption ?? null,
            'chat' => [
                'id' => $chat->id ?? null,
                'type' => $chat->type ?? null,
                'title' => $chat->title ?? null,
                'username' => $chat->username ?? null,
                'first_name' => $chat->first_name ?? null,
                'last_name' => $chat->last_name ?? null
            ],
            'user' => [
                'id' => $user->id ?? null,
                'is_bot' => $user->is_bot ?? null,
                'first_name' => $user->first_name ?? null,
                'last_name' => $user->last_name ?? null,
                'username' => $user->username ?? null,
                'language_code' => $user->language_code ?? null
            ]
        ];
        
        if ($message->document ?? null) {
            $messageData['document'] = [
                'file_id' => $message->document->file_id,
                'file_unique_id' => $message->document->file_unique_id,
                'file_name' => $message->document->file_name ?? null,
                'mime_type' => $message->document->mime_type ?? null,
                'file_size' => $message->document->file_size ?? null
            ];
        }
        
        if ($message->photo ?? null) {
            $messageData['photo'] = array_map(function($photo) {
                return [
                    'file_id' => $photo->file_id,
                    'file_unique_id' => $photo->file_unique_id,
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'file_size' => $photo->file_size ?? null
                ];
            }, $message->photo);
        }
        
        if ($message->video ?? null) {
            $messageData['video'] = [
                'file_id' => $message->video->file_id,
                'file_unique_id' => $message->video->file_unique_id,
                'width' => $message->video->width,
                'height' => $message->video->height,
                'duration' => $message->video->duration,
                'mime_type' => $message->video->mime_type ?? null,
                'file_size' => $message->video->file_size ?? null
            ];
        }
        
        if ($message->audio ?? null) {
            $messageData['audio'] = [
                'file_id' => $message->audio->file_id,
                'file_unique_id' => $message->audio->file_unique_id,
                'duration' => $message->audio->duration,
                'performer' => $message->audio->performer ?? null,
                'title' => $message->audio->title ?? null,
                'file_name' => $message->audio->file_name ?? null,
                'mime_type' => $message->audio->mime_type ?? null,
                'file_size' => $message->audio->file_size ?? null
            ];
        }
        
        $this->log("MESSAGE RECEIVED", $messageData);
    }
}

class NutgramBot {
    private $bot;
    private $logger;
    
    public function __construct($token) {
        require 'vendor/autoload.php';
        
        $this->logger = new BotLogger();
        $this->bot = new Nutgram($token);
        $this->bot->setRunningMode(Webhook::class);
        
        $this->setupHandlers();
    }
    
    private function setupHandlers(): void
    {
        $this->bot->onUpdate(function ($bot) {
            $this->logger->logUpdate($bot->getUpdate());
        });
        
        $this->bot->onMessage(function ($bot) {
            $this->logger->logMessage($bot);
            $this->logger->log("MESSAGE HANDLER TRIGGERED");
        });
        
        $this->bot->onText('hi', function ($bot) {
            $this->logger->log("TEXT COMMAND: hi", ($bot->message()));
            $bot->sendMessage('hello');
        });
        
        $this->bot->onText('image', function ($bot) {
            $this->handleFileCommand($bot, 'image', 'jpg', 'image/jpeg', 'sendPhoto');
        });
        
        $this->bot->onText('video', function ($bot) {
            $this->handleFileCommand($bot, 'video', 'mp4', 'video/mp4', 'sendVideo');
        });
        
        $this->bot->onText('pdf', function ($bot) {
            $this->handleFileCommand($bot, 'pdf', 'pdf', 'application/pdf', 'sendDocument');
        });
        
        $this->bot->onText('sound', function ($bot) {
            $this->handleFileCommand($bot, 'sound', 'wav', 'audio/wav', 'sendAudio');
        });
        
        $this->bot->onDocument(function ($bot) {
            $this->handleReceivedFile($bot, 'document');
        });
        
        $this->bot->onPhoto(function ($bot) {
            $this->handleReceivedFile($bot, 'photo');
        });
        
        $this->bot->onVideo(function ($bot) {
            $this->handleReceivedFile($bot, 'video');
        });
        
        $this->bot->onAudio(function ($bot) {
            $this->handleReceivedFile($bot, 'audio');
        });
        
        $this->bot->fallback(function ($bot) {
            $this->logger->log("FALLBACK HANDLER TRIGGERED", [
                'chat_id' => $bot->chatId(),
                'user_id' => $bot->userId(),
                'update_type' => $bot->getUpdate()->getType()
            ]);
        });
    }
    
    private function handleFileCommand($bot, $type, $extension, $mimeType, $method): void
    {
        $filePath = __DIR__ . "/files/{$type}.{$extension}";
        $this->logger->log("TEXT COMMAND: {$type}", [
            'chat_id' => $bot->chatId(),
            'user_id' => $bot->userId(),
            'file_path' => $filePath,
            'file_exists' => file_exists($filePath)
        ]);
        
        if (file_exists($filePath)) {
            $fileInfo = [
                'name' => "{$type}.{$extension}",
                'size' => filesize($filePath),
                'type' => $mimeType,
                'path' => $filePath
            ];
            $caption = json_encode($fileInfo, JSON_PRETTY_PRINT);
            $bot->$method(fopen($filePath, 'r'), caption: $caption);
            $this->logger->log(strtoupper($type) . " SENT SUCCESSFULLY", $fileInfo);
        } else {
            $bot->sendMessage(ucfirst($type) . ' file not found.');
            $this->logger->log(strtoupper($type) . " FILE NOT FOUND", ['path' => $filePath]);
        }
    }
    
    private function handleReceivedFile($bot, $type): void
    {
        $message = $bot->message();
        
        if ($type === 'document') {
            $file = $message->document;
            $fileDetails = [
                'file_id' => $file->file_id,
                'file_unique_id' => $file->file_unique_id,
                'file_name' => $file->file_name ?? null,
                'mime_type' => $file->mime_type ?? null,
                'file_size' => $file->file_size ?? null,
                'thumbnail' => $file->thumbnail ? [
                    'file_id' => $file->thumbnail->file_id,
                    'file_unique_id' => $file->thumbnail->file_unique_id,
                    'width' => $file->thumbnail->width,
                    'height' => $file->thumbnail->height,
                    'file_size' => $file->thumbnail->file_size ?? null
                ] : null,
                'type' => $type
            ];
        } elseif ($type === 'photo') {
            $photos = $message->photo;
            $largestPhoto = end($photos);
            $fileDetails = [
                'file_id' => $largestPhoto->file_id,
                'file_unique_id' => $largestPhoto->file_unique_id,
                'width' => $largestPhoto->width,
                'height' => $largestPhoto->height,
                'file_size' => $largestPhoto->file_size ?? null,
                'type' => $type
            ];
        } elseif ($type === 'video') {
            $file = $message->video;
            $fileDetails = [
                'file_id' => $file->file_id,
                'file_unique_id' => $file->file_unique_id,
                'width' => $file->width,
                'height' => $file->height,
                'duration' => $file->duration,
                'mime_type' => $file->mime_type ?? null,
                'file_size' => $file->file_size ?? null,
                'thumbnail' => $file->thumbnail ? [
                    'file_id' => $file->thumbnail->file_id,
                    'file_unique_id' => $file->thumbnail->file_unique_id,
                    'width' => $file->thumbnail->width,
                    'height' => $file->thumbnail->height,
                    'file_size' => $file->thumbnail->file_size ?? null
                ] : null,
                'type' => $type
            ];
        } elseif ($type === 'audio') {
            $file = $message->audio;
            $fileDetails = [
                'file_id' => $file->file_id,
                'file_unique_id' => $file->file_unique_id,
                'duration' => $file->duration,
                'performer' => $file->performer ?? null,
                'title' => $file->title ?? null,
                'file_name' => $file->file_name ?? null,
                'mime_type' => $file->mime_type ?? null,
                'file_size' => $file->file_size ?? null,
                'thumbnail' => $file->thumbnail ? [
                    'file_id' => $file->thumbnail->file_id,
                    'file_unique_id' => $file->thumbnail->file_unique_id,
                    'width' => $file->thumbnail->width,
                    'height' => $file->thumbnail->height,
                    'file_size' => $file->thumbnail->file_size ?? null
                ] : null,
                'type' => $type
            ];
        }
        
        $this->logger->log(strtoupper($type) . " RECEIVED", [
            'chat_id' => $bot->chatId(),
            'user_id' => $bot->userId(),
            $type . '_details' => $fileDetails
        ]);
        
        $bot->sendMessage(json_encode($fileDetails, JSON_PRETTY_PRINT));
    }
    
    public function run(): void
    {
        $this->logger->log("NUTGRAM BOT STARTED - Webhook mode activated");
        $this->bot->run();
    }
}

class PurePHPBot {
    private $token;
    private $logger;
    
    public function __construct($token) {
        $this->token = $token;
        $this->logger = new BotLogger();
    }
    
    private function apiRequest($method, $parameters = []) {
        $url = "https://api.telegram.org/bot{$this->token}/{$method}";
        
        $postData = http_build_query($parameters);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        $this->logger->log("API REQUEST", [
            'method' => $method,
            'parameters' => $parameters,
            'response' => json_decode($response, true)
        ]);
        
        return json_decode($response, true);
    }
    
    public function sendMessage($chat_id, $text) {
        return $this->apiRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => $text
        ]);
    }
    
    public function sendPhoto($chat_id, $photo_path, $caption = '') {
        if (!file_exists($photo_path)) {
            $this->logger->log("PHOTO FILE NOT FOUND", ['path' => $photo_path]);
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/sendPhoto";
        
        $postData = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            'photo' => new CURLFile($photo_path)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $this->logger->log("PHOTO SENT", [
            'chat_id' => $chat_id,
            'photo_path' => $photo_path,
            'response' => json_decode($response, true)
        ]);
        
        return json_decode($response, true);
    }
    
    public function sendVideo($chat_id, $video_path, $caption = '') {
        if (!file_exists($video_path)) {
            $this->logger->log("VIDEO FILE NOT FOUND", ['path' => $video_path]);
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/sendVideo";
        
        $postData = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            'video' => new CURLFile($video_path)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $this->logger->log("VIDEO SENT", [
            'chat_id' => $chat_id,
            'video_path' => $video_path,
            'response' => json_decode($response, true)
        ]);
        
        return json_decode($response, true);
    }
    
    public function sendDocument($chat_id, $document_path, $caption = '') {
        if (!file_exists($document_path)) {
            $this->logger->log("DOCUMENT FILE NOT FOUND", ['path' => $document_path]);
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/sendDocument";
        
        $postData = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            'document' => new CURLFile($document_path)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $this->logger->log("DOCUMENT SENT", [
            'chat_id' => $chat_id,
            'document_path' => $document_path,
            'response' => json_decode($response, true)
        ]);
        
        return json_decode($response, true);
    }
    
    public function sendAudio($chat_id, $audio_path, $caption = '') {
        if (!file_exists($audio_path)) {
            $this->logger->log("AUDIO FILE NOT FOUND", ['path' => $audio_path]);
            return false;
        }
        
        $url = "https://api.telegram.org/bot{$this->token}/sendAudio";
        
        $postData = [
            'chat_id' => $chat_id,
            'caption' => $caption,
            'audio' => new CURLFile($audio_path)
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $this->logger->log("AUDIO SENT", [
            'chat_id' => $chat_id,
            'audio_path' => $audio_path,
            'response' => json_decode($response, true)
        ]);
        
        return json_decode($response, true);
    }
    
    public function processUpdate(): void
    {
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);
        
        if (!$update) {
            $this->logger->log("INVALID UPDATE RECEIVED");
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
        
        $messageData = [
            'message_id' => $message['message_id'] ?? null,
            'date' => $message['date'] ?? null,
            'date_formatted' => isset($message['date']) ? date('Y-m-d H:i:s', $message['date']) : null,
            'text' => $text,
            'caption' => $message['caption'] ?? null,
            'chat' => $message['chat'],
            'user' => $user
        ];
        
        if (isset($message['document'])) {
            $messageData['document'] = $message['document'];
        }
        
        if (isset($message['photo'])) {
            $messageData['photo'] = $message['photo'];
        }
        
        if (isset($message['video'])) {
            $messageData['video'] = $message['video'];
        }
        
        if (isset($message['audio'])) {
            $messageData['audio'] = $message['audio'];
        }
        
        $this->logger->log("MESSAGE RECEIVED", $messageData);
        
        if ($text) {
            switch (strtolower($text)) {
                case 'hi':
                    $this->logger->log("TEXT COMMAND: hi", $messageData);
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
        $this->logger->log("TEXT COMMAND: {$type}", [
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            'file_path' => $filePath,
            'file_exists' => file_exists($filePath)
        ]);
        
        if (file_exists($filePath)) {
            $fileInfo = [
                'name' => "{$type}.{$extension}",
                'size' => filesize($filePath),
                'type' => $mimeType,
                'path' => $filePath
            ];
            $caption = json_encode($fileInfo, JSON_PRETTY_PRINT);
            $this->$method($chat_id, $filePath, $caption);
            $this->logger->log(strtoupper($type) . " SENT SUCCESSFULLY", $fileInfo);
        } else {
            $this->sendMessage($chat_id, ucfirst($type) . ' file not found.');
            $this->logger->log(strtoupper($type) . " FILE NOT FOUND", ['path' => $filePath]);
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
        
        $logData = [
            'chat_id' => $chat_id,
            'user_id' => $user_id,
            $type . '_details' => $fileDetails
        ];
        
        if ($photo_count !== null) {
            $logData['photo_count'] = $photo_count;
        }
        
        $this->logger->log(strtoupper($type) . " RECEIVED", $logData);
        
        $this->sendMessage($chat_id, json_encode($fileDetails, JSON_PRETTY_PRINT));
    }
    
    public function run(): void
    {
        $this->logger->log("PURE PHP BOT STARTED - Webhook mode activated");
        $this->processUpdate();
    }
}

// Initialize and run the appropriate bot based on configuration
if ($botType === 'purephp') {
    $bot = new PurePHPBot(BOTAPI);
    $bot->run();
} else {
    $bot = new NutgramBot(BOTAPI);
    $bot->run();
}