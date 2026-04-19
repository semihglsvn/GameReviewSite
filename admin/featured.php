<?php
// admin/featured.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

// Only Admins and Editors can curation content
if (!in_array($_SESSION['role_id'], [1, 2])) {
    header("Location: index.php");
    exit;
}

// Check how many are currently featured
$check_count = $conn->query("SELECT COUNT(id) as total FROM featured_games")->fetch_assoc();
$total_featured = $check_count['total'];
$is_full = ($total_featured >= 16);

// Fetch currently featured games
$stmt = $conn->prepare("
    SELECT fg.id as feature_id, fg.game_id, fg.display_order, 
           g.title, g.cover_image 
    FROM featured_games fg 
    JOIN games g ON fg.game_id = g.id 
    ORDER BY fg.display_order ASC
");
$stmt->execute();
$featured_games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>

<main class="admin-content">
    <div class="admin-header">
        <h2>Curate Homepage Slider (<?php echo number_format($total_featured); ?> / 16)</h2>
        <p style="color: #7f8c8d; margin-top: 5px;">Drag rows or use the arrows to manage display order. Maximum 16 games.</p>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <h3>Add a Game to Slider</h3>
        <p style="color: #7f8c8d; margin-bottom: 10px; font-size: 14px;">The game will be added to the end of the current list.</p>
        <div style="display: flex; gap: 10px; margin-top: 15px; align-items: flex-start; position: relative;">
            <div style="flex: 1; position: relative;">
<input type="text" id="gameSearch" placeholder="Type a game title or exact Game ID..." style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 15px;" autocomplete="off" onkeyup="searchGames()" <?php echo $is_full ? 'disabled' : ''; ?>>                <div id="searchResults" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ccc; border-radius: 4px; z-index: 1000; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    </div>
            </div>
            
            <form id="addFeatureForm" style="display: flex; gap: 10px;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="game_id" id="selectedGameId" required>
                <button type="button" onclick="submitFeature()" id="addFeatureBtn" class="btn-approve" style="padding: 12px 20px; margin: 0; white-space: nowrap;" <?php echo $is_full ? 'disabled' : ''; ?>>
                   Add to Slider
                </button>
            </form>
        </div>
        <p id="selectedGameText" style="margin-top: 10px; font-weight: bold; color: #2980b9; display: none;">Selected: <span></span></p>
        <?php if ($is_full): ?>
            <p style="color: #e74c3c; font-weight: bold; margin-top: 10px;">Slider is full (max 16). Remove a game to add a new one.</p>
        <?php endif; ?>
    </div>

    <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; background: white; padding: 10px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <button type="button" onclick="massRemove()" class="btn-delete" style="opacity: 0.5;" id="massRemoveBtn" disabled>
            Remove Selected
        </button>
        <button type="button" onclick="saveNewOrder()" class="btn-approve" style="font-size: 16px; padding: 10px 25px;" id="saveOrderBtn" disabled>
            Save New Order
        </button>
    </div>

    <div class="list-container-scroll">
        <div class="featured-list-wrapper" id="featuredCardsGrid">
            <?php if (empty($featured_games)): ?>
                <div style="text-align: center; padding: 50px; background: white; border-radius: 8px; border: 1px solid #eee;">
                    <h3 style="color: #95a5a6;">No games are currently featured. Use the search above.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($featured_games as $index => $game): ?>
                    <div class="featured-row" draggable="true" 
                         data-game-id="<?php echo $game['game_id']; ?>" 
                         data-feature-id="<?php echo $game['feature_id']; ?>">
                        
                        <div class="row-controls">
                            <input type="checkbox" class="mass-select" value="<?php echo $game['feature_id']; ?>" onchange="toggleMassRemoveButton()">
                            
                            <div class="order-arrows">
                                <span class="arrow-btn" onclick="moveCardUp(this)">▲</span>
                                <span class="card-number"><?php echo $index + 1; ?></span>
                                <span class="arrow-btn" onclick="moveCardDown(this)">▼</span>
                            </div>
                        </div>

                        <div class="row-info">
                            <?php if ($game['cover_image']): ?>
                                <img src="<?php echo htmlspecialchars($game['cover_image']); ?>" class="row-thumb" alt="Cover">
                            <?php else: ?>
                                <div class="row-thumb-placeholder">No Cover</div>
                            <?php endif; ?>
                            <strong class="row-title"><?php echo htmlspecialchars($game['title']); ?></strong>
                        </div>

                        <div class="row-actions">
                            <button type="button" class="btn-sm btn-delete" onclick="removeFeature(<?php echo $game['feature_id']; ?>)">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
    /* Mobile Scroll Protection */
    .list-container-scroll {
        width: 100%;
        overflow-x: auto;
    }

    .featured-list-wrapper {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 600px; /* Prevents squishing on mobile */
    }

    /* 1-Row Card Layout */
    .featured-row {
        background: white;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        padding: 10px 15px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #e0e0e0;
        cursor: grab;
        transition: background 0.2s;
    }

    .featured-row:hover {
        background: #f8f9fa;
    }
    
    .featured-row.dragging {
        opacity: 0.5;
        border: 1px dashed #3498db;
        background: #e8f4f8;
        cursor: grabbing;
    }

    /* Controls (Checkbox & Arrows) */
    .row-controls {
        display: flex;
        align-items: center;
        gap: 15px;
        width: 80px; /* Fixed width to keep things aligned */
    }

    .mass-select {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .order-arrows {
        display: flex;
        flex-direction: column;
        align-items: center;
        line-height: 1;
    }

    .arrow-btn {
        color: #95a5a6;
        cursor: pointer;
        font-size: 14px;
        padding: 2px 5px;
    }
    .arrow-btn:hover {
        color: #2c3e50;
    }

    .card-number {
        font-weight: bold;
        font-size: 16px;
        color: #2c3e50;
        margin: 2px 0;
    }

    /* Info (Image & Title) */
    .row-info {
        display: flex;
        align-items: center;
        gap: 15px;
        flex: 1;
    }

    .row-thumb {
        width: 40px;
        height: 55px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .row-thumb-placeholder {
        width: 40px;
        height: 55px;
        background: #ddd;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #7f8c8d;
        text-align: center;
    }

    .row-title {
        font-size: 16px;
        color: #2c3e50;
    }

    /* Actions */
    .row-actions {
        width: 100px;
        text-align: right;
    }
</style>

<script>
// Total current count from PHP
let currentFeaturedCount = <?php echo $total_featured; ?>;

// --- 1. AJAX LIVE SEARCH FOR GAMES ---
let searchTimer;
function searchGames() {
    clearTimeout(searchTimer);
    const query = document.getElementById('gameSearch').value;
    const resultsDiv = document.getElementById('searchResults');
    
    if (query.length < 3) {
        resultsDiv.style.display = 'none';
        return;
    }

    searchTimer = setTimeout(() => {
        fetch(`../ajax_search.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                resultsDiv.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(game => {
                        const div = document.createElement('div');
                        div.style.display = 'flex';
                        div.style.alignItems = 'center';
                        div.style.padding = '10px';
                        div.style.cursor = 'pointer';
                        div.style.borderBottom = '1px solid #eee';
                        div.style.gap = '10px';
                        
                        const img = document.createElement('img');
                        img.src = game.cover_image || '../assets/images/no-cover.png';
                        img.style.width = '30px';
                        img.style.height = '40px';
                        img.style.objectFit = 'cover';
                        img.style.borderRadius = '3px';
                        
                        const text = document.createElement('span');
                        text.textContent = game.title;
                        
                        div.appendChild(img);
                        div.appendChild(text);
                        
                        div.onmouseover = () => div.style.background = '#f1f1f1';
                        div.onmouseout = () => div.style.background = 'white';
                        
                        div.onclick = () => {
                            document.getElementById('selectedGameId').value = game.id;
                            document.getElementById('gameSearch').value = ''; 
                            resultsDiv.style.display = 'none'; 
                            
                            const textDisplay = document.getElementById('selectedGameText');
                            textDisplay.style.display = 'block';
                            textDisplay.querySelector('span').textContent = game.title;
                        };
                        resultsDiv.appendChild(div);
                    });
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.innerHTML = '<div style="padding: 10px; color: #777;">No games found.</div>';
                    resultsDiv.style.display = 'block';
                }
            })
            .catch(err => console.error(err));
    }, 300); 
}

document.addEventListener('click', (e) => {
    if (e.target.id !== 'gameSearch') {
        document.getElementById('searchResults').style.display = 'none';
    }
});

// --- 2. SUBMIT NEW FEATURED GAME ---
// --- 2. SUBMIT NEW FEATURED GAME (NOW ACCEPTS DIRECT IDs) ---
function submitFeature() {
    let gameId = document.getElementById('selectedGameId').value;
    const searchVal = document.getElementById('gameSearch').value.trim();

    // SMART ID CHECK: If they didn't click the dropdown, but typed pure numbers
    if (!gameId && /^\d+$/.test(searchVal)) {
        gameId = searchVal;
        document.getElementById('selectedGameId').value = gameId; // Set it for the form
    }

    if (!gameId) {
        alert("Please select a game from the search, or enter a valid numeric Game ID.");
        return;
    }

    if (currentFeaturedCount >= 16) {
        alert("Slider is full (max 16). Please remove a game first.");
        return;
    }

    const form = document.getElementById('addFeatureForm');
    const formData = new FormData(form);

    // Disable button to prevent double clicks
    const btn = document.getElementById('addFeatureBtn');
    btn.disabled = true;
    btn.textContent = "Adding...";

    fetch('process_featured.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert("Error: " + data.error);
                btn.disabled = false;
                btn.textContent = "Add to Slider";
            }
        });
}

// --- 3. REMOVE SINGLE FEATURED GAME ---
function removeFeature(featureId) {
    if (!confirm("Remove this game from the slider?")) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('feature_id', featureId);

    fetch('process_featured.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert("Error: " + data.error);
        });
}

// --- 4. MASS REMOVE LOGIC ---
function toggleMassRemoveButton() {
    const checked = document.querySelectorAll('.mass-select:checked');
    const massBtn = document.getElementById('massRemoveBtn');
    massBtn.disabled = checked.length === 0;
    massBtn.style.opacity = checked.length === 0 ? '0.5' : '1';
}

function massRemove() {
    const checked = document.querySelectorAll('.mass-select:checked');
    if (checked.length === 0) return;
    if (!confirm(`Remove ${checked.length} selected games from the slider?`)) return;

    const selectedIds = Array.from(checked).map(cb => cb.value);
    const formData = new FormData();
    formData.append('action', 'mass_delete');
    formData.append('feature_ids', JSON.stringify(selectedIds));

    fetch('process_featured.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) location.reload();
            else alert("Error: " + data.error);
        });
}

// --- 5. DRAG AND DROP & ORDER LOGIC ---
const grid = document.getElementById('featuredCardsGrid');
const saveBtn = document.getElementById('saveOrderBtn');

grid.addEventListener('dragstart', (e) => {
    const row = e.target.closest('.featured-row');
    if (row) row.classList.add('dragging');
});

grid.addEventListener('dragend', (e) => {
    const row = e.target.closest('.featured-row');
    if (row) {
        row.classList.remove('dragging');
        updateCardNumbers();
        saveBtn.disabled = false;
    }
});

grid.addEventListener('dragover', (e) => {
    e.preventDefault();
    const draggingRow = document.querySelector('.dragging');
    if (!draggingRow) return;
    
    const siblings = Array.from(grid.querySelectorAll('.featured-row:not(.dragging)'));
    let nextSibling = siblings.find(sibling => {
        const box = sibling.getBoundingClientRect();
        return e.clientY <= box.top + box.height / 2;
    });
    
    grid.insertBefore(draggingRow, nextSibling);
});

function updateCardNumbers() {
    const rows = grid.querySelectorAll('.featured-row');
    rows.forEach((row, index) => {
        row.querySelector('.card-number').textContent = index + 1;
    });
}

// Up/Down Arrows logic
function moveCardUp(button) {
    const row = button.closest('.featured-row');
    const prev = row.previousElementSibling;
    if (prev) {
        grid.insertBefore(row, prev);
        updateCardNumbers();
        saveBtn.disabled = false;
    }
}

function moveCardDown(button) {
    const row = button.closest('.featured-row');
    const next = row.nextElementSibling;
    if (next) {
        grid.insertBefore(next, row);
        updateCardNumbers();
        saveBtn.disabled = false;
    }
}

function saveNewOrder() {
    const rows = grid.querySelectorAll('.featured-row');
    const orderIds = Array.from(rows).map(row => row.dataset.gameId);

    const formData = new FormData();
    formData.append('order', JSON.stringify(orderIds));

    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    fetch('process_featured_order.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload(); 
            } else {
                alert("Error saving order: " + data.error);
                saveBtn.disabled = false;
                saveBtn.textContent = 'Save New Order';
            }
        });
}
</script>

<?php include 'includes/admin_footer.php'; ?>