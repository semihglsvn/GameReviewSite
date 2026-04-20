<?php
// admin/menus.php
require_once 'includes/auth.php';
require_once '../config/db.php';

// STRICT SECURITY: ONLY Super Admins (Role 1)
if ($_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Fetch current menus ordered by sort_order
$menus_query = $conn->query("SELECT * FROM menus ORDER BY sort_order ASC");
$menus = $menus_query->fetch_all(MYSQLI_ASSOC);

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>

<main class="admin-content">
    <div class="admin-header" style="margin-bottom: 20px;">
        <h2>Navigation Menus</h2>
        <p style="color: #7f8c8d; margin-top: 5px;">Manage the top navigation links for the public website.</p>
    </div>

    <div class="settings-grid">
        
        <div class="settings-column">
            <div class="settings-card">
                <h3>Add New Link</h3>
                <hr>
                <form id="addMenuForm" onsubmit="addMenu(event)">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>Display Title</label>
                        <input type="text" name="title" required placeholder="e.g., Top Rated">
                    </div>
                    
                    <div class="form-group">
                        <label>URL / File Path</label>
                        <input type="text" name="url" required placeholder="e.g., top-rated.php or https://...">
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active" id="isActive" value="1" checked style="width: auto;">
                        <label for="isActive" style="margin: 0; cursor: pointer;">Visible to Public</label>
                    </div>

                    <button type="submit" id="addBtn" class="btn-approve" style="width: 100%; margin-top: 15px;">Add to Navigation</button>
                </form>
            </div>
        </div>

        <div class="settings-column">
            <div class="settings-card">
                <h3>Current Navigation</h3>
                <hr>
                
                <form id="updateOrderForm" onsubmit="updateOrder(event)">
                    <input type="hidden" name="action" value="update_order">
                    
                    <?php if (count($menus) === 0): ?>
                        <p style="color: #95a5a6; text-align: center;">No menus found.</p>
                    <?php else: ?>
                        <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                            <?php foreach ($menus as $menu): ?>
                                <li style="display: flex; align-items: center; background: #f8f9fa; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; gap: 15px;">
                                    
                                    <input type="number" name="order[<?php echo $menu['id']; ?>]" value="<?php echo $menu['sort_order']; ?>" style="width: 60px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: center;" min="1">
                                    
                                    <div style="flex-grow: 1;">
                                        <strong><?php echo htmlspecialchars($menu['title']); ?></strong><br>
                                        <small style="color: #7f8c8d;"><?php echo htmlspecialchars($menu['url']); ?></small>
                                    </div>
                                    
                                    <button type="button" onclick="toggleMenu(<?php echo $menu['id']; ?>)" class="btn-sm" style="background: <?php echo $menu['is_active'] ? '#2ecc71' : '#e74c3c'; ?>; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                        <?php echo $menu['is_active'] ? 'Visible' : 'Hidden'; ?>
                                    </button>

                                    <button type="button" onclick="deleteMenu(<?php echo $menu['id']; ?>)" class="btn-sm" style="background: transparent; color: #e74c3c; border: 1px solid #e74c3c; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                        Delete
                                    </button>

                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <button type="submit" id="orderBtn" class="btn-login" style="width: 100%; margin-top: 20px;">Save Display Order</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

    </div>
</main>

<style>
    .settings-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; }
    @media (max-width: 850px) { .settings-grid { grid-template-columns: 1fr; } }
    .settings-column { display: flex; flex-direction: column; gap: 20px; }
    .settings-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .settings-card h3 { color: #2c3e50; margin-bottom: 10px; }
    .settings-card hr { border: 0; border-top: 1px solid #eee; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #34495e; font-size: 14px; }
    .form-group input[type="text"], .form-group input[type="number"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
</style>

<script>
function apiRequest(formData, btnId, loadingText, originalText) {
    const btn = btnId ? document.getElementById(btnId) : null;
    if (btn) { btn.disabled = true; btn.textContent = loadingText; }

    fetch('process_menus.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
            if (btn) { btn.disabled = false; btn.textContent = originalText; }
        }
    }).catch(err => {
        alert('Network Error');
        if (btn) { btn.disabled = false; btn.textContent = originalText; }
    });
}

function addMenu(e) {
    e.preventDefault();
    apiRequest(new FormData(e.target), 'addBtn', 'Adding...', 'Add to Navigation');
}

function updateOrder(e) {
    e.preventDefault();
    apiRequest(new FormData(e.target), 'orderBtn', 'Saving...', 'Save Display Order');
}

function toggleMenu(id) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    apiRequest(fd, null, null, null);
}

function deleteMenu(id) {
    if (confirm("Are you sure you want to delete this link?")) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        apiRequest(fd, null, null, null);
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>