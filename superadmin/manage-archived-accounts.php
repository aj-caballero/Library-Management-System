<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
require_once __DIR__ . '/../config/pagination.php';
require_once __DIR__ . '/../config/archived_accounts.php';
ensureRole(['superadmin']);

$flashNotice = $_SESSION['archived_accounts_flash'] ?? null;
unset($_SESSION['archived_accounts_flash']);

$search = trim((string) ($_GET['search'] ?? ''));
$roleFilter = trim((string) ($_GET['role'] ?? ''));
$gradeFilter = trim((string) ($_GET['grade_level'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'reactivate') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $_SESSION['archived_accounts_flash'] = [
            'type' => 'danger',
            'text' => 'Invalid session token. Please try again.',
        ];
    } else {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $targetPage = max(1, (int) ($_POST['page'] ?? 1));
        $targetSearch = trim((string) ($_POST['search'] ?? ''));
        $targetRole = trim((string) ($_POST['role'] ?? ''));
        $targetGrade = trim((string) ($_POST['grade_level'] ?? ''));

        $queryParams = array_filter([
            'page' => $targetPage,
            'search' => $targetSearch,
            'role' => $targetRole,
            'grade_level' => $targetGrade,
        ], static fn($value): bool => $value !== '' && $value !== null);

        if ($userId <= 0) {
            $_SESSION['archived_accounts_flash'] = [
                'type' => 'danger',
                'text' => 'Invalid archived account selected.',
            ];
        } else {
            $checkStmt = $pdo->prepare('SELECT grade_level FROM users WHERE id = :id AND is_archived = 1 LIMIT 1');
            $checkStmt->execute([':id' => $userId]);
            $user = $checkStmt->fetch();

            if (!$user) {
                $_SESSION['archived_accounts_flash'] = [
                    'type' => 'danger',
                    'text' => 'Archived account not found.',
                ];
            } elseif (strpos((string) $user['grade_level'], '10') !== false) {
                $_SESSION['archived_accounts_flash'] = [
                    'type' => 'danger',
                    'text' => 'Grade 10 archived accounts cannot be unarchived.',
                ];
            } else {
                $updateStmt = $pdo->prepare('UPDATE users SET is_archived = 0, is_active = 1, archived_reason = NULL WHERE id = :id');
                $updateStmt->execute([':id' => $userId]);

                logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Unarchived account and set active (User ID: ' . $userId . ')');
                $_SESSION['archived_accounts_flash'] = [
                    'type' => 'success',
                    'text' => 'Account unarchived and set to active.',
                ];
            }
        }

        $redirectUrl = basePath('/superadmin/manage-archived-accounts.php');
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }
        redirect($redirectUrl);
    }
}

$filters = archivedAccountsFilterSql($search, $roleFilter, $gradeFilter);
$summary = archivedAccountsSummaryCount($pdo, $roleFilter, $gradeFilter);

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM users' . $filters['countWhere']);
$countStmt->execute($filters['params']);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $perPage) : 1;

$listSql = 'SELECT id, fullname, email, grade_level, role, archived_reason, last_login_at, created_at FROM users' . $filters['where'] . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$listStmt = $pdo->prepare($listSql);
$listStmt->execute($filters['params']);
$archivedAccounts = $listStmt->fetchAll();

$gradeOptions = $pdo->query("SELECT DISTINCT grade_level FROM users WHERE is_archived = 1 AND grade_level IS NOT NULL AND grade_level != '' ORDER BY grade_level ASC")->fetchAll(PDO::FETCH_COLUMN);

$currentUser = userDisplayName();
$initials = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php', 'label' => 'Dashboard', 'active' => false],
    ['href' => 'manage-users.php', 'label' => 'Manage Users', 'active' => false],
    ['href' => 'manage-archived-accounts.php', 'label' => 'Archived Accounts', 'active' => true],
    ['href' => 'system-logs.php', 'label' => 'System Logs', 'active' => false],
    ['href' => 'settings.php', 'label' => 'Settings', 'active' => false],
];

adminPageStart('Archived Accounts', 'Super Admin / Archived Accounts', $sidebarLinks, 'Super Admin',
    '/Library Management System/logout.php', $currentUser, $initials);

$queryBase = array_filter([
    'search' => $search,
    'role' => $roleFilter,
    'grade_level' => $gradeFilter,
], static fn($value): bool => $value !== '' && $value !== null);
?>

<?php if (is_array($flashNotice) && !empty($flashNotice['text'])): ?>
<div class="alert alert-<?php echo e((string) ($flashNotice['type'] ?? 'info')); ?> mb-3">
    <?php echo e((string) $flashNotice['text']); ?>
</div>
<?php endif; ?>

<div class="archive-stats">
    <div class="archive-stat">
        <div class="archive-stat-label">Archived Accounts</div>
        <div class="archive-stat-value"><?php echo number_format($summary['total']); ?></div>
    </div>
    <div class="archive-stat">
        <div class="archive-stat-label">Archived Students</div>
        <div class="archive-stat-value"><?php echo number_format($summary['students']); ?></div>
    </div>
    <div class="archive-stat">
        <div class="archive-stat-label">Archived Admins</div>
        <div class="archive-stat-value"><?php echo number_format($summary['admins']); ?></div>
    </div>
    <div class="archive-stat">
        <div class="archive-stat-label">Current Results</div>
        <div class="archive-stat-value"><?php echo number_format($totalRows); ?></div>
    </div>
</div>

<form method="GET" class="archive-toolbar">
    <div>
        <label class="form-label">Search</label>
        <input class="form-control" name="search" value="<?php echo e($search); ?>" placeholder="Search name, email, or reason">
    </div>
    <div>
        <label class="form-label">Role</label>
        <select class="form-select" name="role">
            <option value="">All Roles</option>
            <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>Student</option>
            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
    </div>
    <div>
        <label class="form-label">Grade Level</label>
        <select class="form-select" name="grade_level">
            <option value="">All Grades</option>
            <?php foreach ($gradeOptions as $grade): ?>
                <option value="<?php echo e((string) $grade); ?>" <?php echo $gradeFilter === (string) $grade ? 'selected' : ''; ?>><?php echo e((string) $grade); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="archive-actions">
        <button class="btn btn-primary" type="submit">Apply</button>
        <?php if (!empty($queryBase)): ?>
            <a href="manage-archived-accounts.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </div>
</form>

<div class="data-card">
    <div class="data-card-header">
        <div class="data-card-title">Archived Accounts</div>
        <div class="text-sm text-muted"><?php echo number_format($totalRows); ?> matching accounts</div>
    </div>
    <div class="data-card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Grade Level</th>
                        <th>Last Login</th>
                        <th>Archived Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($archivedAccounts)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state archive-empty-note">
                                    <div class="empty-state-title">No archived accounts found</div>
                                    <div class="empty-state-desc">Try adjusting your search or filters.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($archivedAccounts as $account): ?>
                            <tr>
                                <td class="fw-600"><?php echo e($account['fullname']); ?></td>
                                <td class="text-muted"><?php echo e($account['email']); ?></td>
                                <td>
                                    <?php if ($account['role'] === 'admin'): ?>
                                        <span class="badge badge-violet">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-blue">Student</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-muted"><?php echo e($account['grade_level'] ?: 'N/A'); ?></span></td>
                                <td class="text-muted"><?php echo $account['last_login_at'] ? e($account['last_login_at']) : 'Never'; ?></td>
                                <td class="text-muted"><?php echo e($account['archived_reason'] ?: 'No reason provided'); ?></td>
                                <td>
                                    <?php $isGrade10 = strpos((string) $account['grade_level'], '10') !== false; ?>
                                    <?php if ($isGrade10): ?>
                                    <span class="badge badge-danger" title="Grade 10 accounts cannot be unarchived">Locked</span>
                                    <?php else: ?>
                                    <form method="POST" class="d-inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="reactivate">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $account['id']; ?>">
                                        <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
                                        <input type="hidden" name="search" value="<?php echo e($search); ?>">
                                        <input type="hidden" name="role" value="<?php echo e($roleFilter); ?>">
                                        <input type="hidden" name="grade_level" value="<?php echo e($gradeFilter); ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Unarchive</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php renderPaginationLinks('manage-archived-accounts.php', $queryBase, $page, $totalPages, $totalRows, $perPage); ?>

<?php adminPageEnd(); ?>
