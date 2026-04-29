<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['superadmin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    } else {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $fullname = trim((string) ($_POST['fullname'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $gradeLevelInput = trim((string) ($_POST['grade_level'] ?? ''));
        $gradeLevel = $gradeLevelInput === '' ? null : $gradeLevelInput;
        $role = (string) ($_POST['role'] ?? 'student');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($userId <= 0 || $fullname === '' || $email === '') {
            $error = 'Please complete all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (!in_array($role, ['admin', 'student'], true)) {
            $error = 'Only admin or student roles are editable here.';
        } else {
            $targetStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
            $targetStmt->execute([':id' => $userId]);
            $targetUser = $targetStmt->fetch();

            if (!$targetUser || !in_array((string) $targetUser['role'], ['admin', 'student'], true)) {
                $error = 'Only student and admin accounts can be edited.';
            } else {
                try {
                    $stmt = $pdo->prepare('UPDATE users SET fullname = :fullname, email = :email, grade_level = :grade_level, role = :role, is_active = :is_active WHERE id = :id');
                    $stmt->execute([
                        ':fullname' => $fullname,
                        ':email' => $email,
                        ':grade_level' => $gradeLevel,
                        ':role' => $role,
                        ':is_active' => $isActive,
                        ':id' => $userId,
                    ]);
                    logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Updated user details');
                    $success = 'User details updated successfully.';
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $error = 'That email address is already in use.';
                    } else {
                        $error = 'Unable to update user details right now.';
                    }
                }
            }
        }
    }
}

$users = $pdo->query('SELECT id, fullname, email, grade_level, role, is_active, created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <h5 class="text-white mb-4">Super Admin</h5>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="manage-users.php">Manage Users</a>
                <a class="nav-link" href="system-logs.php">System Logs</a>
                <a class="nav-link" href="settings.php">Settings</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-3">Manage Users</h3>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
            <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <div class="card card-shadow">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Name</th><th>Email</th><th>Grade</th><th>Role</th><th>Active</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <?php if (in_array($user['role'], ['admin', 'student'], true)): ?>
                                <form method="POST">
                                    <td>
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                        <input type="text" name="fullname" class="form-control form-control-sm" value="<?php echo e($user['fullname']); ?>" required>
                                    </td>
                                    <td><input type="email" name="email" class="form-control form-control-sm" value="<?php echo e($user['email']); ?>" required></td>
                                    <td><input type="text" name="grade_level" class="form-control form-control-sm" value="<?php echo e($user['grade_level'] ?? ''); ?>" placeholder="Optional"></td>
                                    <td>
                                        <select name="role" class="form-select form-select-sm">
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                                            <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>student</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="is_active" <?php echo (int) $user['is_active'] === 1 ? 'checked' : ''; ?>>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                    </td>
                                </form>
                                <?php else: ?>
                                    <td><?php echo e($user['fullname']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><?php echo e($user['grade_level'] ?? '-'); ?></td>
                                    <td><span class="badge bg-dark"><?php echo e($user['role']); ?></span></td>
                                    <td><span class="badge bg-<?php echo (int) $user['is_active'] === 1 ? 'success' : 'secondary'; ?>"><?php echo (int) $user['is_active'] === 1 ? 'active' : 'inactive'; ?></span></td>
                                    <td><span class="text-muted small">Protected</span></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
