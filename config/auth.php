<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function ensureLoggedIn(): void
{
    if (empty($_SESSION['user'])) {
        redirect('/Library Management System/login.php');
    }
}

function ensureRole(array $roles): void
{
    ensureLoggedIn();

    $currentRole = $_SESSION['user']['role'] ?? '';
    if (!in_array($currentRole, $roles, true)) {
        redirect('/Library Management System/login.php');
    }
}

function logSystemActivity(PDO $pdo, ?int $userId, string $activity): void
{
    $stmt = $pdo->prepare('INSERT INTO system_logs (user_id, activity, created_at) VALUES (:user_id, :activity, NOW())');
    $stmt->execute([
        ':user_id' => $userId,
        ':activity' => $activity,
    ]);
}

function userDisplayName(): string
{
    return $_SESSION['user']['fullname'] ?? 'User';
}

function basePath(string $path = ''): string
{
    $prefix = '/Library Management System';
    return $prefix . $path;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function isValidCsrf(?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }

    return hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token);
}

function uploadErrorMessage(int $errorCode): string
{
    return match ($errorCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file is too large.',
        UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        default => 'File upload failed.',
    };
}

function secureUpload(
    array $file,
    string $prefix,
    string $destinationDir,
    array $allowedExtensions,
    array $allowedMimeTypes,
    int $maxBytes,
    ?string &$error
): ?string {
    $error = null;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
        return null;
    }

    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $error = uploadErrorMessage($uploadError);
        return null;
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        $error = 'Uploaded file exceeds allowed size.';
        return null;
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmpName)) {
        $error = 'Invalid upload source.';
        return null;
    }

    $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        $error = 'File type is not allowed.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpName);
    if ($mimeType === false || !in_array($mimeType, $allowedMimeTypes, true)) {
        $error = 'Invalid file content type.';
        return null;
    }

    if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
        $error = 'Upload directory is not available.';
        return null;
    }

    $filename = sprintf('%s_%d_%d.%s', $prefix, time(), random_int(100, 999), $extension);
    $destinationPath = rtrim($destinationDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $destinationPath)) {
        $error = 'Failed to save uploaded file.';
        return null;
    }

    return $filename;
}
