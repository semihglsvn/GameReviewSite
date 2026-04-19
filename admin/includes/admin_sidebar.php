<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$sidebar_role = $_SESSION['role_id'] ?? 5;
?>
<nav class="sidebar admin-sidebar">
    <h3>Admin Panel</h3>
    <hr>
    <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">Dashboard</a>
    
    <?php if ($sidebar_role == 1 || $sidebar_role == 2): ?>
        <a href="games.php" class="nav-link <?= ($currentPage == 'games.php') ? 'active' : '' ?>">Manage Games</a>
        <a href="featured.php" class="nav-link <?= ($currentPage == 'featured.php') ? 'active' : '' ?>">Featured Games</a>
    <?php endif; ?>

    <a href="users.php" class="nav-link <?= ($currentPage == 'users.php') ? 'active' : '' ?>">Manage Users</a>
    <a href="reports.php" class="nav-link <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">Reported Reviews</a>
    
    <?php if ($sidebar_role == 1): ?>
        <a href="settings.php" class="nav-link <?= ($currentPage == 'settings.php') ? 'active' : '' ?>">Site Settings</a>
        <a href="logs.php" class="nav-link <?= ($currentPage == 'logs.php') ? 'active' : '' ?>">System Logs</a>
    <?php endif; ?>
    
    <a href="../logout.php" class="logout-link">Logout</a>
</nav>