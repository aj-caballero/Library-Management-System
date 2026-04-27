<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['admin', 'superadmin']);

$bookId = (int) ($_GET['id'] ?? 0);
if ($bookId <= 0) {
    header('Location: manage-books.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $bookId]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: manage-books.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $author = trim((string) ($_POST['author'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $gradeLevel = trim((string) ($_POST['grade_level'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'active'));

    if ($error === '' && ($title === '' || $author === '' || $subject === '' || $gradeLevel === '')) {
        $error = 'Please complete all required fields.';
    } elseif ($error === '') {
        $coverName = $book['cover_image'];
        $bookFileName = $book['file_path'];

        $coverUploadError = null;
        $newCoverName = secureUpload(
            $_FILES['cover_image'] ?? [],
            'cover',
            __DIR__ . '/../uploads/covers',
            ['jpg', 'jpeg', 'png', 'webp'],
            ['image/jpeg', 'image/png', 'image/webp'],
            5 * 1024 * 1024,
            $coverUploadError
        );

        if ($coverUploadError !== null) {
            $error = $coverUploadError;
        } elseif ($newCoverName !== null) {
            $coverName = $newCoverName;
        }

        if ($error === '') {
            $bookUploadError = null;
            $newBookFileName = secureUpload(
                $_FILES['book_file'] ?? [],
                'book',
                __DIR__ . '/../uploads/books',
                ['pdf'],
                ['application/pdf'],
                25 * 1024 * 1024,
                $bookUploadError
            );

            if ($bookUploadError !== null) {
                $error = $bookUploadError;
            } elseif ($newBookFileName !== null) {
                $bookFileName = $newBookFileName;
            }
        }

        if ($error === '') {
            $update = $pdo->prepare('UPDATE books SET title = :title, author = :author, subject = :subject, grade_level = :grade_level, cover_image = :cover_image, file_path = :file_path, status = :status WHERE id = :id');
            $update->execute([
                ':title' => $title,
                ':author' => $author,
                ':subject' => $subject,
                ':grade_level' => $gradeLevel,
                ':cover_image' => $coverName,
                ':file_path' => $bookFileName,
                ':status' => in_array($status, ['active', 'inactive'], true) ? $status : 'active',
                ':id' => $bookId,
            ]);

            logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Updated a book entry');
            $success = 'Book updated successfully.';

            $stmt->execute([':id' => $bookId]);
            $book = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
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
            <h3 class="mb-3">Edit Book</h3>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
            <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <div class="card card-shadow">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Title</label><input class="form-control" name="title" value="<?php echo e($book['title']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Author</label><input class="form-control" name="author" value="<?php echo e($book['author']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Subject</label><input class="form-control" name="subject" value="<?php echo e($book['subject']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Grade Level</label><input class="form-control" name="grade_level" value="<?php echo e($book['grade_level']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Cover Image</label><input class="form-control" type="file" name="cover_image" accept="image/*"></div>
                            <div class="col-md-6"><label class="form-label">Book PDF</label><input class="form-control" type="file" name="book_file" accept="application/pdf"></div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?php echo $book['status'] === 'active' ? 'selected' : ''; ?>>active</option>
                                    <option value="inactive" <?php echo $book['status'] === 'inactive' ? 'selected' : ''; ?>>inactive</option>
                                </select>
                            </div>
                            <div class="col-12"><button class="btn btn-primary" type="submit">Update Book</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
