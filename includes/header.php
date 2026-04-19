<?php
// Start the session to check if the user is logged in (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include database connection safely
require_once __DIR__ . '/../config/db.php';

// ==========================================
// FETCH GLOBAL SITE SETTINGS
// ==========================================
$settings_query = $conn->query("SELECT * FROM settings WHERE id = 1");
$site_settings = $settings_query->fetch_assoc();

// Set safe fallbacks just in case the database is empty
$site_title = !empty($site_settings['site_title']) ? $site_settings['site_title'] : 'GameJoint';
$site_logo = !empty($site_settings['site_logo']) ? $site_settings['site_logo'] : 'assets/images/logo.svg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo htmlspecialchars($site_title); ?> - Homepage</title>
    <?php if (!empty($site_settings['site_description'])): ?>
        <meta name="description" content="<?php echo htmlspecialchars($site_settings['site_description']); ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* Ensure parent containers do not clip the absolute dropdown */
        header, .header-content, nav, .col-10, .search-container {
            overflow: visible !important; 
        }

        .search-container {
            position: relative; 
            width: 100%;
            max-width: 300px; 
        }
        
        #search-form {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        #search-input {
            width: 100%;
            padding-right: 40px !important; 
        }
        
        .search-icon-btn {
            position: absolute;
            right: 0; 
            top: 0;
            bottom: 0;
            height: 100%; 
            background: transparent !important; 
            border: none !important; 
            box-shadow: none !important;
            cursor: pointer;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px; 
            transition: color 0.2s;
            outline: none;
        }
        
        .search-icon-btn:hover {
            color: #007bff !important; 
            background: transparent !important; 
        }
        
        .search-icon-btn svg { width: 18px; height: 18px; }
        
        #search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #2a2a2a; 
            color: #fff;
            z-index: 99999; 
            border-radius: 0 0 8px 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.8);
            display: none; 
            margin-top: 5px;
            border: 1px solid #444;
            border-top: none;
        }
        
        .search-result-item {
            display: flex;
            align-items: center;
            padding: 10px;
            text-decoration: none;
            color: #fff;
            border-bottom: 1px solid #444;
            transition: background 0.2s;
        }
        
        .search-result-item:last-child { border-bottom: none; }
        .search-result-item:hover { background: #444; }
        
        .search-result-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 12px;
        }
        
        .search-result-title {
            font-weight: bold;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <div class="row header-content">
                <div class="col-2">
                    <h1>
                        <a href="index.php" class="logo-link">
                            <img src="<?php echo htmlspecialchars($site_logo); ?>" alt="<?php echo htmlspecialchars($site_title); ?> Logo" class="site-logo" style="max-height: 50px;">
                        </a>
                    </h1>
                </div>

                <div class="col-10">
                    <nav>
                        <ul class="nav-left">
                            <li><a href="games.php">Games</a></li>
                        </ul>

                        <div class="search-container">
                            <form action="advanced-search.php" method="GET" id="search-form">
                                <input type="text" placeholder="Search games..." name="search" id="search-input" autocomplete="off">
                                <button type="submit" class="search-icon-btn" aria-label="Search">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </button>
                            </form>
                            <div id="search-results"></div>
                        </div>

                        <ul class="nav-right">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li><a href="profile.php" class="btn-login">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
                                <li><a href="logout.php" class="btn-register" style="background-color: #dc3545;">Logout</a></li>
                            <?php else: ?>
                                <li><a href="login.php" class="btn-login">Login</a></li>
                                <li><a href="register.php" class="btn-register">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            let debounceTimer; 

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                searchResults.innerHTML = '<div style="padding: 10px; color: #aaa; font-size: 14px;">Searching...</div>';
                searchResults.style.display = 'block';

                debounceTimer = setTimeout(() => {
                    fetch(`/GameReviewSite/ajax_search.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.text()) 
                        .then(text => {
                            try {
                                const data = JSON.parse(text); 
                                searchResults.innerHTML = ''; 
                                
                                if (data.length > 0) {
                                    data.forEach(game => {
                                        const a = document.createElement('a');
                                        a.href = `game-details.php?id=${game.id}`;
                                        a.className = 'search-result-item';
                                        
                                        const img = document.createElement('img');
                                        img.src = game.cover_image;
                                        img.className = 'search-result-img';
                                        img.onerror = function() { this.src = 'assets/images/placeholder.png'; }; 
                                        
                                        const title = document.createElement('div');
                                        title.className = 'search-result-title';
                                        title.textContent = game.title;
                                        
                                        a.appendChild(img);
                                        a.appendChild(title);
                                        searchResults.appendChild(a);
                                    });
                                } else {
                                    searchResults.innerHTML = '<div style="padding: 10px; color: #aaa; font-size: 14px;">No games found.</div>';
                                }
                            } catch (e) {
                                console.error("JSON Parsing Error:", text);
                                searchResults.innerHTML = '<div style="padding: 10px; color: #ff6b6b; font-size: 14px;">Search Error.</div>';
                            }
                        })
                        .catch(err => {
                            console.error("Fetch network error:", err);
                            searchResults.innerHTML = '<div style="padding: 10px; color: #ff6b6b; font-size: 14px;">Connection failed.</div>';
                        });
                }, 300); 
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
            
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2 && searchResults.innerHTML !== '') {
                    searchResults.style.display = 'block';
                }
            });
        });
    </script>