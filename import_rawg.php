<?php
// 1. Remove execution time limit for long CLI runs
ini_set('max_execution_time', 0); 
require_once 'config/db.php';

$api_key = 'a708e9dfea1f4bde88a92a8cb5e9d4e2'; 

// --- CONFIGURATION ---
// Page 1001 starts at game #40,001. 
$start_page = 1001;    
// Total pages needed for 500k games is ~12,500. 
$max_pages = 12500;    
$current_page = $start_page;
$games_processed = 0;

// Disabling details to stay within 20k request limit
$fetch_details = false; 

echo "====================================================\n";
echo "GAMEJOINT MASS IMPORTER - THE FINALE\n";
echo "Starting from Page: $start_page\n";
echo "Requests remaining in budget: " . (20000 - ($max_pages - $start_page)) . "\n";
echo "====================================================\n\n";

// Prepared Statement (Requirement #3)
// We use ON DUPLICATE KEY UPDATE to refresh scores/images for existing data
$stmt_game = $conn->prepare("INSERT INTO games (id, title, release_date, esrb_rating, metascore, cover_image, developer, publisher, description) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE 
                             metascore = VALUES(metascore),
                             cover_image = VALUES(cover_image)");

$stmt_genre = $conn->prepare("INSERT IGNORE INTO genres (id, name) VALUES (?, ?)");
$stmt_game_genre = $conn->prepare("INSERT IGNORE INTO game_genres (game_id, genre_id) VALUES (?, ?)");
$stmt_platform = $conn->prepare("INSERT IGNORE INTO platforms (id, name) VALUES (?, ?)");
$stmt_game_platform = $conn->prepare("INSERT IGNORE INTO game_platforms (game_id, platform_id) VALUES (?, ?)");

// Base URL for the start
$api_url = "https://api.rawg.io/api/games?key=$api_key&page=$current_page&page_size=40&ordering=-metacritic";

while ($current_page <= $max_pages && !empty($api_url)) {
    echo "Processing Page $current_page... ";
    
    // Fetch using cURL for better reliability
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'GameJoint/1.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        echo "FAILED (HTTP $http_code). Rate limit? Sleeping 60s...\n";
        sleep(60);
        continue;
    }
    
    $data = json_decode($response, true);
    if (!isset($data['results'])) {
        echo "Empty results. Ending.\n";
        break;
    }

    foreach ($data['results'] as $game) {
        $game_id = $game['id'];
        
        // Default placeholders for bulk mode
        $developer = 'See Details'; 
        $publisher = 'See Details';
        $description = 'Visit game page for full description.';

        // (fetch_details is FALSE, skipping individual API calls)

        $title = $game['name'];
        $release_date = $game['released'] ?? null;
        $metascore = $game['metacritic'] ?? 0;
        $cover_image = $game['background_image'] ?? 'assets/images/placeholder.png';
        $esrb_rating = $game['esrb_rating']['name'] ?? 'Not Rated';

        $stmt_game->bind_param("isssissss", $game_id, $title, $release_date, $esrb_rating, $metascore, $cover_image, $developer, $publisher, $description);
        $stmt_game->execute();

        // Process Genres (Normalization - Req #2)
        if (isset($game['genres'])) {
            foreach ($game['genres'] as $genre) {
                $genre_id = $genre['id'];
                $genre_name = $genre['name'];
                $stmt_genre->bind_param("is", $genre_id, $genre_name);
                $stmt_genre->execute();
                $stmt_game_genre->bind_param("ii", $game_id, $genre_id);
                $stmt_game_genre->execute();
            }
        }

        // Process Platforms (Normalization - Req #2)
        if (isset($game['platforms'])) {
            foreach ($game['platforms'] as $plat) {
                $platform_id = $plat['platform']['id'];
                $platform_name = $plat['platform']['name'];
                $stmt_platform->bind_param("is", $platform_id, $platform_name);
                $stmt_platform->execute();
                $stmt_game_platform->bind_param("ii", $game_id, $platform_id);
                $stmt_game_platform->execute();
            }
        }
        $games_processed++;
    }

    echo "Saved 40. Total this session: $games_processed\n";
    
    // Update URL for next iteration
    $api_url = $data['next'] ?? null;
    $current_page++;
    
    // Small pause to keep connection stable
    usleep(250000); // 0.25s
}

echo "\nMass Import Finished. Total Games Added/Updated: $games_processed\n";
?>