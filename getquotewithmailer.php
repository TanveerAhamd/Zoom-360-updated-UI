<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

// --- Configuration ---
$admin_email = 'contact@zoom-360-engineering.com';
$wa_number   = "923260078800";
$site_url    = "https://zoom-360-engineering.com";
$logo_url    = "./img/Zoom Logo.png"; // Make sure this path exists

function send_mail($to, $to_name, $subject, $body, $file = null)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.zoom-360-engineering.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'contact@zoom-360-engineering.com';
        $mail->Password   = '@zoom-360'; // <--- ENTER YOUR REAL PASSWORD HERE
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('contact@zoom-360-engineering.com', 'Zoom 360 Engineering');
        $mail->addAddress($to, $to_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        if ($file && isset($file['tmp_name']) && !empty($file['tmp_name'])) {
            $mail->addAttachment($file['tmp_name'], $file['name']);
        }

        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

// -------------------- Form data --------------------
$name      = isset($_POST['name']) ? trim($_POST['name']) : '';
$email     = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone     = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$interests = isset($_POST['interests']) ? implode(", ", $_POST['interests']) : "General Inquiry";
$message   = isset($_POST['message']) ? trim($_POST['message']) : '';
$attachment = (isset($_FILES['manuscript']) && $_FILES['manuscript']['error'] == 0) ? $_FILES['manuscript'] : null;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

$wa_link = "https://wa.me/" . $wa_number . "?text=" . urlencode("Hello Zoom 360! I am $name. I just requested a quote for $interests.");

// -------------------- Email Content --------------------

// 1. Admin Email
$admin_body = "
<div style='font-family: Arial, sans-serif; border: 2px solid #fe5a0e; padding: 20px;'>
    <h2 style='color: #fe5a0e;'>New Project Lead</h2>
    <p><strong>Client Name:</strong> $name</p>
    <p><strong>Email:</strong> $email</p>
    <p><strong>Phone:</strong> $phone</p>
    <p><strong>Services:</strong> $interests</p>
    <p><strong>Details:</strong><br>$message</p>
    <br>
    <a href='$wa_link' style='background:#25D366; color:white; padding:12px 25px; text-decoration:none; border-radius:5px; font-weight:bold;'>Reply on WhatsApp</a>
</div>";

// 2. Client Confirmation Email
$client_body = "
<div style='max-width: 600px; margin: auto; font-family: sans-serif; border: 1px solid #ddd; border-top: 5px solid #fe5a0e;'>
    <div style='padding: 20px; text-align: center; background: #fff;'>
        <img src='$logo_url' alt='Zoom 360 Logo' style='max-width: 150px;'>
    </div>
    <div style='padding: 30px;'>
        <h2 style='color: #333;'>Hello $name,</h2>
        <p>Thank you for reaching out to <strong>Zoom 360 Engineering</strong>. We have received your request for <strong>$interests</strong>.</p>
        <p>Our experts are reviewing your details and will get back to you within 24 hours.</p>
        
        <div style='text-align: center; background: #fdf2ed; padding: 20px; margin: 20px 0;'>
            <p style='margin-bottom: 10px; font-weight: bold;'>Need an instant reply?</p>
            <a href='tel:+$wa_number' style='display:block; font-size: 20px; color: #fe5a0e; text-decoration:none; margin-bottom: 10px;'>+$wa_number</a>
            <a href='$wa_link' style='background: #25D366; color: #fff; padding: 10px 20px; text-decoration:none; border-radius: 5px; font-weight: bold;'>Chat on WhatsApp</a>
        </div>
        
        <p style='font-size: 12px; color: #666;'>This is an automated confirmation. Please do not reply to this email.</p>
    </div>
</div>";

$admin_sent = send_mail($admin_email, 'Zoom 360 Admin', 'New Quote Request: ' . $name, $admin_body, $attachment);
$client_sent = send_mail($email, $name, 'Request Received - Zoom 360 Engineering', $client_body);

if ($admin_sent) {
    echo json_encode(['success' => true, 'message' => 'Thank you! Your request has been sent.']);
} else {
    echo json_encode(['success' => false, 'message' => 'System busy. Please try WhatsApp.']);
}
