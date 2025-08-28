<?php

use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;

class NutgramBot {
    private $bot;
    private $logger;

    public function __construct($token) {
        require_once 'vendor/autoload.php';
        require_once 'BotLogger.php';

        $this->logger = new BotLogger();
        $this->bot = new Nutgram($token);
        $this->bot->setRunningMode(Webhook::class);

        $this->setupHandlers();
    }

    private function setupHandlers(): void
    {
        $this->bot->onUpdate(function ($bot) {
            $update = $bot->getUpdate();
            $this->logger->log("RAW UPDATE RECEIVED", $update->toArray());
        });

        $this->bot->onMessage(function ($bot) {
            $this->logger->log("MESSAGE RECEIVED", $bot->message()->toArray());
        });

        $this->bot->onText('hi', function ($bot) {
            $parameters = ['text' => 'hello'];
            $this->logOutgoingRequest('sendMessage', $bot->chatId(), $parameters);
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
            // Fallback handler for unprocessed updates
        });
    }

    private function handleFileCommand($bot, $type, $extension, $mimeType, $method): void
    {
        $filePath = __DIR__ . "/files/{$type}.{$extension}";

        if (file_exists($filePath)) {
            $caption = "THIS IS CAPTION";
            $parameters = ['caption' => $caption, 'file_path' => $filePath];
            $this->logOutgoingRequest($method, $bot->chatId(), $parameters);
            $bot->$method(fopen($filePath, 'r'), caption: $caption);
        } else {
            $parameters = ['text' => ucfirst($type) . ' file not found.'];
            $this->logOutgoingRequest('sendMessage', $bot->chatId(), $parameters);
            $bot->sendMessage(ucfirst($type) . ' file not found.');
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

        // Save file to storage folder
        $this->saveFileToStorage($bot, $fileDetails, $type);

        $parameters = ['text' => json_encode($fileDetails, JSON_PRETTY_PRINT)];
        $this->logOutgoingRequest('sendMessage', $bot->chatId(), $parameters);
        $bot->sendMessage(json_encode($fileDetails, JSON_PRETTY_PRINT));
    }

    private function saveFileToStorage($bot, $fileDetails, $type): void
    {
        try {
            // Get file info from Telegram API
            $fileResponse = $bot->api('getFile', ['file_id' => $fileDetails['file_id']]);

            if ($fileResponse && isset($fileResponse['file_path'])) {
                $filePath = $fileResponse['file_path'];
                $fileUrl = "https://api.telegram.org/file/bot" . $bot->getToken() . "/" . $filePath;

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

    private function logOutgoingRequest($method, $chatId, $parameters = []): void
    {
        $url = "https://api.telegram.org/bot" . $this->bot->getToken() . "/" . $method;
        $this->logger->log("SEND REQUEST TO BOT SERVER : $url", $parameters);
    }

    public function run(): void
    {
        $this->logger->log("NUTGRAM BOT STARTED - Webhook mode activated");
        $this->bot->run();
    }
}