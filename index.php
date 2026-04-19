<?php 
require_once 'includes/header.php'; 

// ==========================================
// 1. CACHING ENGINE (Stale-While-Revalidate)
// ==========================================
$needs_rebuild = false; // Flag to tell JS to ping our background worker

function getCachedData($cacheName, $minutes = 30) {
    global $needs_rebuild;
    $cacheFile = __DIR__ . '/cache/' . $cacheName . '.json';
    
    // If file doesn't exist at all (first time ever loading the site)
    if (!file_exists($cacheFile)) {
        $needs_rebuild = true;
        return []; // Return empty array temporarily so page doesn't crash
    }

    // Check if it's expired
    $cacheTime = $minutes * 60;
    if (time() - filemtime($cacheFile) >= $cacheTime) {
        $needs_rebuild = true; // It's old, flag it for background rebuild
    }

    // ALWAYS return the JSON instantly, even if it's technically expired
    return json_decode(file_get_contents($cacheFile), true);
}

// ==========================================
// 2. HELPER FUNCTION: PAD ARRAYS
// ==========================================
function padGamesArray($games, $multiple = 4, $min_cards = 4) {
    $count = count($games);
    if ($count == 0) {
        for ($i = 0; $i < $min_cards; $i++) {
            $games[] = ['is_empty' => true];
        }
        return $games;
    }
    $remainder = $count % $multiple;
    if ($remainder > 0) {
        $padAmount = $multiple - $remainder;
        for ($i = 0; $i < $padAmount; $i++) {
            $games[] = ['is_empty' => true];
        }
    }
    return $games;
}

// ==========================================
// 3. FETCH DATA (Real-time vs Cached)
// ==========================================

// A. FEATURED GAMES (Real-time, No Cache)
$featured_query = "
    SELECT g.*, fg.custom_banner, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names 
    FROM featured_games fg
    JOIN games g ON fg.game_id = g.id
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    GROUP BY g.id, fg.display_order, fg.custom_banner
    ORDER BY fg.display_order ASC 
    LIMIT 16
";
$featured_games = padGamesArray($conn->query($featured_query)->fetch_all(MYSQLI_ASSOC));

// B. CACHED SECTIONS (Instant JSON read)
$popular_games = padGamesArray(getCachedData('trending_games', 30));
$new_releases = padGamesArray(getCachedData('new_releases', 60));
$top_rated_games = padGamesArray(getCachedData('top_rated', 60));

// Quick array to loop through all 4 sections easily in HTML
$sections = [
    ['title' => 'Featured Games', 'data' => $featured_games],
    ['title' => 'Trending', 'data' => $popular_games],
    ['title' => 'New Releases', 'data' => $new_releases],
    ['title' => 'Top Rated Games', 'data' => $top_rated_games]
];
?>

<style>
    .responsive-slider-track {
        display: flex;
        transition: transform 0.3s ease-in-out;
    }
    .responsive-slide {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .responsive-row {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }
    .responsive-col {
        box-sizing: border-box;
        padding: 0 10px;
        width: 100%; /* Default Mobile: 1 card per row */
        margin-bottom: 20px;
    }
    @media (min-width: 768px) {
        .responsive-col { width: 50%; } /* Tablet: 2 cards per row */
    }
    @media (min-width: 1024px) {
        .responsive-col { width: 25%; } /* Desktop: 4 cards perfectly aligned */
    }
</style>

<div class="container main-content">
    
    <?php foreach ($sections as $section): ?>
        <?php if (count($section['data']) === 0) continue; ?>

        <div class="slider-section-wrapper" style="margin-bottom: 40px;">
            <div class="row section-header">
                <div class="col-12 header-flex" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php echo $section['title']; ?></h2>
                    <div class="slider-controls">
                        <button class="slider-nav-btn prev-btn">&#10094;</button>
                        <button class="slider-nav-btn next-btn">&#10095;</button>
                    </div>
                </div>
            </div>

            <div class="slider-container" style="overflow: hidden;">
                <div class="slider-wrapper">
                    <div class="responsive-slider-track slider-track">
                        
                        <?php 
                        // Split games into slides of 4
                        $slides = array_chunk($section['data'], 4);
                        foreach ($slides as $slide_games): 
                        ?>
                            <div class="responsive-slide slide">
                                <div class="responsive-row">
                                    
                                    <?php foreach ($slide_games as $game): ?>
                                        <div class="responsive-col">
                                            
                                            <?php if (isset($game['is_empty'])): ?>
                                                <div class="game-card" style="opacity: 0.6; height: 100%;">
                                                    <div class="card-image-placeholder" style="width: 100%; height: 180px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; border-radius: 8px 8px 0 0;">
                                                        <span style="color: #666; font-weight: bold;">Empty</span>
                                                    </div>
                                                    <div class="card-content">
                                                        <h3 style="color: #888;">Empty</h3>
                                                        <div style="display: flex; gap: 6px; margin-bottom: 10px; visibility: hidden;">
                                                            <span class="platform-tag" style="display: inline-block;">-</span>
                                                        </div>
                                                        <div class="meta-footer">
                                                            <span>Metascore</span>
                                                            <div class="metascore">--</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <a href="game-details.php?id=<?php echo $game['id']; ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                                                    <div class="game-card" style="height: 100%; display: flex; flex-direction: column;">
                                                        
                                                        <?php $display_img = !empty($game['custom_banner']) ? $game['custom_banner'] : $game['cover_image']; ?>
                                                        
                                                        <img src="<?php echo htmlspecialchars($display_img); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>" style="width: 100%; height: 180px; object-fit: cover; display: block; border-radius: 8px 8px 0 0;">
                                                        
                                                        <div class="card-content" style="flex-grow: 1; display: flex; flex-direction: column; overflow: hidden;">
                                                            <h3 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($game['title']); ?>">
                                                                <?php echo htmlspecialchars($game['title']); ?>
                                                            </h3>
                                                            
                                                            <div style="display: flex; gap: 6px; flex-wrap: nowrap; overflow: hidden; margin-bottom: 10px;" title="<?php echo !empty($game['platform_names']) ? htmlspecialchars($game['platform_names']) : 'Multiple Platforms'; ?>">
                                                                <?php 
                                                                if (!empty($game['platform_names'])) {
                                                                    $platforms = explode(', ', $game['platform_names']);
                                                                    foreach ($platforms as $platform) {
                                                                        echo '<span class="platform-tag" style="display: inline-block; white-space: nowrap;">' . htmlspecialchars($platform) . '</span>';
                                                                    }
                                                                } else {
                                                                    echo '<span class="platform-tag" style="display: inline-block; white-space: nowrap;">N/A</span>';
                                                                }
                                                                ?>
                                                            </div>

                                                            <div class="meta-footer" style="margin-top: auto;">
                                                                <span>Metascore</span>
                                                                <div class="metascore"><?php echo $game['metascore'] > 0 ? $game['metascore'] : 'tbd'; ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endif; ?>

                                        </div>
                                    <?php endforeach; ?>

                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
        </div>
        
        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">
    <?php endforeach; ?>

</div>

<?php if ($needs_rebuild): ?>
<script>
    // The page has already loaded for the user.
    // Ask the server to rebuild the cache for the NEXT user in the background.
    setTimeout(() => {
        fetch('api/rebuild_cache.php').catch(e => console.log("Background cache rebuild triggered."));
    }, 1500);
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>