document.addEventListener("DOMContentLoaded", function() {
    
    // --- SCORE COLORING LOGIC ---
    const scores = document.querySelectorAll('.metascore, .detailmetascore');

    scores.forEach(scoreElement => {
        const scoreText = scoreElement.innerText;
        const scoreValue = parseFloat(scoreText);

        if (!isNaN(scoreValue)) {
            let effectiveScore = scoreValue;
            if (scoreValue <= 10) {
                effectiveScore = scoreValue * 10;
            }

            if (effectiveScore >= 90) {
                scoreElement.classList.add('score-dark-green');
            } else if (effectiveScore >= 75) {
                scoreElement.classList.add('score-green');
            } else if (effectiveScore >= 50) {
                scoreElement.classList.add('score-yellow');
            } else {
                scoreElement.classList.add('score-red');
            }
        } else {
            scoreElement.classList.add('score-none');
        }
    });


    // --- SLIDER LOGIC ---
    const sliders = document.querySelectorAll('.slider-section-wrapper');

    sliders.forEach(container => {
        const track = container.querySelector('.slider-track');
        const prevBtn = container.querySelector('.prev-btn');
        const nextBtn = container.querySelector('.next-btn');
        
        const totalSlides = 4;
        let currentSlide = 0;

        function updateSlider() {
            // Check if track exists to prevent errors on pages without sliders
            if(track) {
                track.style.transform = `translateX(-${currentSlide * 100}%)`;
                prevBtn.style.opacity = currentSlide === 0 ? "0.5" : "1";
                prevBtn.disabled = currentSlide === 0;
                nextBtn.style.opacity = currentSlide === totalSlides - 1 ? "0.5" : "1";
                nextBtn.disabled = currentSlide === totalSlides - 1;
            }
        }

        if(nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (currentSlide < totalSlides - 1) {
                    currentSlide++;
                    updateSlider();
                }
            });
        }

        if(prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentSlide > 0) {
                    currentSlide--;
                    updateSlider();
                }
            });
        }

        updateSlider();
    });


    // --- ADVANCED SEARCH FILTERING & SORTING LOGIC ---
    const searchBtn = document.getElementById('main-search-btn');
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    let gameItems = Array.from(document.querySelectorAll('.game-item')); // Convert NodeList to Array for sorting
    
    // UI Elements
    const initialMsg = document.getElementById('initial-search-message');
    const noResultsMsg = document.getElementById('no-results');
    const gamesGrid = document.getElementById('games-grid'); // This is the .row container
    const clearBtn = document.getElementById('clear-filters');
    const sortSelect = document.getElementById('sort-select');

    // Only run if we are on the search page
    if (searchBtn) {

        function filterAndSortGames() {
            // 1. Hide Initial Message, Show Grid Container
            if(initialMsg) initialMsg.style.display = 'none';
            if(gamesGrid) gamesGrid.style.display = 'block';

            // 2. Get all checked values
            const checkedGenres = Array.from(document.querySelectorAll('input[data-filter-type="genre"]:checked')).map(cb => cb.value);
            const checkedPlatforms = Array.from(document.querySelectorAll('input[data-filter-type="platform"]:checked')).map(cb => cb.value);
            const checkedPlayers = Array.from(document.querySelectorAll('input[data-filter-type="players"]:checked')).map(cb => cb.value);
            const checkedFeatures = Array.from(document.querySelectorAll('input[data-filter-type="feature"]:checked')).map(cb => cb.value);

            // 3. Filter items
            let visibleItems = [];
            
            gameItems.forEach(item => {
                // Get data attributes from the HTML
                const itemGenres = item.getAttribute('data-genre').split(' ');
                const itemPlatforms = item.getAttribute('data-platform').split(' ');
                const itemPlayers = item.getAttribute('data-players') ? item.getAttribute('data-players').split(' ') : [];
                const itemFeatures = item.getAttribute('data-feature') ? item.getAttribute('data-feature').split(' ') : [];

                const genreMatch = (checkedGenres.length === 0) || checkedGenres.some(g => itemGenres.includes(g));
                const platformMatch = (checkedPlatforms.length === 0) || checkedPlatforms.some(p => itemPlatforms.includes(p));
                const playerMatch = (checkedPlayers.length === 0) || checkedPlayers.some(p => itemPlayers.includes(p));
                const featureMatch = (checkedFeatures.length === 0) || checkedFeatures.some(f => itemFeatures.includes(f));

                if (genreMatch && platformMatch && playerMatch && featureMatch) {
                    item.style.display = 'block'; // Show visually
                    visibleItems.push(item);
                } else {
                    item.style.display = 'none'; // Hide
                }
            });

            // 4. Sort visible items
            const sortValue = sortSelect.value;
            
            visibleItems.sort((a, b) => {
                const ratingA = parseFloat(a.getAttribute('data-rating'));
                const ratingB = parseFloat(b.getAttribute('data-rating'));
                const dateA = new Date(a.getAttribute('data-date'));
                const dateB = new Date(b.getAttribute('data-date'));

                if (sortValue === 'rating-desc') return ratingB - ratingA;
                if (sortValue === 'rating-asc') return ratingA - ratingB;
                if (sortValue === 'date-desc') return dateB - dateA;
                if (sortValue === 'date-asc') return dateA - dateB;
                return 0;
            });

            // 5. Re-append sorted items to the grid container
            // (Note: This moves the DOM elements, effectively reordering them visually)
            visibleItems.forEach(item => {
                gamesGrid.appendChild(item);
            });

            // 6. Show/Hide "No Results" message
            if (visibleItems.length === 0) {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        }

        // Trigger filter ONLY on Search Button Click
        searchBtn.addEventListener('click', filterAndSortGames);

        // Clear Filters Button
        if(clearBtn) {
            clearBtn.addEventListener('click', () => {
                // Uncheck all boxes
                filterCheckboxes.forEach(cb => cb.checked = false);
                // Reset Sort
                sortSelect.value = 'rating-desc';
                
                // Reset UI to initial state
                if(initialMsg) initialMsg.style.display = 'block';
                if(gamesGrid) gamesGrid.style.display = 'none';
                if(noResultsMsg) noResultsMsg.style.display = 'none';
            });
        }
    }

     // --- USER REVIEW LOGIC ---
    // --- USER REVIEW LOGIC ---
    const scoreBar = document.getElementById('score-bar');
    const segments = document.querySelectorAll('.bar-seg');
    
    // Inline sidebar elements (we update these for visual feedback on the sidebar)
    const circle = document.getElementById('my-score-circle');
    const rateMsg = document.getElementById('rate-msg');
    
    // Modal Elements
    const modal = document.getElementById('review-modal');
    const modalScoreCircle = document.getElementById('modal-score-circle');
    const cancelBtn = document.getElementById('cancel-modal-btn');
    const closeModalX = document.querySelector('.close-modal-btn');
    
    // We hide the old inline input container since we are using the popup now
    const inlineInput = document.getElementById('review-input-container');
    if(inlineInput) inlineInput.style.display = 'none'; 
    
    let lockedScore = 0;

    // Helper to color the bar segments based on value
    function colorBar(value) {
        segments.forEach(seg => {
            const segVal = parseInt(seg.getAttribute('data-value'));
            if (segVal <= value) {
                seg.classList.add('active');
            } else {
                seg.classList.remove('active');
            }
        });
    }

    // Helper to update a score circle (used for both sidebar and modal)
    function updateScoreCircle(element, value) {
        if (!element) return;

        if(value === 0) {
            element.textContent = '?';
            element.className = 'detailmetascore score-none';
            return;
        }

        element.textContent = value;
        element.className = 'detailmetascore'; // Reset base class
        
        // Apply color logic
        if (value >= 8) element.classList.add('score-green');
        else if (value >= 5) element.classList.add('score-yellow');
        else element.classList.add('score-red');
    }

    if (scoreBar) {
        segments.forEach(seg => {
            // Hover Effect
            seg.addEventListener('mouseenter', () => {
                const val = parseInt(seg.getAttribute('data-value'));
                colorBar(val);
                updateScoreCircle(circle, val);
            });

            // Click Effect -> OPEN MODAL
            seg.addEventListener('click', () => {
                const val = parseInt(seg.getAttribute('data-value'));
                lockedScore = val;
                
                // 1. Lock the sidebar visual
                colorBar(val);
                updateScoreCircle(circle, val);
                rateMsg.textContent = "You rated: " + val;

                // 2. Update the Modal Visuals
                updateScoreCircle(modalScoreCircle, val);

                // 3. Show the Modal (Flex is required for centering)
                if (modal) {
                    modal.style.display = 'flex';
                }
            });
        });

        // Mouse Leave Effect
        scoreBar.addEventListener('mouseleave', () => {
            colorBar(lockedScore);
            updateScoreCircle(circle, lockedScore);
        });
    }

    // --- MODAL CLOSING LOGIC ---
    function closeModal() {
        if (modal) modal.style.display = 'none';
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (closeModalX) closeModalX.addEventListener('click', closeModal);

    // Close if clicking outside the white box
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
    // Helper to color the bar segments dynamically
    function colorBar(value) {
        // Determine the color class based on the score
        let colorClass = 'score-red'; // Default to red (0-4)
        if (value >= 8) {
            colorClass = 'score-green';
        } else if (value >= 5) {
            colorClass = 'score-yellow';
        }

        segments.forEach(seg => {
            const segVal = parseInt(seg.getAttribute('data-value'));
            
            // Reset all color classes first
            seg.classList.remove('score-red', 'score-yellow', 'score-green');

            if (segVal <= value) {
                // Add the specific color class determined above
                seg.classList.add(colorClass);
            } 
        });
    }

});