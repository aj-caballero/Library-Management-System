<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['superadmin']);

$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$activeStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1")->fetchColumn();
$recentActivities = $pdo->query("SELECT sl.activity, sl.created_at, u.fullname FROM system_logs sl LEFT JOIN users u ON u.id = sl.user_id ORDER BY sl.created_at DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <h5 class="text-white mb-4">Super Admin</h5>
            <nav class="nav flex-column">
                <a class="nav-link active" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="manage-users.php">Manage Users</a>
                <a class="nav-link" href="system-logs.php">System Logs</a>
                <a class="nav-link" href="settings.php">Settings</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Dashboard</h3>
                <div>Welcome, <?php echo e(userDisplayName()); ?></div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card card-shadow"><div class="card-body"><h6>Total Users</h6><h3><?php echo $totalUsers; ?></h3></div></div></div>
                <div class="col-md-4"><div class="card card-shadow"><div class="card-body"><h6>Total Books</h6><h3><?php echo $totalBooks; ?></h3></div></div></div>
                <div class="col-md-4"><div class="card card-shadow"><div class="card-body"><h6>Active Students</h6><h3><?php echo $activeStudents; ?></h3></div></div></div>
            </div>
            <div class="card card-shadow">
                <div class="card-header bg-white"><strong>System Activity Summary</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>User</th><th>Activity</th><th>Timestamp</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><?php echo e($activity['fullname'] ?? 'System'); ?></td>
                                <td><?php echo e($activity['activity']); ?></td>
                                <td><?php echo e($activity['created_at']); ?></td>
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
