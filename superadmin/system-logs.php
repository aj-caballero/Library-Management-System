<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['superadmin']);

$logs = $pdo->query("SELECT sl.activity, sl.created_at, u.fullname, u.email FROM system_logs sl LEFT JOIN users u ON u.id = sl.user_id ORDER BY sl.created_at DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs</title>
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
                <a class="nav-link" href="manage-users.php">Manage Users</a>
                <a class="nav-link active" href="system-logs.php">System Logs</a>
                <a class="nav-link" href="settings.php">Settings</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-3">System Logs</h3>
            <div class="card card-shadow">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>User</th><th>Email</th><th>Activity</th><th>Timestamp</th></tr></thead>
                        <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo e($log['fullname'] ?? 'System'); ?></td>
                                <td><?php echo e($log['email'] ?? '-'); ?></td>
                                <td><?php echo e($log['activity']); ?></td>
                                <td><?php echo e($log['created_at']); ?></td>
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
