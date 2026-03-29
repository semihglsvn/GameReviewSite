<?php
// Start the session to check if the user is logged in (if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include database connection safely
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameJoint - Homepage</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- CSS FOR LIVE SEARCH & ICON -->
    <style>
        /* Ensure parent containers do not clip the absolute dropdown */
        header, .header-content, nav, .col-10, .search-container {
            overflow: visible !important; 
        }

        .search-container {
            position: relative; /* Allows the dropdown to attach directly */
            width: 100%;
            max-width: 300px; /* Keeps the search bar contained */
        }
        
        /* Modern Search Input with integrated icon */
        #search-form {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        #search-input {
            width: 100%;
            padding-right: 40px !important; /* Prevents text from hiding behind the icon */
        }
        
        /* THE FIX: Blended transparent button with no gaps */
        .search-icon-btn {
            position: absolute;
            right: 0; /* Snaps to the exact right edge */
            top: 0;
            bottom: 0;
            height: 100%; /* Fills the vertical space of the input */
            background: transparent !important; /* Overrides global button backgrounds */
            border: none !important; /* Overrides global button borders */
            box-shadow: none !important;
            cursor: pointer;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px; /* Gives a comfortable click area */
            transition: color 0.2s;
            outline: none;
        }
        
        .search-icon-btn:hover {
            color: #007bff !important; /* Highlights the icon when hovered */
            background: transparent !important; /* Prevents grey background on hover */
        }
        
        .search-icon-btn svg {
            width: 18px;
            height: 18px;
        }
        
        /* Dropdown Styles */
        #search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #2a2a2a; /* Dark theme to match GameJoint */
            color: #fff;
            z-index: 99999; /* Extremely high z-index to stay above sliders */
            border-radius: 0 0 8px 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.8);
            display: none; /* Hidden by default */
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
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-item:hover {
            background: #444; /* Highlight on hover */
        }
        
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
                            <img src="assets/images/logo.svg" alt="GameJoint Logo" class="site-logo">
                        </a>
                    </h1>
                </div>

                <div class="col-10">
                    <nav>
                        <ul class="nav-left">
                            <li><a href="games.php">Games</a></li>
                        </ul>

                        <!-- SEARCH BAR CONTAINER -->
                        <div class="search-container">
                            <form action="advanced-search.php" method="GET" id="search-form">
                                <input type="text" placeholder="Search games..." name="search" id="search-input" autocomplete="off">
                                <!-- Magnifying Glass Icon Button -->
                                <button type="submit" class="search-icon-btn" aria-label="Search">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </button>
                            </form>
                            <!-- This div will be filled by JavaScript -->
                            <div id="search-results"></div>
                        </div>

                        <ul class="nav-right">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <!-- User is Logged In -->
                                <li><a href="profile.php" class="btn-login">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a></li>
                                <li><a href="logout.php" class="btn-register" style="background-color: #dc3545;">Logout</a></li>
                            <?php else: ?>
                                <!-- User is NOT Logged In -->
                                <li><a href="login.php" class="btn-login">Login</a></li>
                                <li><a href="register.php" class="btn-register">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <!-- LIVE SEARCH JAVASCRIPT -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            let debounceTimer; // Used to prevent spamming the database on every keystroke

            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                
                // If the user clears the box or types only 1 letter, hide results
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                // Instantly show a Loading state so the user knows it is working
                searchResults.innerHTML = '<div style="padding: 10px; color: #aaa; font-size: 14px;">Searching...</div>';
                searchResults.style.display = 'block';

                // Wait 300ms after the user stops typing to fetch results
                debounceTimer = setTimeout(() => {
                    fetch(`/GameReviewSite/ajax_search.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.text()) 
                        .then(text => {
                            try {
                                const data = JSON.parse(text); 
                                searchResults.innerHTML = ''; 
                                
                                if (data.length > 0) {
                                    data.forEach(game => {
                                        // Create clickable wrapper linking to game details
                                        const a = document.createElement('a');
                                        a.href = `game-details.php?id=${game.id}`;
                                        a.className = 'search-result-item';
                                        
                                        // Create image
                                        const img = document.createElement('img');
                                        img.src = game.cover_image;
                                        img.className = 'search-result-img';
                                        img.onerror = function() { this.src = 'assets/images/placeholder.png'; }; 
                                        
                                        // Create title
                                        const title = document.createElement('div');
                                        title.className = 'search-result-title';
                                        title.textContent = game.title;
                                        
                                        // Assemble and append
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

            // Automatically hide the dropdown if the user clicks anywhere else on the page
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
            
            // Re-show dropdown if user clicks back into the search bar and it has text
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2 && searchResults.innerHTML !== '') {
                    searchResults.style.display = 'block';
                }
            });
        });
    </script>