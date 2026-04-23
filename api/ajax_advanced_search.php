<?php
// api/ajax_advanced_search.php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$search_text = $data['search_text'] ?? '';

// Bulletproof Data Sanitization: Force all incoming array values to be strict integers.
// This prevents SQL injection and completely eliminates the need for complex dynamic binding.
$genres = isset($data['genres']) && is_array($data['genres']) ? array_map('intval', $data['genres']) : [];
$platforms = isset($data['platforms']) && is_array($data['platforms']) ? array_map('intval', $data['platforms']) : [];

$logic = $data['logic'] ?? 'OR'; 
$sort = $data['sort'] ?? 'rating-desc';
$hide_tbd = isset($data['hide_tbd']) ? filter_var($data['hide_tbd'], FILTER_VALIDATE_BOOLEAN) : false;
$page = (int)($data['page'] ?? 1);
$limit = 24; 
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"]; 

// 1. Text Search (%LIKE%)
if (!empty($search_text)) {
    $where_clauses[] = "g.title LIKE ?";
}

// 2. Hide TBD (Metascore > 0)
if ($hide_tbd) {
    $where_clauses[] = "(g.metascore IS NOT NULL AND g.metascore > 0)";
}

// 3. Genre & Platform Filters
if (!empty($genres) || !empty($platforms)) {
    $filter_conditions = [];

    if ($logic === 'OR') {
        if (!empty($genres)) {
            $g_list = implode(',', $genres);
            $filter_conditions[] = "g.id IN (SELECT game_id FROM game_genres WHERE genre_id IN ($g_list))";
        }
        if (!empty($platforms)) {
            $p_list = implode(',', $platforms);
            $filter_conditions[] = "g.id IN (SELECT game_id FROM game_platforms WHERE platform_id IN ($p_list))";
        }
        $where_clauses[] = "(" . implode(" OR ", $filter_conditions) . ")";
        
    } else {
        // AND Logic
        if (!empty($genres)) {
            $g_list = implode(',', $genres);
            $count = count($genres);
            $where_clauses[] = "g.id IN (SELECT game_id FROM game_genres WHERE genre_id IN ($g_list) GROUP BY game_id HAVING COUNT(DISTINCT genre_id) = $count)";
        }
        if (!empty($platforms)) {
            $p_list = implode(',', $platforms);
            $count = count($platforms);
            $where_clauses[] = "g.id IN (SELECT game_id FROM game_platforms WHERE platform_id IN ($p_list) GROUP BY game_id HAVING COUNT(DISTINCT platform_id) = $count)";
        }
    }
}

// 4. Sorting
$order_by = "g.metascore DESC, g.id DESC"; 
switch ($sort) {
    case 'rating-desc': $order_by = "g.metascore DESC, g.id DESC"; break;
    case 'rating-asc':  $order_by = "g.metascore ASC, g.id DESC"; break;
    case 'date-desc':   $order_by = "g.release_date DESC, g.id DESC"; break;
    case 'date-asc':    $order_by = "g.release_date ASC, g.id DESC"; break;
}

$where_sql = implode(" AND ", $where_clauses);

// 5. Final SQL Query (With strict GROUP BY columns to fix MySQL Strict Mode)
$sql = "
    SELECT g.id, g.title, g.cover_image, g.metascore, 
           GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names
    FROM games g
    LEFT JOIN game_platforms gp ON g.id = gp.game_id
    LEFT JOIN platforms p ON gp.platform_id = p.id
    WHERE $where_sql
    GROUP BY g.id, g.title, g.cover_image, g.metascore
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";

try {
    $stmt = $conn->prepare($sql);
    
// Explicit, old-school parameter binding that works on 100% of PHP versions
    if (!empty($search_text)) {
        $search_param = '%' . $search_text . '%'; // <-- THIS LINE WAS MISSING
        $stmt->bind_param("sii", $search_param, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'games' => $results, 'has_more' => count($results) === $limit]);
} catch (Exception $e) {
    // If it fails again, check your server's error log!
    error_log("Advanced Search Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database query failed. Check server logs.']);
}
?>