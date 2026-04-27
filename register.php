<?php

declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: '';
    $password = (string) ($_POST['password'] ?? '');
    $gradeLevel = trim((string) ($_POST['grade_level'] ?? ''));

    if ($error === '' && ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || $gradeLevel === '')) {
        $error = 'Please fill out all fields. Password must be at least 8 characters.';
    } elseif ($error === '') {
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute([':email' => $email]);

        if ($checkStmt->fetch()) {
            $error = 'Email is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare('INSERT INTO users (fullname, email, password, grade_level, role, created_at) VALUES (:fullname, :email, :password, :grade_level, :role, NOW())');
            $insertStmt->execute([
                ':fullname' => $fullname,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':grade_level' => $gradeLevel,
                ':role' => 'student',
            ]);

            $success = 'Registration successful. You can now log in.';
            logSystemActivity($pdo, null, 'New student registration submitted');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Online Library System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
    <div class="container login-wrapper d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card card-shadow">
                    <div class="card-header brand-gradient text-white py-3">
                        <h4 class="mb-0 text-center">Student Registration</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error !== ''): ?>
                            <div class="alert alert-danger"><?php echo e($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success !== ''): ?>
                            <div class="alert alert-success"><?php echo e($success); ?></div>
                        <?php endif; ?>
                        <form method="POST" novalidate>
                            <?php echo csrfField(); ?>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Grade Level</label>
                                <select name="grade_level" class="form-select" required>
                                    <option value="">Select grade level</option>
                                    <option>Grade 7</option>
                                    <option>Grade 8</option>
                                    <option>Grade 9</option>
                                    <option>Grade 10</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" minlength="8" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Account</button>
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
