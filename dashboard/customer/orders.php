<?php
session_start();
require_once '../../includes/dbh.inc.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get all orders for this customer with restaurant details
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT o.*, r.name as restaurant_name, r.image as restaurant_image 
                          FROM orders o
                          JOIN restaurants r ON o.restaurant_id = r.id
                          WHERE o.customer_id = ?
                          ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("SELECT oi.*, mi.name as item_name, mi.image as item_image
                              FROM order_items oi
                              JOIN menu_items mi ON oi.menu_item_id = mi.id
                              WHERE oi.order_id = ?");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $_SESSION['error'] = "Error loading your orders. Please try again later.";
}

// Function to get status badge class based on order status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'placed':
            return 'bg-yellow-100 text-yellow-800';
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
    <title>My Orders | Sweet Bite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #fff7ed 0%, #ffe3c0 100%);
        }
        .glass {
            background: rgba(255,255,255,0.7);
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.10);
            backdrop-filter: blur(8px);
            border-radius: 1.5rem;
        }
        .order-card {
            transition: all 0.2s cubic-bezier(.4,0,.2,1);
            border: 2px solid #fff7ed;
        }
        .order-card:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 8px 32px #ff7b2522;
            border: 2px solid #ff7b25;
        }
        .order-item {
            transition: background 0.2s;
        }
        .order-item:hover {
            background: #fff7ed;
        }
        .status-badge {
            transition: box-shadow 0.2s;
        }
        .status-badge:hover {
            box-shadow: 0 0 0 4px #ffedd5, 0 4px 20px #ff7b25a0;
        }
        .glow-btn {
            transition: box-shadow 0.2s, background 0.2s;
        }
        .glow-btn:hover {
            box-shadow: 0 0 0 4px #ffedd5, 0 4px 20px #ff7b25a0;
            background: #ff7b25;
            color: #fff;
        }
        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .footer {
            background: #fff7ed;
            color: #ff7b25;
            padding: 2rem 0 1rem 0;
            text-align: center;
            border-radius: 2rem 2rem 0 0;
            margin-top: 3rem;
            font-weight: 500;
        }
        .footer a {
            color: #ff7b25;
            margin: 0 0.5rem;
            transition: color 0.2s;
        }
        .footer a:hover {
            color: #ff9d5c;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg glass mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-extrabold text-orange-500 flex items-center tracking-tight">
                        <i class="fas fa-utensils mr-2 animate-spin-slow"></i>
                        Sweet Bite
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-orange-500 font-medium transition">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>
                    <a href="/food-delivery-website/auth/logout.php" class="text-gray-700 hover:text-orange-500 font-medium transition">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 fade-in">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-extrabold text-orange-600 tracking-tight">
                <i class="fas fa-clipboard-list text-orange-500 mr-2 animate-bounce"></i>
                My Orders
            </h1>
        </div>
        <!-- Error Message -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6 rounded shadow">
                <p class="text-red-700 text-lg font-semibold"><?= $_SESSION['error'] ?></p>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <!-- Success Message -->
        <?php if (isset($_SESSION['order_success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6 rounded shadow">
                <p class="text-green-700 text-lg font-semibold"><?= $_SESSION['order_success'] ?></p>
            </div>
            <?php unset($_SESSION['order_success']); ?>
        <?php endif; ?>
        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="bg-white p-8 rounded-lg shadow text-center glass fade-in">
                <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-4 animate-pulse"></i>
                <p class="text-gray-500">You haven't placed any orders yet.</p>
                <a href="index.php" class="inline-block mt-4 glow-btn bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                    <i class="fas fa-utensils mr-1"></i> Order Now
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-8">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card bg-white rounded-xl shadow overflow-hidden glass fade-in">
                        <!-- Order Header -->
                        <div class="p-6 border-b flex justify-between items-center bg-gradient-to-r from-orange-50 to-white">
                            <div class="flex items-center">
                                <div class="w-14 h-14 rounded-xl overflow-hidden mr-4 border-2 border-orange-100">
                                    <img src="../../<?= htmlspecialchars($order['restaurant_image'] ?: 'assets/default-restaurant.jpg') ?>" 
                                         alt="<?= htmlspecialchars($order['restaurant_name']) ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 mb-1"><?= htmlspecialchars($order['restaurant_name']) ?></h3>
                                    <p class="text-sm text-gray-500">
                                        Order #<?= $order['id'] ?> • 
                                        <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge px-4 py-1 rounded-full text-sm font-semibold <?= getStatusBadgeClass($order['status']) ?>">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                </span>
                            </div>
                        </div>
                        <!-- Order Items -->
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item p-4 flex items-center">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden mr-4 border border-orange-100">
                                        <img src="../../<?= htmlspecialchars($item['item_image'] ?: 'assets/default-food.jpg') ?>" 
                                             alt="<?= htmlspecialchars($item['item_name']) ?>" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between">
                                            <h4 class="font-medium text-gray-800 text-base"><?= htmlspecialchars($item['item_name']) ?></h4>
                                            <span class="font-bold text-orange-500">$<?= number_format($item['price'], 2) ?></span>
                                        </div>
                                        <div class="flex justify-between items-center mt-1">
                                            <span class="text-sm text-gray-500">Qty: <?= $item['quantity'] ?></span>
                                            <span class="text-sm font-bold">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Order Footer -->
                        <div class="p-6 border-t bg-gradient-to-r from-orange-50 to-white flex justify-between items-center">
                            <div>
                                <?php if ($order['delivery_id']): ?>
                                    <p class="text-sm text-gray-500 flex items-center">
                                        <i class="fas fa-motorcycle mr-1 animate-bounce"></i>
                                        Delivery in progress
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500 flex items-center">
                                        <i class="fas fa-clock mr-1 animate-pulse"></i>
                                        Preparing your order
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Total</p>
                                <p class="text-2xl font-bold text-orange-500">$<?= number_format($order['total'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                el.style.animationDelay = (i * 0.07) + 's';
            });
        });
    </script>
</body>
</html>