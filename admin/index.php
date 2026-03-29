<?php 
require_once 'includes/header.php'; 

// ==========================================
// 1. FETCH RECENT RELEASES (Last 3 years, sorted by date)
// ==========================================
$recent_query = "
    SELECT g.*, GROUP_CONCAT(p.name SEPARATOR ', ') as platform_names 
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE g.release_date <= CURDATE() AND g.release_date >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
    GROUP BY g.id 
    ORDER BY g.release_date DESC 
    LIMIT 16
";
$recent_games = $conn->query($recent_query)->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 2. FETCH HIGHEST RATED (All time)
// ==========================================
$popular_query = "
    SELECT g.*, GROUP_CONCAT(p.name SEPARATOR ', ') as platform_names 
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE g.metascore > 0
    GROUP BY g.id 
    ORDER BY g.metascore DESC 
    LIMIT 16
";
$popular_games = $conn->query($popular_query)->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 3. FETCH TOP RPGs (Showcasing the normalized Genre relationship!)
// ==========================================
$rpg_query = "
    SELECT g.*, GROUP_CONCAT(p.name SEPARATOR ', ') as platform_names 
    FROM games g
    INNER JOIN game_genres gg ON g.id = gg.game_id
    INNER JOIN genres gen ON gg.genre_id = gen.id
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE gen.name = 'RPG' AND g.metascore > 0
    GROUP BY g.id 
    ORDER BY g.metascore DESC 
    LIMIT 16
";
$rpg_games = $conn->query($rpg_query)->fetch_all(MYSQLI_ASSOC);
?>

    <!-- MAIN LAYOUT CONTAINER -->
    <div class="container main-content">
        
        <!-- ===================================== -->
        <!-- SLIDER 1: BEST RECENT RELEASES        -->
        <!-- ===================================== -->
        <div class="slider-section-wrapper">
            <div class="row section-header">
                <div class="col-12 header-flex">
                    <h2>Best Recent Releases</h2>
                    <div class="slider-controls">
                        <button class="slider-nav-btn prev-btn">&#10094;</button>
                        <button class="slider-nav-btn next-btn">&#10095;</button>
                    </div>
                </div>
            </div>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <div class="slider-track">
                        <?php 
                        $slides = array_chunk($recent_games, 4);
                        foreach ($slides as $slide_games): 
                        ?>
                            <div class="slide">
                                <div class="row">
                                    <?php foreach ($slide_games as $game): ?>
                                        <div class="col-3">
                                            <a href="game-details.php?id=<?php echo $game['id']; ?>" class="card-link-wrapper">
                                                <div class="game-card">
                                                    <img src="<?php echo htmlspecialchars($game['cover_image']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>" class="card-img" style="width:100%; height:180px; object-fit:cover;">
                                                    <div class="card-content">
                                                        <h3 class="text-ellipsis" title="<?php echo htmlspecialchars($game['title']); ?>">
                                                            <?php echo htmlspecialchars($game['title']); ?>
                                                        </h3>
                                                        <p class="platform-tag text-ellipsis">
                                                            <?php echo !empty($game['platform_names']) ? htmlspecialchars($game['platform_names']) : 'Multiple Platforms'; ?>
                                                        </p>
                                                        <div class="meta-footer">
                                                            <span>Metascore</span>
                                                            <!-- JS WILL COLOR THIS BASED ON THE NUMBER -->
                                                            <div class="metascore"><?php echo $game['metascore'] > 0 ? $game['metascore'] : 'tbd'; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <hr class="section-divider">

        <!-- ===================================== -->
        <!-- SLIDER 2: HIGHEST RATED ALL TIME      -->
        <!-- ===================================== -->
        <div class="slider-section-wrapper">
            <div class="row section-header">
                <div class="col-12 header-flex">
                    <h2>Highest Rated All-Time</h2>
                    <div class="slider-controls">
                        <button class="slider-nav-btn prev-btn">&#10094;</button>
                        <button class="slider-nav-btn next-btn">&#10095;</button>
                    </div>
                </div>
            </div>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <div class="slider-track">
                        <?php 
                        $slides = array_chunk($popular_games, 4);
                        foreach ($slides as $slide_games): 
                        ?>
                            <div class="slide">
                                <div class="row">
                                    <?php foreach ($slide_games as $game): ?>
                                        <div class="col-3">
                                            <a href="game-details.php?id=<?php echo $game['id']; ?>" class="card-link-wrapper">
                                                <div class="game-card">
                                                    <img src="<?php echo htmlspecialchars($game['cover_image']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>" class="card-img" style="width:100%; height:180px; object-fit:cover;">
                                                    <div class="card-content">
                                                        <h3 class="text-ellipsis" title="<?php echo htmlspecialchars($game['title']); ?>">
                                                            <?php echo htmlspecialchars($game['title']); ?>
                                                        </h3>
                                                        <p class="platform-tag text-ellipsis">
                                                            <?php echo !empty($game['platform_names']) ? htmlspecialchars($game['platform_names']) : 'Multiple Platforms'; ?>
                                                        </p>
                                                        <div class="meta-footer">
                                                            <span>Metascore</span>
                                                            <div class="metascore"><?php echo $game['metascore']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <hr class="section-divider">

        <!-- ===================================== -->
        <!-- SLIDER 3: MUST-PLAY RPGs              -->
        <!-- ===================================== -->
        <div class="slider-section-wrapper">
            <div class="row section-header">
                <div class="col-12 header-flex">
                    <h2>Must-Play RPGs</h2>
                    <div class="slider-controls">
                        <button class="slider-nav-btn prev-btn">&#10094;</button>
                        <button class="slider-nav-btn next-btn">&#10095;</button>
                    </div>
                </div>
            </div>
            <div class="slider-container">
                <div class="slider-wrapper">
                    <div class="slider-track">
                        <?php 
                        $slides = array_chunk($rpg_games, 4);
                        foreach ($slides as $slide_games): 
                        ?>
                            <div class="slide">
                                <div class="row">
                                    <?php foreach ($slide_games as $game): ?>
                                        <div class="col-3">
                                            <a href="game-details.php?id=<?php echo $game['id']; ?>" class="card-link-wrapper">
                                                <div class="game-card">
                                                    <img src="<?php echo htmlspecialchars($game['cover_image']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>" class="card-img" style="width:100%; height:180px; object-fit:cover;">
                                                    <div class="card-content">
                                                        <h3 class="text-ellipsis" title="<?php echo htmlspecialchars($game['title']); ?>">
                                                            <?php echo htmlspecialchars($game['title']); ?>
                                                        </h3>
                                                        <p class="platform-tag text-ellipsis">
                                                            <?php echo !empty($game['platform_names']) ? htmlspecialchars($game['platform_names']) : 'Multiple Platforms'; ?>
                                                        </p>
                                                        <div class="meta-footer">
                                                            <span>Metascore</span>
                                                            <div class="metascore"><?php echo $game['metascore']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php require_once 'includes/footer.php'; ?>