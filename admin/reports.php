<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['admin', 'superadmin']);

$reportType = (string) ($_GET['type'] ?? 'available_books');
$rows = [];
$headers = [];

if ($reportType === 'most_read') {
    $headers = ['Title', 'Author', 'Reads'];
    $rows = $pdo->query("SELECT b.title, b.author, COUNT(rl.id) AS read_count FROM books b LEFT JOIN reading_logs rl ON rl.book_id = b.id GROUP BY b.id ORDER BY read_count DESC LIMIT 20")->fetchAll();
} elseif ($reportType === 'active_users') {
    $headers = ['Student Name', 'Email', 'Grade Level', 'Books Opened'];
    $rows = $pdo->query("SELECT u.fullname, u.email, u.grade_level, COUNT(rl.id) AS books_opened FROM users u LEFT JOIN reading_logs rl ON rl.user_id = u.id WHERE u.role = 'student' AND u.is_active = 1 GROUP BY u.id ORDER BY books_opened DESC")->fetchAll();
} else {
    $reportType = 'available_books';
    $headers = ['Title', 'Author', 'Subject', 'Grade Level', 'Status'];
    $rows = $pdo->query("SELECT title, author, subject, grade_level, status FROM books ORDER BY title ASC")->fetchAll();
}

$logStmt = $pdo->prepare('INSERT INTO reports (generated_by, report_type, generated_at) VALUES (:generated_by, :report_type, NOW())');
$logStmt->execute([
    ':generated_by' => (int) $_SESSION['user']['id'],
    ':report_type' => $reportType,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <h5 class="text-white mb-4">Administrator</h5>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="manage-books.php">Manage Books</a>
                <a class="nav-link" href="add-book.php">Add Book</a>
                <a class="nav-link" href="manage-users.php">Students</a>
                <a class="nav-link active" href="reports.php">Reports</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Reports</h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" onclick="window.print()">Print</button>
                    <a class="btn btn-outline-primary" href="reports.php?type=<?php echo e($reportType); ?>">Refresh</a>
                </div>
            </div>

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <select class="form-select" name="type">
                        <option value="available_books" <?php echo $reportType === 'available_books' ? 'selected' : ''; ?>>Available Books</option>
                        <option value="most_read" <?php echo $reportType === 'most_read' ? 'selected' : ''; ?>>Most Read Books</option>
                        <option value="active_users" <?php echo $reportType === 'active_users' ? 'selected' : ''; ?>>Active Users</option>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary">Generate</button></div>
            </form>

            <div class="card card-shadow">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                        <tr>
                            <?php foreach ($headers as $header): ?>
                                <th><?php echo e($header); ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $cell): ?>
                                    <td><?php echo e((string) $cell); ?></td>
                                <?php endforeach; ?>
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
