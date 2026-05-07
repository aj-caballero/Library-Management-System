<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
require_once __DIR__ . '/../config/pagination.php';
ensureRole(['admin', 'superadmin']);

$totalUsers    = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalBooks    = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$activeStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1")->fetchColumn();
$totalAdmins   = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;
$recentActivitiesTotalRows = (int) $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
$recentActivitiesTotalPages = $recentActivitiesTotalRows > 0 ? (int) ceil($recentActivitiesTotalRows / $perPage) : 1;
$recentActivities = $pdo->query("SELECT sl.activity, sl.created_at, u.fullname FROM system_logs sl LEFT JOIN users u ON u.id = sl.user_id ORDER BY sl.created_at DESC LIMIT " . $perPage . " OFFSET " . $offset)->fetchAll();

$roleRows = $pdo->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role")->fetchAll();
$roleLabels = ['Students', 'Admins', 'Super Admins'];
$roleCounts = [0, 0, 0];
foreach ($roleRows as $row) {
    if ($row['role'] === 'student') {
        $roleCounts[0] = (int) $row['total'];
    } elseif ($row['role'] === 'admin') {
        $roleCounts[1] = (int) $row['total'];
    } elseif ($row['role'] === 'superadmin') {
        $roleCounts[2] = (int) $row['total'];
    }
}

$studentGradeOrder = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
$studentGradeRows = $pdo->query("SELECT grade_level, SUM(CASE WHEN is_archived = 0 THEN 1 ELSE 0 END) AS active_total, SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) AS archived_total FROM users WHERE role = 'student' GROUP BY grade_level")->fetchAll();
$studentGradeMap = [];
foreach ($studentGradeOrder as $grade) {
    $studentGradeMap[$grade] = ['active' => 0, 'archived' => 0];
}
foreach ($studentGradeRows as $row) {
    if (isset($studentGradeMap[$row['grade_level']])) {
        $studentGradeMap[$row['grade_level']]['active'] = (int) $row['active_total'];
        $studentGradeMap[$row['grade_level']]['archived'] = (int) $row['archived_total'];
    }
}
$studentGradeLabels = array_keys($studentGradeMap);
$studentGradeActiveData = array_map(static fn(array $item): int => $item['active'], array_values($studentGradeMap));
$studentGradeArchivedData = array_map(static fn(array $item): int => $item['archived'], array_values($studentGradeMap));

$loginTrendRows = $pdo->query("SELECT DATE_FORMAT(last_login_at, '%Y-%m') AS month_key, COUNT(*) AS total FROM users WHERE last_login_at IS NOT NULL AND last_login_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_key ORDER BY month_key ASC")->fetchAll();
$loginTrendMap = [];
foreach ($loginTrendRows as $row) {
    $loginTrendMap[$row['month_key']] = (int) $row['total'];
}
$loginTrendLabels = [];
$loginTrendData = [];
$monthCursor = new DateTimeImmutable('first day of this month');
for ($i = 5; $i >= 0; $i--) {
    $month = $monthCursor->modify("-$i months");
    $monthKey = $month->format('Y-m');
    $loginTrendLabels[] = $month->format('M Y');
    $loginTrendData[] = $loginTrendMap[$monthKey] ?? 0;
}

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => true],
    ['href' => 'manage-users.php','label' => 'Manage Users', 'active' => false],
    ['href' => '../admin/manage-archived-accounts.php','label' => 'Archived Accounts', 'active' => false],
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

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="data-card">
            <div class="data-card-header">
                <div class="data-card-title">Student Status by Grade</div>
            </div>
            <div style="position: relative; height: 340px;">
                <canvas id="studentGradeChart"></canvas>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const usersByRoleCtx = document.getElementById('usersByRoleChart');
    if (usersByRoleCtx) {
        new Chart(usersByRoleCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($roleLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($roleCounts); ?>,
                    backgroundColor: ['#1b4332', '#2d6a4f', '#74c69d'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    const loginTrendCtx = document.getElementById('loginTrendChart');
    if (loginTrendCtx) {
        new Chart(loginTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($loginTrendLabels); ?>,
                datasets: [{
                    label: 'Logins',
                    data: <?php echo json_encode($loginTrendData); ?>,
                    borderColor: '#2d6a4f',
                    backgroundColor: 'rgba(45, 106, 79, 0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }

    const studentGradeCtx = document.getElementById('studentGradeChart');
    if (studentGradeCtx) {
        new Chart(studentGradeCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($studentGradeLabels); ?>,
                datasets: [
                    {
                        label: 'Active',
                        data: <?php echo json_encode($studentGradeActiveData); ?>,
                        backgroundColor: '#2d6a4f',
                        borderRadius: 8
                    },
                    {
                        label: 'Archived',
                        data: <?php echo json_encode($studentGradeArchivedData); ?>,
                        backgroundColor: '#74c69d',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    x: { stacked: true },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    }
</script>

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

<?php renderPaginationLinks('dashboard.php', [], $page, $recentActivitiesTotalPages, $recentActivitiesTotalRows, $perPage); ?>

<?php adminPageEnd(); ?>
