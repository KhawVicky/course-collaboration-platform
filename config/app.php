<?php
declare(strict_types=1);

// Store the main application settings and URL helpers.

const APP_NAME = 'Course Collaboration Platform';

$configuredBaseUrl = getenv('APP_BASE_URL');
$projectRoot = str_replace('\\', '/', dirname(__DIR__));
$documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
    ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']), '/')
    : '';
$detectedBaseUrl = $documentRoot !== '' && str_starts_with(strtolower($projectRoot), strtolower($documentRoot))
    ? substr($projectRoot, strlen($documentRoot))
    : '';

define('BASE_URL', rtrim($configuredBaseUrl !== false ? $configuredBaseUrl : $detectedBaseUrl, '/'));

// Build a URL inside this application.
function url(string $path = ''): string
{
    return BASE_URL . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

// Escape text before showing it in HTML.
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
