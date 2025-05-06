<?php
session_start();
require_once '../../includes/dbh.inc.php';

// Check if user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header('Location: ../../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];


// Get customer's address if set
$customerAddress = null;
$stmt = $pdo->prepare("SELECT address, image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
$image = $user['image'];
if ($user && !empty($user['address'])) {
    $customerAddress = $user['address'];
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $restaurantId = $_POST['restaurant_id'];
    $menuItemId = $_POST['menu_item_id'];
    $quantity = $_POST['quantity'] ?? 1;

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'items' => []
        ];
    }

    // Get menu item details
    $stmt = $pdo->prepare("SELECT mi.*, r.name as restaurant_name, r.image as restaurant_image 
                          FROM menu_items mi 
                          JOIN restaurants r ON mi.restaurant_id = r.id 
                          WHERE mi.id = ? AND mi.restaurant_id = ?");
    $stmt->execute([$menuItemId, $restaurantId]);
    $menuItem = $stmt->fetch();

    if ($menuItem) {
        // Initialize restaurant in cart if not exists
        if (!isset($_SESSION['cart']['items'][$restaurantId])) {
            $_SESSION['cart']['items'][$restaurantId] = [
                'restaurant_name' => $menuItem['restaurant_name'],
                'restaurant_image' => $menuItem['restaurant_image'],
                'items' => []
            ];
        }

        // Add or update item in cart
        $itemFound = false;
        foreach ($_SESSION['cart']['items'][$restaurantId]['items'] as &$item) {
            if ($item['id'] == $menuItemId) {
                $item['quantity'] += $quantity;
                $itemFound = true;
                break;
            }
        }

        if (!$itemFound) {
            $_SESSION['cart']['items'][$restaurantId]['items'][] = [
                'id' => $menuItemId,
                'name' => $menuItem['name'],
                'price' => $menuItem['price'],
                'quantity' => $quantity,
                'image' => $menuItem['image']
            ];
        }

        $_SESSION['cart_success'] = "Item added to cart!";
    }

    header("Location: index.php?restaurant_id=$restaurantId");
    exit();
}

// Handle removing from cart
if (isset($_GET['remove_item'])) {
    $itemId = $_GET['remove_item'];
    $restaurantId = $_GET['restaurant_id'];
    
    if (isset($_SESSION['cart']['items'][$restaurantId]['items'])) {
        foreach ($_SESSION['cart']['items'][$restaurantId]['items'] as $key => $item) {
            if ($item['id'] == $itemId) {
                unset($_SESSION['cart']['items'][$restaurantId]['items'][$key]);
                $_SESSION['cart']['items'][$restaurantId]['items'] = array_values($_SESSION['cart']['items'][$restaurantId]['items']);
                
                // If restaurant has no items, remove it
                if (empty($_SESSION['cart']['items'][$restaurantId]['items'])) {
                    unset($_SESSION['cart']['items'][$restaurantId]);
                }
                
                $_SESSION['cart_success'] = "Item removed from cart!";
                break;
            }
        }
    }
    
    header("Location: index.php" . (isset($_GET['restaurant_id']) ? "?restaurant_id=" . $_GET['restaurant_id'] : ""));
    exit();
}

// Handle clearing cart
if (isset($_GET['clear_cart'])) {
    unset($_SESSION['cart']);
    $_SESSION['cart_success'] = "Cart cleared successfully!";
    header("Location: index.php" . (isset($_GET['restaurant_id']) ? "?restaurant_id=" . $_GET['restaurant_id'] : ""));
    exit();
}

// Handle placing order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart']['items'])) {
        $_SESSION['cart_error'] = "Your cart is empty!";
        header("Location: index.php");
        exit();
    }

    // Validate address
    $address = $_POST['delivery_address'];
    if (empty($address)) {
        $_SESSION['cart_error'] = "Please enter a delivery address";
        header("Location: index.php");
        exit();
    }

    // Save address to user profile if changed
    if ($address !== $customerAddress) {
        $stmt = $pdo->prepare("UPDATE users SET address = ? WHERE id = ?");
        $stmt->execute([$address, $userId]);
    }

    try {
        $pdo->beginTransaction();

        // Create orders for each restaurant
        foreach ($_SESSION['cart']['items'] as $restaurantId => $restaurantData) {
            // Calculate total for this restaurant
            $total = 0;
            foreach ($restaurantData['items'] as $item) {
                $total += $item['price'] * $item['quantity'];
            }

        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, restaurant_id, total, status, created_at) VALUES (?, ?, ?, 'placed', NOW())");
            $stmt->execute([$userId, $restaurantId, $total]);
        $orderId = $pdo->lastInsertId();

        // Insert order items
            foreach ($restaurantData['items'] as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
            }
        }

        $pdo->commit();

        // Clear cart and show success
        unset($_SESSION['cart']);
        $_SESSION['order_success'] = "Orders placed successfully!";

        // Redirect to orders page
        header("Location: orders.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['cart_error'] = "Error placing order: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Get all restaurants
$restaurants = [];
$stmt = $pdo->prepare("SELECT r.*, COUNT(mi.id) as menu_items_count 
                       FROM restaurants r 
                       LEFT JOIN menu_items mi ON r.id = mi.restaurant_id AND mi.available = 1
                       GROUP BY r.id");
$stmt->execute();
$restaurants = $stmt->fetchAll();

// Get menu items if a restaurant is selected
$menuItems = [];
$selectedRestaurant = null;
if (isset($_GET['restaurant_id'])) {
    $restaurantId = $_GET['restaurant_id'];
    
    // Get restaurant details
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurantId]);
    $selectedRestaurant = $stmt->fetch();
    
    // Get available menu items
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? AND available = 1 ORDER BY name");
    $stmt->execute([$restaurantId]);
    $menuItems = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Food | Sweet Bite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #fff7ed 0%, #ffe3c0 100%);
        }
        .hero-banner {
            background: url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1200&q=80') center/cover no-repeat;
            min-height: 220px;
            border-radius: 0 0 2rem 2rem;
            box-shadow: 0 8px 32px #ff7b2522;
            position: relative;
            overflow: hidden;
        }
        .hero-overlay {
            background: rgba(255, 123, 37, 0.25);
            position: absolute;
            inset: 0;
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .glass {
            background: rgba(255,255,255,0.7);
            box-shadow: 0 8px 32px 0 rgba(31,38,135,0.10);
            backdrop-filter: blur(8px);
            border-radius: 1.5rem;
        }
        .sticky-cart {
            position: sticky;
            top: 2rem;
            z-index: 10;
        }
        .cart-animate {
            animation: cartPop 0.7s cubic-bezier(.4,0,.2,1);
        }
        @keyframes cartPop {
            0% { transform: scale(0.9); opacity: 0; }
            60% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); }
        }
        .glow-btn {
            transition: box-shadow 0.2s, background 0.2s;
        }
        .glow-btn:hover {
            box-shadow: 0 0 0 4px #ffedd5, 0 4px 20px #ff7b25a0;
            background: #ff7b25;
            color: #fff;
        }
        .tab-button.active {
            border-bottom: 3px solid #ff7b25;
            color: #ff7b25;
            font-weight: 600;
            background: linear-gradient(90deg, #ffedd5 0%, #fff 100%);
        }
        .tab-button {
            transition: all 0.2s;
        }
        .tab-button:not(.active):hover {
            color: #ff7b25;
            background: #fff7ed;
        }
        .restaurant-card {
            border: 2px solid #fff7ed;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .restaurant-card:hover {
            border: 2px solid #ff7b25;
            box-shadow: 0 6px 24px #ff7b2522;
        }
        .menu-item-card {
            border: 2px solid #f3f4f6;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .menu-item-card:hover {
            border: 2px solid #ff7b25;
            box-shadow: 0 6px 24px #ff7b2522;
        }
        .quantity-btn {
            transition: background 0.2s, color 0.2s;
        }
        .quantity-btn:hover {
            background: #ff7b25 !important;
            color: #fff !important;
        }
        .cart-item {
            transition: background 0.2s;
        }
        .cart-item:hover {
            background: #fff7ed;
        }
        .order-success {
            animation: fadeIn 1s;
        }
        .order-error {
            animation: fadeIn 1s;
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
    <!-- Hero Banner -->
    <div class="hero-banner mb-8">
        <div class="hero-overlay"></div>
        <div class="hero-content flex flex-col items-center justify-center h-full py-12 relative z-10">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white drop-shadow-lg mb-2 animate-bounce">
                Welcome to Sweet Bite!
            </h1>
            <p class="text-lg md:text-2xl text-white font-medium drop-shadow mb-4 animate-fadeIn">
                Delicious food, delivered fast &amp; fresh.
            </p>
            <div class="flex space-x-4 mt-2">
                <span class="inline-flex items-center px-4 py-2 bg-white bg-opacity-80 rounded-full shadow text-orange-600 font-semibold text-base animate-pulse">
                    <i class="fas fa-biking mr-2"></i> Fast Delivery
                </span>
                <span class="inline-flex items-center px-4 py-2 bg-white bg-opacity-80 rounded-full shadow text-orange-600 font-semibold text-base animate-pulse">
                    <i class="fas fa-leaf mr-2"></i> Fresh Ingredients
                </span>
                <span class="inline-flex items-center px-4 py-2 bg-white bg-opacity-80 rounded-full shadow text-orange-600 font-semibold text-base animate-pulse">
                    <i class="fas fa-star mr-2"></i> Top Rated
                </span>
            </div>
        </div>
    </div>
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fade-in glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="#" class="text-2xl font-extrabold text-orange-500 flex items-center tracking-tight">
                        <i class="fas fa-utensils mr-2 animate-spin-slow"></i>
                        Sweet Bite
                    </a>
                    <div class="user-avatar w-10 h-10 rounded-full ml-4 border-2 border-orange-200 overflow-hidden">
                        <img src="../<?php echo $image ?>" alt="User Avatar" class="w-10 h-10 object-cover">
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="orders.php" class="text-gray-700 hover:text-orange-500 font-medium transition">
                        <i class="fas fa-clipboard-list mr-1"></i> My Orders
                    </a>
                    <a href="/food-delivery-website/auth/logout.php" class="text-gray-700 hover:text-orange-500 font-medium transition">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 fade-in">
        <!-- Tabs -->
        <div class="border-b border-orange-100 mb-8">
            <div class="flex space-x-8">
                <a href="index.php" class="tab-button py-4 px-1 active">
                    <i class="fas fa-utensils mr-2"></i> Order Food
                </a>
                <a href="orders.php" class="tab-button py-4 px-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-clipboard-list mr-2"></i> My Orders
                </a>
                <a href="account.php" class="tab-button py-4 px-1 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-user mr-2"></i> My Account
                </a>
            </div>
        </div>
        <!-- Messages -->
        <?php if (isset($_SESSION['cart_success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6 rounded order-success shadow">
                <p class="text-green-700 text-lg font-semibold"><?= $_SESSION['cart_success'] ?></p>
            </div>
            <?php unset($_SESSION['cart_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['cart_error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6 rounded order-error shadow">
                <p class="text-red-700 text-lg font-semibold"><?= $_SESSION['cart_error'] ?></p>
            </div>
            <?php unset($_SESSION['cart_error']); ?>
        <?php endif; ?>
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Left Column - Restaurants & Menu -->
            <div class="lg:w-2/3">
                <?php if (!isset($_GET['restaurant_id'])): ?>
                    <!-- Restaurant List -->
                    <h2 class="text-3xl font-extrabold mb-6 text-orange-600 tracking-tight">Choose a Restaurant</h2>
                    <?php if (empty($restaurants)): ?>
                        <div class="bg-white p-8 rounded-lg shadow text-center fade-in glass">
                            <i class="fas fa-utensils text-gray-300 text-5xl mb-4 animate-pulse"></i>
                            <p class="text-gray-500">No restaurants available at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <?php foreach ($restaurants as $restaurant): ?>
                                <a href="index.php?restaurant_id=<?= $restaurant['id'] ?>" class="restaurant-card bg-white rounded-xl shadow scale-hover overflow-hidden fade-in glass">
                                    <div class="relative h-48 overflow-hidden">
                                        <img src="../../<?= $restaurant['image'] ?: 'assets/default-restaurant.jpg' ?>" 
                                             alt="<?= htmlspecialchars($restaurant['name']) ?>" 
                                             class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                        <span class="absolute top-2 right-2 bg-orange-100 text-orange-800 text-xs px-3 py-1 rounded-full shadow">
                                            <?= $restaurant['menu_items_count'] ?> items
                                        </span>
                                    </div>
                                    <div class="p-5">
                                        <h3 class="font-bold text-xl text-gray-800 mb-1"><?= htmlspecialchars($restaurant['name']) ?></h3>
                                        <p class="text-gray-500 text-sm flex items-center">
                                                    <i class="fas fa-map-marker-alt mr-1 text-orange-500"></i>
                                                    <?= htmlspecialchars($restaurant['address']) ?>
                                                </p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Restaurant Menu -->
                    <div class="flex items-center mb-6">
                        <a href="index.php" class="text-orange-500 hover:text-orange-600 mr-4 text-lg">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h2 class="text-3xl font-extrabold text-orange-600 tracking-tight"><?= htmlspecialchars($selectedRestaurant['name']) ?></h2>
                    </div>
                    <p class="text-gray-600 mb-6 flex items-center">
                        <i class="fas fa-map-marker-alt mr-1 text-orange-500"></i>
                        <?= htmlspecialchars($selectedRestaurant['address']) ?>
                    </p>
                    <?php if (empty($menuItems)): ?>
                        <div class="bg-white p-8 rounded-lg shadow text-center fade-in glass">
                            <i class="fas fa-utensils text-gray-300 text-5xl mb-4 animate-pulse"></i>
                            <p class="text-gray-500">This restaurant has no available menu items at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-8">
                            <?php foreach ($menuItems as $item): ?>
                                <div class="menu-item-card bg-white rounded-xl shadow scale-hover overflow-hidden fade-in glass">
                                    <div class="flex flex-col md:flex-row">
                                        <div class="md:w-1/3">
                                            <img src="../../<?= $item['image'] ?: 'assets/default-food.jpg' ?>" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                 class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105">
                                        </div>
                                        <div class="md:w-2/3 p-6">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <h3 class="font-bold text-xl text-gray-800 mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                                                    <p class="text-orange-500 font-bold mt-1 text-lg">$<?= number_format($item['price'], 2) ?></p>
                                                </div>
                                            </div>
                                            <p class="text-gray-600 mt-2 mb-4 text-base"><?= htmlspecialchars($item['description']) ?></p>
                                            <form method="POST" class="mt-4 flex items-center">
                                                <input type="hidden" name="restaurant_id" value="<?= $selectedRestaurant['id'] ?>">
                                                <input type="hidden" name="menu_item_id" value="<?= $item['id'] ?>">
                                                <div class="flex items-center mr-4">
                                                    <button type="button" class="quantity-btn bg-gray-200 px-3 py-1 rounded-l" onclick="decrementQuantity(this)">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" name="quantity" value="1" min="1" class="w-12 text-center border-t border-b border-gray-300 py-1">
                                                    <button type="button" class="quantity-btn bg-gray-200 px-3 py-1 rounded-r" onclick="incrementQuantity(this)">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <button type="submit" name="add_to_cart" class="glow-btn bg-orange-500 text-white px-6 py-2 rounded font-semibold shadow hover:bg-orange-600 transition-all duration-200">
                                                    <i class="fas fa-cart-plus mr-2"></i> Add to Cart
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <!-- Right Column - Cart (optional: you can add a beautiful cart summary here) -->
            <div class="lg:w-1/3 hidden lg:block">
                <div class="sticky-cart cart-animate glass p-6 shadow-xl">
                    <h3 class="text-xl font-bold text-orange-600 mb-4 flex items-center">
                        <i class="fas fa-shopping-cart mr-2 animate-bounce"></i> Your Cart
                    </h3>
                    <div class="space-y-4">
                        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart']['items'])): ?>
                            <?php 
                            $grandTotal = 0;
                            foreach ($_SESSION['cart']['items'] as $restaurantId => $restaurantData): 
                                $restaurantTotal = 0;
                            ?>
                                <div class="restaurant-cart-section bg-orange-50 rounded-lg p-3 mb-4">
                                    <div class="flex items-center mb-2">
                                        <img src="../../<?= htmlspecialchars($restaurantData['restaurant_image']) ?>" 
                                             alt="<?= htmlspecialchars($restaurantData['restaurant_name']) ?>" 
                                             class="w-8 h-8 rounded-full object-cover mr-2">
                                        <h4 class="font-semibold text-orange-800"><?= htmlspecialchars($restaurantData['restaurant_name']) ?></h4>
                                    </div>
                                    <div class="space-y-2">
                                        <?php foreach ($restaurantData['items'] as $item): 
                                            $itemTotal = $item['price'] * $item['quantity'];
                                            $restaurantTotal += $itemTotal;
                                        ?>
                                            <div class="cart-item flex items-center justify-between bg-white rounded-lg px-3 py-2 shadow-sm">
                                                <div class="flex items-center">
                                                    <img src="../../<?= htmlspecialchars($item['image']) ?>" 
                                                         alt="<?= htmlspecialchars($item['name']) ?>" 
                                                         class="w-10 h-10 rounded object-cover mr-3">
                                                    <div>
                                                        <div class="font-semibold text-gray-800 text-base"><?= htmlspecialchars($item['name']) ?></div>
                                                        <div class="text-xs text-gray-500">x<?= $item['quantity'] ?> &bull; $<?= number_format($item['price'], 2) ?></div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center">
                                                    <span class="text-orange-500 font-bold mr-3">$<?= number_format($itemTotal, 2) ?></span>
                                                    <a href="index.php?remove_item=<?= $item['id'] ?>&restaurant_id=<?= $restaurantId ?>" 
                                                       class="text-red-400 hover:text-red-600">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="text-right text-sm font-semibold text-orange-800">
                                            Restaurant Total: $<?= number_format($restaurantTotal, 2) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php $grandTotal += $restaurantTotal; ?>
                            <?php endforeach; ?>
                            <div class="border-t border-orange-200 pt-3 mt-3">
                                <div class="flex justify-between items-center text-lg font-bold text-orange-600">
                                    <span>Grand Total:</span>
                                    <span>$<?= number_format($grandTotal, 2) ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-gray-400 text-center py-8">
                                <i class="fas fa-shopping-basket text-3xl mb-2 animate-pulse"></i>
                                <div>Your cart is empty.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart']['items'])): ?>
                        <form method="POST" class="mt-6">
                            <div class="mb-4">
                                <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Delivery Address
                                </label>
                                <textarea
                                    id="delivery_address"
                                    name="delivery_address"
                                    rows="2"
                                    required
                                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                                ><?= htmlspecialchars($customerAddress ?? '') ?></textarea>
                            </div>
                            <button type="submit" name="place_order" class="glow-btn w-full bg-orange-500 text-white py-3 rounded-lg font-bold text-lg shadow hover:bg-orange-600 transition-all duration-200">
                                <i class="fas fa-check-circle mr-2"></i> Place All Orders
                            </button>
                        </form>
                        <a href="index.php?clear_cart=1" class="block text-center text-orange-400 hover:text-orange-600 mt-3 text-sm transition">
                            <i class="fas fa-trash-alt mr-1"></i> Clear Cart
                        </a>
                    <?php endif; ?>
                </div>
            </div>
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
        // Quantity buttons functionality
        function incrementQuantity(button) {
            const input = button.parentElement.querySelector('input[type=\"number\"]');
            input.value = parseInt(input.value) + 1;
        }
        function decrementQuantity(button) {
            const input = button.parentElement.querySelector('input[type=\"number\"]');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
        // Animate fade-in for cards
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.fade-in').forEach(function(el, i) {
                el.style.animationDelay = (i * 0.07) + 's';
            });
        });
    </script>
</body>
</html>