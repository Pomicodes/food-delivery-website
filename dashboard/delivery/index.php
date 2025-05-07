<?php
session_start();
require_once '../../includes/dbh.inc.php';

// Check if user is logged in as a delivery person
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'delivery') {
    header('Location: ../../auth/login.php');
    exit();
}

$deliveryUserId = $_SESSION['user_id'];

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];
        
        try {
            // Verify the order is assigned to this delivery person
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND delivery_id = ?");
            $stmt->execute([$newStatus, $orderId, $deliveryUserId]);
            
            $_SESSION['success'] = "Order status updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating order status: " . $e->getMessage();
        }
    } elseif (isset($_POST['accept_order'])) {
        $orderId = $_POST['order_id'];
        
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
}

// Get assigned orders
$assignedOrders = [];
try {
    $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, r.address as restaurant_address, 
                                  r.image as restaurant_image, u.name as customer_name, 
                                  u.phone as customer_phone, u.address as customer_address
                           FROM orders o
                           JOIN restaurants r ON o.restaurant_id = r.id
                           JOIN users u ON o.customer_id = u.id
                           WHERE o.delivery_id = ? AND o.status = 'picked_up'
                           ORDER BY o.created_at DESC");
    $stmt->execute([$deliveryUserId]);
    $assignedOrders = $stmt->fetchAll();

    // Get order items for each order
    foreach ($assignedOrders as &$order) {
        $stmt = $pdo->prepare("SELECT oi.*, mi.name as item_name 
                              FROM order_items oi
                              JOIN menu_items mi ON oi.menu_item_id = mi.id
                              WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching assigned orders: " . $e->getMessage());
    $_SESSION['error'] = "Error loading your assigned orders";
}

// Get available orders (ready but not assigned)
$availableOrders = [];
try {
    $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, r.address as restaurant_address, 
                                  r.image as restaurant_image, u.name as customer_name
                           FROM orders o
                           JOIN restaurants r ON o.restaurant_id = r.id
                           JOIN users u ON o.customer_id = u.id
                           WHERE o.status = 'ready' AND o.delivery_id IS NULL
                           ORDER BY o.created_at");
    $stmt->execute();
    $availableOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching available orders: " . $e->getMessage());
    $_SESSION['error'] = "Error loading available orders";
}

// Get delivery history
$deliveryHistory = [];
try {
    $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, r.address as restaurant_address, 
                                  r.image as restaurant_image, u.name as customer_name, 
                                  u.phone as customer_phone, u.address as customer_address
                           FROM orders o
                           JOIN restaurants r ON o.restaurant_id = r.id
                           JOIN users u ON o.customer_id = u.id
                           WHERE o.delivery_id = ? AND o.status = 'delivered'
                           ORDER BY o.created_at DESC
                           LIMIT 50");
    $stmt->execute([$deliveryUserId]);
    $deliveryHistory = $stmt->fetchAll();

    // Get order items for each order
    foreach ($deliveryHistory as &$order) {
        $stmt = $pdo->prepare("SELECT oi.*, mi.name as item_name 
                              FROM order_items oi
                              JOIN menu_items mi ON oi.menu_item_id = mi.id
                              WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching delivery history: " . $e->getMessage());
    $_SESSION['error'] = "Error loading delivery history";
}

// Get delivery person profile
$profile = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$deliveryUserId]);
    $profile = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching profile: " . $e->getMessage());
    $_SESSION['error'] = "Error loading profile";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $address, $deliveryUserId]);
        
        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
    }
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'ready':
            return 'bg-blue-100 text-blue-800';
        case 'picked_up':
            return 'bg-purple-100 text-purple-800';
        case 'delivered':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard | Savory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.1);
        }
        .glass-hover {
            transition: all 0.3s ease;
        }
        .glass-hover:hover {
            background: rgba(255,255,255,0.9);
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.15);
            transform: translateY(-2px);
        }
        .order-card {
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        .order-card:hover::before {
            transform: translateX(100%);
        }
        .order-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
        .tab-button {
            transition: all 0.3s ease;
            position: relative;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
        }
        .tab-button::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #f97316;
            transition: width 0.3s ease;
        }
        .tab-button:hover {
            background: rgba(249,115,22,0.1);
        }
        .tab-button:hover::after {
            width: 100%;
        }
        .tab-button.active {
            color: #f97316;
            font-weight: 500;
            background: rgba(249,115,22,0.1);
        }
        .tab-button.active::after {
            width: 100%;
        }
        .status-badge {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            padding: 0.5rem 1rem;
        }
        .status-badge::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
            transition: transform 0.6s ease;
        }
        .status-badge:hover::after {
            transform: rotate(45deg) translate(50%, 50%);
        }
        .status-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .action-button {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .action-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255,255,255,0.2),
                transparent
            );
            transition: 0.5s;
        }
        .action-button:hover::before {
            left: 100%;
        }
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        .nav-link {
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #f97316;
            transition: width 0.3s ease;
        }
        .nav-link:hover {
            background: rgba(249,115,22,0.1);
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .restaurant-image {
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .order-card:hover .restaurant-image {
            transform: scale(1.1);
            border-color: #f97316;
        }
        .message {
            animation: slideIn 0.5s ease-out;
            border-radius: 0.5rem;
        }
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .info-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #f97316;
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .bounce {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .slide {
            animation: slide 2s infinite;
        }
        @keyframes slide {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(5px); }
        }
        .status-ready { animation: pulse 2s infinite; }
        .status-picked_up { animation: bounce 2s infinite; }
        .status-delivered { animation: slide 2s infinite; }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f97316;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .refresh-button {
            transition: all 0.3s ease;
        }
        .refresh-button:hover {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Navigation -->
    <nav class="glass sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-orange-500 flex items-center nav-link">
                        <i class="fas fa-motorcycle mr-2 animate-bounce"></i>
                        Savory Delivery
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="refreshOrders()" class="refresh-button text-gray-600 hover:text-orange-500 p-2 rounded-full hover:bg-orange-50">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <a href="../../auth/logout.php" class="text-gray-600 hover:text-orange-500 nav-link">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="glass bg-red-100 border-l-4 border-red-500 p-4 mb-6 message">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <p class="text-red-700"><?= $_SESSION['error'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="glass bg-green-100 border-l-4 border-green-500 p-4 mb-6 message">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <p class="text-green-700"><?= $_SESSION['success'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="glass border-b border-gray-200 mb-8 rounded-lg">
            <div class="flex space-x-8 px-4">
                <button id="assigned-tab" class="tab-button py-4 px-1 active">
                    <i class="fas fa-clipboard-list mr-2"></i> My Deliveries
                    <span class="ml-2 px-2 py-1 text-xs bg-orange-100 text-orange-600 rounded-full">
                        <?= count($assignedOrders) ?>
                    </span>
                </button>
                <button id="available-tab" class="tab-button py-4 px-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-list-alt mr-2"></i> Available Orders
                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
                        <?= count($availableOrders) ?>
                    </span>
                </button>
                <button id="history-tab" class="tab-button py-4 px-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-history mr-2"></i> Delivery History
                    <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-600 rounded-full">
                        <?= count($deliveryHistory) ?>
                    </span>
                </button>
                <button id="profile-tab" class="tab-button py-4 px-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-user-circle mr-2"></i> Profile
                </button>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div id="loading-spinner" class="hidden flex justify-center items-center py-8">
            <div class="loading-spinner"></div>
        </div>

        <!-- Assigned Orders Tab -->
        <div id="assigned-orders" class="tab-content fade-in">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">My Deliveries</h2>
            
            <?php if (empty($assignedOrders)): ?>
                <div class="glass bg-white p-8 rounded-lg shadow text-center">
                    <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-4 animate-pulse"></i>
                    <p class="text-gray-500">You don't have any assigned deliveries at the moment.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($assignedOrders as $order): ?>
                        <div class="order-card glass bg-white rounded-lg shadow overflow-hidden">
                            <!-- Order Header -->
                            <div class="p-4 border-b flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
                                        <img src="../../<?= htmlspecialchars($order['restaurant_image'] ?: 'assets/default-restaurant.jpg') ?>" 
                                             alt="<?= htmlspecialchars($order['restaurant_name']) ?>" 
                                             class="w-full h-full object-cover restaurant-image">
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">Order #<?= $order['id'] ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <i class="far fa-clock mr-1"></i>
                                            <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadgeClass($order['status']) ?>">
                                        <i class="fas fa-circle mr-1 text-xs"></i>
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Order Details -->
                            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Restaurant Info -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-store mr-2 text-orange-500"></i> Restaurant
                                    </h4>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($order['restaurant_name']) ?></p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?= htmlspecialchars($order['restaurant_address']) ?>
                                    </p>
                                </div>

                                <!-- Customer Info -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-orange-500"></i> Customer
                                    </h4>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($order['customer_name']) ?></p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-phone mr-1"></i>
                                        <?= htmlspecialchars($order['customer_phone']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?= htmlspecialchars($order['customer_address']) ?>
                                    </p>
                                    <?php if (!empty($order['customer_latitude']) && !empty($order['customer_longitude'])): ?>
                                        <div id="map-<?= $order['id'] ?>" class="rounded-lg overflow-hidden mb-2" style="height: 180px;"></div>
                                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $order['customer_latitude'] ?>,<?= $order['customer_longitude'] ?>" target="_blank" class="inline-block mt-1 text-blue-600 hover:underline text-xs">
                                            <i class="fas fa-directions mr-1"></i> Get Directions
                                        </a>
                                        <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            if (typeof L !== 'undefined') {
                                                var map = L.map('map-<?= $order['id'] ?>', { zoomControl: false, attributionControl: false }).setView([<?= $order['customer_latitude'] ?>, <?= $order['customer_longitude'] ?>], 16);
                                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                                                L.marker([<?= $order['customer_latitude'] ?>, <?= $order['customer_longitude'] ?>]).addTo(map);
                                            }
                                        });
                                        </script>
                                    <?php endif; ?>
                                </div>

                                <!-- Order Items -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-utensils mr-2 text-orange-500"></i> Items
                                    </h4>
                                    <ul class="space-y-2">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <li class="flex justify-between items-center text-sm">
                                                <span class="text-gray-800"><?= htmlspecialchars($item['item_name']) ?></span>
                                                <span class="text-gray-600">x<?= $item['quantity'] ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="p-4 border-t bg-gray-50">
                                <form method="POST" class="flex justify-end space-x-4">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <?php if ($order['status'] === 'ready'): ?>
                                        <button type="submit" name="update_status" value="picked_up" 
                                                class="action-button bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600">
                                            <i class="fas fa-box mr-1"></i> Pick Up Order
                                        </button>
                                            <?php elseif ($order['status'] === 'picked_up'): ?>
                                        <input type="hidden" name="status" value="delivered">
                                        <button type="submit" name="update_status" value="1"
                                            class="action-button bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                                            <i class="fas fa-check mr-1"></i> Mark as Delivered
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Orders Tab -->
        <div id="available-orders" class="tab-content hidden fade-in">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Available Orders</h2>
            
            <?php if (empty($availableOrders)): ?>
                <div class="glass bg-white p-8 rounded-lg shadow text-center">
                    <i class="fas fa-list-alt text-gray-300 text-5xl mb-4 animate-pulse"></i>
                    <p class="text-gray-500">No orders are available for delivery at the moment.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($availableOrders as $order): ?>
                        <div class="order-card glass bg-white rounded-lg shadow overflow-hidden">
                            <!-- Order Header -->
                            <div class="p-4 border-b flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
                                        <img src="../../<?= htmlspecialchars($order['restaurant_image'] ?: 'assets/default-restaurant.jpg') ?>" 
                                             alt="<?= htmlspecialchars($order['restaurant_name']) ?>" 
                                             class="w-full h-full object-cover restaurant-image">
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">Order #<?= $order['id'] ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <i class="far fa-clock mr-1"></i>
                                            <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-circle mr-1 text-xs"></i>
                                        Ready for Pickup
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Order Details -->
                            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Restaurant Info -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-store mr-2 text-orange-500"></i> Restaurant
                                    </h4>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($order['restaurant_name']) ?></p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?= htmlspecialchars($order['restaurant_address']) ?>
                                    </p>
                                </div>
                                
                                <!-- Customer Info -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-orange-500"></i> Customer
                                    </h4>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($order['customer_name']) ?></p>
                                </div>
                                </div>
                                
                            <!-- Action Button -->
                            <div class="p-4 border-t bg-gray-50">
                                <form method="POST" action="pickup_order.php" class="flex justify-end">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" name="pickup_order" 
                                            class="action-button bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600">
                                        <i class="fas fa-box mr-1"></i> Pick Up
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Delivery History Tab -->
        <div id="delivery-history" class="tab-content hidden fade-in">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Delivery History</h2>
                <div class="flex items-center space-x-4">
                    <div class="glass px-4 py-2 rounded-lg">
                        <span class="text-gray-600">Total Deliveries:</span>
                        <span class="font-bold text-orange-500 ml-2"><?= count($deliveryHistory) ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (empty($deliveryHistory)): ?>
                <div class="glass bg-white p-8 rounded-lg shadow text-center">
                    <i class="fas fa-history text-gray-300 text-5xl mb-4 animate-pulse"></i>
                    <p class="text-gray-500">No delivery history available.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($deliveryHistory as $order): ?>
                        <div class="order-card glass bg-white rounded-lg shadow overflow-hidden">
                            <!-- Order Header -->
                            <div class="p-4 border-b flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 rounded-full overflow-hidden mr-4">
                                        <img src="../../<?= htmlspecialchars($order['restaurant_image'] ?: 'assets/default-restaurant.jpg') ?>" 
                                             alt="<?= htmlspecialchars($order['restaurant_name']) ?>" 
                                             class="w-full h-full object-cover restaurant-image">
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-800">Order #<?= $order['id'] ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <i class="far fa-clock mr-1"></i>
                                            <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Delivered
                                    </span>
                                </div>
                            </div>

                            <!-- Order Details -->
                            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Restaurant Info -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-store mr-2 text-orange-500"></i> Restaurant
                                    </h4>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($order['restaurant_name']) ?></p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?= htmlspecialchars($order['restaurant_address']) ?>
                                    </p>
                                </div>

                                <!-- Customer Info -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-orange-500"></i> Customer
                                    </h4>
                                    <p class="font-bold text-gray-800"><?= htmlspecialchars($order['customer_name']) ?></p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-phone mr-1"></i>
                                        <?= htmlspecialchars($order['customer_phone']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                        <?= htmlspecialchars($order['customer_address']) ?>
                                    </p>
                                </div>

                                <!-- Order Items -->
                                <div class="glass p-4 rounded-lg">
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-utensils mr-2 text-orange-500"></i> Items
                                    </h4>
                                    <ul class="space-y-2">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <li class="flex justify-between items-center text-sm">
                                                <span class="text-gray-800"><?= htmlspecialchars($item['item_name']) ?></span>
                                                <span class="text-gray-600">x<?= $item['quantity'] ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content hidden fade-in">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Profile Management</h2>
                
                <div class="glass bg-white rounded-lg shadow overflow-hidden">
                    <form method="POST" class="p-6 space-y-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-user mr-2 text-orange-500"></i> Full Name
                                </label>
                                <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" 
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-phone mr-2 text-orange-500"></i> Phone Number
                                </label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($profile['phone']) ?>" 
                                       class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-map-marker-alt mr-2 text-orange-500"></i> Address
                                </label>
                                <textarea name="address" rows="3" 
                                          class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                          required><?= htmlspecialchars($profile['address']) ?></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="update_profile" 
                                    class="action-button bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Statistics Card -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass bg-white p-6 rounded-lg shadow text-center">
                        <i class="fas fa-box text-orange-500 text-3xl mb-2"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Total Deliveries</h3>
                        <p class="text-2xl font-bold text-orange-500"><?= count($deliveryHistory) ?></p>
                    </div>
                    
                    <div class="glass bg-white p-6 rounded-lg shadow text-center">
                        <i class="fas fa-clock text-orange-500 text-3xl mb-2"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Active Deliveries</h3>
                        <p class="text-2xl font-bold text-orange-500"><?= count($assignedOrders) ?></p>
                    </div>
                    
                    <div class="glass bg-white p-6 rounded-lg shadow text-center">
                        <i class="fas fa-star text-orange-500 text-3xl mb-2"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Rating</h3>
                        <p class="text-2xl font-bold text-orange-500">4.8</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        document.getElementById('assigned-tab').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('available-tab').classList.remove('active');
            document.getElementById('history-tab').classList.remove('active');
            document.getElementById('profile-tab').classList.remove('active');
            document.getElementById('assigned-orders').classList.remove('hidden');
            document.getElementById('available-orders').classList.add('hidden');
            document.getElementById('delivery-history').classList.add('hidden');
            document.getElementById('profile').classList.add('hidden');
        });
        
        document.getElementById('available-tab').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('assigned-tab').classList.remove('active');
            document.getElementById('history-tab').classList.remove('active');
            document.getElementById('profile-tab').classList.remove('active');
            document.getElementById('assigned-orders').classList.add('hidden');
            document.getElementById('available-orders').classList.remove('hidden');
            document.getElementById('delivery-history').classList.add('hidden');
            document.getElementById('profile').classList.add('hidden');
        });

        document.getElementById('history-tab').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('assigned-tab').classList.remove('active');
            document.getElementById('available-tab').classList.remove('active');
            document.getElementById('profile-tab').classList.remove('active');
            document.getElementById('assigned-orders').classList.add('hidden');
            document.getElementById('available-orders').classList.add('hidden');
            document.getElementById('delivery-history').classList.remove('hidden');
            document.getElementById('profile').classList.add('hidden');
        });

        document.getElementById('profile-tab').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('assigned-tab').classList.remove('active');
            document.getElementById('available-tab').classList.remove('active');
            document.getElementById('history-tab').classList.remove('active');
            document.getElementById('assigned-orders').classList.add('hidden');
            document.getElementById('available-orders').classList.add('hidden');
            document.getElementById('delivery-history').classList.add('hidden');
            document.getElementById('profile').classList.remove('hidden');
        });

        // Add refresh functionality
        function refreshOrders() {
            const spinner = document.getElementById('loading-spinner');
            spinner.classList.remove('hidden');
            
            // Simulate loading (remove this in production and use actual AJAX)
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }

        // Add fade-in animation to new elements
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                el.style.animationDelay = (i * 0.1) + 's';
            });

            // Add hover effects to info cards
            document.querySelectorAll('.info-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('glass-hover');
                });
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('glass-hover');
                });
            });

            // Add status-specific animations
            document.querySelectorAll('.status-badge').forEach(badge => {
                const status = badge.textContent.trim().toLowerCase();
                if (status.includes('ready')) {
                    badge.classList.add('status-ready');
                } else if (status.includes('picked up')) {
                    badge.classList.add('status-picked_up');
                } else if (status.includes('delivered')) {
                    badge.classList.add('status-delivered');
                }
            });
        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</body>
</html>