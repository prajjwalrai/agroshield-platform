<?php
// Enable error logging but disable display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Ensure no output before headers
ob_start();

require 'vendor/phpmailer/phpmailer/Exception.php';
require 'vendor/phpmailer/phpmailer/PHPMailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log all POST data
error_log('Received POST data: ' . print_r($_POST, true));

// If this is a preflight request, we're done
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize and validate input data
        $name = isset($_POST['contact-name']) ? trim($_POST['contact-name']) : '';
        $email = isset($_POST['contact-email']) ? trim($_POST['contact-email']) : '';
        $phone = isset($_POST['contact-phone']) ? trim($_POST['contact-phone']) : '';
        $contact_subject = isset($_POST['contact-subject']) ? trim($_POST['contact-subject']) : '';
        $message = isset($_POST['contact-message']) ? trim($_POST['contact-message']) : '';

        // Log sanitized data
        error_log('Sanitized data: ' . print_r([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $contact_subject,
            'message' => $message
        ], true));

        // Validate required fields
        if (empty($name) || empty($email) || empty($message)) {
            $missing = [];
            if (empty($name)) $missing[] = 'name';
            if (empty($email)) $missing[] = 'email';
            if (empty($message)) $missing[] = 'message';
            throw new Exception('Please fill in all required fields: ' . implode(', ', $missing));
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }

        // Create a new PHPMailer instance
        $mail = new PHPMailer();
        
        // Enable SMTP debugging for troubleshooting
        error_log('Setting up SMTP connection...');
        
        // Configure Gmail SMTP settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        
        // Set Gmail credentials
        $mail->Username = 'prajjawalraiaman@gmail.com';
        $mail->Password = 'frrn pwlo hmnv vssp';
        
        error_log('SMTP credentials set - Username: ' . $mail->Username);
        
        // Set email content
        $mail->setFrom($email, $name);
        $mail->addAddress('prajjawalraiaman@gmail.com');
        $mail->Subject = 'New Contact Form Submission: ' . $contact_subject;
        
        // Prepare email content with proper formatting
        $mail->Body = "<!DOCTYPE html>
<html>
<head>
    <title>New Contact Form Submission</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
    <h2>New Contact Form Submission</h2>
    <p><strong>Name:</strong> {$name}</p>
    <p><strong>Email:</strong> {$email}</p>";
        
        if (!empty($phone)) {
            $mail->Body .= "<p><strong>Phone:</strong> {$phone}</p>";
        }
        
        $mail->Body .= "<p><strong>Subject:</strong> {$contact_subject}</p>
    <p><strong>Message:</strong><br>{$message}</p>
</body>
</html>";

        // Set plain text version
        $mail->AltBody = "New Contact Form Submission\n\n" .
            "Name: {$name}\n" .
            "Email: {$email}\n" .
            ($phone ? "Phone: {$phone}\n" : "") .
            "Subject: {$contact_subject}\n\n" .
            "Message:\n{$message}";

        error_log('Attempting to send email...');
        
        // Attempt to send email
        if (!$mail->send()) {
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            throw new Exception('Failed to send email: ' . $mail->ErrorInfo);
        }

        error_log('Email sent successfully!');

        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your message has been sent successfully.'
        ]);

    } catch (Exception $e) {
        error_log('Form submission error: ' . $e->getMessage());
        
        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}