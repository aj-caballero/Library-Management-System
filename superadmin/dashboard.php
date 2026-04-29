<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['admin', 'superadmin']);

$totalUsers    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalBooks    = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$activeStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1")->fetchColumn();
$totalAdmins   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$recentActivities = $pdo->query("SELECT sl.activity, sl.created_at, u.fullname FROM system_logs sl LEFT JOIN users u ON u.id = sl.user_id ORDER BY sl.created_at DESC LIMIT 8")->fetchAll();

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => true],
    ['href' => 'manage-users.php','label' => 'Manage Users', 'active' => false],
    ['href' => 'system-logs.php', 'label' => 'System Logs',  'active' => false],
    ['href' => 'settings.php',    'label' => 'Settings',     'active' => false],
];

adminPageStart('Dashboard', 'Super Admin / Overview', $sidebarLinks, 'Super Admin',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<div class="stat-grid">
    <div class="stat-card stat-blue">
        <div class="stat-body">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
            <div class="stat-sub">All roles</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-body">
            <div class="stat-label">Active Students</div>
            <div class="stat-value"><?php echo number_format($activeStudents); ?></div>
            <div class="stat-sub">Currently active</div>
        </div>
    </div>
    <div class="stat-card stat-violet">
        <div class="stat-body">
            <div class="stat-label">Total Books</div>
            <div class="stat-value"><?php echo number_format($totalBooks); ?></div>
            <div class="stat-sub">In library</div>
        </div>
    </div>
    <div class="stat-card stat-amber">
        <div class="stat-body">
            <div class="stat-label">Administrators</div>
            <div class="stat-value"><?php echo number_format($totalAdmins); ?></div>
            <div class="stat-sub">Staff accounts</div>
        </div>
    </div>
</div>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">Recent System Activity</div>
        <a href="system-logs.php" class="btn btn-ghost btn-sm">View All Logs</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recentActivities)): ?>
                <tr><td colspan="3">
                    <div class="empty-state">
                        <div class="empty-state-title">No activity yet</div>
                        <div class="empty-state-desc">System events will appear here.</div>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($recentActivities as $activity): ?>
                <tr>
                    <td class="fw-600"><?php echo e($activity['fullname'] ?? 'System'); ?></td>
                    <td><?php echo e($activity['activity']); ?></td>
                    <td class="text-muted text-sm"><?php echo e($activity['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminPageEnd(); ?>
