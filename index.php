<?php 
require_once 'includes/header.php'; 

// ==========================================
// HELPER FUNCTION: PAD ARRAYS WITH EMPTY CARDS
// ==========================================
// Ensures that if admins put 7 games, it adds 1 empty card. 
// If it's completely empty, it generates $min_cards empty slots to show the layout.
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
// 1. FEATURED GAMES (Admin Selected)
// ==========================================
// Currently uses WHERE 1=0 so it's empty by default.
// Later, connect this to your admin panel (e.g., WHERE is_featured = 1)
$featured_query = "
    SELECT g.*, GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names 
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE 1=0 
    GROUP BY g.id 
    LIMIT 8
";
// Pad this section so it always shows the empty placeholder slots
$featured_games = padGamesArray($conn->query($featured_query)->fetch_all(MYSQLI_ASSOC));

// ==========================================
// 2. POPULAR: TRENDING
// ==========================================
// Uses a LEFT JOIN so it still shows games even if 0 reviews exist yet!
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
// We DO NOT pad this section. Show exactly what exists.
$popular_games = $conn->query($popular_query)->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 3. NEW RELEASES
// ==========================================
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
// We DO NOT pad this section.
$new_releases = $conn->query($new_releases_query)->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 4. TOP RATED GAMES (All Time)
// ==========================================
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
// We DO NOT pad this section.
$top_rated_games = $conn->query($top_rated_query)->fetch_all(MYSQLI_ASSOC);

// Quick array to loop through all 4 sections easily in HTML
$sections = [
    ['title' => 'Featured Games', 'data' => $featured_games],
    ['title' => 'Trending', 'data' => $popular_games],
    ['title' => 'New Releases', 'data' => $new_releases],
    ['title' => 'Top Rated Games', 'data' => $top_rated_games]
];
?>

    <!-- INTERNAL STYLES FOR RESPONSIVE SLIDERS -->
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
            width: 100%; /* Default Mobile: 1 card per row (stacked) */
            margin-bottom: 20px;
        }
        @media (min-width: 768px) {
            .responsive-col { width: 50%; } /* Tablet: 2 cards per row */
        }
        @media (min-width: 1024px) {
            .responsive-col { width: 25%; } /* Desktop: 4 cards perfectly aligned */
        }
    </style>

    <!-- MAIN LAYOUT CONTAINER -->
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
                                <!-- A single slide -->
                                <div class="responsive-slide slide">
                                    <div class="responsive-row">
                                        
                                        <?php foreach ($slide_games as $game): ?>
                                            <!-- Uses our new responsive class instead of rigid inline widths -->
                                            <div class="responsive-col">
                                                
                                                <?php if (isset($game['is_empty'])): ?>
                                                    <!-- RENDER EMPTY CARD (For Admin padding) -->
                                                    <div class="game-card" style="opacity: 0.6; height: 100%;">
                                                        <div class="card-image-placeholder" style="width: 100%; height: 180px; background: #e0e0e0; display: flex; align-items: center; justify-content: center; border-radius: 8px 8px 0 0;">
                                                            <span style="color: #666; font-weight: bold;">Empty Slot</span>
                                                        </div>
                                                        <div class="card-content">
                                                            <h3 style="color: #888;">Empty Slot</h3>
                                                            
                                                            <!-- Hidden Platform Wrapper for spacing consistency -->
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
                                                    <!-- RENDER REAL GAME CARD -->
                                                    <a href="game-details.php?id=<?php echo $game['id']; ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                                                        <div class="game-card" style="height: 100%; display: flex; flex-direction: column;">
                                                            <!-- PERFECT CROPPING: object-fit cover ensures images never stretch -->
                                                            <img src="<?php echo htmlspecialchars($game['cover_image']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>" style="width: 100%; height: 180px; object-fit: cover; display: block; border-radius: 8px 8px 0 0;">
                                                            
                                                            <div class="card-content" style="flex-grow: 1; display: flex; flex-direction: column; overflow: hidden;">
                                                                <h3 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($game['title']); ?>">
                                                                    <?php echo htmlspecialchars($game['title']); ?>
                                                                </h3>
                                                                
                                                                <!-- PLATFORM LOGIC: Splits array and renders individual boxes smoothly -->
                                                                <div style="display: flex; gap: 6px; flex-wrap: nowrap; overflow: hidden; margin-bottom: 10px;" title="<?php echo !empty($game['platform_names']) ? htmlspecialchars($game['platform_names']) : 'Multiple Platforms'; ?>">
                                                                    <?php 
                                                                    if (!empty($game['platform_names'])) {
                                                                        $platforms = explode(', ', $game['platform_names']);
                                                                        foreach ($platforms as $platform) {
                                                                            // Render individual platform tags
                                                                            echo '<span class="platform-tag" style="display: inline-block; white-space: nowrap;">' . htmlspecialchars($platform) . '</span>';
                                                                        }
                                                                    } else {
                                                                        echo '<span class="platform-tag" style="display: inline-block; white-space: nowrap;">N/A</span>';
                                                                    }
                                                                    ?>
                                                                </div>

                                                                <!-- JS COLOR SCRIPT WILL TARGET THIS DIV -->
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

<?php require_once 'includes/footer.php'; ?>