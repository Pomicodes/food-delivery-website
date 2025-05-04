<?php
session_start();
require_once '../../includes/dbh.inc.php';

// Check if user is logged in and is a restaurant owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'restaurant') {
    header('Location: ../../auth/login.php');
    exit();
}

// Get restaurant data
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$restaurant = $stmt->fetch();
$restaurantId = $restaurant['id'] ?? null;
if(!isset($restaurantId)){ 
    header('Location: ./createRestaurant.php');
    exit();
    
}
// Set default date range (last 7 days)
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-7 days'));

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    
    // Validate the order belongs to this restaurant
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND restaurant_id = ?");
    $stmt->execute([$newStatus, $orderId, $restaurantId]);
    
    $_SESSION['success_message'] = 'Order status updated successfully!';
    header("Location: orders.php");
    exit();
}

// Get orders for this restaurant
$orders = [];
if ($restaurantId) {
    $statusFilter = $_GET['status'] ?? null;
    
    $query = "SELECT o.*, u.name as customer_name, u.phone as customer_phone 
              FROM orders o
              JOIN users u ON o.customer_id = u.id
              WHERE o.restaurant_id = ?";
    
    $params = [$restaurantId];
    
    // Apply date filter if provided
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $query .= " AND DATE(o.created_at) BETWEEN ? AND ?";
        array_push($params, $_GET['start_date'], $_GET['end_date']);
        $startDate = $_GET['start_date'];
        $endDate = $_GET['end_date'];
    }
    
    // Apply status filter if provided
    if ($statusFilter && in_array($statusFilter, ['placed', 'ready', 'picked_up', 'delivered'])) {
        $query .= " AND o.status = ?";
        array_push($params, $statusFilter);
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("SELECT oi.*, mi.name as item_name 
                              FROM order_items oi
                              JOIN menu_items mi ON oi.menu_item_id = mi.id
                              WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .order-card { transition: all 0.3s ease; }
        .order-card:hover { transform: translateY(-3px); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-placed { background-color: #FEF3C7; color: #92400E; }
        .status-ready { background-color: #D1FAE5; color: #065F46; }
        .status-picked_up { background-color: #DBEAFE; color: #1E40AF; }
        .status-delivered { background-color: #E5E7EB; color: #4B5563; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include your sidebar -->
    <?php include('./sidebar.php'); ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-clipboard-list text-orange-500 mr-2"></i>
                    Order Management
                </h1>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <input type="date" id="start-date" value="<?= $startDate ?>" class="border rounded px-3 py-2">
                        <span>to</span>
                        <input type="date" id="end-date" value="<?= $endDate ?>" class="border rounded px-3 py-2">
                        <select id="status-filter" class="border rounded px-3 py-2">
                            <option value="">All Statuses</option>
                            <option value="placed" <?= ($_GET['status'] ?? '') === 'placed' ? 'selected' : '' ?>>Placed</option>
                            <option value="ready" <?= ($_GET['status'] ?? '') === 'ready' ? 'selected' : '' ?>>Ready</option>
                            <option value="picked_up" <?= ($_GET['status'] ?? '') === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                            <option value="delivered" <?= ($_GET['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                        <button id="apply-filters" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                            Apply
                        </button>
                    </div>
                </div>
            </div>

            <?php if (!$restaurantId): ?>
                <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-700">You need to create a restaurant first to view orders.</p>
                </div>
            <?php else: ?>
                <!-- Success Message -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                        <p class="text-green-700"><?= $_SESSION['success_message'] ?></p>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- Orders List -->
                <div class="space-y-6">
                    <?php if (empty($orders)): ?>
                        <div class="bg-white p-8 rounded-lg shadow text-center">
                            <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-4"></i>
                            <p class="text-gray-500">No orders found for the selected criteria.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card bg-white rounded-lg shadow overflow-hidden">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">Order #<?= $order['id'] ?></h3>
                                            <p class="text-sm text-gray-500 mt-1">
                                                <i class="far fa-clock mr-1"></i>
                                                <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <span class="status-badge status-<?= $order['status'] ?>">
                                                <?= str_replace('_', ' ', $order['status']) ?>
                                            </span>
                                            <p class="text-lg font-bold text-orange-500">
                                                $<?= number_format($order['total'], 2) ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Customer Info -->
                                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-4">
                                            <div class="bg-orange-100 p-3 rounded-full">
                                                <i class="fas fa-user text-orange-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800"><?= htmlspecialchars($order['customer_name']) ?></p>
                                                <p class="text-sm text-gray-500">
                                                    <i class="fas fa-phone-alt mr-1"></i>
                                                    <?= htmlspecialchars($order['customer_phone']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Items -->
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Order Items</h4>
                                        <div class="space-y-3">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                                    <div>
                                                        <p class="text-gray-800"><?= htmlspecialchars($item['item_name']) ?></p>
                                                        <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?></p>
                                                    </div>
                                                    <p class="text-gray-700">$<?= number_format($item['price'], 2) ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Update -->
                                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                                        <div>
                                            <?php if ($order['delivery_id']): ?>
                                                <p class="text-sm text-gray-500">
                                                    <i class="fas fa-motorcycle mr-1"></i>
                                                    Delivery assigned
                                                </p>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-500">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Waiting for delivery assignment
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST" class="flex items-center space-x-2">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" class="border rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                                                <option value="placed" <?= $order['status'] === 'placed' ? 'selected' : '' ?>>Placed</option>
                                                <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                                <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Apply filters
        document.getElementById('apply-filters').addEventListener('click', function() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            const status = document.getElementById('status-filter').value;
            
            let url = 'orders.php?';
            
            if (startDate && endDate) {
                url += `start_date=${startDate}&end_date=${endDate}`;
            }
            
            if (status) {
                url += `${startDate ? '&' : ''}status=${status}`;
            }
            
            window.location.href = url;
        });
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any initialization code here if needed
        });
    </script>
</body>
</html>