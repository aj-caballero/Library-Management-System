<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_book') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        redirect('manage-books.php');
    }

    $bookId = (int) ($_POST['book_id'] ?? 0);
    if ($bookId > 0) {
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute([':id' => $bookId]);
        logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Deleted a book entry');
    }

    header('Location: manage-books.php');
    exit;
}

$search = trim((string) ($_GET['search'] ?? ''));
$subject = trim((string) ($_GET['subject'] ?? ''));
$gradeLevel = trim((string) ($_GET['grade_level'] ?? ''));

$sql = 'SELECT * FROM books WHERE 1=1';
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

$subjects = $pdo->query('SELECT DISTINCT subject FROM books ORDER BY subject ASC')->fetchAll();
$levels = $pdo->query('SELECT DISTINCT grade_level FROM books ORDER BY grade_level ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books</title>
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
                <a class="nav-link active" href="manage-books.php">Manage Books</a>
                <a class="nav-link" href="add-book.php">Add Book</a>
                <a class="nav-link" href="manage-users.php">Students</a>
                <a class="nav-link" href="reports.php">Reports</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Manage Books</h3>
                <a href="add-book.php" class="btn btn-primary">Add New Book</a>
            </div>

            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4"><input class="form-control" name="search" placeholder="Search title or author" value="<?php echo e($search); ?>"></div>
                <div class="col-md-3">
                    <select class="form-select" name="subject">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $item): ?>
                            <option value="<?php echo e($item['subject']); ?>" <?php echo $subject === $item['subject'] ? 'selected' : ''; ?>><?php echo e($item['subject']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="grade_level">
                        <option value="">All Grade Levels</option>
                        <?php foreach ($levels as $item): ?>
                            <option value="<?php echo e($item['grade_level']); ?>" <?php echo $gradeLevel === $item['grade_level'] ? 'selected' : ''; ?>><?php echo e($item['grade_level']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form>

            <div class="card card-shadow">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead><tr><th>Cover</th><th>Title</th><th>Author</th><th>Subject</th><th>Grade</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($book['cover_image'])): ?>
                                        <img src="/Library Management System/uploads/covers/<?php echo e($book['cover_image']); ?>" width="50" alt="cover">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($book['title']); ?></td>
                                <td><?php echo e($book['author']); ?></td>
                                <td><?php echo e($book['subject']); ?></td>
                                <td><?php echo e($book['grade_level']); ?></td>
                                <td><span class="badge bg-<?php echo $book['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo e($book['status']); ?></span></td>
                                <td>
                                    <a href="edit-book.php?id=<?php echo (int) $book['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" class="d-inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_book">
                                        <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book?')">Delete</button>
                                    </form>
                                </td>
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
