<?php
// api/rebuild_cache.php
require_once '../config/db.php'; // Adjust path if needed

// We tell the browser to ignore this file so it doesn't hold up page loading
// (FastCGI finish request works on some servers, but we'll keep it simple)
ignore_user_abort(true);
set_time_limit(60); 

$cacheDir = __DIR__ . '/../cache';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

// 1. Rebuild Trending
$popular_query = "
    SELECT g.*, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names, COUNT(r.id) as review_count
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    LEFT JOIN reviews r ON g.id = r.game_id AND r.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    WHERE g.metascore > 0
    GROUP BY g.id 
    ORDER BY review_count DESC, g.metascore DESC 
    LIMIT 16
";
$data = $conn->query($popular_query)->fetch_all(MYSQLI_ASSOC);
file_put_contents($cacheDir . '/trending_games.json', json_encode($data));

// 2. Rebuild New Releases
$new_releases_query = "
    SELECT g.*, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names 
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE g.release_date <= CURDATE() 
    GROUP BY g.id 
    ORDER BY g.release_date DESC 
    LIMIT 16
";
$data = $conn->query($new_releases_query)->fetch_all(MYSQLI_ASSOC);
file_put_contents($cacheDir . '/new_releases.json', json_encode($data));

// 3. Rebuild Top Rated
$top_rated_query = "
    SELECT g.*, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names 
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE g.metascore > 0
    GROUP BY g.id 
    ORDER BY g.metascore DESC 
    LIMIT 16
";
$data = $conn->query($top_rated_query)->fetch_all(MYSQLI_ASSOC);
file_put_contents($cacheDir . '/top_rated.json', json_encode($data));

echo json_encode(["status" => "Cache Rebuilt Successfully"]);