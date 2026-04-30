<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['superadmin']);

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'create_admin') {
        $fullname = trim((string) ($_POST['new_fullname'] ?? ''));
        $email = trim((string) ($_POST['new_email'] ?? ''));
        $password = trim((string) ($_POST['new_password'] ?? ''));
        
        if ($fullname === '' || $email === '' || $password === '') {
            $error = 'Please complete all required fields for the new admin.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO users (fullname, email, password, role, is_active, created_at) VALUES (:fullname, :email, :password, :role, 1, NOW())');
                $stmt->execute([
                    ':fullname' => $fullname,
                    ':email' => $email,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => 'admin'
                ]);
                logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Created new admin account: ' . $email);
                $success = 'Admin account created successfully.';
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = 'That email address is already in use.';
                } else {
                    $error = 'Unable to create admin account right now.';
                }
            }
        }
    } else {
        $userId          = (int) ($_POST['user_id'] ?? 0);
        $fullname        = trim((string) ($_POST['fullname'] ?? ''));
        $email           = trim((string) ($_POST['email'] ?? ''));
        $role            = (string) ($_POST['role'] ?? 'student');
        $gradeLevelInput = trim((string) ($_POST['grade_level'] ?? ''));
        $gradeLevel      = $gradeLevelInput === '' ? null : $gradeLevelInput;
        if ($role === 'admin') {
            $gradeLevel = null;
        }
        $isActive        = isset($_POST['is_active']) ? 1 : 0;

        if ($userId <= 0 || $fullname === '' || $email === '') {
            $error = 'Please complete all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (!in_array($role, ['admin', 'student'], true)) {
            $error = 'Only admin or student roles are editable here.';
        } else {
            $targetStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
            $targetStmt->execute([':id' => $userId]);
            $targetUser = $targetStmt->fetch();

            if (!$targetUser || !in_array((string) $targetUser['role'], ['admin', 'student'], true)) {
                $error = 'Only student and admin accounts can be edited.';
            } else {
                try {
                    $stmt = $pdo->prepare('UPDATE users SET fullname = :fullname, email = :email, grade_level = :grade_level, role = :role, is_active = :is_active WHERE id = :id');
                    $stmt->execute([
                        ':fullname'    => $fullname,
                        ':email'       => $email,
                        ':grade_level' => $gradeLevel,
                        ':role'        => $role,
                        ':is_active'   => $isActive,
                        ':id'          => $userId,
                    ]);
                    logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Updated user details');
                    $success = 'User details updated successfully.';
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $error = 'That email address is already in use.';
                    } else {
                        $error = 'Unable to update user details right now.';
                    }
                }
            }
        }
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$filterRole = trim((string) ($_GET['role'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$sql = 'SELECT id, fullname, email, grade_level, role, is_active, created_at FROM users WHERE 1=1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (fullname LIKE :search_name OR email LIKE :search_email)';
    $params[':search_name']  = '%' . $search . '%';
    $params[':search_email'] = '%' . $search . '%';
}
if ($filterRole !== '') {
    $sql .= ' AND role = :role';
    $params[':role'] = $filterRole;
}
if ($filterStatus !== '') {
    $sql .= ' AND is_active = :status';
    $params[':status'] = $filterStatus === 'active' ? 1 : 0;
}

$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-users.php','label' => 'Manage Users', 'active' => true],
    ['href' => 'system-logs.php', 'label' => 'System Logs',  'active' => false],
    ['href' => 'settings.php',    'label' => 'Settings',     'active' => false],
];

adminPageStart('Manage Users', 'Super Admin / Manage Users', $sidebarLinks, 'Super Admin',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>
<?php if ($success !== ''): ?>
<div class="alert alert-success"><?php echo e($success); ?></div>
<?php endif; ?>

<div class="data-card mb-4">
    <div class="data-card-header">
        <div class="data-card-title">Add New Admin Account</div>
    </div>
    <div class="data-card-body">
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="create_admin">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input class="form-control" name="new_fullname" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" name="new_email" placeholder="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" name="new_password" placeholder="Min. 8 characters" required minlength="8">
                </div>
            </div>
            <button class="btn btn-primary btn-sm" type="submit">Create Admin Account</button>
        </form>
    </div>
</div>

<form method="GET" style="margin-bottom:16px;">
    <div class="d-flex gap-2" style="flex-wrap:wrap;">
        <input class="form-control" style="max-width:240px;" name="search"
               placeholder="Search name or email"
               value="<?php echo e($search); ?>">
        <select class="form-select" style="max-width:150px;" name="role">
            <option value="">All Roles</option>
            <option value="student" <?php echo $filterRole === 'student' ? 'selected' : ''; ?>>Student</option>
            <option value="admin" <?php echo $filterRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="superadmin" <?php echo $filterRole === 'superadmin' ? 'selected' : ''; ?>>Superadmin</option>
        </select>
        <select class="form-select" style="max-width:150px;" name="status">
            <option value="">All Status</option>
            <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
        <button class="btn btn-ghost btn-sm" type="submit">Filter</button>
        <?php if ($search !== '' || $filterRole !== '' || $filterStatus !== ''): ?>
        <a href="manage-users.php" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">
            All Users
            <span class="badge badge-muted" style="margin-left:8px;"><?php echo count($users); ?></span>
        </div>
        <span class="text-sm text-muted">Superadmin accounts are protected</span>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Grade</th>
                    <th>Role</th>
                    <th>Active</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
                <?php if (in_array($user['role'], ['admin', 'student'], true)): ?>
                <form method="POST">
                    <td>
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                        <input type="text" name="fullname" class="form-control form-control-sm"
                               value="<?php echo e($user['fullname']); ?>" required style="min-width:130px;">
                    </td>
                    <td>
                        <input type="email" name="email" class="form-control form-control-sm"
                               value="<?php echo e($user['email']); ?>" required style="min-width:170px;">
                    </td>
                    <td>
                        <?php if ($user['role'] === 'student'): ?>
                        <select name="grade_level" class="form-select form-select-sm" style="min-width:120px;">
                            <option value="">—</option>
                            <option value="7" <?php echo ($user['grade_level'] === '7') ? 'selected' : ''; ?>>Grade 7</option>
                            <option value="8" <?php echo ($user['grade_level'] === '8') ? 'selected' : ''; ?>>Grade 8</option>
                            <option value="9" <?php echo ($user['grade_level'] === '9') ? 'selected' : ''; ?>>Grade 9</option>
                            <option value="10" <?php echo ($user['grade_level'] === '10') ? 'selected' : ''; ?>>Grade 10</option>
                        </select>
                        <?php else: ?>
                        <input type="text" class="form-control form-control-sm" value="—" disabled style="min-width:80px;">
                        <input type="hidden" name="grade_level" value="">
                        <?php endif; ?>
                    </td>
                    <td>
                        <select name="role" class="form-select form-select-sm" style="min-width:90px;">
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </td>
                    <td>
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active"
                                   <?php echo (int) $user['is_active'] === 1 ? 'checked' : ''; ?>>
                        </label>
                    </td>
                    <td class="text-muted text-sm"><?php echo e($user['created_at']); ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" type="submit">Save</button>
                    </td>
                </form>
                <?php else: ?>
                    <td class="fw-600"><?php echo e($user['fullname']); ?></td>
                    <td class="text-muted"><?php echo e($user['email']); ?></td>
                    <td class="text-muted">—</td>
                    <td><span class="badge badge-violet">Superadmin</span></td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td class="text-muted text-sm"><?php echo e($user['created_at']); ?></td>
                    <td><span class="badge badge-muted">Protected</span></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php adminPageEnd(); ?>
