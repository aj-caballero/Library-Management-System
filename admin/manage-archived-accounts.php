<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/pagination.php';
ensureRole(['superadmin', 'admin']);

$action = trim((string) ($_GET['action'] ?? ''));
$userId = (int) ($_GET['user_id'] ?? 0);
$message = '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Handle reactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reactivate') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid session token.';
    } else {
        $userId = (int) ($_POST['user_id'] ?? 0);
        
        // Check if account is Grade 10
        $checkStmt = $pdo->prepare("SELECT grade_level, archived_reason FROM users WHERE id = :id");
        $checkStmt->execute([':id' => $userId]);
        $user = $checkStmt->fetch();

        if ($user) {
            if (strpos($user['grade_level'], '10') !== false) {
                $message = 'Error: Grade 10 archived accounts cannot be reactivated.';
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET is_archived = 0, archived_reason = NULL
                    WHERE id = :id
                ");
                $updateStmt->execute([':id' => $userId]);
                
                logSystemActivity($pdo, (int) $_SESSION['user']['id'], "Reactivated archived account (User ID: $userId)");
                $message = 'Account successfully reactivated!';
            }
        } else {
            $message = 'User not found.';
        }
    }
}

// Get archived accounts count
$countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_archived = 1");
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = $totalRows > 0 ? (int) ceil($totalRows / $perPage) : 1;

$stmt = $pdo->prepare("
    SELECT id, fullname, email, grade_level, role, is_archived, archived_reason, last_login_at, created_at
    FROM users
    WHERE is_archived = 1
    ORDER BY created_at DESC
    LIMIT " . $perPage . " OFFSET " . $offset . "
");
$stmt->execute();
$archivedAccounts = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Archived Accounts | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/admin.css">
</head>
<body>
<?php require __DIR__ . '/admin_layout.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h3>Manage Archived Accounts</h3>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert alert-info"><?php echo e($message); ?></div>
    <?php endif; ?>

    <div class="card card-shadow">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="brand-gradient text-white">
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Grade Level</th>
                        <th>Last Login</th>
                        <th>Archived Reason</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($archivedAccounts)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No archived accounts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($archivedAccounts as $account): ?>
                            <tr>
                                <td><?php echo e($account['fullname']); ?></td>
                                <td><?php echo e($account['email']); ?></td>
                                <td><?php echo e($account['grade_level']); ?></td>
                                <td><?php echo $account['last_login_at'] ? e($account['last_login_at']) : 'Never'; ?></td>
                                <td><?php echo e($account['archived_reason']); ?></td>
                                <td>
                                    <?php 
                                    $isGrade10 = strpos($account['grade_level'], '10') !== false;
                                    ?>
                                    <?php if ($isGrade10): ?>
                                        <span class="badge bg-danger" title="Grade 10 accounts cannot be reactivated">Cannot Reactivate</span>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#reactivateModal" onclick="setReactivateData(<?php echo (int) $account['id']; ?>, '<?php echo e($account['fullname']); ?>')">
                                            Reactivate
                                        </button>
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

<?php renderPaginationLinks('manage-archived-accounts.php', [], $page, $totalPages, $totalRows, $perPage); ?>

<!-- Reactivate Modal -->
<div class="modal fade" id="reactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reactivate Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <p>Are you sure you want to reactivate <strong id="reactivateName"></strong>'s account?</p>
                    <input type="hidden" name="user_id" id="reactivateUserId" value="">
                    <input type="hidden" name="action" value="reactivate">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reactivate Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function setReactivateData(userId, fullName) {
        document.getElementById('reactivateUserId').value = userId;
        document.getElementById('reactivateName').textContent = fullName;
    }
</script>
</body>
</html>
