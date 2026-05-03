<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/password_reset.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: '';

    if ($error === '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    }

    // Always show a generic success message to avoid email enumeration
    if ($error === '') {
        $stmt = $pdo->prepare('SELECT id, fullname, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && (int) $user['is_active'] === 1) {
            $token = createPasswordResetToken($pdo, $email, 60);
            // best-effort send
            sendPasswordResetEmail($email, $user['fullname'] ?? 'User', $token);
        }

        $success = 'If an account with that email exists, a password reset link has been sent.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Online Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body class="login-page">
    <div class="container login-wrapper d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-shadow">
                    <div class="card-header brand-gradient text-white text-center py-3">
                        <h4 class="mb-0">Password Reset</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo e($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success !== ''): ?>
                            <div class="alert alert-success"><?php echo e($success); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Send reset link</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="/Library Management System/login.php">Back to login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
