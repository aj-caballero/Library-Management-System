<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['superadmin']);

$search = trim((string) ($_GET['search'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

$sql = "SELECT sl.activity, sl.created_at, u.fullname, u.email FROM system_logs sl LEFT JOIN users u ON u.id = sl.user_id WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.fullname LIKE :search_name OR u.email LIKE :search_email OR sl.activity LIKE :search_activity)";
    $params[':search_name']     = '%' . $search . '%';
    $params[':search_email']    = '%' . $search . '%';
    $params[':search_activity'] = '%' . $search . '%';
}
if ($dateFrom !== '') {
    $sql .= " AND DATE(sl.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $sql .= " AND DATE(sl.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$sql .= " ORDER BY sl.created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-users.php','label' => 'Manage Users', 'active' => false],
    ['href' => 'system-logs.php', 'label' => 'System Logs',  'active' => true],
    ['href' => 'settings.php',    'label' => 'Settings',     'active' => false],
];

adminPageStart('System Logs', 'Super Admin / System Logs', $sidebarLinks, 'Super Admin',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<form method="GET" style="margin-bottom:16px;">
    <div class="d-flex gap-2" style="flex-wrap:wrap;align-items:center;">
        <input class="form-control" style="max-width:240px;" name="search"
               placeholder="Search user, email or activity"
               value="<?php echo e($search); ?>">
        <div class="d-flex align-center gap-2">
            <span class="text-sm text-muted">From:</span>
            <input type="date" class="form-control" style="max-width:140px;" name="date_from" value="<?php echo e($dateFrom); ?>">
        </div>
        <div class="d-flex align-center gap-2">
            <span class="text-sm text-muted">To:</span>
            <input type="date" class="form-control" style="max-width:140px;" name="date_to" value="<?php echo e($dateTo); ?>">
        </div>
        <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
        <?php if ($search !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
        <a href="system-logs.php" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">
            System Activity Log
            <span class="badge badge-muted" style="margin-left:8px;">Last 100</span>
        </div>
        <span class="text-sm text-muted">Most recent events first</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Activity</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <div class="empty-state-title">No logs yet</div>
                        <div class="empty-state-desc">System activity will be recorded here.</div>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($logs as $i => $log): ?>
                <tr>
                    <td class="text-muted"><?php echo $i + 1; ?></td>
                    <td class="fw-600"><?php echo e($log['fullname'] ?? 'System'); ?></td>
                    <td class="text-muted"><?php echo e($log['email'] ?? '—'); ?></td>
                    <td><?php echo e($log['activity']); ?></td>
                    <td class="text-muted text-sm"><?php echo e($log['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminPageEnd(); ?>
