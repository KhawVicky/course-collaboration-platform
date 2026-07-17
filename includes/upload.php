<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Set the maximum upload size for each file type.
const MATERIAL_MAX_BYTES = 10485760;
const SUBMISSION_MAX_BYTES = 20971520;

// Return the allowed extensions and MIME types.
function allowed_uploads(string $kind): array
{
    if ($kind === 'material') {
        return [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'ppt' => ['application/vnd.ms-powerpoint', 'application/octet-stream'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
            'xls' => ['application/vnd.ms-excel', 'application/octet-stream'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
        ];
    }
    return [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    ];
}

// Validate an uploaded file and save it with a safe name.
function store_upload(array $file, string $kind): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Choose a file that uploaded successfully.');
    }
    $size = (int) ($file['size'] ?? 0);
    $maximum = $kind === 'material' ? MATERIAL_MAX_BYTES : SUBMISSION_MAX_BYTES;
    if ($size < 1 || $size > $maximum) {
        throw new InvalidArgumentException('The file is empty or exceeds the ' . ($maximum / 1048576) . ' MB limit.');
    }
    $original = basename((string) ($file['name'] ?? ''));
    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = allowed_uploads($kind);
    if (!isset($allowed[$extension])) {
        throw new InvalidArgumentException('This file extension is not allowed.');
    }
    $temporaryPath = (string) ($file['tmp_name'] ?? '');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) ?: 'application/octet-stream';
    if (!in_array($mime, $allowed[$extension], true)) {
        throw new InvalidArgumentException('The file content does not match an allowed file type.');
    }
    $stored = bin2hex(random_bytes(20)) . '.' . $extension;
    $folder = $kind === 'material' ? 'materials' : 'submissions';
    $directory = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('The upload directory is unavailable.');
    }
    $destination = $directory . DIRECTORY_SEPARATOR . $stored;
    if (!move_uploaded_file($temporaryPath, $destination)) {
        throw new RuntimeException('The uploaded file could not be stored.');
    }
    return ['stored_filename' => $stored, 'original_filename' => $original, 'mime_type' => $mime, 'file_size' => $size, 'path' => $destination];
}
