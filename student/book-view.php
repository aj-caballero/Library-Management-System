<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['student']);

$bookId = (int) ($_GET['id'] ?? 0);
if ($bookId <= 0) {
    header('Location: library.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM books WHERE id = :id AND status = 'active' LIMIT 1");
$stmt->execute([':id' => $bookId]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: library.php');
    exit;
}

$favStmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND book_id = :book_id LIMIT 1");
$favStmt->execute([':user_id' => $_SESSION['user']['id'], ':book_id' => $bookId]);
$isFavorite = (bool) $favStmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    if ($isFavorite) {
        $pdo->prepare("DELETE FROM favorites WHERE user_id = :user_id AND book_id = :book_id")->execute([':user_id' => $_SESSION['user']['id'], ':book_id' => $bookId]);
        $isFavorite = false;
    } else {
        $pdo->prepare("INSERT IGNORE INTO favorites (user_id, book_id) VALUES (:user_id, :book_id)")->execute([':user_id' => $_SESSION['user']['id'], ':book_id' => $bookId]);
        $isFavorite = true;
    }
}

$logStmt = $pdo->prepare('INSERT INTO reading_logs (user_id, book_id, opened_at) VALUES (:user_id, :book_id, NOW())');
$logStmt->execute([
    ':user_id' => (int) $_SESSION['user']['id'],
    ':book_id' => $bookId,
]);

logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Opened a book for reading');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($book['title']); ?> | Book View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/_navbar.php'; ?>
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-shadow">
                <?php if (!empty($book['cover_image'])): ?>
                    <img src="/Library Management System/uploads/covers/<?php echo e($book['cover_image']); ?>" class="card-img-top" alt="cover">
                <?php endif; ?>
                <div class="card-body">
                    <h4><?php echo e($book['title']); ?></h4>
                    <p class="mb-1"><strong>Author:</strong> <?php echo e($book['author']); ?></p>
                    <p class="mb-1"><strong>Subject:</strong> <?php echo e($book['subject']); ?></p>
                    <p class="mb-0"><strong>Grade Level:</strong> <?php echo e($book['grade_level']); ?></p>
                    <hr>
                    <form method="POST">
                        <input type="hidden" name="action" value="toggle_favorite">
                        <button type="submit" class="btn <?php echo $isFavorite ? 'btn-danger' : 'btn-outline-danger'; ?> w-100 fw-bold">
                            <?php echo $isFavorite ? '♥ Remove from Favorites' : '♡ Add to Favorites'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card card-shadow">
                <div class="card-header bg-white"><strong>Embedded PDF Reader</strong></div>
                <div class="card-body p-2">
                    <iframe src="/Library Management System/uploads/books/<?php echo e($book['file_path']); ?>" width="100%" height="680" style="border:0;"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
