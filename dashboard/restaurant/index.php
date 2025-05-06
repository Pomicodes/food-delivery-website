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
    header("Location: index.php");
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
    <title>Restaurant Orders | Sweet Bite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #fff7ed 0%, #ffe3c0 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255,255,255,0.7);
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.10);
            backdrop-filter: blur(8px);
            border-radius: 1.5rem;
            transition: all 0.3s ease;
        }
        .glass:hover {
            background: rgba(255,255,255,0.8);
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.15);
        }
        .order-card {
            transition: all 0.3s cubic-bezier(.4,0,.2,1);
            border: 2px solid #fff7ed;
            position: relative;
            overflow: hidden;
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
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 8px 32px #ff7b2522;
            border: 2px solid #ff7b25;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
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
            box-shadow: 0 0 0 4px #ffedd5, 0 4px 20px #ff7b25a0;
            transform: translateY(-1px);
        }
        .status-placed { 
            background-color: #FEF3C7; 
            color: #92400E;
            animation: pulse 2s infinite;
        }
        .status-ready { 
            background-color: #D1FAE5; 
            color: #065F46;
            animation: bounce 2s infinite;
        }
        .status-picked_up { 
            background-color: #DBEAFE; 
            color: #1E40AF;
            animation: slide 2s infinite;
        }
        .status-delivered { 
            background-color: #E5E7EB; 
            color: #4B5563;
        }
        .glow-btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .glow-btn::before {
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
        .glow-btn:hover::before {
            left: 100%;
        }
        .glow-btn:hover {
            box-shadow: 0 0 0 4px #ffedd5, 0 4px 20px #ff7b25a0;
            background: #ff7b25;
            color: #fff;
            transform: translateY(-2px);
        }
        .fade-in {
            animation: fadeIn 0.8s ease-in;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(20px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        @keyframes slide {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(5px); }
        }
        .footer {
            background: #fff7ed;
            color: #ff7b25;
            padding: 2rem 0 1rem 0;
            text-align: center;
            border-radius: 2rem 2rem 0 0;
            margin-top: 3rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: footerShine 3s infinite;
        }
        @keyframes footerShine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .footer a {
            color: #ff7b25;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            position: relative;
        }
        .footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #ff7b25;
            transition: width 0.3s ease;
        }
        .footer a:hover {
            color: #ff9d5c;
            transform: translateY(-2px);
        }
        .footer a:hover::after {
            width: 100%;
        }
        .order-item {
            transition: all 0.3s ease;
        }
        .order-item:hover {
            background: rgba(255,123,37,0.05);
            transform: translateX(5px);
        }
        select {
            transition: all 0.3s ease;
        }
        select:hover {
            border-color: #ff7b25;
            box-shadow: 0 0 0 2px rgba(255,123,37,0.2);
        }
        input[type="date"] {
            transition: all 0.3s ease;
        }
        input[type="date"]:hover {
            border-color: #ff7b25;
            box-shadow: 0 0 0 2px rgba(255,123,37,0.2);
        }
    </style>
</head>
<body class="min-h-screen">
    <?php include('./sidebar.php'); ?>
    <div class="ml-64 p-8 fade-in">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-extrabold text-orange-600 tracking-tight">
                    <i class="fas fa-clipboard-list text-orange-500 mr-2 animate-bounce"></i>
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
                        <button id="apply-filters" class="glow-btn bg-orange-500 text-white px-4 py-2 rounded font-semibold shadow hover:bg-orange-600 transition-all duration-200">
                            Apply
                        </button>
                    </div>
                </div>
            </div>
            <?php if (!$restaurantId): ?>
                <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6 rounded shadow">
                    <p class="text-red-700 text-lg font-semibold">You need to create a restaurant first to view orders.</p>
                </div>
            <?php else: ?>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6 rounded shadow">
                        <p class="text-green-700 text-lg font-semibold"><?= $_SESSION['success_message'] ?></p>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                <div class="space-y-8">
                    <?php if (empty($orders)): ?>
                        <div class="bg-white p-8 rounded-lg shadow text-center glass fade-in">
                            <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-4 animate-pulse"></i>
                            <p class="text-gray-500">No orders found for the selected criteria.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card bg-white rounded-xl shadow overflow-hidden glass fade-in">
                                <div class="p-8">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800 mb-1">Order #<?= $order['id'] ?></h3>
                                            <p class="text-sm text-gray-500 mt-1">
                                                <i class="far fa-clock mr-1"></i>
                                                <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <span class="status-badge status-<?= $order['status'] ?>">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                <?= str_replace('_', ' ', $order['status']) ?>
                                            </span>
                                            <p class="text-lg font-bold text-orange-500">
                                                $<?= number_format($order['total'], 2) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mb-4 p-4 bg-orange-50 rounded-lg">
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
                                    <div class="mb-6">
                                        <h4 class="text-md font-medium text-gray-700 mb-3">Order Items</h4>
                                        <div class="space-y-3">
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                                    <div>
                                                        <p class="text-gray-800 font-semibold"><?= htmlspecialchars($item['item_name']) ?></p>
                                                        <p class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?></p>
                                                    </div>
                                                    <p class="text-orange-500 font-bold">$<?= number_format($item['price'], 2) ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                                        <div>
                                            <?php if ($order['delivery_id']): ?>
                                                <p class="text-sm text-gray-500 flex items-center">
                                                    <i class="fas fa-motorcycle mr-1 animate-bounce"></i>
                                                    Delivery assigned
                                                </p>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-500 flex items-center">
                                                    <i class="fas fa-info-circle mr-1 animate-pulse"></i>
                                                    Waiting for delivery assignment
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST" class="flex items-center space-x-2">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <?php if ($order['status'] === 'placed'): ?>
                                                <button type="submit" name="update_status" value="1" class="glow-btn bg-green-500 text-white px-4 py-2 rounded font-semibold shadow hover:bg-green-600 transition-all duration-200">
                                                    <i class="fas fa-check mr-1"></i> Mark as Ready
                                                </button>
                                                <input type="hidden" name="status" value="ready">
                                            <?php else: ?>
                                            <select name="status" class="border rounded px-3 py-2 text-sm" onchange="this.form.submit()">
                                                <option value="placed" <?= $order['status'] === 'placed' ? 'selected' : '' ?>>Placed</option>
                                                <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                                                <option value="picked_up" <?= $order['status'] === 'picked_up' ? 'selected' : '' ?>>Picked Up</option>
                                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                            <?php endif; ?>
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
    <footer class="footer">
        <div>
            &copy; <?= date('Y') ?> Sweet Bite &mdash; Made with <span class="text-red-400">&#10084;</span> for food lovers.
        </div>
        <div class="mt-2">
            <a href="#"><i class="fab fa-facebook fa-lg"></i></a>
            <a href="#"><i class="fab fa-instagram fa-lg"></i></a>
            <a href="#"><i class="fab fa-twitter fa-lg"></i></a>
        </div>
    </footer>
    <script>
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
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                el.style.animationDelay = (i * 0.07) + 's';
            });
        });
    </script>
</body>
</html>