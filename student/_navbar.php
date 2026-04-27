<?php
$settings = $pdo->query('SELECT system_name, school_name, logo_path FROM system_settings ORDER BY id ASC LIMIT 1')->fetch();
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <?php if (!empty($settings['logo_path'])): ?>
                <img src="/Library Management System/uploads/covers/<?php echo e($settings['logo_path']); ?>" width="34" height="34" class="rounded" alt="Logo">
            <?php else: ?>
                <span class="badge brand-gradient">PNHS</span>
            <?php endif; ?>
            <span><?php echo e($settings['system_name'] ?? 'Online Library System'); ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav" aria-controls="studentNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="studentNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="library.php">Library</a></li>
                <li class="nav-item"><a class="nav-link" href="reading-history.php">Reading History</a></li>
            </ul>
            <form class="d-flex me-3" action="library.php" method="GET">
                <input class="form-control" name="search" placeholder="Search books">
            </form>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"><?php echo e(userDisplayName()); ?></button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="/Library Management System/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
