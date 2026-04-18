<?php
// admin/logs.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

if ($_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit;
}

// --- PAGINATION & TAB MEMORY ---
$limit = 50; // Logs per page
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'adminTab';

// Admin Pagination
$admin_page = isset($_GET['admin_page']) && is_numeric($_GET['admin_page']) ? (int)$_GET['admin_page'] : 1;
$admin_offset = ($admin_page - 1) * $limit;

// Error Pagination
$error_page = isset($_GET['error_page']) && is_numeric($_GET['error_page']) ? (int)$_GET['error_page'] : 1;
$error_offset = ($error_page - 1) * $limit;


// --- 1. ADMIN LOGS QUERY (With Filters) ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action_filter = isset($_GET['action_filter']) ? trim($_GET['action_filter']) : '';

$admin_base_query = "FROM admin_logs WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $admin_base_query .= " AND (admin_username LIKE ? OR details LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
if ($action_filter !== '') {
    $admin_base_query .= " AND action_type = ?";
    $params[] = $action_filter;
    $types .= "s";
}

// Get Total Admin Logs for Pagination
$admin_count_stmt = $conn->prepare("SELECT COUNT(id) as total " . $admin_base_query);
if (!empty($params)) $admin_count_stmt->bind_param($types, ...$params);
$admin_count_stmt->execute();
$total_admin_logs = $admin_count_stmt->get_result()->fetch_assoc()['total'];
$admin_total_pages = ceil($total_admin_logs / $limit);

// Fetch Actual Admin Logs
$admin_query = "SELECT * " . $admin_base_query . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $admin_offset;
$types .= "ii";

$stmt = $conn->prepare($admin_query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$admin_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unique action types for the dropdown filter
$available_actions = $conn->query("SELECT DISTINCT action_type FROM admin_logs ORDER BY action_type ASC")->fetch_all(MYSQLI_ASSOC);


// --- 2. SYSTEM ERRORS QUERY ---
$error_count_query = $conn->query("SELECT COUNT(id) as total FROM system_logs");
$total_errors = $error_count_query->fetch_assoc()['total'];
$error_total_pages = ceil($total_errors / $limit);

$error_stmt = $conn->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT ? OFFSET ?");
$error_stmt->bind_param("ii", $limit, $error_offset);
$error_stmt->execute();
$system_logs = $error_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


// --- PRESERVE URL PARAMS FOR BUTTONS ---
$query_params = [];
if ($search !== '') $query_params['search'] = $search;
if ($action_filter !== '') $query_params['action_filter'] = $action_filter;
if ($error_page > 1) $query_params['error_page'] = $error_page;
$admin_query_str = !empty($query_params) ? "&" . http_build_query($query_params) : "";

$err_query_params = [];
if ($admin_page > 1) $err_query_params['admin_page'] = $admin_page;
if ($search !== '') $err_query_params['search'] = $search;
if ($action_filter !== '') $err_query_params['action_filter'] = $action_filter;
$error_query_str = !empty($err_query_params) ? "&" . http_build_query($err_query_params) : "";

include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>

<main class="admin-content">
    <div class="admin-header">
        <h2>System Logs</h2>
    </div>

    <div style="margin-bottom: 20px; border-bottom: 2px solid #ccc;">
        <button class="tab-btn <?php echo $active_tab === 'adminTab' ? 'active' : ''; ?>" onclick="switchTab('adminTab', this)" style="padding: 10px 20px; font-weight: bold; background: <?php echo $active_tab === 'adminTab' ? '#2c3e50' : '#ecf0f1'; ?>; color: <?php echo $active_tab === 'adminTab' ? 'white' : '#333'; ?>; border: none; cursor: pointer; border-radius: 4px 4px 0 0;">Admin Actions</button>
        <button class="tab-btn <?php echo $active_tab === 'errorTab' ? 'active' : ''; ?>" onclick="switchTab('errorTab', this)" style="padding: 10px 20px; font-weight: bold; background: <?php echo $active_tab === 'errorTab' ? '#2c3e50' : '#ecf0f1'; ?>; color: <?php echo $active_tab === 'errorTab' ? 'white' : '#333'; ?>; border: none; cursor: pointer; border-radius: 4px 4px 0 0;">System Errors</button>
    </div>

    <div id="adminTab" class="table-container log-tab" style="display: <?php echo $active_tab === 'adminTab' ? 'block' : 'none'; ?>;">
        
        <div style="margin-bottom: 15px; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
            <form method="GET" action="logs.php" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="tab" value="adminTab">
                <input type="text" name="search" placeholder="Search admin or details..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 2; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                
                <select name="action_filter" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    <option value="">All Action Types</option>
                    <?php foreach ($available_actions as $action): ?>
                        <option value="<?php echo $action['action_type']; ?>" <?php echo ($action_filter == $action['action_type']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($action['action_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn-sm btn-approve" style="margin: 0;">Filter</button>
                <?php if ($search !== '' || $action_filter !== ''): ?>
                    <a href="logs.php?tab=adminTab" class="btn-sm" style="background: #95a5a6; color: white; text-decoration: none; padding: 9px 15px;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <table style="font-size: 14px;">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Admin</th>
                    <th>Action Type</th>
                    <th>Detailed Log</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admin_logs)): ?>
                    <tr><td colspan="4" style="text-align: center;">No admin actions matched your filter.</td></tr>
                <?php else: ?>
                    <?php foreach ($admin_logs as $log): ?>
                        <tr>
                            <td style="color: #7f8c8d;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['admin_username']); ?></strong></td>
                            <td>
                                <span style="background: #34495e; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                                    <?php echo htmlspecialchars($log['action_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($admin_total_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
                <?php if ($admin_page > 1): ?>
                    <a href="logs.php?tab=adminTab&admin_page=<?php echo $admin_page - 1 . $admin_query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">&laquo; Prev</a>
                <?php endif; ?>
                <span style="padding: 8px 12px; background: #2c3e50; color: white; border-radius: 4px;">Page <?php echo $admin_page; ?> of <?php echo $admin_total_pages; ?></span>
                <?php if ($admin_page < $admin_total_pages): ?>
                    <a href="logs.php?tab=adminTab&admin_page=<?php echo $admin_page + 1 . $admin_query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="errorTab" class="table-container log-tab" style="display: <?php echo $active_tab === 'errorTab' ? 'block' : 'none'; ?>;">
        <table style="font-size: 14px;">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($system_logs)): ?>
                    <tr><td colspan="4" style="text-align: center; color: green; font-weight: bold;">0 System Errors Logged!</td></tr>
                <?php else: ?>
                    <?php foreach ($system_logs as $log): ?>
                        <tr>
                            <td style="color: #7f8c8d;"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <span style="background: #e74c3c; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                                    <?php echo htmlspecialchars($log['error_type']); ?>
                                </span>
                            </td>
                            <td style="color: #c0392b; font-weight: bold;"><?php echo htmlspecialchars($log['error_message']); ?></td>
                            <td style="font-family: monospace; font-size: 13px; color: #555;">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($error_total_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
                <?php if ($error_page > 1): ?>
                    <a href="logs.php?tab=errorTab&error_page=<?php echo $error_page - 1 . $error_query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">&laquo; Prev</a>
                <?php endif; ?>
                <span style="padding: 8px 12px; background: #2c3e50; color: white; border-radius: 4px;">Page <?php echo $error_page; ?> of <?php echo $error_total_pages; ?></span>
                <?php if ($error_page < $error_total_pages): ?>
                    <a href="logs.php?tab=errorTab&error_page=<?php echo $error_page + 1 . $error_query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</main>

<script>
function switchTab(tabId, element) {
    // Update UI visually
    document.querySelectorAll('.log-tab').forEach(tab => tab.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.style.background = '#ecf0f1';
        btn.style.color = '#333';
    });
    document.getElementById(tabId).style.display = 'block';
    element.style.background = '#2c3e50';
    element.style.color = 'white';

    // Update the URL so if they refresh, it stays on this tab
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.pushState({}, '', url);
}
</script>

<?php include 'includes/admin_footer.php'; ?>