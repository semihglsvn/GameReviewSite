<?php
// admin/users.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

$user_role = $_SESSION['role_id']; // 1=Admin, 2=Editor, 3=Mod

// --- SEARCH, SORT & PAGINATION ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50; 
$offset = ($page - 1) * $limit;

$allowed_sorts = [
    'id_desc' => 'id DESC', 'id_asc' => 'id ASC',
    'user_asc' => 'username ASC', 'user_desc' => 'username DESC',
    'role_asc' => 'role_id ASC', 'role_desc' => 'role_id DESC'
];
$sort_key = isset($_GET['sort']) && array_key_exists($_GET['sort'], $allowed_sorts) ? $_GET['sort'] : 'id_desc';
$order_by_sql = $allowed_sorts[$sort_key];

$query_params = [];
if ($search !== '') $query_params['search'] = $search;
if ($sort_key !== 'id_desc') $query_params['sort'] = $sort_key;
$query_str = !empty($query_params) ? "&" . http_build_query($query_params) : "";

// --- DATABASE QUERIES ---
if ($search !== '') {
    $count_stmt = $conn->prepare("SELECT COUNT(id) as total FROM users WHERE username LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%')");
    $count_stmt->bind_param("ss", $search, $search);
    $count_stmt->execute();
    $total_users = $count_stmt->get_result()->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT id, username, email, role_id, is_banned, shadowbanned_reports, false_report_strikes FROM users WHERE username LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%') ORDER BY $order_by_sql LIMIT ? OFFSET ?");
    $stmt->bind_param("ssii", $search, $search, $limit, $offset);
} else {
    $count_query = $conn->query("SELECT COUNT(id) as total FROM users");
    $total_users = $count_query->fetch_assoc()['total'];

    $stmt = $conn->prepare("SELECT id, username, email, role_id, is_banned, shadowbanned_reports, false_report_strikes FROM users ORDER BY $order_by_sql LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$users_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$total_pages = ceil($total_users / $limit);

// Role Names Mapping
$role_names = [1 => 'Admin', 2 => 'Editor', 3 => 'Moderator', 4 => 'Critic', 5 => 'User'];

include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>

<main class="admin-content">
    <div class="admin-header">
        <h2>Manage Users (<?php echo number_format($total_users); ?> total)</h2>
    </div>

    <div style="margin-bottom: 20px; background: white; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between;">
        <form method="GET" action="users.php" style="display: flex; gap: 10px; flex: 1;">
            <input type="text" name="search" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_key); ?>"> 
            <button type="submit" class="btn-login" style="margin-top:0; width:auto; padding: 10px 20px;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="users.php" style="padding: 10px 20px; background: #95a5a6; color: white; text-decoration: none; border-radius: 4px; display:flex; align-items:center;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <?php if ($user_role == 1): ?> <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAll" onclick="toggleAllCheckboxes(this)"></th>
                    <?php endif; ?>
                    <th><a href="?sort=<?php echo $sort_key == 'id_desc' ? 'id_asc' : 'id_desc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="color:#2c3e50; text-decoration:none; font-weight:bold;">ID <?php echo $sort_key == 'id_asc' ? '▲' : ($sort_key == 'id_desc' ? '▼' : '↕'); ?></a></th>
                    <th><a href="?sort=<?php echo $sort_key == 'user_asc' ? 'user_desc' : 'user_asc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="color:#2c3e50; text-decoration:none; font-weight:bold;">User / Email <?php echo $sort_key == 'user_asc' ? '▲' : ($sort_key == 'user_desc' ? '▼' : '↕'); ?></a></th>
                    <th><a href="?sort=<?php echo $sort_key == 'role_asc' ? 'role_desc' : 'role_asc'; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" style="color:#2c3e50; text-decoration:none; font-weight:bold;">Role <?php echo $sort_key == 'role_asc' ? '▲' : ($sort_key == 'role_desc' ? '▼' : '↕'); ?></a></th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users_result)): ?>
                    <tr><td colspan="6" style="text-align: center;">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users_result as $u): ?>
                        <tr id="user-row-<?php echo $u['id']; ?>">
                            <?php if ($user_role == 1): ?>
                                <td style="text-align: center;">
                                    <?php if ($u['id'] != $_SESSION['user_id'] && $u['role_id'] != 1): ?>
                                        <input type="checkbox" class="user-checkbox" value="<?php echo $u['id']; ?>">
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            
                            <td>#<?php echo $u['id']; ?></td>
                            <td>
                                <strong><a href="../profile.php?id=<?php echo $u['id']; ?>" target="_blank" style="color:#2c3e50; text-decoration:none;"><?php echo htmlspecialchars($u['username']); ?> &#8599;</a></strong><br>
                                <span style="font-size:12px; color:#7f8c8d;"><?php echo htmlspecialchars($u['email']); ?></span>
                            </td>
                            <td>
                                <span style="background:#ecf0f1; padding:3px 8px; border-radius:4px; font-size:12px; font-weight:bold;">
                                    <?php echo $role_names[$u['role_id']] ?? 'Unknown'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['is_banned']): ?>
                                    <span style="color:#e74c3c; font-weight:bold;">Banned</span>
                                <?php elseif ($u['shadowbanned_reports']): ?>
                                    <span style="color:#d35400; font-weight:bold;">Shadowbanned (Reports)</span>
                                <?php else: ?>
                                    <span style="color:#27ae60; font-weight:bold;">Active</span>
                                <?php endif; ?>
                                <?php if ($u['false_report_strikes'] > 0): ?>
                                    <div style="font-size:11px; color:#7f8c8d;"><?php echo $u['false_report_strikes']; ?> Strikes</div>
                                <?php endif; ?>
                            </td>
<td>
                                <?php if ($u['id'] != $_SESSION['user_id']): // Don't let them edit themselves here ?>
                                    
                                    <?php if ($user_role == 1 || ($user_role == 2 && !in_array($u['role_id'], [1, 2, 3]))): ?>
                                        <button class="btn-sm btn-edit" onclick="openRoleModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>', <?php echo $u['role_id']; ?>)">Edit Role</button>
                                    <?php endif; ?>

                                    <?php if ($user_role == 1 || (($user_role == 2 || $user_role == 3) && !in_array($u['role_id'], [1, 2, 3]))): ?>
                                        <?php if ($u['is_banned']): ?>
                                            <button class="btn-sm btn-approve" onclick="processUserAction(<?php echo $u['id']; ?>, 'unban')">Unban</button>
                                        <?php else: ?>
                                            <button class="btn-sm btn-delete" onclick="openBanModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username']); ?>')">Ban</button>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($user_role == 1 && !empty($users_result)): ?>
            <div style="margin-top: 15px; padding: 10px; background: #ecf0f1; border-radius: 4px; display: flex; align-items: center; gap: 15px;">
                <span style="font-weight: bold;">Mass Action:</span>
                <button class="btn-sm btn-delete" onclick="massDeleteUsers()">Delete Selected Users</button>
                <span style="font-size: 12px; color: #e74c3c; font-weight:bold;">WARNING: This permanently deletes the user and ALL their reviews!</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 5px; margin-top: 20px;">
            <?php if ($page > 1): ?>
                <a href="users.php?page=<?php echo $page - 1 . $query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">&laquo; Prev</a>
            <?php endif; ?>
            <span style="padding: 8px 12px; background: #2c3e50; color: white; border-radius: 4px;">Page <?php echo $page; ?> of <?php echo number_format($total_pages); ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="users.php?page=<?php echo $page + 1 . $query_str; ?>" style="padding: 8px 12px; background: #ddd; text-decoration: none; border-radius: 4px; color: black;">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<div id="roleModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeRoleModal()">&times;</span>
        <h3 style="margin-top:0;">Change Role: <span id="roleUsername" style="color:#2c3e50;"></span></h3>
        <form id="roleForm">
            <input type="hidden" id="role_user_id">
            <div style="margin-bottom: 15px;">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">Select New Role:</label>
                <select id="new_role_id" style="width:100%; padding:8px; box-sizing:border-box;">
                    <?php if ($user_role == 1): ?>
                        <option value="1">Admin</option>
                        <option value="2">Editor</option>
                        <option value="3">Moderator</option>
                    <?php endif; ?>
                    <option value="4">Critic</option>
                    <option value="5">User</option>
                </select>
            </div>
            
            <?php if ($user_role == 1 || $user_role == 3): ?>
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Reset Reporting Strikes?</label>
                    <label><input type="checkbox" id="reset_strikes" value="1"> Reset strikes to 0 and remove shadowban</label>
                </div>
            <?php endif; ?>

            <div id="role-feedback" style="margin-bottom:10px; font-weight:bold;"></div>
            <div class="modal-actions">
                <button type="button" class="btn-sm" onclick="closeRoleModal()" style="background-color:#95a5a6;">Cancel</button>
                <button type="button" class="btn-sm btn-approve" onclick="submitRoleChange()">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="banUserModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeBanModal()">&times;</span>
        <h3 style="margin-top:0; color:#e74c3c;">Ban User: <span id="banTargetName"></span></h3>
        <form id="banUserForm">
            <input type="hidden" id="ban_user_id">
            <div style="margin-bottom: 10px;"><label><input type="radio" name="ban_duration" value="24h" checked> 24 Hours</label></div>
            <div style="margin-bottom: 10px;"><label><input type="radio" name="ban_duration" value="7d"> 7 Days</label></div>
            <div style="margin-bottom: 15px;"><label><input type="radio" name="ban_duration" value="perm"> <span style="color:red; font-weight:bold;">Permanent Ban</span></label></div>
            <div id="ban-user-feedback" style="margin-bottom:10px; font-weight:bold;"></div>
            <div class="modal-actions">
                <button type="button" class="btn-sm" onclick="closeBanModal()" style="background-color:#95a5a6;">Cancel</button>
                <button type="button" class="btn-sm btn-delete" onclick="submitBanUser()">Execute Ban</button>
            </div>
        </form>
    </div>
</div>

<script>
// Checkbox Logic
function toggleAllCheckboxes(masterCheckbox) {
    let checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
}

// --- SHIFT-CLICK MASS SELECTION FOR USERS ---
let lastCheckedUser = null;
const userCheckboxes = document.querySelectorAll('.user-checkbox');

userCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('click', function(e) {
        let inBetween = false;

        // If the Shift key is held down AND we have previously clicked a box
        if (e.shiftKey && lastCheckedUser) {
            userCheckboxes.forEach(cb => {
                // If we hit either the box we just clicked OR the box we clicked last time
                // we toggle our "inBetween" tracker on or off
                if (cb === this || cb === lastCheckedUser) {
                    inBetween = !inBetween;
                }
                
                // If we are currently "in between" the two clicks, check/uncheck the box
                // to match the exact state of the box we just shift-clicked
                if (inBetween || cb === this || cb === lastCheckedUser) {
                    cb.checked = this.checked; 
                }
            });
        }
        
        // Remember this checkbox as the last one clicked for next time
        lastCheckedUser = this;
    });
});

// Role Modal
const roleModal = document.getElementById("roleModal");
function openRoleModal(id, username, currentRole) {
    document.getElementById("role_user_id").value = id;
    document.getElementById("roleUsername").innerText = username;
    document.getElementById("new_role_id").value = currentRole;
    document.getElementById("role-feedback").innerText = "";
    if (document.getElementById("reset_strikes")) document.getElementById("reset_strikes").checked = false;
    roleModal.style.display = "block";
}
function closeRoleModal() { roleModal.style.display = "none"; }
function submitRoleChange() {
    let userId = document.getElementById("role_user_id").value;
    let newRole = document.getElementById("new_role_id").value;
    let resetStrikes = document.getElementById("reset_strikes") && document.getElementById("reset_strikes").checked ? 1 : 0;
    
    let fd = new FormData();
    fd.append('action', 'change_role');
    fd.append('user_id', userId);
    fd.append('role_id', newRole);
    fd.append('reset_strikes', resetStrikes);
    
    sendAjax(fd, 'role-feedback');
}

// Ban Modal
const banModal = document.getElementById("banUserModal");
function openBanModal(id, username) {
    document.getElementById("ban_user_id").value = id;
    document.getElementById("banTargetName").innerText = username;
    document.getElementById("ban-user-feedback").innerText = "";
    banModal.style.display = "block";
}
function closeBanModal() { banModal.style.display = "none"; }
function submitBanUser() {
    let userId = document.getElementById("ban_user_id").value;
    let duration = document.querySelector('input[name="ban_duration"]:checked').value;
    
    let fd = new FormData();
    fd.append('action', 'ban');
    fd.append('user_id', userId);
    fd.append('duration', duration);
    
    sendAjax(fd, 'ban-user-feedback');
}

// Quick Actions (Unban)
function processUserAction(userId, action) {
    if (action === 'unban' && !confirm("Are you sure you want to unban this user?")) return;
    let fd = new FormData();
    fd.append('action', action);
    fd.append('user_id', userId);
    sendAjax(fd, null);
}

// Mass Delete (Rule 16)
function massDeleteUsers() {
    let selectedIds = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    if (selectedIds.length === 0) { alert("Please select at least one user."); return; }
    if (!confirm(`CRITICAL WARNING: Are you sure you want to permanently delete these ${selectedIds.length} users and all their reviews?`)) return;

    let fd = new FormData();
    fd.append('action', 'mass_delete');
    fd.append('user_ids', JSON.stringify(selectedIds));
    sendAjax(fd, null);
}

// Universal AJAX Handler
function sendAjax(formData, feedbackElementId) {
    fetch('process_user.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            if(feedbackElementId) {
                document.getElementById(feedbackElementId).style.color = "green";
                document.getElementById(feedbackElementId).innerText = "Success! Refreshing...";
            }
            setTimeout(() => { location.reload(); }, 800);
        } else {
            if(feedbackElementId) {
                document.getElementById(feedbackElementId).style.color = "red";
                document.getElementById(feedbackElementId).innerText = data.error;
            } else {
                alert("Error: " + data.error);
            }
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>