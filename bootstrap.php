<?php
/**
 * User: Moradi ( @MortezaMoradi )
 * Date: 9/8/2025  Time: 11:57 AM
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once 'BotLogger.php';



const ROOT_BOT_URL = 'https://api.telegram.org';
# const ROOT_BOT_URL = 'http://localhost:8081';

function getBotApiUrl(?string $token = null, ?string $method = null): string
{
    $url = rtrim(ROOT_BOT_URL, '/');

    if ($token !== null) {
        $url .= "/bot{$token}";
    }

    if ($method !== null) {
        $url .= "/{$method}";
    }

    return $url."/";
}

const BOTAPI = "7382545541:AAHQk4A5_4LnYUdH-P3CtuSsTejdEKEWmb4";
