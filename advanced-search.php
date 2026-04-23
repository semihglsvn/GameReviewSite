<?php
require_once 'includes/header.php';
require_once 'config/db.php';

// Fetch dynamic filters from database
$genres = $conn->query("SELECT id, name FROM genres ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
$platforms = $conn->query("SELECT id, name FROM platforms ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container main-content">
    
    <div class="row section-header">
        <div class="col-12">
            <h1>Game Finder</h1>
            <p>Select your filters and click Search to find your next favorite game.</p>
        </div>
    </div>

    <div class="row">
        
        <div class="col-3">
            <div class="filter-sidebar" style="background: white; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                
                <button id="main-search-btn" style="width:100%; margin-bottom:20px; padding:12px; background:#27ae60; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold; font-size:16px;">SEARCH GAMES</button>

                <h3 style="margin-top:0;">Title Search</h3>
                <div class="filter-group" style="margin-bottom: 20px;">
                    <input type="text" id="search-text" placeholder="Type a game name..." style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box;">
                </div>

                <h3>Score Filter</h3>
                <div class="filter-group" style="margin-bottom: 20px; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                    <label style="display:flex; align-items:center; cursor:pointer;">
                        <input type="checkbox" id="hide-tbd-checkbox" style="margin-right: 8px;"> 
                        <strong>Hide TBD (No Metascore)</strong>
                    </label>
                </div>

                <h3>Filter Logic</h3>
                <div class="filter-group" style="margin-bottom: 20px; background: #f8f9fa; padding: 10px; border-radius: 4px;">
                    <label style="display:block; margin-bottom:5px; cursor:pointer;"><input type="radio" name="filter_logic" value="OR" checked> <strong>Match ANY (OR)</strong><br><small style="color:#7f8c8d; font-weight:normal;">Finds games that have at least one of your selected tags.</small></label>
                    <label style="display:block; cursor:pointer;"><input type="radio" name="filter_logic" value="AND"> <strong>Match ALL (AND)</strong><br><small style="color:#7f8c8d; font-weight:normal;">Strict. Finds games that match every single tag selected.</small></label>
                </div>

                <h3>Sort By</h3>
                <div class="filter-group" style="margin-bottom: 20px;">
                    <select id="sort-select" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="rating-desc">Highest Rated</option>
                        <option value="rating-asc">Lowest Rated</option>
                        <option value="date-desc">Newest First</option>
                        <option value="date-asc">Oldest First</option>
                    </select>
                </div>

                <h3>Genre</h3>
                <div class="filter-group" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; max-height: 250px; overflow-y: auto; overflow-x: hidden; padding-right: 10px;">
                    <?php foreach ($genres as $genre): ?>
                        <div style="margin-bottom: 2px;">
                            <label style="display: flex; align-items: center; margin: 0; cursor: pointer; font-size: 14px; width: 100%;">
                                <input type="checkbox" class="filter-checkbox" value="<?php echo $genre['id']; ?>" data-type="genre" style="margin: 0 8px 0 0;"> 
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3>Platform</h3>
                <div class="filter-group" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; max-height: 250px; overflow-y: auto; overflow-x: hidden; padding-right: 10px;">
                    <?php foreach ($platforms as $platform): ?>
                        <div style="margin-bottom: 2px;">
                            <label style="display: flex; align-items: center; margin: 0; cursor: pointer; font-size: 14px; width: 100%;">
                                <input type="checkbox" class="filter-checkbox" value="<?php echo $platform['id']; ?>" data-type="platform" style="margin: 0 8px 0 0;"> 
                                <?php echo htmlspecialchars($platform['name']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button id="clear-filters" style="width:100%; margin-top:15px; padding:8px; background:#e74c3c; color:white; border:none; border-radius:4px; cursor:pointer;">Clear Filters</button>
            </div>
        </div>

        <div class="col-9">
            
            <div id="initial-search-message" style="text-align:center; padding:50px; background:white; border-radius:8px; border:1px solid #ddd;">
                <h2 style="color:#2c3e50;">Ready to find a game?</h2>
                <p style="color:#7f8c8d;">Select filters from the sidebar and click <strong>SEARCH GAMES</strong> to explore the database.</p>
            </div>

            <div id="no-results" style="display:none; text-align:center; padding:50px; background:white; border-radius:8px; border:1px solid #ddd;">
                <h3 style="color:#e74c3c;">No matching games found.</h3>
                <p>Try switching the Filter Logic to "Match ANY (OR)", or clear some checkboxes.</p>
            </div>

            <div id="loading-message" style="display:none; text-align:center; padding:50px; background:white; border-radius:8px; border:1px solid #ddd;">
                <h2 style="color:#3498db;">Searching Database...</h2>
                <p style="color:#7f8c8d;">Please wait while we gather your games.</p>
            </div>

            <div class="row" id="games-grid" style="display:none; flex-wrap: wrap;"></div>
            
            <div class="text-center" style="margin-top: 30px; display:none; width: 100%;" id="load-more-container">
                <button id="load-more-btn" style="padding: 10px 30px; background: #34495e; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Load More Results</button>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    
    let currentPage = 1;
    let isSearching = false;

    const searchBtn = document.getElementById('main-search-btn');
    const clearBtn = document.getElementById('clear-filters');
    const loadMoreBtn = document.getElementById('load-more-btn');
    const grid = document.getElementById('games-grid');
    const loadMoreContainer = document.getElementById('load-more-container');
    const searchInput = document.getElementById('search-text');

    // STRICT UI STATE CONTROLLER: Prevents elements from overlapping
// ==========================================
    // THE NUCLEAR FIX: Strict UI State Controller
    // Immune to duplicate HTML tags and CSS conflicts
    // ==========================================
    function setUIState(state) {
        // 1. Forcefully hide EVERY possible message box on the page first
        document.querySelectorAll('#initial-search-message, #loading-message, #no-results').forEach(el => {
            el.style.display = 'none';
        });
        
        // 2. Safely turn on ONLY the specific message we want
        if (state === 'initial') {
            document.querySelectorAll('#initial-search-message').forEach(el => el.style.display = 'block');
        } else if (state === 'loading') {
            document.querySelectorAll('#loading-message').forEach(el => el.style.display = 'block');
        } else if (state === 'empty') {
            document.querySelectorAll('#no-results').forEach(el => el.style.display = 'block');
        }
        
        // 3. Handle the Grid and Load More button
        document.querySelectorAll('#games-grid').forEach(el => {
            el.style.display = (state === 'grid') ? 'flex' : 'none';
        });
        
        if (state !== 'grid') {
            document.querySelectorAll('#load-more-container').forEach(el => {
                el.style.display = 'none';
            });
        }
    }
    // AUTO-SEARCH FROM URL PARAMETERS
    const urlParams = new URLSearchParams(window.location.search);
    const initialSearchQuery = urlParams.get('search');
    
    if (initialSearchQuery) {
        searchInput.value = initialSearchQuery;
        setTimeout(() => executeSearch(1), 100);
    }

    function getScoreClass(scoreVal) {
        if (isNaN(scoreVal) || scoreVal <= 0) return 'score-none';
        if (scoreVal >= 90) return 'score-dark-green';
        if (scoreVal >= 75) return 'score-green';
        if (scoreVal >= 50) return 'score-yellow';
        return 'score-red';
    }

    function buildGameCard(game) {
        let scoreVal = parseInt(game.metascore);
        let score = (!isNaN(scoreVal) && scoreVal > 0) ? scoreVal : 'tbd';
        let scoreClass = getScoreClass(scoreVal);
        let platforms = game.platform_names ? game.platform_names : 'N/A';
        let safeTitle = game.title ? game.title.replace(/"/g, '&quot;') : '';

        return `
            <div class="col-4" style="margin-bottom: 20px;">
                <a href="game-details.php?id=${game.id}" style="text-decoration:none; color:inherit; display:block; height:100%;">
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
                </a>
            </div>`;
    }

    function executeSearch(page = 1) {
        if (isSearching) return;
        isSearching = true;

        if (page === 1) {
            searchBtn.innerText = "SEARCHING...";
            grid.innerHTML = '';
            setUIState('loading'); // Safely lock to Loading
        } else {
            loadMoreBtn.innerText = "Loading...";
        }

        const searchText = searchInput.value.trim();
        const logic = document.querySelector('input[name="filter_logic"]:checked').value;
        const sort = document.getElementById('sort-select').value;
        const hideTbd = document.getElementById('hide-tbd-checkbox').checked;
        
        const genres = Array.from(document.querySelectorAll('.filter-checkbox[data-type="genre"]:checked')).map(cb => cb.value);
        const platforms = Array.from(document.querySelectorAll('.filter-checkbox[data-type="platform"]:checked')).map(cb => cb.value);

        const payload = {
            search_text: searchText,
            logic: logic,
            sort: sort,
            hide_tbd: hideTbd,
            genres: genres,
            platforms: platforms,
            page: page
        };

        fetch('api/ajax_advanced_search.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.games.length === 0 && page === 1) {
                    setUIState('empty'); // Safely lock to Empty
                } else {
                    setUIState('grid'); // Safely lock to Grid
                    
                    data.games.forEach(game => {
                        grid.insertAdjacentHTML('beforeend', buildGameCard(game));
                    });
                    
                    if (data.has_more) {
                        loadMoreContainer.style.display = 'block';
                    } else {
                        loadMoreContainer.style.display = 'none';
                    }
                }
            } else {
                setUIState('initial');
                alert("Search Error: " + data.error);
            }
        })
        .catch(err => {
            console.error("Search failed", err);
            setUIState('initial');
        })
        .finally(() => {
            isSearching = false;
            searchBtn.innerText = "SEARCH GAMES";
            loadMoreBtn.innerText = "Load More Results";
            currentPage = page;
        });
    }

    searchBtn.addEventListener('click', () => executeSearch(1));
    loadMoreBtn.addEventListener('click', () => executeSearch(currentPage + 1));

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        document.getElementById('hide-tbd-checkbox').checked = false;
        document.querySelectorAll('.filter-checkbox').forEach(cb => cb.checked = false);
        document.querySelector('input[name="filter_logic"][value="OR"]').checked = true;
        document.getElementById('sort-select').value = 'rating-desc';
        
        setUIState('initial'); // Safely return to start
        window.history.replaceState({}, document.title, window.location.pathname);
    });
    
    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') executeSearch(1);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>