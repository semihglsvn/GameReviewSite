<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$sidebar_role = $_SESSION['role_id'] ?? 5;
?>
<nav class="sidebar">
    <h3>Admin Panel</h3>
    <hr>
    <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">Dashboard</a>
    
    <?php if ($sidebar_role != 3): ?>
        <a href="games.php" class="nav-link <?= ($currentPage == 'games.php') ? 'active' : '' ?>">Manage Games</a>
    <?php endif; ?>

    <a href="users.php" class="nav-link <?= ($currentPage == 'users.php') ? 'active' : '' ?>">Manage Users</a>
    <a href="reports.php" class="nav-link <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">Reported Reviews</a>
    <a href="../logout.php" class="logout-link">Logout</a>
</nav>