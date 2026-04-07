<?php
// seed_bg3.php
require_once 'config/db.php';

echo "==========================================\n";
echo " DUMMY USERS & CRITICS SEEDER\n";
echo "==========================================\n";

// 1. Array of Users to Insert
$users = [
    // Normal Users (role_id = 5)
    [101, 5, 'user1', 'user1@test.com', '2000-01-01'],
    [102, 5, 'user2', 'user2@test.com', '2000-01-01'],
    [103, 5, 'user3', 'user3@test.com', '2000-01-01'],
    [104, 5, 'user4', 'user4@test.com', '2000-01-01'],
    [105, 5, 'user5', 'user5@test.com', '2000-01-01'],
    
    // Critics (role_id = 4)
    [106, 4, 'critic1', 'critic1@test.com', '1990-05-15'],
    [107, 4, 'critic2', 'critic2@test.com', '1992-08-20'],
    [108, 4, 'critic3', 'critic3@test.com', '1988-11-30']
];

// Use INSERT IGNORE so running this twice doesn't crash your database
$stmt_user = $conn->prepare("INSERT IGNORE INTO users (id, role_id, username, email, password_hash, dob) VALUES (?, ?, ?, ?, ?, ?)");

foreach ($users as $u) {
    // Securely hash the username so the password matches the username (e.g. 'user1' = 'user1')
    $hashed_password = password_hash($u[2], PASSWORD_DEFAULT);
    
    $stmt_user->bind_param("iissss", $u[0], $u[1], $u[2], $u[3], $hashed_password, $u[4]);
    $stmt_user->execute();
}
$stmt_user->close();
echo "[SUCCESS] Users and Critics created with securely hashed passwords.\n";

// 2. Array of Reviews to Insert (Baldur's Gate 3 ID: 324997)
$reviews = [
    // User Reviews
    [101, 324997, 95, 'Absolutely incredible game. Best RPG I have ever played!', 'approved'],
    [102, 324997, 88, 'The story is amazing, but the inventory management is a bit annoying.', 'approved'],
    [103, 324997, 100, '10/10 would romance Karlach again.', 'approved'],
    [104, 324997, 92, 'So much freedom to do whatever you want. Game of the decade.', 'approved'],
    [105, 324997, 85, 'Turn-based combat isn\'t usually my thing, but this game hooked me completely.', 'approved'],
    
    // Critic Reviews
    [106, 324997, 100, 'A masterful achievement in modern role-playing games. Larian Studios has set a new benchmark for the genre with unparalleled player agency.', 'approved'],
    [107, 324997, 96, 'Deep, tactical combat mixed with exceptional writing elevates this classic D&D adaptation to legendary status. A must-play.', 'approved'],
    [108, 324997, 94, 'Rich world-building, gorgeous visuals, and a narrative that genuinely reacts to your choices. Baldur\'s Gate 3 is a triumph.', 'approved']
];

$stmt_review = $conn->prepare("INSERT IGNORE INTO reviews (user_id, game_id, score, comment, status) VALUES (?, ?, ?, ?, ?)");

foreach ($reviews as $r) {
    $stmt_review->bind_param("iiiss", $r[0], $r[1], $r[2], $r[3], $r[4]);
    $stmt_review->execute();
}
$stmt_review->close();
$conn->close();

echo "[SUCCESS] All reviews posted to Baldur's Gate 3 successfully!\n";
echo "==========================================\n";
echo "You can now login at login.php with username: 'user1' and password: 'user1'\n";
?>