<?php
// admin/games.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

if ($_SESSION['role_id'] == 3) {
    header("Location: index.php");
    exit;
}
$user_role = $_SESSION['role_id']; 

// --- SEARCH & PAGINATION ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50; 
$offset = ($page - 1) * $limit;

// --- SORTING LOGIC (WHITELISTED FOR SECURITY) ---
$allowed_sorts = [
    'id_desc' => 'id DESC',
    'id_asc' => 'id ASC',
    'title_asc' => 'title ASC',
    'title_desc' => 'title DESC',
    'score_desc' => 'metascore DESC',
    'score_asc' => 'metascore ASC',
    'date_desc' => 'release_date DESC',
    'date_asc' => 'release_date ASC'
];

$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id_desc';
$order_by_sql = $allowed_sorts[$sort_key];

// Build the query string for pagination links so we don't lose search/sort state
$query_params = [];
if ($search !== '') $query_params['search'] = $search;
if ($sort_key !== 'id_desc') $query_params['sort'] = $sort_key;
$query_str = !empty($query_params) ? "&" . http_build_query($query_params) : "";

// --- DATABASE QUERIES ---
if ($search !== '') {
    $count_stmt = $conn->prepare("SELECT COUNT(id) as total FROM games WHERE title LIKE CONCAT('%', ?, '%')");
    $count_stmt->bind_param("s", $search);
    $count_stmt->execute();
    $total_games = $count_stmt->get_result()->fetch_assoc()['total'];

    // Notice we inject $order_by_sql directly because it is strictly whitelisted above
    $stmt = $conn->prepare("SELECT id, title, metascore, release_date FROM games WHERE title LIKE CONCAT('%', ?, '%') ORDER BY $order_by_sql LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $search, $limit, $offset);
} else {
    $count_query = $conn->query("SELECT COUNT(id) as total FROM games");
    $total_games = $count_query->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT id, title, metascore, release_date FROM games ORDER BY $order_by_sql LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$games_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total_pages = ceil($total_games / $limit);

include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>
<main class="admin-content">
    
    <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Manage Games (<?php echo number_format($total_games); ?> total)</h2>
        
        <?php if ($user_role == 1 || $user_role == 2): ?>
            <button class="btn-sm btn-approve" style="font-size: 14px; padding: 10px 20px;" onclick="openGameModal()">+ Add New Game</button>
        <?php endif; ?>
    </div>
<div style="margin-bottom: 20px; background: white; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between;">
        <form method="GET" action="games.php" style="display: flex; gap: 10px; flex: 1;">
            <input type="text" name="search" placeholder="Search by game title..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_key); ?>"> 
            <button type="submit" class="btn-login" style="margin-top:0; width:auto; padding: 10px 20px;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="games.php" style="padding: 10px 20px; background: #95a5a6; color: white; text-decoration: none; border-radius: 4px; display:flex; align-items:center;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>
                        <a href="?sort=<?php echo $sort_key == 'id_desc' ? 'id_asc' : 'id_desc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="color:#2c3e50; text-decoration:none; font-weight:bold;">
                            ID <?php echo $sort_key == 'id_asc' ? '▲' : ($sort_key == 'id_desc' ? '▼' : '↕'); ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=<?php echo $sort_key == 'title_asc' ? 'title_desc' : 'title_asc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="color:#2c3e50; text-decoration:none; font-weight:bold;">
                            Game Title <?php echo $sort_key == 'title_asc' ? '▲' : ($sort_key == 'title_desc' ? '▼' : '↕'); ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=<?php echo $sort_key == 'date_desc' ? 'date_asc' : 'date_desc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="color:#2c3e50; text-decoration:none; font-weight:bold;">
                            Release Date <?php echo $sort_key == 'date_asc' ? '▲' : ($sort_key == 'date_desc' ? '▼' : '↕'); ?>
                        </a>
                    </th>
                    <th>
                        <a href="?sort=<?php echo $sort_key == 'score_desc' ? 'score_asc' : 'score_desc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="color:#2c3e50; text-decoration:none; font-weight:bold;">
                            Metascore <?php echo $sort_key == 'score_asc' ? '▲' : ($sort_key == 'score_desc' ? '▼' : '↕'); ?>
                        </a>
                    </th>
                    <th style="color:#2c3e50;">Actions</th>
                </tr>
            </thead>
          <tbody>
                <?php if (empty($games_result)): ?>
                    <tr><td colspan="5" style="text-align: center;">No games found.</td></tr>
                <?php else: ?>
                    <?php foreach ($games_result as $game): ?>
                        <tr>
                            <td>#<?php echo $game['id']; ?></td>
                            
                            <td>
                                <a href="../game-details.php?id=<?php echo $game['id']; ?>" target="_blank" style="color: #2c3e50; text-decoration: none;">
                                    <strong><?php echo htmlspecialchars($game['title']); ?></strong>
                                    <span style="font-size: 12px; color: #7f8c8d; margin-left: 5px;">&#8599;</span>
                                </a>
                            </td>
                            
                            <td><?php echo $game['release_date'] ? date('M d, Y', strtotime($game['release_date'])) : 'TBD'; ?></td>
                            
                            <td>
                                <?php 
                                    $score = $game['metascore'];
                                    // Use specific hex colors to match your theme
                                    if ($score >= 75) $color = "#00ce7a"; 
                                    elseif ($score >= 50) $color = "#d4a822"; 
                                    elseif ($score > 0) $color = "#ff0000"; 
                                    else $color = "#999999";
                                ?>
                                <span style="color: <?php echo $color; ?>; font-weight: bold; font-size: 16px;">
                                    <?php echo $score > 0 ? $score : 'N/A'; ?>
                                </span>
                            </td>
                            
                            <td>
                                <button class="btn-sm btn-edit" onclick="openGameModal(<?php echo $game['id']; ?>)">Edit</button>
                                
                                <?php if ($user_role == 1): ?>
                                    <form method="POST" action="delete_game.php" onsubmit="return confirm('WARNING: Delete <?php echo addslashes(htmlspecialchars($game['title'])); ?>?');" style="display:inline;">
                                        <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" class="btn-sm btn-delete">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
            <?php 
                // Properly build the query string to preserve BOTH search and sort
                $query_params = [];
                if ($search !== '') $query_params['search'] = $search;
                if (isset($_GET['sort'])) $query_params['sort'] = $_GET['sort'];
                
                $query_str = !empty($query_params) ? "&" . http_build_query($query_params) : ""; 
            ?>
            
            <?php if ($page > 1): ?>
                <a href="games.php?page=<?php echo $page - 1 . $query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">&laquo; Prev</a>
            <?php endif; ?>

            <span style="padding: 8px 12px; background: #2c3e50; color: white; border-radius: 4px;">Page <?php echo $page; ?> of <?php echo number_format($total_pages); ?></span>

            <?php if ($page < $total_pages): ?>
                <a href="games.php?page=<?php echo $page + 1 . $query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main> <?php 
// 1. MUST include the modal first so the HTML and JavaScript load into the body
require_once 'includes/game_modal.php'; 

// 2. MUST include the footer last because it contains the closing </body> and </html> tags
require_once 'includes/footer.php'; 
?>