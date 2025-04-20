<?php
header('Content-Type: application/json');

// Database configuration (replace with your actual database credentials)
$db_host = 'localhost';
$db_name = 'agroshield_db';
$db_user = 'root';
$db_pass = '';

// Email configuration
$to_email = 'support@agroshield.com';
$subject = 'New Pesticide Identification Request';

// Create connection
try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]));
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input data
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $farm_size = filter_input(INPUT_POST, 'farm_size', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $crop_type = filter_input(INPUT_POST, 'crop_type', FILTER_SANITIZE_STRING);
    $pest_problem = filter_input(INPUT_POST, 'pest_problem', FILTER_SANITIZE_STRING);
    $symptoms = filter_input(INPUT_POST, 'symptoms', FILTER_SANITIZE_STRING);
    $pesticide_used = filter_input(INPUT_POST, 'pesticide_used', FILTER_SANITIZE_STRING);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($crop_type) || empty($pest_problem) || empty($symptoms)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address.'
        ]);
        exit;
    }
    
    // Handle file uploads
    $upload_dir = 'uploads/';
    $uploaded_files = [];
    $file_errors = [];
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_tmp = $_FILES['images']['tmp_name'][$key];
            $file_type = $_FILES['images']['type'][$key];
            
            // Validate file
            $max_size = 5 * 1024 * 1024; // 5MB
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            
            if ($file_size > $max_size) {
                $file_errors[] = "File $file_name is too large (max 5MB).";
                continue;
            }
            
            if (!in_array($file_type, $allowed_types)) {
                $file_errors[] = "File $file_name has an invalid type (only JPG, PNG, GIF allowed).";
                continue;
            }
            
            // Generate unique filename
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_filename = uniqid('img_', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $uploaded_files[] = $new_filename;
            } else {
                $file_errors[] = "Failed to upload $file_name.";
            }
        }
    }
    
    // Insert data into database
    try {
        $stmt = $conn->prepare("INSERT INTO pesticide_requests (
            name, email, phone, farm_size, crop_type, pest_problem, 
            symptoms, pesticide_used, images, submitted_at
        ) VALUES (
            :name, :email, :phone, :farm_size, :crop_type, :pest_problem, 
            :symptoms, :pesticide_used, :images, NOW()
        )");
        
        $images_str = implode(',', $uploaded_files);
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':farm_size', $farm_size);
        $stmt->bindParam(':crop_type', $crop_type);
        $stmt->bindParam(':pest_problem', $pest_problem);
        $stmt->bindParam(':symptoms', $symptoms);
        $stmt->bindParam(':pesticide_used', $pesticide_used);
        $stmt->bindParam(':images', $images_str);
        
        $stmt->execute();
        $request_id = $conn->lastInsertId();
    } catch(PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
    
    // Send email notification
    $message = "New pesticide identification request received:\n\n";
    $message .= "Request ID: $request_id\n";
    $message .= "Name: $name\n";
    $message .= "Email: $email\n";
    $message .= "Phone: $phone\n";
    $message .= "Farm Size: $farm_size acres\n";
    $message .= "Crop Type: $crop_type\n";
    $message .= "Pest Problem: $pest_problem\n";
    $message .= "Symptoms: $symptoms\n";
    $message .= "Pesticide Used: $pesticide_used\n";
    
    if (!empty($file_errors)) {
        $message .= "\nFile upload errors:\n" . implode("\n", $file_errors);
    }
    
    $headers = "From: no-reply@agroshield.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    mail($to_email, $subject, $message, $headers);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Your pesticide identification request has been submitted successfully! Our team will review your submission and get back to you shortly.',
        'request_id' => $request_id,
        'file_errors' => $file_errors
    ]);
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>