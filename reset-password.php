<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/password_reset.php';

$error = '';
$success = '';

$email = trim((string) ($_GET['email'] ?? $_POST['email'] ?? ''));
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($error === '' && ($password === '' || $confirm === '')) {
        $error = 'Please complete all required fields.';
    } elseif ($error === '' && $password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($error === '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $token === '') {
            $error = 'Invalid reset link.';
        } elseif (!verifyPasswordResetToken($pdo, $email, $token)) {
            $error = 'Reset link is invalid or has expired.';
        } else {
            $pwHash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password = :pw WHERE email = :email');
            $update->execute([':pw' => $pwHash, ':email' => $email]);
            consumePasswordResetToken($pdo, $email);
            $success = 'Your password has been reset. You may now log in.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | Online Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body class="login-page">
    <div class="container login-wrapper d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-shadow">
                    <div class="card-header brand-gradient text-white text-center py-3">
                        <h4 class="mb-0">Reset Password</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo e($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success !== ''): ?>
                            <div class="alert alert-success"><?php echo e($success); ?></div>
                        <?php endif; ?>

                        <?php if ($success === ''): ?>
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="email" value="<?php echo e($email); ?>">
                            <input type="hidden" name="token" value="<?php echo e($token); ?>">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                        <?php else: ?>
                        <div class="text-center mt-3">
                            <a href="/Library Management System/login.php">Back to login</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
