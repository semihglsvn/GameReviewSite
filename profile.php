<?php
// Start session first if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

// ==========================================
// 1. GET PROFILE USER ID
// ==========================================
// If an ID is in the URL, view that profile. Otherwise, view your own profile.
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0);

if ($profile_id <= 0) { 
    header("Location: login.php"); 
    exit; 
}

// ==========================================
// 2. FETCH PROFILE DATA
// ==========================================
$stmt = $conn->prepare("SELECT id, username, created_at, role_id, is_banned FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$profile_user = $stmt->get_result()->fetch_assoc();

if (!$profile_user) { 
    header("Location: index.php"); 
    exit; 
}

// Determine profile type for shapes (4 = Critic, 5 = Regular User)
$profile_role = (int)$profile_user['role_id'];
$is_critic_profile = ($profile_role === 4);
$score_class = $is_critic_profile ? 'metascore' : 'detailmetascore';

// ==========================================
// 3. CURRENT VIEWER ROLES (For Moderation)
// ==========================================
$viewer_id = $_SESSION['user_id'] ?? 0;
$viewer_role = $_SESSION['role_id'] ?? null;
$is_staff = in_array($viewer_role, [1, 2, 3]);

// ==========================================
// 4. FETCH REVIEWS
// ==========================================
$rev_stmt = $conn->prepare("
    SELECT r.*, g.title as game_title,
           GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names
    FROM reviews r
    JOIN games g ON r.game_id = g.id
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE r.user_id = ? AND r.status = 'approved'
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$rev_stmt->bind_param("i", $profile_id);
$rev_stmt->execute();
$reviews = $rev_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once 'includes/header.php';
?>

<style>
    .text-collapsed {
        display: -webkit-box;
        -webkit-line-clamp: 4; 
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-wrap: break-word; 
        white-space: pre-wrap;
        margin-bottom: 5px;
    }
    .btn-read-more {
        display: none; 
        background: transparent !important; 
        border: none !important;
        color: #000 !important;
        font-weight: bold;
        text-decoration: underline !important;
        cursor: pointer;
        padding: 5px 0 !important;
        font-size: 13px;
        text-align: left;
        margin-bottom: 10px;
    }
    .btn-read-more:hover { color: #666 !important; }
    
    /* Dist bar fixes */
    .dist-track { background: #eee; width: 100%; height: 10px; border-radius: 5px; overflow: hidden; margin: 0 10px; flex-grow: 1; }
    .dist-fill { height: 100%; transition: width 0.4s ease; }
    .bg-pos { background: #2ecc71; }
    .bg-mix { background: #f1c40f; }
    .bg-neg { background: #e74c3c; }
    .dist-row { display: flex; align-items: center; margin-bottom: 5px; font-size: 13px; }
    .dist-label { width: 60px; font-weight: bold; }
    .dist-count { width: 30px; text-align: right; color: #7f8c8d; }
</style>

<div class="container main-content">
    
    <div class="row" style="display:flex; justify-content:space-between; align-items:flex-end;">
        <div class="col-8">
            <h1 style="margin-bottom: 5px;"><?php echo htmlspecialchars($profile_user['username']); ?></h1>
            <span class="release-date">Member Since <?php echo date('M Y', strtotime($profile_user['created_at'])); ?></span>
            <?php if ($profile_user['is_banned']): ?>
                <span style="background:#e74c3c; color:white; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:bold; margin-left:10px;">BANNED</span>
            <?php endif; ?>
        </div>
        <div class="col-4 text-right">
            <?php if ($is_staff && $profile_role > 3): // Don't let staff ban other staff easily here ?>
                <button type="button" onclick="openBanModal(<?php echo $profile_user['id']; ?>, '<?php echo addslashes(htmlspecialchars($profile_user['username'])); ?>')" class="btn-cancel" style="background:#c0392b; padding:8px 15px; border:none; color:white; border-radius:4px; cursor:pointer; font-weight:bold;">BAN USER</button>
            <?php endif; ?>
        </div>
    </div>
    <hr>

    <div class="row mt-30 mb-30">
        <div class="col-12">
            <h4 class="font-weight-bold mb-15">Review Stats</h4>
            <div class="game-card overview-card" style="display:flex; flex-wrap:wrap; padding:20px;">
                
                <div class="overview-left" style="flex: 0 0 150px; text-align:center; border-right:1px solid #eee; padding-right:20px;">
                    <div id="avg-score" class="<?php echo $score_class; ?> metascore-large" style="margin:0 auto; width:70px; height:70px; line-height:70px; font-size:28px;">-</div>
                    <div class="text-sub-grey mt-10" style="font-weight:bold;">Avg. Score</div>
                </div>
                
                <div class="overview-right" style="flex: 1; padding-left:30px;">
                    <h6 style="margin-top:0; color:#2c3e50;">Score Distribution</h6>
                    <div class="mb-20">
                        <div class="dist-row">
                            <span class="dist-label">Positive</span>
                            <div class="dist-track"><div id="pos-bar" class="dist-fill bg-pos" style="width: 0%;"></div></div>
                            <span id="pos-count" class="dist-count">0</span>
                        </div>
                        <div class="dist-row">
                            <span class="dist-label">Mixed</span>
                            <div class="dist-track"><div id="mixed-bar" class="dist-fill bg-mix" style="width: 0%;"></div></div>
                            <span id="mixed-count" class="dist-count">0</span>
                        </div>
                        <div class="dist-row">
                            <span class="dist-label">Negative</span>
                            <div class="dist-track"><div id="neg-bar" class="dist-fill bg-neg" style="width: 0%;"></div></div>
                            <span id="neg-count" class="dist-count">0</span>
                        </div>
                    </div>
                    
                    <hr class="divider-light">
                    
                    <div class="row">
                        <div class="col-6">
                            <span class="text-sub-grey" style="display:block; margin-bottom:5px;">Highest Rated</span>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div id="highest-score-val" class="<?php echo $score_class; ?>">-</div>
                                <b id="highest-game-name" style="font-size:14px; color:#2c3e50;">-</b>
                            </div>
                        </div>
                        <div class="col-6">
                            <span class="text-sub-grey" style="display:block; margin-bottom:5px;">Lowest Rated</span>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div id="lowest-score-val" class="<?php echo $score_class; ?>">-</div>
                                <b id="lowest-game-name" style="font-size:14px; color:#2c3e50;">-</b>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <hr>

    <div class="row">
        <div class="col-12">
            
            <div class="section-title-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 class="h3-nomargin">Reviews (<?php echo count($reviews); ?>)</h3>
                
                <div class="filter-group" style="display:flex; gap:10px;">
                    <select onchange="filterReviews('profile-review-list', this.value)" style="padding:6px; border:1px solid #ccc; border-radius:4px;">
                        <option value="all">Show All</option>
                        <option value="green">Positive</option>
                        <option value="yellow">Mixed</option>
                        <option value="red">Negative</option>
                    </select>

                    <select onchange="sortReviews('profile-review-list', this.value)" style="padding:6px; border:1px solid #ccc; border-radius:4px;">
                        <option value="date-desc">Newest First</option>
                        <option value="date-asc">Oldest First</option>
                        <option value="desc">Best Score</option>
                        <option value="asc">Worst Score</option>
                    </select>
                </div>
            </div>

            <div id="profile-review-list">
                <?php if (empty($reviews)): ?>
                    <p class="text-sub-grey" style="text-align:center; padding:30px;">No reviews published yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="game-card review-item mb-20" style="padding:20px;" 
                             data-score="<?php echo $review['score']; ?>" 
                             data-date="<?php echo $review['created_at']; ?>">
                            
                            <div class="card-content" style="display:flex; flex-direction:column; height:100%;">
                                <div class="text-sub-grey mb-10" style="font-size:12px;">
                                    <?php echo strtoupper(date('M d, Y', strtotime($review['created_at']))); ?>
                                </div>
                                
                                <div style="display:flex; align-items:center; margin-bottom:15px;">
                                    <div class="<?php echo $score_class; ?> metascore-large mr-10"><?php echo $review['score']; ?></div>
                                    <h4 style="margin:0;"><a href="game-details.php?id=<?php echo $review['game_id']; ?>" style="color:#2c3e50; text-decoration:none;"><?php echo htmlspecialchars($review['game_title']); ?></a></h4>
                                </div>
                                
                                <?php if (!empty($review['comment'])): ?>
                                    <p class="text-collapsed review-body-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <button class="btn-read-more">Read More</button>
                                <?php endif; ?>
                                
                                <div style="margin-top:auto; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-size:11px; font-weight:bold; color:#7f8c8d; background:#ecf0f1; padding:3px 8px; border-radius:4px;">
                                        <?php echo !empty($review['platform_names']) ? htmlspecialchars($review['platform_names']) : 'PC'; ?>
                                    </span>
                                    
                                    <?php if ($viewer_id > 0 && $viewer_id !== $profile_id): ?>
                                        <a href="#" class="btn-report footer-link-bold" data-review-id="<?php echo $review['id']; ?>" style="color:#e74c3c; font-size:12px; font-weight:bold; text-decoration:none;">REPORT</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<?php require_once 'includes/report_modal.php'; ?>

<div id="full-review-modal" class="modal-overlay">
    <div class="modal-content text-left modal-width-limited">
        <span class="close-full-review-btn modal-close-right" style="float:right; font-size:24px; cursor:pointer;">&times;</span>
        <div class="modal-review-header" style="display:flex; align-items:center; margin-bottom:15px;">
            <div id="modal-review-score" class="<?php echo $score_class; ?> mr-15" style="width:50px; height:50px; line-height:50px; font-size:22px;"></div>
            <h3 id="modal-review-author" class="h3-nomargin" style="margin-left: 15px;">Game Title</h3>
        </div>
        <p id="modal-review-text" class="modal-text-content" style="word-wrap:break-word; white-space:pre-wrap; background:#f9f9f9; padding:15px; border-radius:4px; border:1px solid #eee;">Review text...</p>
        <div class="text-right mt-20">
            <button type="button" id="close-full-review-btn" class="btn-cancel" style="background: #333;">Close</button>
        </div>
    </div>
</div>

<?php if ($is_staff): ?>
<div id="banModal" class="modal-overlay" style="display:none; align-items:center; justify-content:center; z-index: 10000;">
    <div class="modal-content" style="max-width:400px; text-align:left;">
        <span class="close-modal" onclick="closeBanModal()" style="float:right; font-size:24px; cursor:pointer;">&times;</span>
        <h3 style="margin-top:0; color:#e74c3c;">Punish User</h3>
        <p>Select ban duration for <strong id="banUsername">User</strong>:</p>
        
        <form id="banForm" onsubmit="executeBan(event)">
            <input type="hidden" name="action" value="ban">
            <input type="hidden" name="user_id" id="modal_author_id">
            
            <div style="margin-bottom: 10px;">
                <label style="cursor:pointer;"><input type="radio" name="duration" value="24h" checked> 24 Hours</label>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="cursor:pointer;"><input type="radio" name="duration" value="7d"> 7 Days</label>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="cursor:pointer;"><input type="radio" name="duration" value="perm"> <span style="color:red; font-weight:bold;">Permanent Ban</span></label>
            </div>
            
            <div class="text-right mt-20">
                <button type="button" class="btn-cancel mr-10" onclick="closeBanModal()" style="background-color:#95a5a6;">Cancel</button>
                <button type="submit" id="banSubmitBtn" class="submit-review-btn" style="background:#c0392b; width:auto; padding:10px 20px; border:none;">Execute Ban</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        updateOverviewStats();
        setTimeout(initReadMoreButtons, 100);
        window.addEventListener('resize', initReadMoreButtons);
    });

    // Color logic
    function applyColorToEl(el, score) {
        el.classList.remove('score-none', 'score-dark-green', 'score-green', 'score-yellow', 'score-red');
        let effective = parseFloat(score);
        if (effective <= 10) effective = effective * 10; // Normalize

        if (effective >= 90) el.classList.add('score-dark-green');
        else if (effective >= 75) el.classList.add('score-green');
        else if (effective >= 50) el.classList.add('score-yellow');
        else el.classList.add('score-red');
    }

    // Dynamic Stats Calculator
    function updateOverviewStats() {
        const reviews = document.querySelectorAll('.review-item');
        let total = 0, pos = 0, mixed = 0, neg = 0, hi = -1, lo = 1000, hiG = "-", loG = "-";
        let validCount = 0;

        reviews.forEach(r => {
            let sBox = r.querySelector('.metascore') || r.querySelector('.detailmetascore');
            if(!sBox) return;

            let s = parseFloat(sBox.innerText);
            const tElement = r.querySelector('h4 a');
            const t = tElement ? tElement.innerText : "-";
            
            if (!isNaN(s)) {
                let effectiveS = s <= 10 ? s * 10 : s; // Normalize to 100 scale

                total += effectiveS;
                validCount++;
                if (effectiveS >= 75) pos++; 
                else if (effectiveS >= 50) mixed++; 
                else neg++;
                
                if (effectiveS > hi) { hi = effectiveS; hiG = t; }
                if (effectiveS < lo) { lo = effectiveS; loG = t; }
                
                applyColorToEl(sBox, effectiveS); // Apply color to list item
            }
        });

        if (validCount > 0) {
            let avgNorm = Math.round(total / validCount);
            // If they are a 10-scale user, convert the avg back to 10-scale for display
            let displayAvg = <?php echo $is_critic_profile ? 'avgNorm' : '(avgNorm / 10).toFixed(1)'; ?>;
            
            let avgEl = document.getElementById('avg-score');
            avgEl.innerText = displayAvg;
            applyColorToEl(avgEl, avgNorm);
            
            document.getElementById('pos-count').innerText = pos;
            document.getElementById('mixed-count').innerText = mixed;
            document.getElementById('neg-count').innerText = neg;
            
            document.getElementById('pos-bar').style.width = (pos/validCount*100)+'%';
            document.getElementById('mixed-bar').style.width = (mixed/validCount*100)+'%';
            document.getElementById('neg-bar').style.width = (neg/validCount*100)+'%';
            
            const hiEl = document.getElementById('highest-score-val');
            const loEl = document.getElementById('lowest-score-val');
            
            hiEl.innerText = <?php echo $is_critic_profile ? 'hi' : 'hi/10'; ?>; 
            loEl.innerText = <?php echo $is_critic_profile ? 'lo' : 'lo/10'; ?>;

            document.getElementById('highest-game-name').innerText = hiG;
            document.getElementById('lowest-game-name').innerText = loG;

            applyColorToEl(hiEl, hi);
            applyColorToEl(loEl, lo);
        }
    }

    // Dropdown Filters
    function filterReviews(containerId, color) {
        const reviews = document.querySelectorAll('#' + containerId + ' .review-item');
        reviews.forEach(r => {
            if (color === 'all') { r.style.display = 'block'; return; }
            let val = parseFloat(r.dataset.score);
            let normalized = val <= 10 ? val * 10 : val;
            let type = 'red';
            if (normalized >= 75) type = 'green';
            else if (normalized >= 50) type = 'yellow';

            r.style.display = (type === color) ? 'block' : 'none';
        });
    }

    function sortReviews(containerId, method) {
        const container = document.getElementById(containerId);
        const reviews = Array.from(container.querySelectorAll('.review-item'));

        reviews.sort((a, b) => {
            let scoreA = parseFloat(a.dataset.score);
            let scoreB = parseFloat(b.dataset.score);
            if(scoreA <= 10) scoreA *= 10;
            if(scoreB <= 10) scoreB *= 10;

            let dateA = new Date(a.dataset.date).getTime();
            let dateB = new Date(b.dataset.date).getTime();

            if (method === 'desc') return scoreB - scoreA;
            if (method === 'asc') return scoreA - scoreB;
            if (method === 'date-desc') return dateB - dateA;
            if (method === 'date-asc') return dateA - dateB;
        });

        reviews.forEach(r => container.appendChild(r));
    }

    // Read More Logic
    function initReadMoreButtons() {
        const modal = document.getElementById('full-review-modal');
        const modalAuthor = document.getElementById('modal-review-author');
        const modalScore = document.getElementById('modal-review-score');
        const modalText = document.getElementById('modal-review-text');

        document.querySelectorAll('.btn-read-more').forEach(btn => {
            const p = btn.previousElementSibling;
            
            if (p && p.scrollHeight > p.clientHeight + 5) {
                btn.style.display = 'inline-block';
            } else {
                btn.style.display = 'none';
            }

            btn.onclick = function(e) {
                e.preventDefault();
                const card = btn.closest('.game-card');
                const title = card.querySelector('h4 a').textContent;
                const scoreBox = card.querySelector('.metascore') || card.querySelector('.detailmetascore');
                const score = scoreBox ? scoreBox.textContent : "-";

                modalAuthor.textContent = title;
                modalText.textContent = p.textContent.trim();
                
                modalScore.textContent = score;
                applyColorToEl(modalScore, parseFloat(score));
                modal.style.display = 'flex';
            };
        });

        const close = () => modal.style.display = 'none';
        document.querySelector('.close-full-review-btn').onclick = close;
        document.getElementById('close-full-review-btn').onclick = close;
        window.onclick = (e) => { if (e.target == modal) close(); };
    }

    // Ban AJAX Logic
    <?php if ($is_staff): ?>
    function openBanModal(authorId, authorName) {
        document.getElementById("modal_author_id").value = authorId;
        document.getElementById("banUsername").innerText = authorName;
        document.getElementById("banModal").style.display = "flex";
    }
    
    function closeBanModal() {
        document.getElementById("banModal").style.display = "none";
    }

    function executeBan(e) {
        e.preventDefault(); 
        const form = document.getElementById('banForm');
        const formData = new FormData(form);
        const btn = document.getElementById('banSubmitBtn');
        
        btn.disabled = true;
        btn.innerText = "Banning...";

        fetch('admin/process_users.php', { // Note: Verify this matches your actual DB file name!
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("User successfully banned.");
                location.reload(); 
            } else {
                alert("Error: " + data.error);
                btn.disabled = false;
                btn.innerText = "Execute Ban";
            }
        })
        .catch(err => {
            alert("Network Error.");
            btn.disabled = false;
            btn.innerText = "Execute Ban";
        });
    }
    <?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>