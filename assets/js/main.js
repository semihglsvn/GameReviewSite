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
    let gameItems = Array.from(document.querySelectorAll('.game-item')); 
    
    const initialMsg = document.getElementById('initial-search-message');
    const noResultsMsg = document.getElementById('no-results');
    const gamesGrid = document.getElementById('games-grid'); 
    const clearBtn = document.getElementById('clear-filters');
    const sortSelect = document.getElementById('sort-select');

    if (searchBtn) {

        function filterAndSortGames() {
            if(initialMsg) initialMsg.style.display = 'none';
            if(gamesGrid) gamesGrid.style.display = 'block';

            const checkedGenres = Array.from(document.querySelectorAll('input[data-filter-type="genre"]:checked')).map(cb => cb.value);
            const checkedPlatforms = Array.from(document.querySelectorAll('input[data-filter-type="platform"]:checked')).map(cb => cb.value);
            const checkedPlayers = Array.from(document.querySelectorAll('input[data-filter-type="players"]:checked')).map(cb => cb.value);
            const checkedFeatures = Array.from(document.querySelectorAll('input[data-filter-type="feature"]:checked')).map(cb => cb.value);

            let visibleItems = [];
            
            gameItems.forEach(item => {
                const itemGenres = item.getAttribute('data-genre').split(' ');
                const itemPlatforms = item.getAttribute('data-platform').split(' ');
                const itemPlayers = item.getAttribute('data-players') ? item.getAttribute('data-players').split(' ') : [];
                const itemFeatures = item.getAttribute('data-feature') ? item.getAttribute('data-feature').split(' ') : [];

                const genreMatch = (checkedGenres.length === 0) || checkedGenres.some(g => itemGenres.includes(g));
                const platformMatch = (checkedPlatforms.length === 0) || checkedPlatforms.some(p => itemPlatforms.includes(p));
                const playerMatch = (checkedPlayers.length === 0) || checkedPlayers.some(p => itemPlayers.includes(p));
                const featureMatch = (checkedFeatures.length === 0) || checkedFeatures.some(f => itemFeatures.includes(f));

                if (genreMatch && platformMatch && playerMatch && featureMatch) {
                    item.style.display = 'block'; 
                    visibleItems.push(item);
                } else {
                    item.style.display = 'none'; 
                }
            });

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

            visibleItems.forEach(item => {
                gamesGrid.appendChild(item);
            });

            if (visibleItems.length === 0) {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        }

        searchBtn.addEventListener('click', filterAndSortGames);

        if(clearBtn) {
            clearBtn.addEventListener('click', () => {
                filterCheckboxes.forEach(cb => cb.checked = false);
                sortSelect.value = 'rating-desc';
                if(initialMsg) initialMsg.style.display = 'block';
                if(gamesGrid) gamesGrid.style.display = 'none';
                if(noResultsMsg) noResultsMsg.style.display = 'none';
            });
        }
    }

    // --- USER REVIEW LOGIC ---
    const scoreBar = document.getElementById('score-bar');
    const segments = document.querySelectorAll('.bar-seg');
    
    const circle = document.getElementById('my-score-circle');
    const rateMsg = document.getElementById('rate-msg');
    
    const modal = document.getElementById('review-modal');
    const modalScoreCircle = document.getElementById('modal-score-circle');
    const cancelBtn = document.getElementById('cancel-modal-btn');
    const closeModalX = document.querySelector('.close-modal-btn');
    
    const inlineInput = document.getElementById('review-input-container');
    if(inlineInput) inlineInput.style.display = 'none'; 
    
    let lockedScore = 0;

    function updateScoreCircle(element, value) {
        if (!element) return;
        if(value === 0) {
            element.textContent = '?';
            element.className = 'detailmetascore score-none';
            return;
        }
        element.textContent = value;
        element.className = 'detailmetascore'; 
        if (value >= 8) element.classList.add('score-green');
        else if (value >= 5) element.classList.add('score-yellow');
        else element.classList.add('score-red');
    }

    function colorBar(value) {
        let colorClass = 'score-red'; 
        if (value >= 8) colorClass = 'score-green';
        else if (value >= 5) colorClass = 'score-yellow';

        segments.forEach(seg => {
            const segVal = parseInt(seg.getAttribute('data-value'));
            seg.classList.remove('score-red', 'score-yellow', 'score-green');
            if (segVal <= value) seg.classList.add(colorClass);
        });
    }

    if (scoreBar) {
        segments.forEach(seg => {
            seg.addEventListener('mouseenter', () => {
                const val = parseInt(seg.getAttribute('data-value'));
                colorBar(val);
                updateScoreCircle(circle, val);
            });

            seg.addEventListener('click', () => {
                const val = parseInt(seg.getAttribute('data-value'));
                lockedScore = val;
                colorBar(val);
                updateScoreCircle(circle, val);
                rateMsg.textContent = "You rated: " + val;
                updateScoreCircle(modalScoreCircle, val);
                if (modal) modal.style.display = 'flex';
            });
        });

        scoreBar.addEventListener('mouseleave', () => {
            colorBar(lockedScore);
            updateScoreCircle(circle, lockedScore);
        });
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (closeModalX) closeModalX.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    // --- REPORT MODAL LOGIC ---
    const reportModal = document.getElementById('report-modal');
    const reportBtns = document.querySelectorAll('.btn-report');
    const closeReportX = document.querySelector('.close-report-btn');
    const cancelReportBtn = document.getElementById('cancel-report-btn');

    function closeReportModal() {
        if (reportModal) reportModal.style.display = 'none';
    }

    reportBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            if (reportModal) reportModal.style.display = 'flex';
        });
    });

    if (cancelReportBtn) cancelReportBtn.addEventListener('click', closeReportModal);
    if (closeReportX) closeReportX.addEventListener('click', closeReportModal);
    if (reportModal) {
        reportModal.addEventListener('click', (e) => {
            if (e.target === reportModal) closeReportModal();
        });
    }

    // --- REVIEW SORTING LOGIC (Score & Time) ---
    window.sortReviews = function(listId, order) {
        const list = document.getElementById(listId);
        const items = Array.from(list.getElementsByClassName('review-item'));
        const showMoreBtn = document.querySelector(`button[data-target="${listId}"]`);
        
        // 1. Sort the items
        items.sort((a, b) => {
            // Get Data
            const scoreA = parseFloat(a.getAttribute('data-score'));
            const scoreB = parseFloat(b.getAttribute('data-score'));
            const dateTextA = a.querySelector('.card-content > div:first-child').textContent.trim();
            const dateTextB = b.querySelector('.card-content > div:first-child').textContent.trim();
            
            const dateA = new Date(dateTextA);
            const dateB = new Date(dateTextB);

            // LOGIC
            if (order === 'desc') return scoreB - scoreA;       // Best Score
            if (order === 'asc') return scoreA - scoreB;        // Worst Score
            if (order === 'date-desc') return dateB - dateA;    // Newest
            if (order === 'date-asc') return dateA - dateB;     // Oldest
            return 0;
        });

        const DEFAULT_VISIBLE = 2; 

        // 2. Re-append items and RESET visibility
        items.forEach((item, index) => {
            list.appendChild(item); 
            
            // Only manipulate visibility if the item isn't already hidden by a color filter
            if (!item.classList.contains('review-filtered-hide')) {
                if (index < DEFAULT_VISIBLE) {
                    item.classList.remove('review-hidden');
                } else {
                    item.classList.add('review-hidden');
                }
            }
        });

        // 3. Reset the "Show More" button visibility
        if (showMoreBtn) {
            // Count how many items are actually available to be shown
            const visibleCount = items.filter(i => !i.classList.contains('review-filtered-hide')).length;
            
            if (visibleCount > DEFAULT_VISIBLE) {
                showMoreBtn.style.display = 'inline-block';
            } else {
                showMoreBtn.style.display = 'none';
            }
        }
    };

    // --- READ MORE POPUP LOGIC ---
    const fullReviewModal = document.getElementById('full-review-modal');
    const modalAuthor = document.getElementById('modal-review-author');
    const modalScore = document.getElementById('modal-review-score');
    const modalText = document.getElementById('modal-review-text');
    const closeFullReviewX = document.querySelector('.close-full-review-btn');
    const closeFullReviewBtn = document.getElementById('close-full-review-btn');

    function closeFullReview() {
        if(fullReviewModal) fullReviewModal.style.display = 'none';
    }

    if(closeFullReviewX) closeFullReviewX.addEventListener('click', closeFullReview);
    if(closeFullReviewBtn) closeFullReviewBtn.addEventListener('click', closeFullReview);
    if(fullReviewModal) {
        fullReviewModal.addEventListener('click', (e) => {
            if(e.target === fullReviewModal) closeFullReview();
        });
    }

function initReadMoreButtons() {
        const readMoreBtns = document.querySelectorAll('.btn-read-more');

        // 1. Define the logic to check heights
        const updateVisibility = () => {
            readMoreBtns.forEach(btn => {
                const textElement = btn.previousElementSibling;
                const card = btn.closest('.game-card, .review-item');
                
                if (!textElement || !card) return;

                // --- LOGIC: HEIGHT CHECK ---
                // We compare the total height of the text (scrollHeight) 
                // vs the visible height allowed by CSS (clientHeight).
                // We add a 5px buffer to account for font-smoothing/sub-pixel differences.
                if (textElement.scrollHeight > textElement.clientHeight + 5) {
                    btn.style.display = 'inline-block';
                } else {
                    btn.style.display = 'none';
                }
                
                // --- CLICK HANDLER (Same as before) ---
                // Only attach if we haven't already (to avoid duplicate listeners)
                if (!btn.hasAttribute('data-init')) {
                    btn.setAttribute('data-init', 'true');
                    
                    btn.onclick = function(e) {
                        e.preventDefault();

                        const authorEl = card.querySelector('h4') || card.querySelector('.author') || card.querySelector('a');
                        const author = authorEl ? authorEl.textContent.trim() : "Unknown";
                        const scoreBox = card.querySelector('.metascore') || card.querySelector('.detailmetascore');
                        
                        // Get full text
                        let fullText = textElement.textContent.trim();

                        // Populate Modal
                        if(modalAuthor) modalAuthor.textContent = author;
                        if(modalText) modalText.textContent = fullText;
                        
                        if (modalScore && scoreBox) {
                            modalScore.className = scoreBox.className; 
                            modalScore.textContent = scoreBox.textContent;
                            modalScore.style.width = "50px";
                            modalScore.style.height = "50px";
                            modalScore.style.lineHeight = "50px";
                            modalScore.style.fontSize = "22px";
                        }

                        if(fullReviewModal) fullReviewModal.style.display = 'flex';
                    };
                }
            });
        };

        // 2. Run the check immediately
        updateVisibility();

        // 3. Run the check again after a slight delay to ensure fonts/layout are loaded
        setTimeout(updateVisibility, 100);

        // 4. Update on window resize (in case text wraps differently)
        window.addEventListener('resize', updateVisibility);
    }
    initReadMoreButtons();
    window.initReadMoreButtons = initReadMoreButtons;

    // --- SHOW MORE LOGIC  ---
    const showMoreBtns = document.querySelectorAll('.btn-show-more');

    showMoreBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const container = document.getElementById(targetId);
            
            if (!container) return;

            const hiddenItems = Array.from(container.querySelectorAll('.review-item.review-hidden'));
            
            // Show next 15
            const itemsToShow = hiddenItems.slice(0, 15);
            
            itemsToShow.forEach(item => {
                item.classList.remove('review-hidden');
            });

            if (typeof window.initReadMoreButtons === "function") {
                window.initReadMoreButtons();
            }

            // Hide button if no more items
            const remainingHidden = container.querySelectorAll('.review-item.review-hidden');
            if (remainingHidden.length === 0) {
                this.style.display = 'none';
            }
        });
    });

    // --- REVIEW FILTERING LOGIC ---
    window.filterReviews = function(listId, color) {
        const list = document.getElementById(listId);
        const items = list.querySelectorAll('.review-item');
        const showMoreBtn = document.querySelector(`button[data-target="${listId}"]`);

        // Show All reset 
        if (color === 'all') {
            items.forEach(item => item.classList.remove('review-filtered-hide'));
            window.sortReviews(listId, 'desc'); 
            return;
        }

        // --- APPLY COLOR FILTER ---
        let matchCount = 0;

        items.forEach(item => {
            const score = parseFloat(item.getAttribute('data-score'));
            let effective = score <= 10 ? score * 10 : score;

            let isMatch = false;
            
            if (color === 'green' && effective >= 75) isMatch = true;
            else if (color === 'yellow' && effective >= 50 && effective < 75) isMatch = true;
            else if (color === 'red' && effective < 50) isMatch = true;

            if (isMatch) {
                item.classList.remove('review-filtered-hide'); 
                item.classList.remove('review-hidden');
                matchCount++;
            } else {
                item.classList.add('review-filtered-hide');
            }
        });

        // Hide "Show More" button when filtering
        if (showMoreBtn) {
            showMoreBtn.style.display = 'none';
        }
        
        if (typeof window.initReadMoreButtons === "function") {
            window.initReadMoreButtons();
        }
    };
});