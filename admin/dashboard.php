<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
require_once __DIR__ . '/../config/pagination.php';
ensureRole(['admin', 'superadmin']);

$totalBooks    = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$activeBooks   = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'active'")->fetchColumn();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;
$mostReadTotalRows = (int) $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$mostReadTotalPages = $mostReadTotalRows > 0 ? (int) ceil($mostReadTotalRows / $perPage) : 1;
$mostRead = $pdo->query("SELECT b.title, b.author, COUNT(rl.id) AS read_count FROM books b LEFT JOIN reading_logs rl ON rl.book_id = b.id GROUP BY b.id ORDER BY read_count DESC, b.created_at DESC LIMIT " . $perPage . " OFFSET " . $offset)->fetchAll();

$gradeOrder = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
$booksByGradeRows = $pdo->query("SELECT grade_level, COUNT(*) AS total FROM books WHERE status = 'active' GROUP BY grade_level")->fetchAll();
$booksByGradeMap = array_fill_keys($gradeOrder, 0);
foreach ($booksByGradeRows as $row) {
    if (isset($booksByGradeMap[$row['grade_level']])) {
        $booksByGradeMap[$row['grade_level']] = (int) $row['total'];
    }
}
$booksByGradeLabels = array_keys($booksByGradeMap);
$booksByGradeData = array_values($booksByGradeMap);

$readingTrendRows = $pdo->query("SELECT DATE_FORMAT(opened_at, '%Y-%m') AS month_key, COUNT(*) AS total FROM reading_logs WHERE opened_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY month_key ORDER BY month_key ASC")->fetchAll();
$readingTrendMap = [];
foreach ($readingTrendRows as $row) {
    $readingTrendMap[$row['month_key']] = (int) $row['total'];
}
$readingTrendLabels = [];
$readingTrendData = [];
$monthCursor = new DateTimeImmutable('first day of this month');
for ($i = 5; $i >= 0; $i--) {
    $month = $monthCursor->modify("-$i months");
    $monthKey = $month->format('Y-m');
    $readingTrendLabels[] = $month->format('M Y');
    $readingTrendData[] = $readingTrendMap[$monthKey] ?? 0;
}

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => true],
    ['href' => 'manage-books.php','label' => 'Manage Books', 'active' => false],
    ['href' => 'add-book.php',    'label' => 'Add Book',     'active' => false],
    ['href' => 'manage-users.php','label' => 'Students',     'active' => false],
    ['href' => 'manage-archived-accounts.php','label' => 'Archived Accounts',     'active' => false],
    ['href' => 'reports.php',     'label' => 'Reports',      'active' => false],
];

adminPageStart('Dashboard', 'Administrator / Overview', $sidebarLinks, 'Administrator',
    '/Library Management System/logout.php', $currentUser, $initials);
?>
<div class="stat-grid">
    <div class="stat-card stat-blue">
        <div class="stat-body">
            <div class="stat-label">Total Books</div>
            <div class="stat-value"><?php echo number_format($totalBooks); ?></div>
            <div class="stat-sub">All entries</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-body">
            <div class="stat-label">Active Books</div>
            <div class="stat-value"><?php echo number_format($activeBooks); ?></div>
            <div class="stat-sub">Available now</div>
        </div>
    </div>
    <div class="stat-card stat-violet">
        <div class="stat-body">
            <div class="stat-label">Students</div>
            <div class="stat-value"><?php echo number_format($totalStudents); ?></div>
            <div class="stat-sub">Registered</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="data-card h-100">
            <div class="data-card-header">
                <div class="data-card-title">Active Books by Grade Level</div>
            </div>
            <div style="position: relative; height: 320px;">
                <canvas id="booksByGradeChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="data-card h-100">
            <div class="data-card-header">
                <div class="data-card-title">Reading Activity - Last 6 Months</div>
            </div>
            <div style="position: relative; height: 320px;">
                <canvas id="readingTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const booksByGradeCtx = document.getElementById('booksByGradeChart');
    if (booksByGradeCtx) {
        new Chart(booksByGradeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($booksByGradeLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($booksByGradeData); ?>,
                    backgroundColor: ['#1b4332', '#2d6a4f', '#40916c', '#74c69d'],
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

    const readingTrendCtx = document.getElementById('readingTrendChart');
    if (readingTrendCtx) {
        new Chart(readingTrendCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($readingTrendLabels); ?>,
                datasets: [{
                    label: 'Book opens',
                    data: <?php echo json_encode($readingTrendData); ?>,
                    backgroundColor: '#2d6a4f',
                    borderRadius: 8
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
</script>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">Most Read Books — Top 5</div>
        <a href="reports.php?type=most_read" class="btn btn-ghost btn-sm">View Full Report</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Reads</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($mostRead)): ?>
                <tr><td colspan="4">
                    <div class="empty-state">
                        <div class="empty-state-title">No reading data yet</div>
                        <div class="empty-state-desc">Books will appear here once students start reading.</div>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($mostRead as $i => $book): ?>
                <tr>
                    <td class="text-muted fw-600"><?php echo $i + 1; ?></td>
                    <td class="fw-600"><?php echo e($book['title']); ?></td>
                    <td class="text-muted"><?php echo e($book['author']); ?></td>
                    <td><span class="badge badge-blue"><?php echo (int) $book['read_count']; ?> reads</span></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php renderPaginationLinks('dashboard.php', [], $page, $mostReadTotalPages, $mostReadTotalRows, $perPage); ?>

<?php adminPageEnd(); ?>
