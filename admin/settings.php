<?php
// admin/settings.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

// STRICT SECURITY: ONLY Super Admins (Role 1)
if ($_SESSION['role_id'] != 1) {
    header("Location: index.php");
    exit;
}

// Fetch current settings (Assuming ID 1 is your main config row)
$stmt = $conn->prepare("SELECT * FROM settings WHERE id = 1");
$stmt->execute();
$settings = $stmt->get_result()->fetch_assoc();

// Fallbacks just in case the row is empty
$site_title = $settings['site_title'] ?? '';
$site_desc = $settings['site_description'] ?? '';
$logo_url = $settings['site_logo'] ?? '';
$footer_about = $settings['footer_about_text'] ?? '';
$copyright = $settings['copyright_text'] ?? '';
$email = $settings['contact_email'] ?? '';
$facebook = $settings['facebook_url'] ?? '';
$twitter = $settings['twitter_url'] ?? '';

include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>

<main class="admin-content">
    <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2>Site Settings</h2>
            <p style="color: #7f8c8d; margin-top: 5px;">Manage global branding, SEO, and footer text.</p>
        </div>
        <button type="button" onclick="saveSettings()" id="saveBtn" class="btn-approve" style="padding: 12px 30px; font-size: 16px;">Save All Settings</button>
    </div>

    <form id="settingsForm" class="settings-grid">
        
        <div class="settings-column">
            
            <div class="settings-card">
                <h3>General Identity</h3>
                <hr>
                <div class="form-group">
                    <label>Site Title (Browser Tab & SEO)</label>
                    <input type="text" name="site_title" value="<?php echo htmlspecialchars($site_title); ?>" required>
                </div>
                <div class="form-group">
                    <label>Site Description (SEO Meta Description)</label>
                    <textarea name="site_description" rows="3"><?php echo htmlspecialchars($site_desc); ?></textarea>
                </div>
            </div>

            <div class="settings-card">
                <h3>Footer Content</h3>
                <hr>
                <div class="form-group">
                    <label>About Us Paragraph (Shows in Footer)</label>
                    <textarea name="footer_about_text" rows="4"><?php echo htmlspecialchars($footer_about); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Copyright Text</label>
                    <input type="text" name="copyright_text" value="<?php echo htmlspecialchars($copyright); ?>" placeholder="e.g., © 2026 GameJoint. All rights reserved.">
                </div>
            </div>

        </div>

        <div class="settings-column">
            
            <div class="settings-card">
                <h3>Site Logo</h3>
                <hr>
                <div class="form-group">
                    <label>Logo URL (Local path or external link)</label>
                    <input type="text" name="site_logo" id="logoInput" value="<?php echo htmlspecialchars($logo_url); ?>" onkeyup="updateLogoPreview()" placeholder="../assets/images/logo.png">
                </div>
                <div style="margin-top: 10px; padding: 15px; background: #ecf0f1; border-radius: 6px; text-align: center;">
                    <p style="font-size: 12px; color: #7f8c8d; margin-bottom: 10px; text-transform: uppercase;">Live Preview</p>
                    <img id="logoPreview" src="<?php echo htmlspecialchars($logo_url); ?>" alt="Site Logo" style="max-width: 100%; max-height: 80px; display: <?php echo empty($logo_url) ? 'none' : 'inline-block'; ?>;">
                    <span id="logoNoImage" style="color: #95a5a6; display: <?php echo empty($logo_url) ? 'inline-block' : 'none'; ?>;">No Logo Set</span>
                </div>
            </div>

            <div class="settings-card">
                <h3>Contact & Socials</h3>
                <hr>
                <div class="form-group">
                    <label>Public Contact Email</label>
                    <input type="email" name="contact_email" value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="form-group">
                    <label>Facebook URL</label>
                    <input type="url" name="facebook_url" value="<?php echo htmlspecialchars($facebook); ?>" placeholder="https://facebook.com/yourpage">
                </div>
                <div class="form-group">
                    <label>Twitter / X URL</label>
                    <input type="url" name="twitter_url" value="<?php echo htmlspecialchars($twitter); ?>" placeholder="https://twitter.com/yourhandle">
                </div>
            </div>

        </div>
    </form>
</main>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 850px) {
        .settings-grid { grid-template-columns: 1fr; }
        .admin-header { flex-direction: column; align-items: stretch !important; gap: 15px; }
    }
    
    .settings-column {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .settings-card {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .settings-card h3 { color: #2c3e50; margin-bottom: 10px; }
    .settings-card hr { border: 0; border-top: 1px solid #eee; margin-bottom: 20px; }

    .form-group { margin-bottom: 15px; }
    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 8px;
        color: #34495e;
        font-size: 14px;
    }
    .form-group input, .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-family: inherit;
    }
    .form-group input:focus, .form-group textarea:focus {
        border-color: #3498db;
        outline: none;
    }
</style>

<script>
function updateLogoPreview() {
    const url = document.getElementById('logoInput').value;
    const img = document.getElementById('logoPreview');
    const noImgText = document.getElementById('logoNoImage');
    
    if (url.trim() !== '') {
        img.src = url;
        img.style.display = 'inline-block';
        noImgText.style.display = 'none';
    } else {
        img.style.display = 'none';
        noImgText.style.display = 'inline-block';
    }
}

function saveSettings() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    const btn = document.getElementById('saveBtn');

    btn.disabled = true;
    btn.textContent = 'Saving...';

    fetch('process_settings.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.textContent = 'Save All Settings';
        
        if (data.success) {
            // Flash green button to show success
            btn.style.backgroundColor = '#27ae60';
            btn.textContent = 'Saved Successfully!';
            setTimeout(() => {
                btn.style.backgroundColor = '';
                btn.textContent = 'Save All Settings';
            }, 2000);
        } else {
            alert('Error saving settings: ' + data.error);
        }
    })
    .catch(err => {
        alert('A network error occurred.');
        btn.disabled = false;
        btn.textContent = 'Save All Settings';
    });
}
</script>

<?php include 'includes/admin_footer.php'; ?>