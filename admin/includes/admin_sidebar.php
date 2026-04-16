<?php
// Get the current file name to highlight the active menu item
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
    <h3>Admin Panel</h3>
    <hr>
    <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">Dashboard</a>
    <a href="games.php" class="nav-link <?= ($currentPage == 'games.php') ? 'active' : '' ?>">Manage Games</a>
    <a href="users.php" class="nav-link <?= ($currentPage == 'users.php') ? 'active' : '' ?>">Manage Users</a>
    <a href="reports.php" class="nav-link <?= ($currentPage == 'reports.php') ? 'active' : '' ?>">Reported Reviews</a>
    <a href="../logout.php" class="logout-link">Logout</a>
</nav>