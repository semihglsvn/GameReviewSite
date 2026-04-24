<?php

// Start the session to check if the user is logged in (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include database connection safely
require_once __DIR__ . '/../config/db.php';

// ==========================================
// SECURE AUTO-LOGIN CHECK (Remember Me)
// ==========================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    
    $cookie_parts = explode('_', $_COOKIE['remember_token']);
    
    if (count($cookie_parts) === 2) {
        $cookie_user_id = (int)$cookie_parts[0];
        $cookie_token = $cookie_parts[1];
        
        $stmt = $conn->prepare("SELECT id, username, role_id, remember_token_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $cookie_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (!empty($user['remember_token_hash']) && hash_equals($user['remember_token_hash'], hash('sha256', $cookie_token))) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];
            } else {
                setcookie('remember_token', '', time() - 3600, "/");
            }
        } else {
            setcookie('remember_token', '', time() - 3600, "/");
        }
        $stmt->close();
    } else {
        setcookie('remember_token', '', time() - 3600, "/");
    }
}

// ==========================================
// FETCH GLOBAL SITE SETTINGS
// ==========================================
$settings_query = $conn->query("SELECT * FROM settings WHERE id = 1");
$site_settings = $settings_query->fetch_assoc();

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

<link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">    
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
    
    <script>
        if (localStorage.getItem('site-theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>

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
                            <?php
                            $nav_query = $conn->query("SELECT title, url FROM menus WHERE is_active = 1 ORDER BY sort_order ASC");
                            while ($nav = $nav_query->fetch_assoc()): 
                            ?>
                                <li><a href="<?php echo htmlspecialchars($nav['url']); ?>"><?php echo htmlspecialchars($nav['title']); ?></a></li>
                            <?php endwhile; ?>
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

<ul class="nav-right" style="position: relative; z-index: 999; display: flex; align-items: center; gap: 12px; margin: 0; padding: 0;">
    
<li style="position: relative; z-index: 999999; pointer-events: auto;">
    <button id="theme-toggle-btn" 
            onclick="forceThemeToggle(event)" 
            style="position: relative; z-index: 999999; background: #fff; border: 2px solid #000; padding: 6px 12px; border-radius: 20px; cursor: pointer; font-size: 14px; color: #000; font-weight: bold; white-space: nowrap;">
        <script>document.write(localStorage.getItem('site-theme') === 'dark' ? '☀️ Light Mode' : '🌙 Dark Mode');</script>
        <noscript>🌙 Dark Mode</noscript>
    </button>

    <script>
        // This function lives right next to the button so it cannot be blocked
        function forceThemeToggle(e) {
            e.preventDefault();
            document.body.classList.toggle('dark-mode');
            
            var btn = document.getElementById('theme-toggle-btn');
            if (document.body.classList.contains('dark-mode')) {
                localStorage.setItem('site-theme', 'dark');
                btn.innerHTML = '☀️ Light Mode';
            } else {
                localStorage.setItem('site-theme', 'light');
                btn.innerHTML = '🌙 Dark Mode';
            }
        }
    </script>
</li>

    <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="profile.php" class="btn-login" style="white-space: nowrap;">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
        <li><a href="logout.php" class="btn-register" style="background-color: #dc3545; white-space: nowrap;">Logout</a></li>
    <?php else: ?>
        <li><a href="login.php" class="btn-login" style="position: relative; z-index: 999;">Login</a></li>
        <li><a href="register.php" class="btn-register" style="position: relative; z-index: 999;">Register</a></li>
    <?php endif; ?>
</ul>
                </div>
            </div>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- THEME TOGGLE LOGIC ---
            const themeBtn = document.getElementById('theme-toggle-btn');
            
            if (themeBtn) {
                themeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('dark-mode');
                    
                    if (document.body.classList.contains('dark-mode')) {
                        localStorage.setItem('site-theme', 'dark');
                        themeBtn.innerHTML = '☀️ Light Mode';
                    } else {
                        localStorage.setItem('site-theme', 'light');
                        themeBtn.innerHTML = '🌙 Dark Mode';
                    }
                });
            }

            // --- HEADER SEARCH LOGIC ---
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            let debounceTimer; 

            if(searchInput) {
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
            }
        });
    </script>