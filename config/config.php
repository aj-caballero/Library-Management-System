<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'library_management_system';
$username = 'root';
$password = '';

// SMTP settings for PHPMailer. Replace these with your real mail provider values.
// Gmail users typically need an App Password instead of their normal password.
if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', 'smtp.example.com');
    define('MAIL_PORT', 587);
    define('MAIL_USERNAME', 'your-email@example.com');
    define('MAIL_PASSWORD', 'your-email-password-or-app-password');
    define('MAIL_FROM_ADDRESS', 'your-email@example.com');
    define('MAIL_FROM_NAME', 'Paliparan NHS Online Library');
    define('MAIL_ENCRYPTION', 'tls');
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password, $options);
} catch (PDOException $exception) {
    die('Database connection failed: ' . $exception->getMessage());
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
