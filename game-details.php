<?php
require_once __DIR__ . '/config/db.php';
require_once 'includes/header.php';

// ==========================================
// OYUN ID GÜVENLİK KONTROLÜ
// ==========================================
$game_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($game_id <= 0) { header("Location: index.php"); exit; }

// ==========================================
// 1. OYUN BİLGİLERİ
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
// 2. MEVCUT KULLANICININ ROLÜ
// ==========================================
$current_user_role = null;
if (isset($_SESSION['user_id'])) {
    $role_stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
    $role_stmt->bind_param("i", $_SESSION['user_id']);
    $role_stmt->execute();
    $current_user_role = $role_stmt->get_result()->fetch_assoc()['role_id'] ?? null;
}
$is_critic = ($current_user_role == 4);
$is_user   = ($current_user_role == 5);

// ==========================================
// 3. KRİTİK YORUMLARI (role_id = 4)
// ==========================================
$stmt3 = $conn->prepare("
    SELECT r.*, u.username FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.game_id = ? AND r.status = 'approved' AND u.role_id = 4
    ORDER BY r.created_at DESC
");
$stmt3->bind_param("i", $game_id);
$stmt3->execute();
$critic_reviews = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 4. KULLANICI YORUMLARI (role_id = 5)
// ==========================================
$stmt2 = $conn->prepare("
    SELECT r.*, u.username FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.game_id = ? AND r.status = 'approved' AND u.role_id = 5
    ORDER BY r.created_at DESC
");
$stmt2->bind_param("i", $game_id);
$stmt2->execute();
$user_reviews = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

// ==========================================
// 5. KULLANICI ORTALAMA SKORU
// ==========================================
$stmt4 = $conn->prepare("
    SELECT AVG(r.score) as avg_score, COUNT(*) as total
    FROM reviews r JOIN users u ON r.user_id = u.id
    WHERE r.game_id = ? AND r.status = 'approved' AND u.role_id = 5
");
$stmt4->bind_param("i", $game_id);
$stmt4->execute();
$avg_data   = $stmt4->get_result()->fetch_assoc();
$user_avg   = $avg_data['avg_score'] ? round($avg_data['avg_score'], 1) : null;
$user_total = $avg_data['total'] ?? 0;

// ==========================================
// 6. YORUM GÖNDERME
// ==========================================
$review_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $score   = (int)($_POST['score'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $uid     = (int)$_SESSION['user_id'];
    $max     = $is_critic ? 100 : 10;

    if ($score >= 1 && $score <= $max && !empty($comment)) {
        $chk = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND game_id = ?");
        $chk->bind_param("ii", $uid, $game_id);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $review_message = '<div class="alert alert-warning">You have already reviewed this game.</div>';
        } else {
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
?>

<style>
    .review-hidden {
        display: none !important;
    }
    .score-text-label {
        font-size: 13px;
        font-weight: 600;
        margin-left: 15px;
        text-transform: uppercase;
        color: #666666 !important;
    }
    .review-username-link {
        text-decoration: none !important;
        color: #111111 !important; 
    }
    .review-username-link:hover {
        text-decoration: underline !important;
    }
    
    /* Force long text to break and clamp after 4 lines */
    .text-collapsed {
        display: -webkit-box;
        -webkit-line-clamp: 4; 
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-wrap: break-word; 
        white-space: pre-wrap;
        margin-bottom: 5px;
    }

    /* FIX: Prevents global CSS from turning the button into a black block */
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
        background: transparent !important; /* Stops the black block */
        color: #666 !important; /* Subtle fade on hover */
        box-shadow: none !important;
        outline: none !important;
    }

    /* FIX: Flexbox to push the Report button to the absolute bottom of every card */
    .review-item .card-content {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .card-footer-flex {
        margin-top: auto !important; /* This is the magic rule that pushes it down */
        padding-top: 15px;
    }
</style>

<div class="container main-content">

    <?php echo $review_message; ?>
    <?php if (isset($_GET['reviewed'])): ?>
        <div class="alert alert-success">Your review has been submitted!</div>
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
            <?php if (!empty($game['description'])): ?>
                <div class="game-card" style="margin-top:20px;">
                    <div class="card-content">
                        <p><?php echo nl2br(htmlspecialchars($game['description'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
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

                    <?php if ($is_critic || $is_user): ?>
                        <h3>Add Your Review</h3>
                        <div class="meta-footer no-border no-margin-top display-block">
                            <div class="rate-header-row">
                                <span id="rate-msg" class="rate-msg-text">Rate this game</span>
                                <div id="my-score-circle" class="detailmetascore score-none">?</div>
                            </div>
                            <?php if ($is_critic): ?>
                                <form method="POST" action="game-details.php?id=<?php echo $game_id; ?>">
                                    <input type="number" name="score" id="score-input" min="1" max="100" placeholder="Score (1-100)" class="review-textarea" style="height:auto;padding:8px;margin-bottom:10px;">
                                    <div class="critic-review-container">
                                        <textarea name="comment" placeholder="Write your review here..." class="review-textarea"></textarea>
                                        <button type="submit" class="submit-review-btn">Post Review</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="interactive-bar" id="score-bar">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <div class="bar-seg" data-value="<?php echo $i; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <h3>Add Your Review</h3>
                        <p class="text-sub-grey"><a href="login.php">Login</a> to write a review.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <div class="row section-header"></div>

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
                                </div>
                                <?php if (!empty($review['comment'])): ?>
                                    <p class="text-collapsed review-body-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <button class="btn-read-more">Read More</button>
                                <?php endif; ?>
                                
                                <div class="card-footer-flex">
                                    <a href="#" class="btn-report footer-link-bold" data-review-id="<?php echo $review['id']; ?>">REPORT</a>
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

    <div id="review-modal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal-btn">&times;</span>
            <h3>Write a Review</h3>
            <form method="POST" action="game-details.php?id=<?php echo $game_id; ?>" onsubmit="document.getElementById('modal-hidden-score').value = document.getElementById('modal-score-circle').innerText;">
                <div class="mb-15" style="display:flex;align-items:center;gap:10px;">
                    <span>Your Score:</span>
                    <div id="modal-score-circle" class="detailmetascore">?</div>
                </div>
                <input type="hidden" name="score" id="modal-hidden-score" value="0">
                <textarea name="comment" placeholder="Tell us what you loved or hated..." class="review-textarea" rows="5"></textarea>
                <div class="text-right mt-10">
                    <button type="button" id="cancel-modal-btn" class="btn-cancel mr-10">Cancel</button>
                    <button type="submit" class="submit-review-btn" style="width:auto;padding:10px 20px;">Post</button>
                </div>
            </form>
        </div>
    </div>

    <div id="report-modal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-report-btn modal-close-right">&times;</span>
            <h3 class="mt-0">Report Review</h3>
            <p class="text-sub-grey mb-15" style="font-size:14px;">Select all that apply:</p>
            <div class="text-left mb-20">
                <label class="modal-label-block"><input type="checkbox" value="spam" class="modal-checkbox">Spam or Advertising</label>
                <label class="modal-label-block"><input type="checkbox" value="abuse" class="modal-checkbox">Abusive or Harassing</label>
                <label class="modal-label-block"><input type="checkbox" value="irrelevant" class="modal-checkbox">Off-topic / Irrelevant</label>
                <label class="modal-label-block"><input type="checkbox" value="spoiler" class="modal-checkbox">Contains Spoilers</label>
            </div>
            <div class="text-right">
                <button type="button" id="cancel-report-btn" class="btn-cancel">Cancel</button>
                <button class="btn-submit modal-submit-btn">Submit Report</button>
            </div>
        </div>
    </div>

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

</div>

<?php require_once 'includes/footer.php'; ?>