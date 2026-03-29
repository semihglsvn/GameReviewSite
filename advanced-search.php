<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - GameJoint</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/search.css">
</head>
<body>

    <header>
        <div class="container">
            <div class="row header-content">
                <div class="col-2">
                    <!-- Added h1 wrapper to match index.html height -->
                    <h1>
                        <a href="index.html" class="logo-link">
                            <img src="assets/images/logo.svg" alt="GameJoint Logo" class="site-logo">
                        </a>
                    </h1>
                </div>
                <div class="col-10">
                    <nav>
                        <ul class="nav-left">
                            <li><a href="games.html">Games</a></li>
                        </ul>
                        <div class="search-container">
                            <form action="advanced-search.html">
                                <input type="text" placeholder="Search games..." name="search">
                                <button type="submit">Search</button>
                            </form>
                        </div>
                        <ul class="nav-right">
                            <li><a href="login.html" class="btn-login">Login</a></li>
                            <li><a href="register.html" class="btn-register">Register</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container main-content">
        
        <div class="row section-header">
            <div class="col-12">
                <h1>Game Finder</h1>
                <p>Select your filters and click Search to find your next favorite game.</p>
            </div>
        </div>

        <div class="row">
            
            <!-- LEFT SIDEBAR: EXPANDED FILTERS -->
            <div class="col-3">
                <div class="filter-sidebar">
                    
                    <button id="main-search-btn" style="width:100%; margin-bottom:20px; padding:12px; background:#27ae60; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:bold; font-size:16px;">SEARCH GAMES</button>

                    <!-- ADDED SORTING OPTIONS -->
                    <h3>Sort By</h3>
                    <div class="filter-group">
                        <select id="sort-select" style="width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                            <option value="rating-desc">Highest Rated</option>
                            <option value="rating-asc">Lowest Rated</option>
                            <option value="date-desc">Newest First</option>
                            <option value="date-asc">Oldest First</option>
                        </select>
                    </div>

                    <h3>Genre</h3>
                    <div class="filter-group">
                        <label><input type="checkbox" class="filter-checkbox" value="rpg" data-filter-type="genre"> RPG</label>
                        <label><input type="checkbox" class="filter-checkbox" value="action" data-filter-type="genre"> Action</label>
                        <label><input type="checkbox" class="filter-checkbox" value="adventure" data-filter-type="genre"> Adventure</label>
                        <label><input type="checkbox" class="filter-checkbox" value="strategy" data-filter-type="genre"> Strategy</label>
                        <label><input type="checkbox" class="filter-checkbox" value="shooter" data-filter-type="genre"> Shooter (FPS/TPS)</label>
                        <label><input type="checkbox" class="filter-checkbox" value="simulation" data-filter-type="genre"> Simulation</label>
                        <label><input type="checkbox" class="filter-checkbox" value="sports" data-filter-type="genre"> Sports</label>
                        <label><input type="checkbox" class="filter-checkbox" value="racing" data-filter-type="genre"> Racing</label>
                        <label><input type="checkbox" class="filter-checkbox" value="horror" data-filter-type="genre"> Horror</label>
                        <label><input type="checkbox" class="filter-checkbox" value="puzzle" data-filter-type="genre"> Puzzle</label>
                        <label><input type="checkbox" class="filter-checkbox" value="fighting" data-filter-type="genre"> Fighting</label>
                        <label><input type="checkbox" class="filter-checkbox" value="platformer" data-filter-type="genre"> Platformer</label>
                        <label><input type="checkbox" class="filter-checkbox" value="mmo" data-filter-type="genre"> MMO</label>
                    </div>

                    <h3>Platform</h3>
                    <div class="filter-group">
                        <label><input type="checkbox" class="filter-checkbox" value="pc" data-filter-type="platform"> PC</label>
                        <label><input type="checkbox" class="filter-checkbox" value="ps5" data-filter-type="platform"> PlayStation 5</label>
                        <label><input type="checkbox" class="filter-checkbox" value="ps4" data-filter-type="platform"> PlayStation 4</label>
                        <label><input type="checkbox" class="filter-checkbox" value="xbox-series" data-filter-type="platform"> Xbox Series X/S</label>
                        <label><input type="checkbox" class="filter-checkbox" value="xbox-one" data-filter-type="platform"> Xbox One</label>
                        <label><input type="checkbox" class="filter-checkbox" value="switch" data-filter-type="platform"> Nintendo Switch</label>
                        <label><input type="checkbox" class="filter-checkbox" value="mobile" data-filter-type="platform"> Mobile (iOS/Android)</label>
                    </div>

                    <h3>Player Count</h3>
                    <div class="filter-group">
                        <label><input type="checkbox" class="filter-checkbox" value="single" data-filter-type="players"> Singleplayer</label>
                        <label><input type="checkbox" class="filter-checkbox" value="multi" data-filter-type="players"> Multiplayer</label>
                        <label><input type="checkbox" class="filter-checkbox" value="coop" data-filter-type="players"> Co-op</label>
                    </div>

                    <h3>Features</h3>
                    <div class="filter-group">
                        <label><input type="checkbox" class="filter-checkbox" value="open-world" data-filter-type="feature"> Open World</label>
                        <label><input type="checkbox" class="filter-checkbox" value="story-rich" data-filter-type="feature"> Story Rich</label>
                        <label><input type="checkbox" class="filter-checkbox" value="vr" data-filter-type="feature"> VR Supported</label>
                        <label><input type="checkbox" class="filter-checkbox" value="indie" data-filter-type="feature"> Indie</label>
                    </div>
                    
                    <button id="clear-filters" style="width:100%; margin-top:15px; padding:8px; background:#e74c3c; color:white; border:none; border-radius:4px; cursor:pointer;">Clear Filters</button>
                </div>
            </div>

            <!-- RIGHT CONTENT: GAME GRID -->
            <div class="col-9">
                
                <!-- Initial State Message -->
                <div id="initial-search-message" style="text-align:center; padding:50px; background:white; border-radius:8px; border:1px solid #ddd;">
                    <h2 style="color:#2c3e50;">Ready to find a game?</h2>
                    <p style="color:#7f8c8d;">Select genres, platforms, or features from the sidebar and click <strong>SEARCH GAMES</strong> to see results.</p>
                </div>

                <!-- No Results Message (Hidden) -->
                <div id="no-results" style="display:none; text-align:center; padding:50px; background:white; border-radius:8px; border:1px solid #ddd;">
                    <h3 style="color:#e74c3c;">No matching games found.</h3>
                    <p>Try unchecking some filters to broaden your search.</p>
                </div>

                <!-- GRID (All items hidden by default via JS) -->
                <div class="row" id="games-grid" style="display:none;">
                    
                    <!-- Game 1: BG3 -->
                    <div class="col-4 game-item" data-genre="rpg adventure strategy" data-platform="pc ps5 xbox-series" data-players="single coop" data-feature="story-rich open-world" data-rating="96" data-date="2023-08-03">
                        <a href="game-details.html" style="text-decoration:none; color:inherit;">
                            <div class="game-card">
                                <img src="assets/images/baldursgate3.png" alt="BG3" class="card-img">
                                <div class="card-content">
                                    <h3>Baldur's Gate 3</h3>
                                    <p class="platform-tag">PC, PS5</p>
                                    <div class="meta-footer"><span>Score</span><div class="metascore">96</div></div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Game 2: Elden Ring -->
                    <div class="col-4 game-item" data-genre="rpg action adventure" data-platform="pc ps5 ps4 xbox-series xbox-one" data-players="single multi" data-feature="open-world story-rich" data-rating="96" data-date="2022-02-25">
                        <div class="game-card">
                            <div class="card-image-placeholder">Elden</div>
                            <div class="card-content">
                                <h3>Elden Ring</h3>
                                <p class="platform-tag">Multi</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">96</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game 3: Civ 6 -->
                    <div class="col-4 game-item" data-genre="strategy simulation" data-platform="pc ps4 xbox-one switch mobile" data-players="single multi" data-feature="" data-rating="88" data-date="2016-10-21">
                        <div class="game-card">
                            <div class="card-image-placeholder">Civ6</div>
                            <div class="card-content">
                                <h3>Civilization VI</h3>
                                <p class="platform-tag">Multi</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">88</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game 4: Halo Infinite -->
                    <div class="col-4 game-item" data-genre="shooter action" data-platform="xbox-series xbox-one pc" data-players="single multi coop" data-feature="open-world" data-rating="87" data-date="2021-12-08">
                        <div class="game-card">
                            <div class="card-image-placeholder">Halo</div>
                            <div class="card-content">
                                <h3>Halo Infinite</h3>
                                <p class="platform-tag">Xbox, PC</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">87</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game 5: Hollow Knight -->
                    <div class="col-4 game-item" data-genre="action platformer adventure" data-platform="pc ps4 xbox-one switch" data-players="single" data-feature="indie story-rich" data-rating="90" data-date="2017-02-24">
                        <div class="game-card">
                            <div class="card-image-placeholder">Hollow</div>
                            <div class="card-content">
                                <h3>Hollow Knight</h3>
                                <p class="platform-tag">Multi</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">90</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game 6: Forza Horizon 5 -->
                    <div class="col-4 game-item" data-genre="racing simulation sports" data-platform="xbox-series xbox-one pc" data-players="single multi" data-feature="open-world" data-rating="92" data-date="2021-11-09">
                        <div class="game-card">
                            <div class="card-image-placeholder">Forza</div>
                            <div class="card-content">
                                <h3>Forza Horizon 5</h3>
                                <p class="platform-tag">Xbox, PC</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">92</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game 7: Resident Evil 4 -->
                    <div class="col-4 game-item" data-genre="horror action adventure" data-platform="pc ps5 ps4 xbox-series" data-players="single" data-feature="story-rich" data-rating="93" data-date="2023-03-24">
                        <div class="game-card">
                            <div class="card-image-placeholder">RE4</div>
                            <div class="card-content">
                                <h3>Resident Evil 4</h3>
                                <p class="platform-tag">Multi</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">93</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game 8: It Takes Two -->
                    <div class="col-4 game-item" data-genre="adventure platformer" data-platform="pc ps5 ps4 xbox-series xbox-one switch" data-players="coop" data-feature="story-rich indie" data-rating="88" data-date="2021-03-26">
                        <div class="game-card">
                            <div class="card-image-placeholder">Takes2</div>
                            <div class="card-content">
                                <h3>It Takes Two</h3>
                                <p class="platform-tag">Multi</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">88</div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Game 9: Stardew Valley -->
                    <div class="col-4 game-item" data-genre="simulation rpg" data-platform="pc ps4 xbox-one switch mobile" data-players="single coop" data-feature="indie" data-rating="89" data-date="2016-02-26">
                        <div class="game-card">
                            <div class="card-image-placeholder">Stardew</div>
                            <div class="card-content">
                                <h3>Stardew Valley</h3>
                                <p class="platform-tag">Multi</p>
                                <div class="meta-footer"><span>Score</span><div class="metascore">89</div></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <footer>
        <div class="container">
            <p>&copy; 2024 GameJoint.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>

</body>
</html>