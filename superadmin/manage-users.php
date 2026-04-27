<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isValidCsrf($_POST['csrf_token'] ?? null)) {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = (string) ($_POST['role'] ?? 'student');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($userId > 0 && in_array($role, ['superadmin', 'admin', 'student'], true)) {
            $stmt = $pdo->prepare('UPDATE users SET role = :role, is_active = :is_active WHERE id = :id');
            $stmt->execute([
                ':role' => $role,
                ':is_active' => $isActive,
                ':id' => $userId,
            ]);
            logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Updated user role or status');
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
            <div class="card card-shadow">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Name</th><th>Email</th><th>Grade</th><th>Role</th><th>Active</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo e($user['fullname']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td><?php echo e($user['grade_level'] ?? '-'); ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-2 align-items-center">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                        <select name="role" class="form-select form-select-sm">
                                            <option value="superadmin" <?php echo $user['role'] === 'superadmin' ? 'selected' : ''; ?>>superadmin</option>
                                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                                            <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>student</option>
                                        </select>
                                </td>
                                <td>
                                        <input type="checkbox" name="is_active" <?php echo (int) $user['is_active'] === 1 ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                    </form>
                                </td>
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
