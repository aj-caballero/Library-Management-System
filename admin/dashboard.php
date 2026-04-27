<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['admin', 'superadmin']);

$totalBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();
$totalStudents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$mostRead = $pdo->query("SELECT b.title, b.author, COUNT(rl.id) AS read_count FROM books b LEFT JOIN reading_logs rl ON rl.book_id = b.id GROUP BY b.id ORDER BY read_count DESC, b.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <h5 class="text-white mb-4">Administrator</h5>
            <nav class="nav flex-column">
                <a class="nav-link active" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="manage-books.php">Manage Books</a>
                <a class="nav-link" href="add-book.php">Add Book</a>
                <a class="nav-link" href="manage-users.php">Students</a>
                <a class="nav-link" href="reports.php">Reports</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Dashboard</h3>
                <div>Welcome, <?php echo e(userDisplayName()); ?></div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-6"><div class="card card-shadow"><div class="card-body"><h6>Total Books</h6><h3><?php echo $totalBooks; ?></h3></div></div></div>
                <div class="col-md-6"><div class="card card-shadow"><div class="card-body"><h6>Total Students</h6><h3><?php echo $totalStudents; ?></h3></div></div></div>
            </div>
            <div class="card card-shadow">
                <div class="card-header bg-white"><strong>Most Read Books (Top 5)</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Title</th><th>Author</th><th>Reads</th></tr></thead>
                        <tbody>
                        <?php foreach ($mostRead as $book): ?>
                            <tr>
                                <td><?php echo e($book['title']); ?></td>
                                <td><?php echo e($book['author']); ?></td>
                                <td><?php echo (int) $book['read_count']; ?></td>
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
