<?php

declare(strict_types=1);

/**
 * Shared layout helper for admin/superadmin panel.
 *
 * Required variables before including:
 *   $pageTitle   string  — Page title shown in topbar
 *   $breadcrumb  string  — Breadcrumb subtitle
 *   $sidebarLinks  array — [ ['href', 'icon', 'label', 'active'] ]
 *   $sidebarRole  string — e.g. "Administrator" or "Super Admin"
 *   $logoutHref  string  — Path to logout.php
 *   $currentUser  string — Display name
 *   $initials    string  — 1–2 letter avatar initials
 */
function adminPageStart(
    string $pageTitle,
    string $breadcrumb,
    array  $sidebarLinks,
    string $sidebarRole,
    string $logoutHref,
    string $currentUser,
    string $initials
): void {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?> — LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Library Management System/assets/css/admin.css">
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="admin-sidebar" id="adminSidebar">
    <a class="sidebar-brand" href="#">
        <div class="sidebar-brand-icon">LMS</div>
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-title">LibraryMS</div>
            <div class="sidebar-brand-sub"><?php echo htmlspecialchars($sidebarRole, ENT_QUOTES); ?></div>
        </div>
    </a>

    <div class="sidebar-section-label">Main Menu</div>
    <nav class="sidebar-nav">
        <?php foreach ($sidebarLinks as $link): ?>
        <a href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES); ?>"
           class="sidebar-link<?php echo !empty($link['active']) ? ' active' : ''; ?>">
            <?php echo htmlspecialchars($link['label'], ENT_QUOTES); ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-section-label">Account</div>
    <nav class="sidebar-nav" style="padding-top:0;">
        <a href="<?php echo htmlspecialchars($logoutHref, ENT_QUOTES); ?>" class="sidebar-link danger">
            Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        Logged in as<br>
        <strong><?php echo htmlspecialchars($currentUser, ENT_QUOTES); ?></strong>
    </div>
</aside>

<header class="admin-topbar">
    <div class="d-flex align-center gap-3">
        <button class="topbar-hamburger" id="hamburgerBtn" aria-label="Toggle menu">
            <span class="ham-bar"></span>
            <span class="ham-bar"></span>
            <span class="ham-bar"></span>
        </button>
        <div class="topbar-left">
            <div class="topbar-title"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?></div>
            <div class="topbar-breadcrumb"><?php echo htmlspecialchars($breadcrumb, ENT_QUOTES); ?></div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-user-info">
            <div class="topbar-user-name"><?php echo htmlspecialchars($currentUser, ENT_QUOTES); ?></div>
            <div class="topbar-user-role"><?php echo htmlspecialchars($sidebarRole, ENT_QUOTES); ?></div>
        </div>
        <div class="topbar-avatar"><?php echo htmlspecialchars($initials, ENT_QUOTES); ?></div>
    </div>
</header>

<main class="admin-main">
<div class="admin-content">
<?php
}

function adminPageEnd(): void {
?>
</div><!-- /admin-content -->
</main>

<script>
(function () {
    var sidebar   = document.getElementById('adminSidebar');
    var overlay   = document.getElementById('sidebarOverlay');
    var hamburger = document.getElementById('hamburgerBtn');
    if (!sidebar || !overlay || !hamburger) return;
    function toggle() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('open');
    }
    hamburger.addEventListener('click', toggle);
    overlay.addEventListener('click', toggle);
})();
</script>
</body>
</html>
<?php
}

/**
 * Build initials from a display name.
 */
function makeInitials(string $name): string {
    $parts = array_filter(explode(' ', $name));
    $letters = array_map(fn($p) => strtoupper($p[0]), $parts);
    return substr(implode('', $letters), 0, 2);
}
