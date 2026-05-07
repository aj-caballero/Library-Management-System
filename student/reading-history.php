<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/pagination.php';
ensureRole(['student']);

$search = trim((string) ($_GET['search'] ?? ''));
$subject = trim((string) ($_GET['subject'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sql = 'SELECT b.title, b.author, b.subject, rl.opened_at FROM reading_logs rl INNER JOIN books b ON b.id = rl.book_id WHERE rl.user_id = :user_id';
$countSql = 'SELECT COUNT(*) FROM reading_logs rl INNER JOIN books b ON b.id = rl.book_id WHERE rl.user_id = :user_id';
$params = [':user_id' => (int) $_SESSION['user']['id']];

if ($search !== '') {
    $sql .= ' AND (b.title LIKE :search_title OR b.author LIKE :search_author)';
    $countSql .= ' AND (b.title LIKE :search_title OR b.author LIKE :search_author)';
    $params[':search_title'] = '%' . $search . '%';
    $params[':search_author'] = '%' . $search . '%';
}

if ($subject !== '') {
    $sql .= ' AND b.subject = :subject';
    $countSql .= ' AND b.subject = :subject';
    $params[':subject'] = $subject;
}

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $perPage) : 1;

$sql .= ' ORDER BY rl.opened_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll();

// Get unique subjects for filter dropdown
$subjectsStmt = $pdo->prepare('SELECT DISTINCT b.subject FROM reading_logs rl INNER JOIN books b ON b.id = rl.book_id WHERE rl.user_id = :user_id ORDER BY b.subject ASC');
$subjectsStmt->execute([':user_id' => (int) $_SESSION['user']['id']]);
$subjects = $subjectsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/_navbar.php'; ?>
<div class="container py-4">
    <h3 class="mb-3">Reading History</h3>
    <form class="row g-2 mb-4" method="GET">
        <div class="col-md-6"><input class="form-control" name="search" placeholder="Search by title or author" value="<?php echo e($search); ?>"></div>
        <div class="col-md-4">
            <select class="form-select" name="subject">
                <option value="">All subjects</option>
                <?php foreach ($subjects as $item): ?>
                    <option value="<?php echo e($item['subject']); ?>" <?php echo $subject === $item['subject'] ? 'selected' : ''; ?>><?php echo e($item['subject']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
    <div class="card card-shadow">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Book Title</th><th>Author</th><th>Subject</th><th>Opened At</th></tr></thead>
                <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <p class="text-muted mb-0">No reading history found</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?php echo e($row['title']); ?></td>
                            <td><?php echo e($row['author']); ?></td>
                            <td><?php echo e($row['subject']); ?></td>
                            <td><?php echo e($row['opened_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderPaginationLinks('reading-history.php', [
    'search' => $search,
    'subject' => $subject,
], $page, $totalPages, $totalRows, $perPage); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
