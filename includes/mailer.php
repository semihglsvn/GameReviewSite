<?php
// includes/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

// Added $image_path parameter to handle the logo
function sendGameJointEmail($to_email, $subject, $html_body, $image_path = null) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gamejoint.noreply@gmail.com';
        $mail->Password   = 'svmfyusmhdetnaul'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // EMBED IMAGE LOGIC
        if ($image_path && file_exists($image_path)) {
            // 'logo_img' is the CID (Content-ID) we use in the HTML src
            $mail->addEmbeddedImage($image_path, 'logo_img');
        }

        // Sender and Recipient
        $mail->setFrom('gamejoint.noreply@gmail.com', 'GameJoint System');
        $mail->addAddress($to_email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>