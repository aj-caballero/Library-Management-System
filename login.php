<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'superadmin') {
        redirect(basePath('/superadmin/dashboard.php'));
    }
    if ($role === 'admin') {
        redirect(basePath('/admin/dashboard.php'));
    }
    redirect(basePath('/student/dashboard.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: '';
    $password = $_POST['password'] ?? '';

    if ($error === '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '')) {
        $error = 'Please provide a valid email and password.';
    } elseif ($error === '') {
        $stmt = $pdo->prepare('SELECT id, fullname, email, password, grade_level, role, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            logSystemActivity($pdo, (int) $user['id'], 'User logged in');

            if ($user['role'] === 'superadmin') {
                redirect(basePath('/superadmin/dashboard.php'));
            }
            if ($user['role'] === 'admin') {
                redirect(basePath('/admin/dashboard.php'));
            }
            redirect(basePath('/student/dashboard.php'));
        }

        $error = 'Invalid credentials or inactive account.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Online Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
    <div class="container login-wrapper d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-shadow">
                    <div class="card-header brand-gradient text-white text-center py-3">
                        <h4 class="mb-0">Paliparan NHS Online Library</h4>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-4">Sign in to continue.</p>
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo e($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" novalidate>
                            <?php echo csrfField(); ?>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="/Library Management System/register.php" class="small">Student registration</a>
                        </div>
                        <hr>
                        <p class="small text-muted mb-0">Demo password for seeded accounts: <strong>password123</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
