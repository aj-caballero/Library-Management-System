<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['admin', 'superadmin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        if ($studentId > 0) {
            $newPassword = 'student' . rand(1000, 9999);
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id AND role = 'student'");
            $stmt->execute([':password' => $hashed, ':id' => $studentId]);
            if ($stmt->rowCount() > 0) {
                logSystemActivity($pdo, (int) $_SESSION['user']['id'], "Reset password for student ID $studentId");
                $success = "Password reset successfully. The new password is: <strong>$newPassword</strong>";
            } else {
                $error = 'Failed to reset password. Student not found.';
            }
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$gradeLevel = trim((string) ($_GET['grade_level'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));

$sql = "SELECT id, fullname, email, grade_level, created_at, is_active FROM users WHERE role = 'student'";
$params = [];

if ($search !== '') {
    $sql .= " AND (fullname LIKE :search_name OR email LIKE :search_email)";
    $params[':search_name']  = '%' . $search . '%';
    $params[':search_email'] = '%' . $search . '%';
}
if ($gradeLevel !== '') {
    $sql .= " AND grade_level = :grade_level";
    $params[':grade_level'] = $gradeLevel;
}
if ($status !== '') {
    $sql .= " AND is_active = :status";
    $params[':status'] = $status === 'active' ? 1 : 0;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$levels = $pdo->query("SELECT DISTINCT grade_level FROM users WHERE role = 'student' AND grade_level IS NOT NULL AND grade_level != '' ORDER BY grade_level ASC")->fetchAll(PDO::FETCH_COLUMN);

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-books.php','label' => 'Manage Books', 'active' => false],
    ['href' => 'add-book.php',    'label' => 'Add Book',     'active' => false],
    ['href' => 'manage-users.php','label' => 'Students',     'active' => true],
    ['href' => 'reports.php',     'label' => 'Reports',      'active' => false],
];

adminPageStart('Students', 'Administrator / Students', $sidebarLinks, 'Administrator',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
<div class="alert alert-success"><?php echo $success; // contains HTML strong tag ?></div>
<?php endif; ?>

<form method="GET" style="margin-bottom:16px;">
    <div class="d-flex gap-2" style="flex-wrap:wrap;">
        <input class="form-control" style="max-width:240px;" name="search"
               placeholder="Search name or email"
               value="<?php echo e($search); ?>">
        <select class="form-select" style="max-width:180px;" name="grade_level">
            <option value="">All Grades</option>
            <?php foreach ($levels as $level): ?>
                <option value="<?php echo e((string) $level); ?>"
                    <?php echo $gradeLevel === (string) $level ? 'selected' : ''; ?>>
                    <?php echo e((string) $level); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="form-select" style="max-width:150px;" name="status">
            <option value="">All Status</option>
            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
        <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
        <?php if ($search !== '' || $gradeLevel !== '' || $status !== ''): ?>
        <a href="manage-users.php" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">
            Registered Students
            <span class="badge badge-muted" style="margin-left:8px;"><?php echo count($students); ?></span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Grade Level</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($students)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-title">No students yet</div>
                        <div class="empty-state-desc">Students will appear here once they register.</div>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td class="fw-600"><?php echo e($student['fullname']); ?></td>
                    <td class="text-muted"><?php echo e($student['email']); ?></td>
                    <td>
                        <?php if (!empty($student['grade_level'])): ?>
                        <span class="badge badge-muted"><?php echo e($student['grade_level']); ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int) $student['is_active'] === 1): ?>
                        <span class="badge badge-success">Active</span>
                        <?php else: ?>
                        <span class="badge badge-muted">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted text-sm"><?php echo e($student['created_at']); ?></td>
                    <td>
                        <form method="POST" class="d-inline-flex" onsubmit="return confirm('Reset password for <?php echo addslashes($student['fullname']); ?>?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="reset_password">
                            <input type="hidden" name="student_id" value="<?php echo (int) $student['id']; ?>">
                            <button type="submit" class="btn btn-warning btn-sm" style="color: #fff;">Reset Password</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminPageEnd(); ?>
