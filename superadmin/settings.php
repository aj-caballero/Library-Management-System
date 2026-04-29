<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/admin_layout.php';
ensureRole(['superadmin']);

$settings = $pdo->query('SELECT * FROM system_settings ORDER BY id ASC LIMIT 1')->fetch();
$success  = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $systemName = trim((string) ($_POST['system_name'] ?? 'Online Library System'));
    $schoolName = trim((string) ($_POST['school_name'] ?? 'Paliparan National High School'));
    $logoPath   = $settings['logo_path'] ?? null;

    if ($error === '') {
        $uploadError = null;
        $newLogoPath = secureUpload(
            $_FILES['logo'] ?? [],
            'logo',
            __DIR__ . '/../uploads/covers',
            ['jpg', 'jpeg', 'png', 'webp'],
            ['image/jpeg', 'image/png', 'image/webp'],
            5 * 1024 * 1024,
            $uploadError
        );

        if ($uploadError !== null) {
            $error = $uploadError;
        } elseif ($newLogoPath !== null) {
            $logoPath = $newLogoPath;
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare('UPDATE system_settings SET system_name = :system_name, school_name = :school_name, logo_path = :logo_path WHERE id = :id');
        $stmt->execute([
            ':system_name' => $systemName,
            ':school_name' => $schoolName,
            ':logo_path'   => $logoPath,
            ':id'          => (int) $settings['id'],
        ]);
        logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Updated system settings');
        $success  = 'Settings updated successfully.';
        $settings = $pdo->query('SELECT * FROM system_settings ORDER BY id ASC LIMIT 1')->fetch();
    }
}

$currentUser  = userDisplayName();
$initials     = makeInitials($currentUser);
$sidebarLinks = [
    ['href' => 'dashboard.php',   'label' => 'Dashboard',    'active' => false],
    ['href' => 'manage-users.php','label' => 'Manage Users', 'active' => false],
    ['href' => 'system-logs.php', 'label' => 'System Logs',  'active' => false],
    ['href' => 'settings.php',    'label' => 'Settings',     'active' => true],
];

adminPageStart('Settings', 'Super Admin / Settings', $sidebarLinks, 'Super Admin',
    '/Library Management System/logout.php', $currentUser, $initials);
?>

<?php if ($success !== ''): ?>
<div class="alert alert-success"><?php echo e($success); ?></div>
<?php endif; ?>
<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="data-card" style="max-width:600px;">
    <div class="data-card-header">
        <div class="data-card-title">System Configuration</div>
    </div>
    <div class="data-card-body">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label class="form-label">System Name</label>
                <input class="form-control" name="system_name"
                       value="<?php echo e($settings['system_name'] ?? ''); ?>" required>
                <div class="form-hint">Displayed throughout the application.</div>
            </div>

            <div class="form-group">
                <label class="form-label">School / Institution Name</label>
                <input class="form-control" name="school_name"
                       value="<?php echo e($settings['school_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">System Logo</label>
                <?php if (!empty($settings['logo_path'])): ?>
                <div style="margin-bottom:10px;display:flex;align-items:center;gap:12px;">
                    <img src="/Library Management System/uploads/covers/<?php echo e($settings['logo_path']); ?>"
                         style="height:56px;width:auto;border-radius:8px;border:1px solid var(--card-border);"
                         alt="Current Logo">
                    <span class="text-sm text-muted">Current logo</span>
                </div>
                <?php endif; ?>
                <input class="form-control" type="file" name="logo" accept="image/*">
                <div class="form-hint">JPG, PNG or WebP — max 5 MB. Leave blank to keep current.</div>
            </div>

            <div style="margin-top:8px;">
                <button class="btn btn-primary" type="submit">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<?php adminPageEnd(); ?>
