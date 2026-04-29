<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['student']);

$sql = "SELECT b.id, b.title, b.author, b.subject, b.grade_level, b.cover_image 
        FROM favorites f 
        JOIN books b ON f.book_id = b.id 
        WHERE f.user_id = :user_id AND b.status = 'active'
        ORDER BY f.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $_SESSION['user']['id']]);
$books = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites | Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/_navbar.php'; ?>
<div class="container py-4">
    <h3 class="mb-4">My Favorites</h3>

    <?php if (empty($books)): ?>
        <div class="text-center py-5 text-muted">
            <h4>No favorite books yet!</h4>
            <p>Browse the library and add some books to your favorites list.</p>
            <a href="library.php" class="btn btn-primary mt-2">Go to Library</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($books as $book): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card card-shadow book-card h-100">
                        <?php if (!empty($book['cover_image'])): ?>
                            <img src="/Library Management System/uploads/covers/<?php echo e($book['cover_image']); ?>" class="card-img-top" alt="<?php echo e($book['title']); ?>">
                        <?php else: ?>
                            <div class="brand-gradient text-white d-flex align-items-center justify-content-center" style="height:220px;">No Cover</div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h6><?php echo e($book['title']); ?></h6>
                            <p class="small text-muted mb-1"><?php echo e($book['author']); ?></p>
                            <p class="small mb-2"><?php echo e($book['subject']); ?> | <?php echo e($book['grade_level']); ?></p>
                            <a href="book-view.php?id=<?php echo (int) $book['id']; ?>" class="btn btn-sm btn-outline-primary w-100">Read Book</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
