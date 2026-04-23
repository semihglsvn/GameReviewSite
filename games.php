<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// ==========================================
// 1. DYNAMIC GENRE FETCHING
// ==========================================
$genres_query = "
    SELECT g.id, g.name, COUNT(gg.game_id) as game_count
    FROM genres g
    JOIN game_genres gg ON g.id = gg.genre_id
    GROUP BY g.id, g.name
    HAVING game_count >= 4
    ORDER BY g.name ASC
";
$genres = $conn->query($genres_query)->fetch_all(MYSQLI_ASSOC);

$slider_sections = [];
foreach ($genres as $g) { 
    $slider_sections[] = ['type' => 'genre', 'id' => $g['id'], 'title' => $g['name'] . ' Games']; 
}

function padInitialArray($games) {
    $count = count($games);
    if ($count == 0) return array_fill(0, 4, ['is_empty' => true]);
    $rem = $count % 4;
    if ($rem > 0) {
        for ($i = 0; $i < (4 - $rem); $i++) { $games[] = ['is_empty' => true]; }
    }
    return $games;
}
?>

<style>
    .responsive-slider-track { display: flex; transition: transform 0.4s ease-in-out; }
    .responsive-slide { flex: 0 0 100%; max-width: 100%; }
    .responsive-row { display: flex; flex-wrap: wrap; margin: 0 -10px; }
    .responsive-col { box-sizing: border-box; padding: 0 10px; width: 100%; margin-bottom: 20px; }
    @media (min-width: 768px) { .responsive-col { width: 50%; } }
    @media (min-width: 1024px) { .responsive-col { width: 25%; } }
</style>

<div class="container main-content">
    
    <div class="row section-header">
        <div class="col-12">
            <h2 style="margin-top:0;">Browse by Genre</h2>
            <p>Explore our extensive database of games across all categories.</p>
        </div>
    </div>

    <?php foreach ($slider_sections as $section): ?>
        <?php
        $stmt = $conn->prepare("
            SELECT g.id, g.title, g.cover_image, g.metascore, 
                   GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names 
            FROM games g 
            JOIN game_genres gg ON g.id = gg.game_id 
            LEFT JOIN game_platforms gp ON g.id = gp.game_id 
            LEFT JOIN platforms p ON gp.platform_id = p.id 
            WHERE gg.genre_id = ? 
            GROUP BY g.id 
            ORDER BY g.metascore DESC, g.id DESC 
            LIMIT 12
        ");
        $stmt->bind_param("i", $section['id']);
        $stmt->execute();
        $games = padInitialArray($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        
        if (count($games) === 4 && isset($games[0]['is_empty'])) continue; 
        
        $slides = array_chunk($games, 4);
        ?>

        <div class="infinite-slider-wrapper" style="margin-bottom: 40px; position:relative; overflow:hidden;">
            <div class="row section-header">
                <div class="col-12 header-flex" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                    <div class="slider-controls">
                        <button class="custom-prev-btn" disabled style="background:#333; color:white; border:none; padding:5px 12px; cursor:pointer; border-radius:4px; font-size:16px;">&#10094;</button>
                        <button class="custom-next-btn" style="background:#333; color:white; border:none; padding:5px 12px; cursor:pointer; border-radius:4px; font-size:16px; margin-left:5px;">&#10095;</button>
                    </div>
                </div>
            </div>

            <div class="infinite-container" style="overflow: hidden;">
                <div class="responsive-slider-track dynamic-track" 
                     data-type="<?php echo $section['type']; ?>" 
                     data-id="<?php echo $section['id']; ?>" 
                     data-page="1" 
                     data-has-more="true" 
                     data-loading="false"
                     data-current-slide="0"
                     style="transform: translateX(0%);">
                    
                    <?php foreach ($slides as $slide_games): ?>
                        <div class="responsive-slide slide">
                            <div class="responsive-row">
                                <?php foreach ($slide_games as $game): ?>
                                    <div class="responsive-col">
                                        <?php if (isset($game['is_empty'])): ?>
                                            <div class="game-card" style="opacity:0; pointer-events:none; height:100%;"></div>
                                        <?php else: ?>
                                            <a href="game-details.php?id=<?php echo $game['id']; ?>" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                                                <div class="game-card" style="height: 100%; display: flex; flex-direction: column;">
                                                    
                                                    <img src="<?php echo htmlspecialchars($game['cover_image']); ?>" onerror="this.src='https://placehold.co/400x300/2a2a2a/888888?text=No+Cover'" style="width: 100%; height: 180px; object-fit: cover; display: block; border-radius: 8px 8px 0 0;">
                                                    
                                                    <div class="card-content" style="flex-grow: 1; display: flex; flex-direction: column; overflow: hidden;">
                                                        <h3 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($game['title']); ?>">
                                                            <?php echo htmlspecialchars($game['title']); ?>
                                                        </h3>
                                                        <div style="display: flex; gap: 6px; flex-wrap: nowrap; overflow: hidden; margin-bottom: 10px;">
                                                            <span class="platform-tag" style="white-space: nowrap;">
                                                                <?php echo !empty($game['platform_names']) ? htmlspecialchars($game['platform_names']) : 'N/A'; ?>
                                                            </span>
                                                        </div>
                                                        <div class="meta-footer" style="margin-top: auto; flex-shrink: 0;">
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
        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">
    <?php endforeach; ?>

</div>
<script>
document.addEventListener("DOMContentLoaded", function() {

    function buildSlideHTML(slideGames) {
        let html = '<div class="responsive-slide slide"><div class="responsive-row">';
        slideGames.forEach(game => {
            html += '<div class="responsive-col">';
            if (game.is_empty) {
                html += '<div class="game-card" style="opacity:0; pointer-events:none; height:100%;"></div>';
            } else {
                let scoreVal = parseInt(game.metascore);
                let score = (!isNaN(scoreVal) && scoreVal > 0) ? scoreVal : 'tbd';
                let platforms = game.platform_names ? game.platform_names : 'N/A';
                let safeTitle = game.title ? game.title.replace(/"/g, '&quot;') : '';

                // =========================================================
                // THE FIX: Use your official CSS classes instead of inline styles!
                // =========================================================
                let scoreClass = 'score-none'; // Default grey for TBD
                if (score !== 'tbd') {
                    if (scoreVal >= 90) {
                        scoreClass = 'score-dark-green';
                    } else if (scoreVal >= 75) {
                        scoreClass = 'score-green';
                    } else if (scoreVal >= 50) {
                        scoreClass = 'score-yellow';
                    } else {
                        scoreClass = 'score-red';
                    }
                }

                html += `
                    <a href="game-details.php?id=${game.id}" style="text-decoration: none; color: inherit; display: block; height: 100%;">
                        <div class="game-card" style="height: 100%; display: flex; flex-direction: column;">
                            <img src="${game.cover_image}" onerror="this.src='https://placehold.co/400x300/2a2a2a/888888?text=No+Cover'" style="width: 100%; height: 180px; object-fit: cover; display: block; border-radius: 8px 8px 0 0;">
                            
                            <div class="card-content" style="flex-grow: 1; display: flex; flex-direction: column; overflow: hidden;">
                                <h3 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${safeTitle}">${game.title}</h3>
                                <div style="display: flex; gap: 6px; flex-wrap: nowrap; overflow: hidden; margin-bottom: 10px;">
                                    <span class="platform-tag" style="white-space: nowrap;">${platforms}</span>
                                </div>
                                <div class="meta-footer" style="margin-top: auto; flex-shrink: 0;">
                                    <span>Metascore</span>
                                    <div class="metascore ${scoreClass}">${score}</div>
                                </div>
                            </div>
                        </div>
                    </a>`;
            }
            html += '</div>';
        });
        html += '</div></div>';
        return html;
    }

    // ... (The rest of your slider wrapper code stays exactly the same) ...
    document.querySelectorAll('.infinite-slider-wrapper').forEach(wrapper => {
        const track = wrapper.querySelector('.dynamic-track');
        const prevBtn = wrapper.querySelector('.custom-prev-btn');
        const nextBtn = wrapper.querySelector('.custom-next-btn');
        
        function updateSliderPosition() {
            let currentSlide = parseInt(track.dataset.currentSlide);
            let totalSlides = track.querySelectorAll('.slide').length;
            
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            prevBtn.disabled = currentSlide === 0;
            
            if (currentSlide === totalSlides - 1 && track.dataset.hasMore === 'false') {
                nextBtn.disabled = true;
                nextBtn.style.opacity = '0.5';
                nextBtn.style.cursor = 'not-allowed';
            } else {
                nextBtn.disabled = false;
                nextBtn.style.opacity = '1';
                nextBtn.style.cursor = 'pointer';
            }
        }

        prevBtn.addEventListener('click', () => {
            let currentSlide = parseInt(track.dataset.currentSlide);
            if (currentSlide > 0) {
                track.dataset.currentSlide = currentSlide - 1;
                updateSliderPosition();
            }
        });

        nextBtn.addEventListener('click', () => {
            let currentSlide = parseInt(track.dataset.currentSlide);
            let totalSlides = track.querySelectorAll('.slide').length;
            
            if (currentSlide === totalSlides - 2 && track.dataset.hasMore === 'true' && track.dataset.loading === 'false') {
                track.dataset.loading = 'true';
                let page = parseInt(track.dataset.page) + 1;
                
                let fetchUrl = `api/ajax_load_slider.php?type=${track.dataset.type}&id=${track.dataset.id}&page=${page}&t=${Date.now()}`;
                
                fetch(fetchUrl)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.slides.length > 0) {
                            data.slides.forEach(slideArray => {
                                track.insertAdjacentHTML('beforeend', buildSlideHTML(slideArray));
                            });
                            track.dataset.page = page;
                            track.dataset.hasMore = data.has_more ? 'true' : 'false';
                        } else {
                            track.dataset.hasMore = 'false';
                        }
                        track.dataset.loading = 'false';
                        updateSliderPosition(); 
                    })
                    .catch(err => {
                        console.error("Slider Fetch Error:", err);
                        track.dataset.loading = 'false';
                    });
            }

            if (currentSlide < totalSlides - 1) {
                track.dataset.currentSlide = currentSlide + 1;
                updateSliderPosition();
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>