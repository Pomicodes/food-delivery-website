<?php
session_start();
require_once '../../includes/dbh.inc.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php?tab=menu&error=Invalid request method');
    exit();
}

// Check if menu item ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Location: settings.php?tab=menu&error=Invalid menu item ID');
    exit();
}

$menuItemId = $_POST['id'];
$userId = $_SESSION['user_id'];

try {
    // First, verify that the menu item belongs to the user's restaurant
    $stmt = $pdo->prepare("
        SELECT mi.* 
        FROM menu_items mi
        JOIN restaurants r ON mi.restaurant_id = r.id
        WHERE mi.id = ? AND r.user_id = ?
    ");
    $stmt->execute([$menuItemId, $userId]);
    $menuItem = $stmt->fetch();

    if (!$menuItem) {
        header('Location: settings.php?tab=menu&error=Menu item not found or unauthorized');
        exit();
    }

    // Start transaction
    $pdo->beginTransaction();

    // Update order_items to set menu_item_id to NULL
    $stmt = $pdo->prepare("UPDATE order_items SET menu_item_id = NULL WHERE menu_item_id = ?");
    $stmt->execute([$menuItemId]);

    // Delete the menu item
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->execute([$menuItemId]);

    // If the menu item had an image, delete it from the server
    if (!empty($menuItem['image']) && file_exists('../../' . $menuItem['image'])) {
        unlink('../../' . $menuItem['image']);
    }

    // Commit transaction
    $pdo->commit();

    header('Location: settings.php?tab=menu&success=Menu item deleted successfully');
    exit();

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error for debugging
    error_log("Menu item deletion error: " . $e->getMessage());
    header('Location: settings.php?tab=menu&error=Failed to delete menu item: ' . urlencode($e->getMessage()));
    exit();
} 