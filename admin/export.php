<?php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

// Only Admins (1) and Editors (2)
$user_role = $_SESSION['role_id'] ?? 5;
if ($user_role != 1 && $user_role != 2) {
    header("Location: index.php");
    exit;
}

include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>

<main class="admin-content">
    <div class="admin-header">
        <h2>Veri Dışa Aktar (Export Data)</h2>
    </div>

    <div style="background: white; padding: 30px; border-radius: 8px; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <p style="color: #7f8c8d; margin-bottom: 20px;">Sistem verilerini analiz veya yedekleme amacıyla CSV formatında indirebilirsiniz.</p>
        
        <form action="export_csv.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label style="font-weight: bold; color: #2c3e50;">Dışa Aktarılacak Veri (Select Data):</label>
                <select name="export_type" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px;">
                    <option value="games">Oyunlar (Games)</option>
                    <option value="users">Kullanıcılar (Users)</option>
                    <option value="reports">Raporlanan İncelemeler (Reported Reviews)</option>
                </select>
            </div>

            <button type="submit" class="btn-login" style="width: 100%; padding: 12px; font-size: 16px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                CSV Olarak İndir
            </button>
        </form>
    </div>
</main>

<?php include 'includes/admin_footer.php'; ?>