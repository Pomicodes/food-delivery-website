<?php
session_start();
require_once '../../includes/dbh.inc.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

// Process form when submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required = ['name', 'address', 'phone'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields");
            }
        }

        // Handle file upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Configuration
            $uploadDir = '../../uploads/restaurants/';
            $maxFileSize = 2 * 1024 * 1024; // 2MB
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp'
            ];

            // Create upload directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                // Add an .htaccess file to prevent direct access
                file_put_contents($uploadDir . '.htaccess', "deny from all");
            }

            // Get file info
            $fileTmpPath = $_FILES['image']['tmp_name'];
            $fileName = $_FILES['image']['name'];
            $fileSize = $_FILES['image']['size'];
            $fileType = mime_content_type($fileTmpPath);

            // Validate file
            if ($fileSize > $maxFileSize) {
                throw new Exception('Image size must be less than 2MB');
            }

            if (!array_key_exists($fileType, $allowedTypes)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed');
            }

            // Create a secure filename
            $fileNameCleaned = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $fileName);
            $fileExt = $allowedTypes[$fileType];
            $newFileName = 'rest_' . $_SESSION['user_id'] . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            // Resize image if needed (optional)
            // You could add image resizing here using GD or Imagick

            // Move the file
            if (!move_uploaded_file($fileTmpPath, $destination)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Set relative path for database
            $imagePath = 'uploads/restaurants/' . $newFileName;
        } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle specific upload errors
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'File is too large',
                UPLOAD_ERR_FORM_SIZE => 'File is too large',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            throw new Exception($uploadErrors[$_FILES['image']['error']] ?? 'Unknown upload error');
        }

        // Insert restaurant data
        $stmt = $pdo->prepare("
            INSERT INTO restaurants 
            (user_id, name, address, phone, image) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            htmlspecialchars($_POST['name']),
            htmlspecialchars($_POST['address']),
            htmlspecialchars($_POST['phone']),
            $imagePath
        ]);

        // Get the newly created restaurant ID
        $restaurantId = $pdo->lastInsertId();

        // Redirect to success page
        header("Location: ./index.php?success=1&id=$restaurantId");
        exit();
        
    } catch (Exception $e) {
        // Log the error
        error_log('Restaurant creation error: ' . $e->getMessage());
        
        // Redirect back with error
        header('Location: ./createRestaurant.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

// If not a POST request, redirect back
header('Location: ./createRestaurant.php');
exit();