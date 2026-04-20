<?php
// Start session first if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/db.php';

// ==========================================
// 1. OYUN ID GÜVENLİK KONTROLÜ
// ==========================================
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($game_id <= 0) { header("Location: index.php"); exit; }

// ==========================================
// 2. MEVCUT KULLANICININ ROLÜ VE BAN DURUMU
// ==========================================
$current_user_role = null;
$is_banned = 0;
$existing_review = null;
$is_staff = false;
$is_critic = false;
$is_user = false;

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    
    // Get user role and ban status
    $role_stmt = $conn->prepare("SELECT role_id, is_banned FROM users WHERE id = ?");
    $role_stmt->bind_param("i", $uid);
    $role_stmt->execute();
    $user_data = $role_stmt->get_result()->fetch_assoc();
    
    if ($user_data) {
        $current_user_role = $user_data['role_id'];
        $is_banned = $user_data['is_banned'];
    }

    // Check if the user already reviewed this game
    $chk_stmt = $conn->prepare("SELECT id, score, comment FROM reviews WHERE user_id = ? AND game_id = ?");
    $chk_stmt->bind_param("ii", $uid, $game_id);
    $chk_stmt->execute();
    $existing_review = $chk_stmt->get_result()->fetch_assoc();
    
    $is_staff  = in_array($current_user_role, [1, 2, 3]);
    $is_critic = ($current_user_role == 4);
    $is_user   = ($current_user_role == 5);
}

// ==========================================
// 3. FORM GÖNDERİMLERİ (MUST BE BEFORE HEADER.PHP)
// ==========================================
$review_message = '';

// A. YORUM SİLME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review']) && isset($_SESSION['user_id'])) {
    $del = $conn->prepare("DELETE FROM reviews WHERE user_id = ? AND game_id = ?");
    $del->bind_param("ii", $_SESSION['user_id'], $game_id);
    $del->execute();
    header("Location: game-details.php?id={$game_id}&deleted=1");
    exit;
}

// B. YORUM GÖNDERME VE GÜNCELLEME İŞLEMİ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id']) && isset($_POST['score'])) {
    $score   = (int)$_POST['score'];
    $comment = trim($_POST['comment'] ?? '');
    $uid     = (int)$_SESSION['user_id'];
    $max     = $is_critic ? 100 : 10;

    if ($score >= 1 && $score <= $max && !empty($comment)) {
        if ($existing_review) {
            // Update existing review
            $upd = $conn->prepare("UPDATE reviews SET score = ?, comment = ?, created_at = CURRENT_TIMESTAMP WHERE user_id = ? AND game_id = ?");
            $upd->bind_param("isii", $score, $comment, $uid, $game_id);
            if ($upd->execute()) {
                header("Location: game-details.php?id={$game_id}&updated=1");
                exit;
            }
        } else {
            // Insert new review
            $ins = $conn->prepare("INSERT INTO reviews (user_id, game_id, score, comment) VALUES (?, ?, ?, ?)");
            $ins->bind_param("iiis", $uid, $game_id, $score, $comment);
            if ($ins->execute()) {
                header("Location: game-details.php?id={$game_id}&reviewed=1");
                exit;
            }
        }
    } else {
        $review_message = '<div class="alert alert-danger">Please enter a valid score (1-' . ($is_critic ? '100' : '10') . ') and a comment.</div>';
    }
}

// ==========================================
// NOW IT IS SAFE TO LOAD THE HTML HEADER
// ==========================================
require_once 'includes/header.php';

// ==========================================
// 4. OYUN BİLGİLERİ (Okuma İşlemleri)
// ==========================================
$stmt = $conn->prepare("
    SELECT g.*,
        GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR ', ') as platform_names,
        GROUP_CONCAT(DISTINCT gen.name ORDER BY gen.name SEPARATOR ', ') as genre_names
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    LEFT JOIN game_genres gg ON g.id = gg.game_id
    LEFT JOIN genres gen ON gg.genre_id = gen.id
    WHERE g.id = ?
    GROUP BY g.id
");
$stmt->bind_param("i", $game_id);
$stmt->execute();
$game = $stmt->get_result()->fetch_assoc();
if (!$game) { header("Location: index.php"); exit; }

// ==========================================
// 5. KRİTİK YORUMLARI (role_id = 4)
// ==========================================
// ADDED: AND u.is_banned = 0
$stmt3 = $conn->prepare("
    SELECT r.*, u.username FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.game_id = ? AND r.status = 'approved' AND u.role_id = 4 AND u.is_banned = 0
    ORDER BY r.created_at DESC
");
$stmt3->bind_param("i", $game_id);
$stmt3->execute();
$critic_reviews = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 6. KULLANICI YORUMLARI (role_id = 5)
// ==========================================
// ADDED: AND u.is_banned = 0
$stmt2 = $conn->prepare("
    SELECT r.*, u.username FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.game_id = ? AND r.status = 'approved' AND u.role_id = 5 AND u.is_banned = 0
    ORDER BY r.created_at DESC
");
$stmt2->bind_param("i", $game_id);
$stmt2->execute();
$user_reviews = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 7. KULLANICI ORTALAMA SKORU
// ==========================================
// ADDED: AND u.is_banned = 0 (So banned users don't affect the overall game score!)
$stmt4 = $conn->prepare("
    SELECT AVG(r.score) as avg_score, COUNT(*) as total
    FROM reviews r JOIN users u ON r.user_id = u.id
    WHERE r.game_id = ? AND r.status = 'approved' AND u.role_id = 5 AND u.is_banned = 0
");
$stmt4->bind_param("i", $game_id);
$stmt4->execute();
$avg_data   = $stmt4->get_result()->fetch_assoc();
$user_avg   = $avg_data['avg_score'] ? round($avg_data['avg_score'], 1) : null;
$user_total = $avg_data['total'] ?? 0;
?>
 
<style>
    .review-hidden { display: none !important; }
    .score-text-label { font-size: 13px; font-weight: 600; margin-left: 15px; text-transform: uppercase; color: #666666 !important; }
    .review-username-link { text-decoration: none !important; color: #111111 !important; }
    .review-username-link:hover { text-decoration: underline !important; }
    
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
    .btn-read-more:hover, .btn-read-more:focus {
        background: transparent !important;
        color: #666 !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .review-item .card-content { display: flex; flex-direction: column; height: 100%; }
    .card-footer-flex { margin-top: auto !important; padding-top: 15px; }
</style>

<div class="container main-content">

    <?php echo $review_message; ?>
    <?php if (isset($_GET['reviewed'])): ?>
        <div class="alert alert-success">Your review has been submitted!</div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Your review has been updated!</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Your review has been deleted.</div>
    <?php endif; ?>

    <div class="row section-header">
        <div class="col-12" style="display:flex; flex-wrap:wrap;">
            <h2 style="width:100%;"><?php echo htmlspecialchars($game['title']); ?></h2>
            <div class="col-8" style="padding-left:0;">
                <?php foreach (array_filter(explode(', ', $game['platform_names'] ?? '')) as $p): ?>
                    <span class="platform-tag d-inline-block mr-10"><?php echo htmlspecialchars($p); ?></span>
                <?php endforeach; ?>
                <?php foreach (array_filter(explode(', ', $game['genre_names'] ?? '')) as $g): ?>
                    <span class="platform-tag d-inline-block mr-10"><?php echo htmlspecialchars($g); ?></span>
                <?php endforeach; ?>
                <?php if ($game['release_date']): ?>
                    <span class="text-meta-small">Released On: <?php echo date('M j, Y', strtotime($game['release_date'])); ?></span>
                <?php endif; ?>
            </div>
            <div class="col-4 text-right" style="padding-right:0;">
                <?php if (!empty($game['developer'])): ?>
                    <span class="platform-tag d-inline-block ml-10">DEV</span>
                    <span class="text-meta-small"><?php echo htmlspecialchars($game['developer']); ?></span>
                <?php endif; ?>
                <?php if (!empty($game['publisher'])): ?>
                    <span class="platform-tag d-inline-block ml-10">PUB</span>
                    <span class="text-meta-small"><?php echo htmlspecialchars($game['publisher']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-8">
            <div class="game-card">
                <img src="<?php echo htmlspecialchars($game['cover_image']); ?>"
                     alt="<?php echo htmlspecialchars($game['title']); ?>"
                     height="400px" style="width:100%;object-fit:cover;"
                     onerror="this.src='assets/images/placeholder.png'">
            </div>
        </div>

        <div class="col-4">
            <div class="game-card">
                <div class="card-content">

                    <h3>Metascore</h3>
                    <div class="meta-footer no-border mt-0">
                        <?php if ($game['metascore'] > 0): ?>
                            <span class="score-text-label" style="margin-left:0; display:block; margin-bottom:5px;"></span>
                            <div class="metascore"><?php echo $game['metascore']; ?></div>
                        <?php else: ?>
                            <span>Not yet scored</span>
                            <div class="metascore">tbd</div>
                        <?php endif; ?>
                    </div>
                    <p class="text-sub-grey mt-5">Based on <?php echo count($critic_reviews); ?> Critic Reviews</p>

                    <hr class="divider-light">

                    <h3>User Score</h3>
                    <div class="meta-footer no-border mt-0">
                        <?php if ($user_avg): ?>
                            <span class="score-text-label" style="margin-left:0; display:block; margin-bottom:5px;"></span> 
                            <div class="detailmetascore"><?php echo $user_avg; ?></div>
                        <?php else: ?>
                            <span>No user ratings yet</span>
                            <div class="detailmetascore">tbd</div>
                        <?php endif; ?>
                    </div>
                    <p class="text-sub-grey mt-5">Based on <?php echo $user_total; ?> User Ratings</p>

                    <hr class="divider-light">

                    <h3>Your Review</h3>
                    <?php if ($is_banned): ?>
                        <div class="alert alert-danger" style="margin-top: 15px;">Your account has been restricted. You cannot post reviews at this time.</div>
                    <?php elseif ($is_staff): ?>
                        <p class="text-sub-grey">Staff members cannot write reviews.</p>
                    
                    <?php elseif ($existing_review && !isset($_GET['edit'])): ?>
                        <div class="meta-footer no-border mt-0" style="display:block;">
                            <div style="display:flex; align-items:center; gap: 10px;">
                                <div class="<?php echo $is_critic ? 'metascore' : 'detailmetascore'; ?>"><?php echo $existing_review['score']; ?></div>
                                <strong style="color: #444;">Posted Score</strong>
                            </div>
                            
                            <div style="margin-top:20px; display:flex; gap:10px;">
                                <a href="game-details.php?id=<?php echo $game_id; ?>&edit=1" class="btn-approve" style="text-decoration:none; padding:8px 15px; font-size:13px; text-align:center; width:50%;">Edit</a>
                                <form method="POST" action="game-details.php?id=<?php echo $game_id; ?>" onsubmit="return confirm('Are you sure you want to delete your review?');" style="margin:0; width:50%;">
                                    <input type="hidden" name="delete_review" value="1">
                                    <button type="submit" class="btn-cancel" style="background:#e74c3c; padding:8px 15px; font-size:13px; width:100%; border:none; cursor:pointer; color:white; border-radius:4px;">Delete</button>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($is_critic || $is_user): ?>
                        <div class="meta-footer no-border no-margin-top display-block">
                            <div class="rate-header-row">
                                <span id="rate-msg" class="rate-msg-text"><?php echo isset($_GET['edit']) ? 'Edit your review' : 'Rate this game'; ?></span>
                                <div id="my-score-circle" class="detailmetascore score-none">?</div>
                            </div>
                            <?php if ($is_critic): ?>
                                <form method="POST" action="game-details.php?id=<?php echo $game_id; ?>">
                                    <input type="number" name="score" id="score-input" min="1" max="100" placeholder="Score (1-100)" class="review-textarea" style="height:auto;padding:8px;margin-bottom:10px;" value="<?php echo $existing_review['score'] ?? ''; ?>">
                                    <div class="critic-review-container">
                                        <textarea name="comment" placeholder="Write your review here..." class="review-textarea"><?php echo htmlspecialchars($existing_review['comment'] ?? ''); ?></textarea>
                                        <button type="submit" class="submit-review-btn"><?php echo isset($_GET['edit']) ? 'Update' : 'Post Review'; ?></button>
                                    </div>
                                    <?php if(isset($_GET['edit'])): ?>
                                        <a href="game-details.php?id=<?php echo $game_id; ?>" style="display:block; text-align:center; margin-top:10px; color:#888; font-size:12px; text-decoration:none;">Cancel Edit</a>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <div class="interactive-bar" id="score-bar" data-existing-score="<?php echo $existing_review['score'] ?? 0; ?>">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <div class="bar-seg" data-value="<?php echo $i; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                                <?php if(isset($_GET['edit'])): ?>
                                    <a href="game-details.php?id=<?php echo $game_id; ?>" style="display:block; text-align:center; margin-top:10px; color:#888; font-size:12px; text-decoration:none;">Cancel Edit</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sub-grey"><a href="login.php">Login</a> to write a review.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div> <div class="row section-header"></div>

    <div class="row mt-30">

        <div class="col-6">
            <div class="section-title-row">
                <h3 class="h3-nomargin">Critic Reviews</h3>
            </div>

            <div id="critic-list">
                <?php if (empty($critic_reviews)): ?>
                    <p class="text-sub-grey">No critic reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($critic_reviews as $index => $review): ?>
                        <div class="game-card review-item <?php echo $index >= 4 ? 'review-hidden' : ''; ?> h-auto-imp mb-20"
                             data-score="<?php echo $review['score']; ?>"
                             data-date="<?php echo $review['created_at']; ?>">
                            <div class="card-content">
                                <div class="text-date-grey mb-10">
                                    <?php echo strtoupper(date('M d, Y', strtotime($review['created_at']))); ?>
                                </div>
                                <div class="review-item-header" style="display:flex; align-items:center; margin-bottom:15px;">
                                    <div class="metascore metascore-large"><?php echo $review['score']; ?></div>
                                    <h4 class="h3-nomargin" style="margin-left: 15px;">
                                        <a href="profile.php?id=<?php echo $review['user_id']; ?>" class="review-username-link">
                                            <?php echo htmlspecialchars($review['username']); ?>
                                        </a>
                                    </h4>
                                    
                                    <?php if ($is_staff): ?>
                                        <button type="button" onclick="openBanModal(<?php echo $review['user_id']; ?>, '<?php echo addslashes(htmlspecialchars($review['username'])); ?>')" class="btn-cancel" style="background:#c0392b; padding:4px 8px; font-size:11px; border:none; color:white; border-radius:4px; cursor:pointer; margin-left:auto;">BAN USER</button>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                    <p class="text-collapsed review-body-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <button class="btn-read-more">Read More</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (count($critic_reviews) > 4): ?>
                <button class="btn-show-more" data-target="critic-list">Show More Critic Reviews</button>
            <?php endif; ?>
        </div>

        <div class="col-6">
            <div class="section-title-row">
                <h3 class="h3-nomargin">User Reviews</h3>
            </div>

            <div id="user-list">
                <?php if (empty($user_reviews)): ?>
                    <p class="text-sub-grey">No user reviews yet. Be the first!</p>
                <?php else: ?>
                    <?php foreach ($user_reviews as $index => $review): ?>
                        <div class="game-card review-item <?php echo $index >= 4 ? 'review-hidden' : ''; ?> h-auto-imp mb-20"
                             data-score="<?php echo $review['score']; ?>"
                             data-date="<?php echo $review['created_at']; ?>">
                            <div class="card-content">
                                <div class="text-date-grey mb-10">
                                    <?php echo strtoupper(date('M d, Y', strtotime($review['created_at']))); ?>
                                </div>
                                <div class="review-item-header" style="display:flex; align-items:center; margin-bottom:15px;">
                                    <div class="detailmetascore metascore-large"><?php echo $review['score']; ?></div>
                                    <h4 class="h3-nomargin" style="margin-left: 15px;">
                                        <a href="profile.php?id=<?php echo $review['user_id']; ?>" class="review-username-link">
                                            <?php echo htmlspecialchars($review['username']); ?>
                                        </a>
                                    </h4>
                                    
                                    <?php if ($is_staff): ?>
                                        <button type="button" onclick="openBanModal(<?php echo $review['user_id']; ?>, '<?php echo addslashes(htmlspecialchars($review['username'])); ?>')" class="btn-cancel" style="background:#c0392b; padding:4px 8px; font-size:11px; border:none; color:white; border-radius:4px; cursor:pointer; margin-left:auto;">BAN USER</button>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                    <p class="text-collapsed review-body-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <button class="btn-read-more">Read More</button>
                                <?php endif; ?>
                                
                                <div class="card-footer-flex">
                                    <?php if (!isset($_SESSION['user_id']) || $review['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="#" class="btn-report footer-link-bold" data-review-id="<?php echo $review['id']; ?>">REPORT</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (count($user_reviews) > 4): ?>
                <button class="btn-show-more" data-target="user-list">Show More User Reviews</button>
            <?php endif; ?>
        </div>
    </div>

    <div id="review-modal" class="modal-overlay" <?php if(isset($_GET['edit']) && $is_user) echo 'style="display:flex;"'; ?>>
        <div class="modal-content">
            <span class="close-modal-btn" onclick="if(window.location.href.indexOf('edit=') > -1) { window.location.href='game-details.php?id=<?php echo $game_id; ?>'; }">&times;</span>
            <h3><?php echo isset($_GET['edit']) ? 'Edit your Review' : 'Write a Review'; ?></h3>
            <form method="POST" action="game-details.php?id=<?php echo $game_id; ?>" onsubmit="document.getElementById('modal-hidden-score').value = document.getElementById('modal-score-circle').innerText;">
                <div class="mb-15" style="display:flex;align-items:center;gap:10px;">
                    <span>Your Score:</span>
                    <div id="modal-score-circle" class="detailmetascore"><?php echo $existing_review['score'] ?? '?'; ?></div>
                </div>
                <input type="hidden" name="score" id="modal-hidden-score" value="<?php echo $existing_review['score'] ?? '0'; ?>">
                <textarea name="comment" placeholder="Tell us what you loved or hated..." class="review-textarea" rows="5"><?php echo htmlspecialchars($existing_review['comment'] ?? ''); ?></textarea>
                <div class="text-right mt-10">
                    <button type="button" id="cancel-modal-btn" class="btn-cancel mr-10" onclick="if(window.location.href.indexOf('edit=') > -1) { window.location.href='game-details.php?id=<?php echo $game_id; ?>'; }">Cancel</button>
                    <button type="submit" class="submit-review-btn" style="width:auto;padding:10px 20px;"><?php echo isset($_GET['edit']) ? 'Update' : 'Post'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once 'includes/report_modal.php'; ?>

    <div id="full-review-modal" class="modal-overlay">
        <div class="modal-content text-left modal-width-limited">
            <span class="close-full-review-btn modal-close-right">&times;</span>
            <div class="modal-review-header" style="display:flex; align-items:center; margin-bottom:15px;">
                <div id="modal-review-score" class="detailmetascore mr-15"></div>
                <h3 id="modal-review-author" class="h3-nomargin" style="margin-left: 15px;">Author Name</h3>
            </div>
            <p id="modal-review-text" class="modal-text-content" style="word-wrap:break-word; white-space:pre-wrap;">Review text goes here...</p>
            <div class="text-right mt-20">
                <button type="button" id="close-full-review-btn" class="btn-cancel" style="background:#333;">Close</button>
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
    
    <script>
    function openBanModal(authorId, authorName) {
        document.getElementById("modal_author_id").value = authorId;
        document.getElementById("banUsername").innerText = authorName;
        document.getElementById("banModal").style.display = "flex";
    }
    
    function closeBanModal() {
        document.getElementById("banModal").style.display = "none";
    }

    // THE AJAX BACKGROUND SUBMISSION
    function executeBan(e) {
        e.preventDefault(); // Stops the white screen redirect!
        
        const form = document.getElementById('banForm');
        const formData = new FormData(form);
        const btn = document.getElementById('banSubmitBtn');
        
        btn.disabled = true;
        btn.innerText = "Banning...";

        fetch('admin/process_user.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("User successfully banned.");
                location.reload(); // Reloads the page so their reviews hide or update
            } else {
                alert("Error: " + data.error);
                btn.disabled = false;
                btn.innerText = "Execute Ban";
            }
        })
        .catch(err => {
            alert("A network error occurred.");
            btn.disabled = false;
            btn.innerText = "Execute Ban";
        });
    }
    </script>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>