<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['student']);

$userId = (int) $_SESSION['user']['id'];
$stmt = $pdo->prepare('SELECT id, fullname, email, lrn, grade_level FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: '';
    $lrn = trim((string) ($_POST['lrn'] ?? ''));
    $gradeLevel = trim((string) ($_POST['grade_level'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');

    if ($error === '' && ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $gradeLevel === '' || !preg_match('/^\d{12}$/', $lrn))) {
        $error = 'Please provide valid profile details.';
    } elseif ($error === '') {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $check->execute([':email' => $email, ':id' => $userId]);

        if ($check->fetch()) {
            $error = 'Email is already used by another account.';
        } else {
            if ($newPassword !== '') {
                if (strlen($newPassword) < 8) {
                    $error = 'New password must be at least 8 characters.';
                } else {
                    $update = $pdo->prepare('UPDATE users SET fullname = :fullname, email = :email, lrn = :lrn, grade_level = :grade_level, password = :password WHERE id = :id');
                    $update->execute([
                        ':fullname' => $fullname,
                        ':email' => $email,
                        ':lrn' => $lrn,
                        ':grade_level' => $gradeLevel,
                        ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
                        ':id' => $userId,
                    ]);
                }
            } else {
                $update = $pdo->prepare('UPDATE users SET fullname = :fullname, email = :email, lrn = :lrn, grade_level = :grade_level WHERE id = :id');
                $update->execute([
                    ':fullname' => $fullname,
                    ':email' => $email,
                    ':lrn' => $lrn,
                    ':grade_level' => $gradeLevel,
                    ':id' => $userId,
                ]);
            }

            if ($error === '') {
                $_SESSION['user']['fullname'] = $fullname;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['lrn'] = $lrn;
                $_SESSION['user']['grade_level'] = $gradeLevel;
                logSystemActivity($pdo, $userId, 'Updated student profile');
                $success = 'Profile updated successfully.';
                $stmt->execute([':id' => $userId]);
                $user = $stmt->fetch();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/_navbar.php'; ?>
<div class="container py-4" style="max-width: 760px;">
    <h3 class="mb-3">My Profile</h3>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
    <div class="card card-shadow">
        <div class="card-body">
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="fullname" value="<?php echo e($user['fullname']); ?>" required></div>
                <div class="mb-3"><label class="form-label">LRN (Learner Reference Number)</label><input class="form-control" name="lrn" value="<?php echo e($user['lrn']); ?>" inputmode="numeric" maxlength="12" pattern="[0-9]{12}" title="LRN must be exactly 12 digits" required></div>
                <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?php echo e($user['email']); ?>" required></div>
                <div class="mb-3"><label class="form-label">Grade Level</label>
                    <select class="form-select" name="grade_level" required>
                        <option <?php echo $user['grade_level'] === 'Grade 7' ? 'selected' : ''; ?>>Grade 7</option>
                        <option <?php echo $user['grade_level'] === 'Grade 8' ? 'selected' : ''; ?>>Grade 8</option>
                        <option <?php echo $user['grade_level'] === 'Grade 9' ? 'selected' : ''; ?>>Grade 9</option>
                        <option <?php echo $user['grade_level'] === 'Grade 10' ? 'selected' : ''; ?>>Grade 10</option>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">New Password (optional)</label><input class="form-control" type="password" name="new_password" minlength="8"></div>
                <button class="btn btn-primary" type="submit">Save Changes</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
