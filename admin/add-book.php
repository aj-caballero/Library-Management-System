<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['admin', 'superadmin']);

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

    if ($error === '' && ($title === '' || $author === '' || $subject === '' || $gradeLevel === '')) {
        $error = 'Please complete all required fields.';
    } elseif ($error === '' && (empty($_FILES['book_file']['name']) || (int) $_FILES['book_file']['error'] !== UPLOAD_ERR_OK)) {
        $error = 'Book PDF is required.';
    } elseif ($error === '') {
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
                ':title'      => $title,
                ':author'     => $author,
                ':subject'    => $subject,
                ':grade_level'=> $gradeLevel,
                ':cover_image'=> $coverName,
                ':file_path'  => $bookName,
                ':uploaded_by'=> (int) $_SESSION['user']['id'],
            ]);
            logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Added a new book');
            $success = 'Book added successfully.';
            $selectedSubject = '';
            $newSubject = '';
        }
    }
}

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-books.php','label' => 'Manage Books', 'active' => false],
    ['href' => 'add-book.php',    'label' => 'Add Book',     'active' => true],
    ['href' => 'manage-users.php','label' => 'Students',     'active' => false],
    ['href' => 'reports.php',     'label' => 'Reports',      'active' => false],
];

adminPageStart('Add Book', 'Administrator / Add Book', $sidebarLinks, 'Administrator',
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
        <div class="data-card-title">New Book Details</div>
        <a href="manage-books.php" class="btn btn-ghost btn-sm">Back to Books</a>
    </div>
    <div class="data-card-body">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="form-row" style="margin-bottom:0;">
                <div class="form-group">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input class="form-control" name="title" placeholder="Book title" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Author <span class="text-danger">*</span></label>
                    <input class="form-control" name="author" placeholder="Author name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject <span class="text-danger">*</span></label>
                    <select class="form-select" name="subject" id="subjectSelect" required>
                        <option value="">Select subject</option>
                        <?php foreach ($subjects as $item): ?>
                        <option value="<?php echo e((string) $item); ?>"
                            <?php echo $selectedSubject === (string) $item ? 'selected' : ''; ?>>
                            <?php echo e((string) $item); ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="__new__" <?php echo $selectedSubject === '__new__' ? 'selected' : ''; ?>>
                            Add new subject…
                        </option>
                    </select>
                </div>
                <div class="form-group" id="newSubjectWrap"
                     style="display:<?php echo $selectedSubject === '__new__' ? 'block' : 'none'; ?>;">
                    <label class="form-label">New Subject Name <span class="text-danger">*</span></label>
                    <input class="form-control" id="newSubjectInput" name="new_subject"
                           value="<?php echo e($newSubject); ?>" placeholder="Enter subject name">
                </div>
                <div class="form-group">
                    <label class="form-label">Grade Level <span class="text-danger">*</span></label>
                    <select class="form-select" name="grade_level" required>
                        <option value="">Select grade</option>
                        <option>Grade 7</option>
                        <option>Grade 8</option>
                        <option>Grade 9</option>
                        <option>Grade 10</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Image</label>
                    <input class="form-control" type="file" name="cover_image" accept="image/*">
                    <div class="form-hint">JPG, PNG or WebP — max 5 MB</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Book PDF <span class="text-danger">*</span></label>
                    <input class="form-control" type="file" name="book_file" accept="application/pdf" required>
                    <div class="form-hint">PDF only — max 25 MB</div>
                </div>
            </div>
            <div style="margin-top:8px;">
                <button class="btn btn-primary" type="submit">Save Book</button>
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
