<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['student']);

$search = trim((string) ($_GET['search'] ?? ''));
$subject = trim((string) ($_GET['subject'] ?? ''));
$gradeLevel = trim((string) ($_GET['grade_level'] ?? ''));

$sql = "SELECT id, title, author, subject, grade_level, cover_image FROM books WHERE status = 'active'";
$params = [];
if ($search !== '') {
    $sql .= ' AND (title LIKE :search_title OR author LIKE :search_author)';
    $params[':search_title'] = '%' . $search . '%';
    $params[':search_author'] = '%' . $search . '%';
}
if ($subject !== '') {
    $sql .= ' AND subject = :subject';
    $params[':subject'] = $subject;
}
if ($gradeLevel !== '') {
    $sql .= ' AND grade_level = :grade_level';
    $params[':grade_level'] = $gradeLevel;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

$subjects = $pdo->query("SELECT DISTINCT subject FROM books WHERE status = 'active' ORDER BY subject ASC")->fetchAll();
$levels = $pdo->query("SELECT DISTINCT grade_level FROM books WHERE status = 'active' ORDER BY grade_level ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/_navbar.php'; ?>
<div class="container py-4">
    <h3 class="mb-3">Digital Library</h3>
    <form class="row g-2 mb-4" method="GET">
        <div class="col-md-5"><input class="form-control" name="search" placeholder="Search by title or author" value="<?php echo e($search); ?>"></div>
        <div class="col-md-3">
            <select class="form-select" name="subject">
                <option value="">All subjects</option>
                <?php foreach ($subjects as $item): ?>
                    <option value="<?php echo e($item['subject']); ?>" <?php echo $subject === $item['subject'] ? 'selected' : ''; ?>><?php echo e($item['subject']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="grade_level">
                <option value="">All grades</option>
                <?php foreach ($levels as $item): ?>
                    <option value="<?php echo e($item['grade_level']); ?>" <?php echo $gradeLevel === $item['grade_level'] ? 'selected' : ''; ?>><?php echo e($item['grade_level']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div>
    </form>

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
                        <a href="book-view.php?id=<?php echo (int) $book['id']; ?>" class="btn btn-sm btn-outline-primary w-100">Read</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
