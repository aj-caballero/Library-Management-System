<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensureRole(['superadmin']);

$settings = $pdo->query('SELECT * FROM system_settings ORDER BY id ASC LIMIT 1')->fetch();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Please refresh and try again.';
    }

    $systemName = trim((string) ($_POST['system_name'] ?? 'Online Library System'));
    $schoolName = trim((string) ($_POST['school_name'] ?? 'Paliparan National High School'));
    $logoPath = $settings['logo_path'] ?? null;

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
            ':logo_path' => $logoPath,
            ':id' => (int) $settings['id'],
        ]);
        logSystemActivity($pdo, (int) $_SESSION['user']['id'], 'Updated system settings');
        $success = 'Settings updated successfully.';
        $settings = $pdo->query('SELECT * FROM system_settings ORDER BY id ASC LIMIT 1')->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/style.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <h5 class="text-white mb-4">Super Admin</h5>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="manage-users.php">Manage Users</a>
                <a class="nav-link" href="system-logs.php">System Logs</a>
                <a class="nav-link active" href="settings.php">Settings</a>
                <a class="nav-link" href="/Library Management System/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="col-md-9 col-lg-10 p-4">
            <h3 class="mb-3">System Settings</h3>
            <?php if ($success !== ''): ?><div class="alert alert-success"><?php echo e($success); ?></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

            <div class="card card-shadow">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <?php echo csrfField(); ?>
                        <div class="mb-3">
                            <label class="form-label">System Name</label>
                            <input class="form-control" name="system_name" value="<?php echo e($settings['system_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">School Name</label>
                            <input class="form-control" name="school_name" value="<?php echo e($settings['school_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <input class="form-control" type="file" name="logo" accept="image/*">
                            <?php if (!empty($settings['logo_path'])): ?>
                                <img src="/Library Management System/uploads/covers/<?php echo e($settings['logo_path']); ?>" class="mt-2" width="120" alt="System Logo">
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-primary" type="submit">Save Settings</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
