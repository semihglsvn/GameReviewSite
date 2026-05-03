<?php
require_once 'includes/auth.php';
require_once '../config/db.php';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';

$stmt = $conn->prepare("SELECT username, email, role_id FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Determine role name for display
$role_name = "Moderator";
if ($admin['role_id'] == 1) $role_name = "Admin";
if ($admin['role_id'] == 2) $role_name = "Editor";
?>

<main class="admin-content">
    <div class="admin-header">
        <h2>Profilim (My Profile)</h2>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; background-color: #d4edda; color: #155724; border-radius: 4px; border: 1px solid #c3e6cb;">
            <?= htmlspecialchars($_SESSION['message']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; background-color: #f8d7da; color: #721c24; border-radius: 4px; border: 1px solid #f5c6cb;">
            <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- Left Card: Profile Details -->
        <div style="flex: 1; min-width: 300px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 80px; height: 80px; background: #2c3e50; color: white; font-size: 32px; font-weight: bold; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <?= strtoupper(substr($admin['username'], 0, 1)) ?>
                </div>
                <h3 style="margin: 10px 0 5px 0; color: #2c3e50;"><?= htmlspecialchars($admin['username']) ?></h3>
                <span style="background: #ecf0f1; padding: 4px 10px; border-radius: 20px; font-size: 12px; color: #7f8c8d;"><?= $role_name ?></span>
            </div>
            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
            
            <form action="process_profile.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="action" value="update_info">
                <div>
                    <label style="font-weight: bold; color: #2c3e50;">Kullanıcı Adı:</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px;">
                </div>
                <div>
                    <label style="font-weight: bold; color: #2c3e50;">E-posta:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px;">
                </div>
                <button type="submit" style="padding: 10px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Bilgileri Güncelle</button>
            </form>
        </div>

        <!-- Right Card: Security -->
        <div style="flex: 1; min-width: 300px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h3 style="color: #2c3e50; margin-top: 0;">Güvenlik (Security)</h3>
            <p style="color: #7f8c8d; font-size: 14px;">Şifrenizi değiştirmek için mevcut şifrenizi girmelisiniz.</p>
            
            <form action="process_profile.php" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="action" value="update_password">
                <div>
                    <label style="font-weight: bold; color: #2c3e50;">Mevcut Şifre (Old Password):</label>
                    <input type="password" name="old_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px;">
                </div>
                <div>
                    <label style="font-weight: bold; color: #2c3e50;">Yeni Şifre (New Password):</label>
                    <input type="password" name="new_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px;">
                </div>
                <button type="submit" style="padding: 10px; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Şifreyi Değiştir</button>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/admin_footer.php'; ?>