<?php
$copyright_text = !empty($site_settings['copyright_text']) 
    ? $site_settings['copyright_text'] 
    : '© ' . date("Y") . ' ' . $site_title . '. All rights reserved.';
$footer_about = !empty($site_settings['footer_about_text']) 
    ? $site_settings['footer_about_text'] 
    : ''; // Empty by default unless you type something in the admin panel
?>
    <footer>
        <div class="container" style="text-align: center; padding: 20px 0;">
            
            <p style="margin-bottom: 10px; font-weight: bold;">
                <?php echo htmlspecialchars($copyright_text); ?>
            </p>
            
            <?php if (!empty($footer_about)): ?>
                <p style="font-size: 14px; color: #ccc; margin-bottom: 10px; max-width: 600px; margin-left: auto; margin-right: auto;">
                    <?php echo nl2br(htmlspecialchars($footer_about)); ?>
                </p>
            <?php endif; ?>

            <p style="font-size: 12px; color: #888;">
                Game data and images provided by the <a href="https://rawg.io/" target="_blank" style="color: #aaa;">RAWG Video Games Database API</a>.
            </p>
            
            <?php if (!empty($site_settings['facebook_url']) || !empty($site_settings['twitter_url'])): ?>
                <div style="margin-top: 15px;">
                    <?php if (!empty($site_settings['twitter_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_settings['twitter_url']); ?>" target="_blank" style="color: #3498db; margin: 0 10px; text-decoration: none;">Twitter / X</a>
                    <?php endif; ?>
                    <?php if (!empty($site_settings['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($site_settings['facebook_url']); ?>" target="_blank" style="color: #2980b9; margin: 0 10px; text-decoration: none;">Facebook</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>