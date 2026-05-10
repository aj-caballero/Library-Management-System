<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/otp.php';

$error = '';
$success = '';
$resendCooldownSeconds = 60;
$remainingCooldown = 0;

// Check if user came from registration
if (empty($_SESSION['otp_email'])) {
    redirect('/Library Management System/register.php');
}

$email = $_SESSION['otp_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $action = (string) ($_POST['action'] ?? 'verify');

    if ($error === '' && $action === 'resend') {
        $cooldownStmt = $pdo->prepare('SELECT LEAST(60, GREATEST(0, 60 - TIMESTAMPDIFF(SECOND, created_at, NOW()))) AS remaining_cooldown FROM otp_verifications WHERE email = :email LIMIT 1');
        $cooldownStmt->execute([':email' => $email]);
        $cooldownRecord = $cooldownStmt->fetch();

        if (!$cooldownRecord) {
            $error = 'No pending OTP found. Please register again.';
            unset($_SESSION['otp_email']);
        } else {
            $remainingCooldown = (int) ($cooldownRecord['remaining_cooldown'] ?? 0);

            if ($remainingCooldown > 0) {
                $error = 'Please wait ' . $remainingCooldown . ' seconds before requesting a new OTP.';
            } elseif (resendOTPVerification($pdo, $email)) {
                $success = 'A new OTP has been sent to your email.';
            } else {
                $error = 'Failed to resend OTP. Please try again.';
            }
        }
    }

    $otp = trim((string) ($_POST['otp'] ?? ''));

    if ($error === '' && $action !== 'resend' && $otp === '') {
        $error = 'Please enter the OTP.';
    } elseif ($error === '' && $action !== 'resend') {
        // Verify OTP
        $otpData = verifyOTP($pdo, $email, $otp);

        if ($otpData === null) {
            $attempts = incrementOTPAttempts($pdo, $email);

            if ($attempts >= 5) {
                $error = 'Too many failed attempts. Please register again.';
                clearOTP($pdo, $email);
                unset($_SESSION['otp_email']);
            } else {
                $error = "Invalid OTP. Attempts remaining: " . (5 - $attempts);
            }
        } else {
            // OTP is valid, create the user account
            try {
                $insertStmt = $pdo->prepare('INSERT INTO users (fullname, email, lrn, password, grade_level, role, created_at) VALUES (:fullname, :email, :lrn, :password, :grade_level, :role, NOW())');
                $insertStmt->execute([
                    ':fullname' => $otpData['fullname'],
                    ':email' => $otpData['email'],
                    ':lrn' => $otpData['lrn'],
                    ':password' => $otpData['password_hash'],
                    ':grade_level' => $otpData['grade_level'],
                    ':role' => 'student',
                ]);

                // Clear OTP record
                clearOTP($pdo, $email);
                unset($_SESSION['otp_email']);

                logSystemActivity($pdo, null, 'New student registration verified');

                $success = 'Registration successful! Redirecting to login...';
                header('Refresh: 2; url=/Library Management System/login.php');
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

// Count remaining time for OTP
$otpStmt = $pdo->prepare('SELECT expires_at, LEAST(60, GREATEST(0, 60 - TIMESTAMPDIFF(SECOND, created_at, NOW()))) AS remaining_cooldown FROM otp_verifications WHERE email = :email LIMIT 1');
$otpStmt->execute([':email' => $email]);
$otpRecord = $otpStmt->fetch();
$expiresAt = $otpRecord ? $otpRecord['expires_at'] : null;

if ($otpRecord && isset($otpRecord['remaining_cooldown'])) {
    $remainingCooldown = (int) $otpRecord['remaining_cooldown'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | Online Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
    <div class="container login-wrapper d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card card-shadow">
                    <div class="card-header brand-gradient text-white py-3">
                        <h4 class="mb-0 text-center">Email Verification</h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">
                            We've sent a 6-digit OTP to<br>
                            <strong><?php echo e($email); ?></strong>
                        </p>

                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo e($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success !== ''): ?>
                            <div class="alert alert-success"><?php echo e($success); ?></div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="verify">
                            <div class="mb-3">
                                <label class="form-label">Enter OTP</label>
                                <input type="text" 
                                       name="otp" 
                                       class="form-control text-center" 
                                       placeholder="000000" 
                                       maxlength="6" 
                                       inputmode="numeric" 
                                       required
                                       pattern="[0-9]{6}">
                                <small class="form-text text-muted">
                                    Enter the 6-digit code sent to your email
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Verify & Create Account</button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="small text-muted mb-2">Didn't receive the code?</p>
                            <form method="POST" class="mb-2">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="resend">
                                <button type="submit" id="resendOtpBtn" class="btn btn-outline-primary btn-sm" <?php echo $remainingCooldown > 0 ? 'disabled' : ''; ?>>
                                    Resend OTP
                                </button>
                            </form>
                            <p id="resendTimer" class="small text-muted mb-2" data-remaining="<?php echo (int) $remainingCooldown; ?>"></p>
                            <a href="/Library Management System/register.php" class="btn btn-ghost btn-sm">Back to Registration</a>
                        </div>

                        <?php if ($expiresAt): ?>
                            <div class="alert alert-info mt-3 mb-0 text-center small">
                                OTP expires: <?php echo e($expiresAt); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Allow only numbers in OTP input
        document.querySelector('input[name="otp"]')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        const resendButton = document.getElementById('resendOtpBtn');
        const resendTimer = document.getElementById('resendTimer');
        let remaining = parseInt(resendTimer?.dataset.remaining || '0', 10);

        function updateResendState() {
            if (!resendButton || !resendTimer) {
                return;
            }

            if (remaining > 0) {
                resendButton.disabled = true;
                resendTimer.textContent = `You can resend OTP in ${remaining}s`;
                remaining -= 1;
            } else {
                resendButton.disabled = false;
                resendTimer.textContent = 'You can now request a new OTP.';
            }
        }

        if (resendButton && resendTimer) {
            updateResendState();
            if (remaining > 0) {
                setInterval(updateResendState, 1000);
            }
        }
    </script>
</body>
</html>
