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
                           WHERE o.delivery_id = ? AND o.status IN ('ready', 'picked_up')
                           ORDER BY 
                             CASE o.status 
                               WHEN 'ready' THEN 1 
                               WHEN 'picked_up' THEN 2 
                               ELSE 3 
                             END, o.created_at");
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
        .order-card {
            transition: all 0.2s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .tab-button {
            transition: all 0.2s ease;
        }
        .tab-button.active {
            border-bottom: 2px solid #f97316;
            color: #f97316;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold text-orange-500 flex items-center">
                        <i class="fas fa-motorcycle mr-2"></i>
                        Savory Delivery
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../../auth/logout.php" class="text-gray-600 hover:text-orange-500">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700"><?= $_SESSION['error'] ?></p>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                <p class="text-green-700"><?= $_SESSION['success'] ?></p>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-8">
            <div class="flex space-x-8">
                <button id="assigned-tab" class="tab-button py-4 px-1 active">
                    <i class="fas fa-clipboard-list mr-2"></i> My Deliveries
                </button>
                <button id="available-tab" class="tab-button py-4 px-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-list-alt mr-2"></i> Available Orders
                </button>
            </div>
        </div>

        <!-- Assigned Orders Tab -->
        <div id="assigned-orders" class="tab-content">
            <h2 class="text-2xl font-bold mb-6">My Deliveries</h2>
            
            <?php if (empty($assignedOrders)): ?>
                <div class="bg-white p-8 rounded-lg shadow text-center">
                    <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">You don't have any assigned deliveries at the moment.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($assignedOrders as $order): ?>
                        <div class="order-card bg-white rounded-lg shadow overflow-hidden">
                            <!-- Order Header -->
                            <div class="p-4 border-b flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 rounded overflow-hidden mr-4">
                                        <img src="../../<?= htmlspecialchars($order['restaurant_image'] ?: 'assets/default-restaurant.jpg') ?>" 
                                             alt="<?= htmlspecialchars($order['restaurant_name']) ?>" 
                                             class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <h3 class="font-bold">Order #<?= $order['id'] ?></h3>
                                        <p class="text-sm text-gray-500">
                                            <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadgeClass($order['status']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Order Details -->
                            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Restaurant Info -->
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-store mr-2 text-orange-500"></i> Restaurant
                                    </h4>
                                    <p class="font-bold"><?= htmlspecialchars($order['restaurant_name']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($order['restaurant_address']) ?></p>
                                </div>

                                <!-- Customer Info -->
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-user mr-2 text-orange-500"></i> Customer
                                    </h4>
                                    <p class="font-bold"><?= htmlspecialchars($order['customer_name']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($order['customer_phone']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($order['customer_address']) ?></p>
                                </div>

                                <!-- Order Items -->
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-2">
                                        <i class="fas fa-utensils mr-2 text-orange-500"></i> Items
                                    </h4>
                                    <ul class="space-y-1">
                                        <?php foreach ($order['items'] as $item): ?>
                                            <li class="text-sm">
                                                <?= htmlspecialchars($item['item_name']) ?> 
                                                <span class="font-medium">(x<?= $item['quantity'] ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Status Update -->
                            <div class="p-4 border-t bg-gray-50">
                                <form method="POST" class="flex justify-between items-center">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    
                                    <div class="flex-1">
                                        <select name="status" class="border rounded px-3 py-2 mr-4">
                                            <?php if ($order['status'] === 'ready'): ?>
                                                <option value="picked_up">Mark as Picked Up</option>
                                            <?php elseif ($order['status'] === 'picked_up'): ?>
                                                <option value="delivered">Mark as Delivered</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <button type="submit" name="update_status" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                                            Update Status
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Orders Tab -->
        <div id="available-orders" class="tab-content hidden">
            <h2 class="text-2xl font-bold mb-6">Available Orders</h2>
            
            <?php if (empty($availableOrders)): ?>
                <div class="bg-white p-8 rounded-lg shadow text-center">
                    <i class="fas fa-clipboard-list text-gray-300 text-5xl mb-4"></i>
                    <p class="text-gray-500">No available orders at the moment.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($availableOrders as $order): ?>
                        <div class="order-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4 border-b">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold">Order #<?= $order['id'] ?></h3>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium <?= getStatusBadgeClass($order['status']) ?>">
                                        Ready for pickup
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                            
                            <div class="p-4">
                                <div class="mb-4">
                                    <h4 class="font-medium text-gray-700 mb-1">
                                        <i class="fas fa-store mr-2 text-orange-500"></i> Restaurant
                                    </h4>
                                    <p><?= htmlspecialchars($order['restaurant_name']) ?></p>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($order['restaurant_address']) ?></p>
                                </div>
                                
                                <div class="mb-4">
                                    <h4 class="font-medium text-gray-700 mb-1">
                                        <i class="fas fa-user mr-2 text-orange-500"></i> Customer
                                    </h4>
                                    <p><?= htmlspecialchars($order['customer_name']) ?></p>
                                </div>
                                
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" name="accept_order" class="w-full bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                                        Accept Delivery
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tab functionality
        document.getElementById('assigned-tab').addEventListener('click', function() {
            document.getElementById('assigned-orders').classList.remove('hidden');
            document.getElementById('available-orders').classList.add('hidden');
            this.classList.add('active');
            document.getElementById('available-tab').classList.remove('active');
        });
        
        document.getElementById('available-tab').addEventListener('click', function() {
            document.getElementById('assigned-orders').classList.add('hidden');
            document.getElementById('available-orders').classList.remove('hidden');
            this.classList.add('active');
            document.getElementById('assigned-tab').classList.remove('active');
        });
    </script>
</body>
</html>