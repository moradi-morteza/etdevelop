<?php
/**
 * User: Moradi ( @MortezaMoradi )
 * Date: 8/26/2025  Time: 5:15 PM
 * Router with detailed logging for understanding Telegram bot messages
 */

require 'vendor/autoload.php';
require 'Const.php';

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;

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
        $this->log("RAW UPDATE RECEIVED", $update->toArray());
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

$logger = new BotLogger();
$bot = new Nutgram(BOTAPI);
$bot->setRunningMode(Webhook::class);

$bot->onUpdate(function (Nutgram $bot) use ($logger) {
    $logger->logUpdate($bot->getUpdate());
});

$bot->onMessage(function (Nutgram $bot) use ($logger) {
    $logger->logMessage($bot);
    $logger->log("MESSAGE HANDLER TRIGGERED");
});

$bot->onText('hi', function (Nutgram $bot) use ($logger) {
    $logger->log("TEXT COMMAND: hi", ($bot->message()));
    $bot->sendMessage('hello');
});

$bot->onText('image', function (Nutgram $bot) use ($logger) {
    $filePath = __DIR__ . '/files/image.jpg';
    $logger->log("TEXT COMMAND: image", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'file_path' => $filePath,
        'file_exists' => file_exists($filePath)
    ]);
    
    if (file_exists($filePath)) {
        $fileInfo = [
            'name' => 'image.jpg',
            'size' => filesize($filePath),
            'type' => 'image/jpeg',
            'path' => $filePath
        ];
        $caption = json_encode($fileInfo, JSON_PRETTY_PRINT);
        $bot->sendPhoto(fopen($filePath, 'r'), caption: $caption);
        $logger->log("IMAGE SENT SUCCESSFULLY", $fileInfo);
    } else {
        $bot->sendMessage('Image file not found.');
        $logger->log("IMAGE FILE NOT FOUND", ['path' => $filePath]);
    }
});

$bot->onText('video', function (Nutgram $bot) use ($logger) {
    $filePath = __DIR__ . '/files/video.mp4';
    $logger->log("TEXT COMMAND: video", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'file_path' => $filePath,
        'file_exists' => file_exists($filePath)
    ]);
    
    if (file_exists($filePath)) {
        $fileInfo = [
            'name' => 'video.mp4',
            'size' => filesize($filePath),
            'type' => 'video/mp4',
            'path' => $filePath
        ];
        $caption = json_encode($fileInfo, JSON_PRETTY_PRINT);
        $bot->sendVideo(fopen($filePath, 'r'), caption: $caption);
        $logger->log("VIDEO SENT SUCCESSFULLY", $fileInfo);
    } else {
        $bot->sendMessage('Video file not found.');
        $logger->log("VIDEO FILE NOT FOUND", ['path' => $filePath]);
    }
});

$bot->onText('pdf', function (Nutgram $bot) use ($logger) {
    $filePath = __DIR__ . '/files/pdf.pdf';
    $logger->log("TEXT COMMAND: pdf", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'file_path' => $filePath,
        'file_exists' => file_exists($filePath)
    ]);
    
    if (file_exists($filePath)) {
        $fileInfo = [
            'name' => 'pdf.pdf',
            'size' => filesize($filePath),
            'type' => 'application/pdf',
            'path' => $filePath
        ];
        $caption = json_encode($fileInfo, JSON_PRETTY_PRINT);
        $bot->sendDocument(fopen($filePath, 'r'), caption: $caption);
        $logger->log("PDF SENT SUCCESSFULLY", $fileInfo);
    } else {
        $bot->sendMessage('PDF file not found.');
        $logger->log("PDF FILE NOT FOUND", ['path' => $filePath]);
    }
});

$bot->onText('sound', function (Nutgram $bot) use ($logger) {
    $filePath = __DIR__ . '/files/sound.wav';
    $logger->log("TEXT COMMAND: sound", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'file_path' => $filePath,
        'file_exists' => file_exists($filePath)
    ]);
    
    if (file_exists($filePath)) {
        $fileInfo = [
            'name' => 'sound.wav',
            'size' => filesize($filePath),
            'type' => 'audio/wav',
            'path' => $filePath
        ];
        $caption = json_encode($fileInfo, JSON_PRETTY_PRINT);
        $bot->sendAudio(fopen($filePath, 'r'), caption: $caption);
        $logger->log("AUDIO SENT SUCCESSFULLY", $fileInfo);
    } else {
        $bot->sendMessage('Sound file not found.');
        $logger->log("AUDIO FILE NOT FOUND", ['path' => $filePath]);
    }
});

$bot->onDocument(function (Nutgram $bot) use ($logger) {
    $document = $bot->message()->document;
    
    $fileDetails = [
        'file_id' => $document->file_id,
        'file_unique_id' => $document->file_unique_id,
        'file_name' => $document->file_name ?? null,
        'mime_type' => $document->mime_type ?? null,
        'file_size' => $document->file_size ?? null,
        'thumbnail' => $document->thumbnail ? [
            'file_id' => $document->thumbnail->file_id,
            'file_unique_id' => $document->thumbnail->file_unique_id,
            'width' => $document->thumbnail->width,
            'height' => $document->thumbnail->height,
            'file_size' => $document->thumbnail->file_size ?? null
        ] : null
    ];
    
    $logger->log("DOCUMENT RECEIVED", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'document_details' => $fileDetails
    ]);
    
    $bot->sendMessage(json_encode($fileDetails, JSON_PRETTY_PRINT));
});

$bot->onPhoto(function (Nutgram $bot) use ($logger) {
    $photos = $bot->message()->photo;
    $largestPhoto = end($photos);
    
    $fileDetails = [
        'file_id' => $largestPhoto->file_id,
        'file_unique_id' => $largestPhoto->file_unique_id,
        'width' => $largestPhoto->width,
        'height' => $largestPhoto->height,
        'file_size' => $largestPhoto->file_size ?? null,
        'type' => 'photo'
    ];
    
    $logger->log("PHOTO RECEIVED", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'photo_count' => count($photos),
        'largest_photo' => $fileDetails
    ]);
    
    $bot->sendMessage(json_encode($fileDetails, JSON_PRETTY_PRINT));
});

$bot->onVideo(function (Nutgram $bot) use ($logger) {
    $video = $bot->message()->video;
    
    $fileDetails = [
        'file_id' => $video->file_id,
        'file_unique_id' => $video->file_unique_id,
        'width' => $video->width,
        'height' => $video->height,
        'duration' => $video->duration,
        'mime_type' => $video->mime_type ?? null,
        'file_size' => $video->file_size ?? null,
        'thumbnail' => $video->thumbnail ? [
            'file_id' => $video->thumbnail->file_id,
            'file_unique_id' => $video->thumbnail->file_unique_id,
            'width' => $video->thumbnail->width,
            'height' => $video->thumbnail->height,
            'file_size' => $video->thumbnail->file_size ?? null
        ] : null,
        'type' => 'video'
    ];
    
    $logger->log("VIDEO RECEIVED", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'video_details' => $fileDetails
    ]);
    
    $bot->sendMessage(json_encode($fileDetails, JSON_PRETTY_PRINT));
});

$bot->onAudio(function (Nutgram $bot) use ($logger) {
    $audio = $bot->message()->audio;
    
    $fileDetails = [
        'file_id' => $audio->file_id,
        'file_unique_id' => $audio->file_unique_id,
        'duration' => $audio->duration,
        'performer' => $audio->performer ?? null,
        'title' => $audio->title ?? null,
        'file_name' => $audio->file_name ?? null,
        'mime_type' => $audio->mime_type ?? null,
        'file_size' => $audio->file_size ?? null,
        'thumbnail' => $audio->thumbnail ? [
            'file_id' => $audio->thumbnail->file_id,
            'file_unique_id' => $audio->thumbnail->file_unique_id,
            'width' => $audio->thumbnail->width,
            'height' => $audio->thumbnail->height,
            'file_size' => $audio->thumbnail->file_size ?? null
        ] : null,
        'type' => 'audio'
    ];
    
    $logger->log("AUDIO RECEIVED", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'audio_details' => $fileDetails
    ]);
    
    $bot->sendMessage(json_encode($fileDetails, JSON_PRETTY_PRINT));
});

$bot->fallback(function (Nutgram $bot) use ($logger) {
    $logger->log("FALLBACK HANDLER TRIGGERED", [
        'chat_id' => $bot->chatId(),
        'user_id' => $bot->userId(),
        'update_type' => $bot->getUpdate()->getType()
    ]);
});

$logger->log("BOT STARTED - Webhook mode activated");
$bot->run();
