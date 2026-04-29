<?php
$settings = $pdo->query('SELECT system_name, school_name, logo_path FROM system_settings ORDER BY id ASC LIMIT 1')->fetch();
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <img src="/Library Management System/assets/images/SchoolLogo.png" width="40" height="40" alt="School Logo">
            <span><?php echo e($settings['system_name'] ?? 'Online Library'); ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav" aria-controls="studentNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="studentNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="library.php">Library</a></li>
                <li class="nav-item"><a class="nav-link" href="favorites.php">Favorites</a></li>
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
