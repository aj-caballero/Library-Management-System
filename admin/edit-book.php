<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
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

$error   = '';
$success = '';
$selectedSubject = '';
$newSubject      = '';

$subjects = $pdo->query('SELECT DISTINCT subject FROM books ORDER BY subject ASC')->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $title           = trim((string) ($_POST['title'] ?? ''));
    $author          = trim((string) ($_POST['author'] ?? ''));
    $selectedSubject = trim((string) ($_POST['subject'] ?? ''));
    $newSubject      = trim((string) ($_POST['new_subject'] ?? ''));
    $subject         = $selectedSubject === '__new__' ? $newSubject : $selectedSubject;
    $gradeLevel      = trim((string) ($_POST['grade_level'] ?? ''));
    $status          = trim((string) ($_POST['status'] ?? 'active'));

    if ($error === '' && ($title === '' || $author === '' || $subject === '' || $gradeLevel === '')) {
        $error = 'Please complete all required fields.';
    } elseif ($error === '') {
        $coverName    = $book['cover_image'];
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
                ':title'       => $title,
                ':author'      => $author,
                ':subject'     => $subject,
                ':grade_level' => $gradeLevel,
                ':cover_image' => $coverName,
                ':file_path'   => $bookFileName,
                ':status'      => in_array($status, ['active', 'inactive'], true) ? $status : 'active',
                ':id'          => $bookId,
            ]);

            logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Updated a book entry');
            $success = 'Book updated successfully.';

            $stmt->execute([':id' => $bookId]);
            $book = $stmt->fetch();
        }
    }
}

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-books.php','label' => 'Manage Books', 'active' => true],
    ['href' => 'add-book.php',    'label' => 'Add Book',     'active' => false],
    ['href' => 'manage-users.php','label' => 'Students',     'active' => false],
    ['href' => 'reports.php',     'label' => 'Reports',      'active' => false],
];

adminPageStart('Edit Book', 'Administrator / Manage Books / Edit', $sidebarLinks, 'Administrator',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
<div class="alert alert-success"><?php echo e($success); ?></div>
<?php endif; ?>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">Edit: <?php echo e($book['title']); ?></div>
        <a href="manage-books.php" class="btn btn-ghost btn-sm">Back to Books</a>
    </div>
    <div class="data-card-body">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input class="form-control" name="title" value="<?php echo e($book['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Author <span class="text-danger">*</span></label>
                    <input class="form-control" name="author" value="<?php echo e($book['author']); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <select class="form-select" name="subject" id="subjectSelect" required>
                        <option value="">Select subject</option>
                        <?php foreach ($subjects as $item): ?>
                        <option value="<?php echo e((string) $item); ?>"
                            <?php echo $book['subject'] === (string) $item ? 'selected' : ''; ?>>
                            <?php echo e((string) $item); ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="__new__">
                            Add new subject…
                        </option>
                    </select>
                </div>
                <div class="form-group" id="newSubjectWrap" style="display:none;">
                    <label class="form-label">New Subject Name <span class="text-danger">*</span></label>
                    <input class="form-control" id="newSubjectInput" name="new_subject"
                           placeholder="Enter subject name">
                </div>
                <div class="form-group">
                    <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                    <select class="form-select" name="grade_level" required>
                        <option value="">Select grade</option>
                        <option value="Grade 7" <?php echo $book['grade_level'] === 'Grade 7' ? 'selected' : ''; ?>>Grade 7</option>
                        <option value="Grade 8" <?php echo $book['grade_level'] === 'Grade 8' ? 'selected' : ''; ?>>Grade 8</option>
                        <option value="Grade 9" <?php echo $book['grade_level'] === 'Grade 9' ? 'selected' : ''; ?>>Grade 9</option>
                        <option value="Grade 10" <?php echo $book['grade_level'] === 'Grade 10' ? 'selected' : ''; ?>>Grade 10</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" <?php echo $book['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $book['status'] === 'inactive' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Image</label>
                    <?php if (!empty($book['cover_image'])): ?>
                    <div style="margin-bottom:8px;">
                        <img src="/Library Management System/uploads/covers/<?php echo e($book['cover_image']); ?>"
                             style="height:60px;width:auto;border-radius:5px;border:1px solid var(--card-border);"
                             alt="current cover">
                    </div>
                    <?php endif; ?>
                    <input class="form-control" type="file" name="cover_image" accept="image/*">
                    <div class="form-hint">Leave empty to keep current image</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Book PDF</label>
                    <input class="form-control" type="file" name="book_file" accept="application/pdf">
                    <div class="form-hint">Leave empty to keep current file</div>
                </div>
            </div>
            <div style="margin-top:8px;">
                <button class="btn btn-primary" type="submit">Update Book</button>
                <a href="manage-books.php" class="btn btn-ghost" style="margin-left:8px;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var sel   = document.getElementById('subjectSelect');
    var wrap  = document.getElementById('newSubjectWrap');
    var input = document.getElementById('newSubjectInput');
    if (!sel || !wrap || !input) return;
    function toggle() {
        var isNew = sel.value === '__new__';
        wrap.style.display = isNew ? 'block' : 'none';
        input.required     = isNew;
        if (!isNew) input.value = '';
    }
    sel.addEventListener('change', toggle);
    toggle();
})();
</script>

<?php adminPageEnd(); ?>
