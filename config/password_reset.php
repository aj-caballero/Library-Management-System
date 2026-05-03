<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

function ensurePasswordResetTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

function createPasswordResetToken(PDO $pdo, string $email, int $expiryMinutes = 60): string
{
    ensurePasswordResetTable($pdo);

    // remove existing tokens for this email
    $del = $pdo->prepare('DELETE FROM password_resets WHERE email = :email');
    $del->execute([':email' => $email]);

    $token = bin2hex(random_bytes(32));
    $tokenHash = password_hash($token, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (:email, :token_hash, DATE_ADD(NOW(), INTERVAL :mins MINUTE))');
    $stmt->execute([
        ':email' => $email,
        ':token_hash' => $tokenHash,
        ':mins' => $expiryMinutes,
    ]);

    return $token;
}

function sendPasswordResetEmail(string $email, string $fullname, string $token): bool
{
    $resetUrl = basePath('/reset-password.php') . '?email=' . rawurlencode($email) . '&token=' . rawurlencode($token);

    $mailer = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mailer->isSMTP();
        $mailer->Host = MAIL_HOST;
        $mailer->SMTPAuth = true;
        $mailer->Username = MAIL_USERNAME;
        $mailer->Password = MAIL_PASSWORD;
        $mailer->Port = (int) MAIL_PORT;
        $mailer->SMTPSecure = MAIL_ENCRYPTION;
        $mailer->CharSet = 'UTF-8';

        $mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mailer->addAddress($email, $fullname);

        $mailer->isHTML(true);
        $mailer->Subject = 'Paliparan NHS Online Library - Password Reset';
        $mailer->Body = "<p>Hello " . e($fullname) . ",</p>"
            . "<p>We received a request to reset your password. Click the link below to choose a new password. This link will expire in 60 minutes.</p>"
            . "<p><a href=\"" . e($resetUrl) . "\">Reset your password</a></p>"
            . "<p>If you did not request a password reset, you can ignore this email.</p>"
            . "<p>Best regards,<br>Paliparan NHS Library System</p>";

        $mailer->AltBody = "Hello $fullname\n\nVisit this link to reset your password: $resetUrl\n\nIf you did not request this, ignore this email.";

        $mailer->send();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function verifyPasswordResetToken(PDO $pdo, string $email, string $token): bool
{
    ensurePasswordResetTable($pdo);

    $stmt = $pdo->prepare('SELECT token_hash, expires_at FROM password_resets WHERE email = :email AND expires_at > NOW() LIMIT 1');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();
    if (!$row) return false;

    $hash = $row['token_hash'];
    return password_verify($token, $hash);
}

function consumePasswordResetToken(PDO $pdo, string $email): void
{
    ensurePasswordResetTable($pdo);
    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE email = :email');
    $stmt->execute([':email' => $email]);
}
