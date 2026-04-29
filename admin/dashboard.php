<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['admin', 'superadmin']);

$totalBooks    = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$activeBooks   = (int) $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'active'")->fetchColumn();
$mostRead = $pdo->query("SELECT b.title, b.author, COUNT(rl.id) AS read_count FROM books b LEFT JOIN reading_logs rl ON rl.book_id = b.id GROUP BY b.id ORDER BY read_count DESC, b.created_at DESC LIMIT 5")->fetchAll();

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => true],
    ['href' => 'manage-books.php','label' => 'Manage Books', 'active' => false],
    ['href' => 'add-book.php',    'label' => 'Add Book',     'active' => false],
    ['href' => 'manage-users.php','label' => 'Students',     'active' => false],
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

<?php adminPageEnd(); ?>
