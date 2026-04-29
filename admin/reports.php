<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['admin', 'superadmin']);

$reportType = (string) ($_GET['type'] ?? 'available_books');
$isCsvExport = (isset($_GET['action']) && $_GET['action'] === 'export_csv');

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

$rows      = [];
$headers   = [];
$totalRows = 0;

if ($reportType === 'most_read') {
    $headers = ['Title', 'Author', 'Reads'];
    $countSql = "SELECT COUNT(DISTINCT b.id) FROM books b LEFT JOIN reading_logs rl ON rl.book_id = b.id";
    $totalRows = (int) $pdo->query($countSql)->fetchColumn();
    
    $sql = "SELECT b.title, b.author, COUNT(rl.id) AS read_count FROM books b LEFT JOIN reading_logs rl ON rl.book_id = b.id GROUP BY b.id ORDER BY read_count DESC";
    if (!$isCsvExport) $sql .= " LIMIT $perPage OFFSET $offset";
    $rows = $pdo->query($sql)->fetchAll();
    
} elseif ($reportType === 'active_users') {
    $headers = ['Student Name', 'Email', 'Grade Level', 'Books Opened'];
    $countSql = "SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1";
    $totalRows = (int) $pdo->query($countSql)->fetchColumn();
    
    $sql = "SELECT u.fullname, u.email, u.grade_level, COUNT(rl.id) AS books_opened FROM users u LEFT JOIN reading_logs rl ON rl.user_id = u.id WHERE u.role = 'student' AND u.is_active = 1 GROUP BY u.id ORDER BY books_opened DESC";
    if (!$isCsvExport) $sql .= " LIMIT $perPage OFFSET $offset";
    $rows = $pdo->query($sql)->fetchAll();
    
} else {
    $reportType = 'available_books';
    $headers    = ['Title', 'Author', 'Subject', 'Grade Level', 'Status'];
    $countSql = "SELECT COUNT(*) FROM books";
    $totalRows = (int) $pdo->query($countSql)->fetchColumn();
    
    $sql = "SELECT title, author, subject, grade_level, status FROM books ORDER BY title ASC";
    if (!$isCsvExport) $sql .= " LIMIT $perPage OFFSET $offset";
    $rows = $pdo->query($sql)->fetchAll();
}

$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $perPage) : 1;

$logStmt = $pdo->prepare('INSERT INTO reports (generated_by, report_type, generated_at) VALUES (:generated_by, :report_type, NOW())');
$logStmt->execute([
    ':generated_by' => (int) $_SESSION['user']['id'],
    ':report_type'  => $reportType,
]);

if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=report_' . $reportType . '_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    // Add # to headers
    $csvHeaders = array_merge(['#'], $headers);
    fputcsv($output, $csvHeaders);
    
    foreach ($rows as $i => $row) {
        $cells = array_values(array_filter(
            $row,
            fn($k) => !is_int($k),
            ARRAY_FILTER_USE_KEY
        ));
        $csvRow = array_merge([$i + 1], $cells);
        fputcsv($output, $csvRow);
    }
    fclose($output);
    exit;
}

$reportLabels = [
    'available_books' => 'Available Books',
    'most_read'       => 'Most Read Books',
    'active_users'    => 'Active Users',
];

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-books.php','label' => 'Manage Books', 'active' => false],
    ['href' => 'add-book.php',    'label' => 'Add Book',     'active' => false],
    ['href' => 'manage-users.php','label' => 'Students',     'active' => false],
    ['href' => 'reports.php',     'label' => 'Reports',      'active' => true],
];

adminPageStart('Reports', 'Administrator / Reports', $sidebarLinks, 'Administrator',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<div class="toolbar">
    <form method="GET" class="d-flex gap-2" style="flex-wrap:wrap;flex:1;">
        <select class="form-select" style="max-width:220px;" name="type">
            <?php foreach ($reportLabels as $val => $label): ?>
            <option value="<?php echo e($val); ?>" <?php echo $reportType === $val ? 'selected' : ''; ?>>
                <?php echo e($label); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Generate</button>
    </form>
    <a class="btn btn-ghost btn-sm" href="reports.php?type=<?php echo e($reportType); ?>&action=export_csv">Generate CSV</a>
    <a class="btn btn-ghost btn-sm" href="reports.php?type=<?php echo e($reportType); ?>">Refresh</a>
</div>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">
            <?php echo e($reportLabels[$reportType] ?? 'Report'); ?>
            <span class="badge badge-muted" style="margin-left:8px;"><?php echo number_format($totalRows); ?> total</span>
        </div>
        <span class="text-sm text-muted">Generated: <?php echo date('M j, Y H:i'); ?></span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <?php foreach ($headers as $header): ?>
                    <th><?php echo e($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="<?php echo count($headers) + 1; ?>">
                    <div class="empty-state">
                        <div class="empty-state-title">No data available</div>
                        <div class="empty-state-desc">There is nothing to display for this report type.</div>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $row):
                    $cells = array_values(array_filter(
                        $row,
                        fn($k) => !is_int($k),
                        ARRAY_FILTER_USE_KEY
                    ));
                ?>
                <tr>
                    <td class="text-muted"><?php echo $i + 1; ?></td>
                    <?php foreach ($cells as $cell): ?>
                    <td><?php echo e((string) $cell); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <span class="text-sm text-muted" style="margin-right:auto;">
            Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalRows); ?> of <?php echo $totalRows; ?> entries
        </span>
        
        <?php if ($page > 1): ?>
            <a href="reports.php?type=<?php echo e($reportType); ?>&page=<?php echo $page - 1; ?>" class="page-btn">Prev</a>
        <?php else: ?>
            <span class="page-btn disabled">Prev</span>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($p = $startPage; $p <= $endPage; $p++):
        ?>
            <a href="reports.php?type=<?php echo e($reportType); ?>&page=<?php echo $p; ?>" 
               class="page-btn <?php echo $p === $page ? 'active' : ''; ?>">
                <?php echo $p; ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="reports.php?type=<?php echo e($reportType); ?>&page=<?php echo $page + 1; ?>" class="page-btn">Next</a>
        <?php else: ?>
            <span class="page-btn disabled">Next</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php adminPageEnd(); ?>
