<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['admin', 'superadmin']);

$students = $pdo->query("SELECT fullname, email, grade_level, created_at, is_active FROM users WHERE role = 'student' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Users</title>
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
                <a class="nav-link active" href="manage-users.php">Students</a>
                <a class="nav-link" href="reports.php">Reports</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-3">Registered Students</h3>
            <div class="card card-shadow">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>Name</th><th>Email</th><th>Grade Level</th><th>Status</th><th>Joined</th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo e($student['fullname']); ?></td>
                                <td><?php echo e($student['email']); ?></td>
                                <td><?php echo e($student['grade_level'] ?? '-'); ?></td>
                                <td><span class="badge bg-<?php echo (int) $student['is_active'] === 1 ? 'success' : 'secondary'; ?>"><?php echo (int) $student['is_active'] === 1 ? 'active' : 'inactive'; ?></span></td>
                                <td><?php echo e($student['created_at']); ?></td>
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
