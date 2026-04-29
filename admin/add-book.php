<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['admin', 'superadmin']);

$error = '';
$success = '';
$subject = '';
$selectedSubject = '';
$newSubject = '';

$subjects = $pdo->query('SELECT DISTINCT subject FROM books ORDER BY subject ASC')->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $author = trim((string) ($_POST['author'] ?? ''));
    $selectedSubject = trim((string) ($_POST['subject'] ?? ''));
    $newSubject = trim((string) ($_POST['new_subject'] ?? ''));
    $subject = $selectedSubject === '__new__' ? $newSubject : $selectedSubject;
    $gradeLevel = trim((string) ($_POST['grade_level'] ?? ''));

    if ($error === '' && ($title === '' || $author === '' || $subject === '' || $gradeLevel === '')) {
        $error = 'Please complete all required fields.';
    } elseif ($error === '' && (empty($_FILES['book_file']['name']) || (int) $_FILES['book_file']['error'] !== UPLOAD_ERR_OK)) {
        $error = 'Book PDF is required.';
    } elseif ($error === '') {
        $coverName = null;

        $coverUploadError = null;
        $coverName = secureUpload(
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
        }

        if ($error === '') {
            $bookUploadError = null;
            $bookName = secureUpload(
                $_FILES['book_file'] ?? [],
                'book',
                __DIR__ . '/../uploads/books',
                ['pdf'],
                ['application/pdf'],
                25 * 1024 * 1024,
                $bookUploadError
            );

            if ($bookUploadError !== null || $bookName === null) {
                $error = $bookUploadError ?? 'Book PDF is required.';
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare("INSERT INTO books (title, author, subject, grade_level, cover_image, file_path, status, uploaded_by, created_at) VALUES (:title, :author, :subject, :grade_level, :cover_image, :file_path, 'active', :uploaded_by, NOW())");
            $stmt->execute([
                ':title' => $title,
                ':author' => $author,
                ':subject' => $subject,
                ':grade_level' => $gradeLevel,
                ':cover_image' => $coverName,
                ':file_path' => $bookName,
                ':uploaded_by' => (int) $_SESSION['user']['id'],
            ]);
            logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Added a new book');
            $success = 'Book added successfully.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book</title>
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
                <a class="nav-link active" href="add-book.php">Add Book</a>
                <a class="nav-link" href="manage-users.php">Students</a>
                <a class="nav-link" href="reports.php">Reports</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-3">Add Book</h3>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
            <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>

            <div class="card card-shadow">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Title</label>
                                <input class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Author</label>
                                <input class="form-control" name="author" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject" id="subjectSelect" required>
                                    <option value="">Select subject</option>
                                    <?php foreach ($subjects as $item): ?>
                                        <option value="<?php echo e((string) $item); ?>" <?php echo $selectedSubject === (string) $item ? 'selected' : ''; ?>><?php echo e((string) $item); ?></option>
                                    <?php endforeach; ?>
                                    <option value="__new__" <?php echo $selectedSubject === '__new__' ? 'selected' : ''; ?>>Add new subject</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="newSubjectWrap" style="display: <?php echo $selectedSubject === '__new__' ? 'block' : 'none'; ?>;">
                                <label class="form-label">New Subject</label>
                                <input class="form-control" id="newSubjectInput" name="new_subject" value="<?php echo e($newSubject); ?>" placeholder="Type new subject">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Grade Level</label>
                                <select class="form-select" name="grade_level" required>
                                    <option value="">Select grade level</option>
                                    <option>Grade 7</option>
                                    <option>Grade 8</option>
                                    <option>Grade 9</option>
                                    <option>Grade 10</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cover Image</label>
                                <input class="form-control" type="file" name="cover_image" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Book PDF</label>
                                <input class="form-control" type="file" name="book_file" accept="application/pdf" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary" type="submit">Save Book</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
<script>
    (function () {
        var subjectSelect = document.getElementById('subjectSelect');
        var newSubjectWrap = document.getElementById('newSubjectWrap');
        var newSubjectInput = document.getElementById('newSubjectInput');

        if (!subjectSelect || !newSubjectWrap || !newSubjectInput) {
            return;
        }

        function toggleNewSubject() {
            var isNew = subjectSelect.value === '__new__';
            newSubjectWrap.style.display = isNew ? 'block' : 'none';
            newSubjectInput.required = isNew;
            if (!isNew) {
                newSubjectInput.value = '';
            }
        }

        subjectSelect.addEventListener('change', toggleNewSubject);
        toggleNewSubject();
    })();
</script>
</body>
</html>
