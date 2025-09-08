<?php
/**
 * User: Moradi ( @MortezaMoradi )
 * Date: 8/26/2025  Time: 5:15 PM
 * Combined Telegram bot with two implementations: Pure PHP or Nutgram
 * Set BOT_TYPE in Const.php: 'nutgram' or 'purephp'
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/storage/php_errors.log');
error_reporting(E_ALL);

require_once 'bootstrap.php';
require_once 'NutgramBot.php';
require_once 'PurePHPBot.php';

// Configuration - Set BOT_TYPE in your Const.php file
// Options: 'nutgram' or 'purephp'
$botType = defined('BOT_TYPE') ? BOT_TYPE : 'purephp';

// Initialize and run the appropriate bot based on configuration
if ($botType === 'purephp') {
    $bot = new PurePHPBot(BOTAPI);
    $bot->run();
} else {
    $bot = new NutgramBot(BOTAPI);
    $bot->run();
}