<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Ensure the OTP verification table exists before using it.
 */
function ensureOtpVerificationTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS otp_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        otp VARCHAR(6) NOT NULL,
        fullname VARCHAR(150) NOT NULL,
        lrn VARCHAR(50) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        attempts INT DEFAULT 0,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT chk_otp_lrn_12_digits CHECK (CHAR_LENGTH(lrn) = 12 AND lrn REGEXP '^[0-9]{12}$')
    )");
}

/**
 * Generate a random 6-digit OTP
 */
function generateOTP(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Send OTP via email
 */
function sendOTPEmail(string $email, string $otp, string $fullname): bool
{
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

        $mailer->isHTML(false);
        $mailer->Subject = 'Paliparan NHS Online Library - Email Verification';
        $mailer->Body = "Hello $fullname,\n\n"
            . "Welcome to Paliparan NHS Online Library!\n\n"
            . "Your OTP (One-Time Password) for email verification is: $otp\n\n"
            . "This OTP will expire in 10 minutes.\n\n"
            . "If you did not request this OTP, please ignore this email.\n\n"
            . "Best regards,\nPaliparan NHS Library System";

        $mailer->send();
        return true;
    } catch (Throwable $exception) {
        return false;
    }
}

/**
 * Save OTP verification record
 */
function saveOTPVerification(
    PDO $pdo,
    string $email,
    string $otp,
    string $fullname,
    string $lrn,
    string $gradeLevel,
    string $passwordHash
): bool {
    ensureOtpVerificationTable($pdo);

    // Delete any existing unverified OTPs for this email
    $deleteStmt = $pdo->prepare('DELETE FROM otp_verifications WHERE email = :email');
    $deleteStmt->execute([':email' => $email]);

    // Insert new OTP
    $stmt = $pdo->prepare('INSERT INTO otp_verifications (email, otp, fullname, lrn, grade_level, password_hash, expires_at) VALUES (:email, :otp, :fullname, :lrn, :grade_level, :password_hash, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
    
    return $stmt->execute([
        ':email' => $email,
        ':otp' => $otp,
        ':fullname' => $fullname,
        ':lrn' => $lrn,
        ':grade_level' => $gradeLevel,
        ':password_hash' => $passwordHash,
    ]);
}

/**
 * Verify OTP and get verification data
 */
function verifyOTP(PDO $pdo, string $email, string $otp): ?array
{
    ensureOtpVerificationTable($pdo);

    $stmt = $pdo->prepare('SELECT * FROM otp_verifications WHERE email = :email AND otp = :otp AND expires_at > NOW() LIMIT 1');
    $stmt->execute([
        ':email' => $email,
        ':otp' => $otp,
    ]);

    return $stmt->fetch() ?: null;
}

/**
 * Increment OTP attempts
 */
function incrementOTPAttempts(PDO $pdo, string $email): int
{
    ensureOtpVerificationTable($pdo);

    $stmt = $pdo->prepare('UPDATE otp_verifications SET attempts = attempts + 1 WHERE email = :email');
    $stmt->execute([':email' => $email]);

    $getStmt = $pdo->prepare('SELECT attempts FROM otp_verifications WHERE email = :email LIMIT 1');
    $getStmt->execute([':email' => $email]);
    $result = $getStmt->fetch();

    return (int) ($result['attempts'] ?? 0);
}

/**
 * Clear OTP after successful verification
 */
function clearOTP(PDO $pdo, string $email): void
{
    ensureOtpVerificationTable($pdo);

    $stmt = $pdo->prepare('DELETE FROM otp_verifications WHERE email = :email');
    $stmt->execute([':email' => $email]);
}
