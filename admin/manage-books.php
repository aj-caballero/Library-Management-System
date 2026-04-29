<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array((string) ($_POST['action'] ?? ''), ['archive_book', 'restore_book'], true)) {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        redirect('manage-books.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $bookId = (int) ($_POST['book_id'] ?? 0);
    if ($bookId > 0) {
        if ($action === 'archive_book') {
            $stmt = $pdo->prepare("UPDATE books SET status = 'inactive' WHERE id = :id");
            $stmt->execute([':id' => $bookId]);
            logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Archived a book entry');
        } elseif ($action === 'restore_book') {
            $stmt = $pdo->prepare("UPDATE books SET status = 'active' WHERE id = :id");
            $stmt->execute([':id' => $bookId]);
            logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Restored a book entry');
        }
    }

    $redirectView = trim((string) ($_POST['view'] ?? 'active'));
    if (!in_array($redirectView, ['active', 'archived', 'all'], true)) {
        $redirectView = 'active';
    }

    header('Location: manage-books.php?view=' . urlencode($redirectView));
    exit;
}

$view       = trim((string) ($_GET['view'] ?? 'active'));
$search     = trim((string) ($_GET['search'] ?? ''));
$subject    = trim((string) ($_GET['subject'] ?? ''));
$gradeLevel = trim((string) ($_GET['grade_level'] ?? ''));

if (!in_array($view, ['active', 'archived', 'all'], true)) {
    $view = 'active';
}

$sql    = 'SELECT * FROM books WHERE 1=1';
$params = [];

if ($view === 'active') {
    $sql .= " AND status = 'active'";
} elseif ($view === 'archived') {
    $sql .= " AND status = 'inactive'";
}

if ($search !== '') {
    $sql .= ' AND (title LIKE :search_title OR author LIKE :search_author)';
    $params[':search_title']  = '%' . $search . '%';
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

$subjectSql = 'SELECT DISTINCT subject FROM books WHERE 1=1';
$levelSql   = 'SELECT DISTINCT grade_level FROM books WHERE 1=1';
if ($view === 'active') {
    $subjectSql .= " AND status = 'active'";
    $levelSql   .= " AND status = 'active'";
} elseif ($view === 'archived') {
    $subjectSql .= " AND status = 'inactive'";
    $levelSql   .= " AND status = 'inactive'";
}
$subjectSql .= ' ORDER BY subject ASC';
$levelSql   .= ' ORDER BY grade_level ASC';
$subjects = $pdo->query($subjectSql)->fetchAll();
$levels   = $pdo->query($levelSql)->fetchAll();

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-books.php','label' => 'Manage Books', 'active' => true],
    ['href' => 'add-book.php',    'label' => 'Add Book',     'active' => false],
    ['href' => 'manage-users.php','label' => 'Students',     'active' => false],
    ['href' => 'reports.php',     'label' => 'Reports',      'active' => false],
];

adminPageStart('Manage Books', 'Administrator / Manage Books', $sidebarLinks, 'Administrator',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<div class="toolbar">
    <div class="tab-pills">
        <a href="manage-books.php?view=active"
           class="tab-pill<?php echo $view === 'active' ? ' active' : ''; ?>">Active</a>
        <a href="manage-books.php?view=archived"
           class="tab-pill<?php echo $view === 'archived' ? ' active' : ''; ?>">Archived</a>
        <a href="manage-books.php?view=all"
           class="tab-pill<?php echo $view === 'all' ? ' active' : ''; ?>">All</a>
    </div>
    <div class="toolbar-spacer"></div>
    <a href="add-book.php" class="btn btn-primary btn-sm">Add New Book</a>
</div>

<form method="GET" style="margin-bottom:16px;">
    <input type="hidden" name="view" value="<?php echo e($view); ?>">
    <div class="d-flex gap-2" style="flex-wrap:wrap;">
        <input class="form-control" style="max-width:240px;" name="search"
               placeholder="Search title or author"
               value="<?php echo e($search); ?>">
        <select class="form-select" style="max-width:180px;" name="subject">
            <option value="">All Subjects</option>
            <?php foreach ($subjects as $item): ?>
                <option value="<?php echo e($item['subject']); ?>"
                    <?php echo $subject === $item['subject'] ? 'selected' : ''; ?>>
                    <?php echo e($item['subject']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" style="max-width:180px;" name="grade_level">
            <option value="">All Grades</option>
            <?php foreach ($levels as $item): ?>
                <option value="<?php echo e($item['grade_level']); ?>"
                    <?php echo $gradeLevel === $item['grade_level'] ? 'selected' : ''; ?>>
                    <?php echo e($item['grade_level']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
        <?php if ($search !== '' || $subject !== '' || $gradeLevel !== ''): ?>
        <a href="manage-books.php?view=<?php echo e($view); ?>" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">
            <?php echo ucfirst($view); ?> Books
            <span class="badge badge-muted" style="margin-left:8px;"><?php echo count($books); ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Subject</th>
                    <th>Grade</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($books)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-title">No books found</div>
                        <div class="empty-state-desc">Try adjusting your filters or add a new book.</div>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                <tr>
                    <td>
                        <div class="book-thumb">
                            <?php if (!empty($book['cover_image'])): ?>
                            <img src="/Library Management System/uploads/covers/<?php echo e($book['cover_image']); ?>"
                                 alt="cover">
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="fw-600"><?php echo e($book['title']); ?></td>
                    <td class="text-muted"><?php echo e($book['author']); ?></td>
                    <td><?php echo e($book['subject']); ?></td>
                    <td><span class="badge badge-muted"><?php echo e($book['grade_level']); ?></span></td>
                    <td>
                        <?php if ($book['status'] === 'active'): ?>
                        <span class="badge badge-success">Active</span>
                        <?php else: ?>
                        <span class="badge badge-muted">Archived</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                        <?php if ($book['status'] === 'inactive'): ?>
                            <form method="POST" class="d-inline-flex">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="restore_book">
                                <input type="hidden" name="view" value="<?php echo e($view); ?>">
                                <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                <button type="submit" class="btn btn-success btn-sm"
                                    onclick="return confirm('Restore this archived book?')">Restore</button>
                            </form>
                        <?php else: ?>
                            <a href="edit-book.php?id=<?php echo (int) $book['id']; ?>"
                               class="btn btn-ghost btn-sm">Edit</a>
                            <form method="POST" class="d-inline-flex">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="archive_book">
                                <input type="hidden" name="view" value="<?php echo e($view); ?>">
                                <input type="hidden" name="book_id" value="<?php echo (int) $book['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Archive this book?')">Archive</button>
                            </form>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminPageEnd(); ?>
