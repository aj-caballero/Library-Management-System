<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['student']);

$stmt = $pdo->prepare('SELECT b.title, b.author, b.subject, rl.opened_at FROM reading_logs rl INNER JOIN books b ON b.id = rl.book_id WHERE rl.user_id = :user_id ORDER BY rl.opened_at DESC');
$stmt->execute([':user_id' => (int) $_SESSION['user']['id']]);
$history = $stmt->fetchAll();
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
    <div class="card card-shadow">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Book Title</th><th>Author</th><th>Subject</th><th>Opened At</th></tr></thead>
                <tbody>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?php echo e($row['title']); ?></td>
                        <td><?php echo e($row['author']); ?></td>
                        <td><?php echo e($row['subject']); ?></td>
                        <td><?php echo e($row['opened_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
