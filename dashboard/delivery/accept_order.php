<?php
session_start();
require_once '../../includes/dbh.inc.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'delivery') {
    header('Location: ../../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = $_POST['order_id'];
    $deliveryUserId = $_SESSION['user_id'];
    
    try {
        // Assign order to this delivery person
        $stmt = $pdo->prepare("UPDATE orders SET delivery_id = ?, status = 'picked_up' WHERE id = ? AND status = 'ready' AND delivery_id IS NULL");
        $stmt->execute([$deliveryUserId, $orderId]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Order #$orderId has been assigned to you!";
        } else {
            $_SESSION['error'] = "Order could not be assigned. It may have been taken by another delivery person.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error accepting order: " . $e->getMessage();
    }
}

header("Location: index.php");
exit();