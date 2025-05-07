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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        // Add new menu item
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $available = isset($_POST['available']) ? 1 : 0;
        
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image'])) {
            $uploadDir = '../../uploads/menu_items/';
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $imageName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/menu_items/' . $imageName;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO menu_items (restaurant_id, name, description, price, available, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$restaurantId, $name, $description, $price, $available, $imagePath]);
        
        $_SESSION['success_message'] = 'Menu item added successfully!';
        header("Location: menu.php");
        exit();
    } elseif (isset($_POST['update_item'])) {
        // Update existing menu item
        $id = $_POST['item_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $available = isset($_POST['available']) ? 1 : 0;
        
        // Handle image upload if a new image was provided
        $imageUpdate = '';
        $imageParams = [];
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = '../../uploads/menu_items/';
            $imageName = time() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $imageName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = 'uploads/menu_items/' . $imageName;
                $imageUpdate = ', image = ?';
                $imageParams[] = $imagePath;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, available = ? $imageUpdate WHERE id = ? AND restaurant_id = ?");
        $params = array_merge([$name, $description, $price, $available], $imageParams, [$id, $restaurantId]);
        $stmt->execute($params);
        
        $_SESSION['success_message'] = 'Menu item updated successfully!';
        header("Location: menu.php");
        exit();
    } elseif (isset($_POST['delete_item'])) {
        // Delete menu item
        $id = $_POST['item_id'];
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // First, update order_items to set menu_item_id to NULL
            $stmt = $pdo->prepare("UPDATE order_items SET menu_item_id = NULL WHERE menu_item_id = ?");
            $stmt->execute([$id]);
            
            // Then delete the menu item
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$id, $restaurantId]);
            
            // Commit transaction
            $pdo->commit();
        
        $_SESSION['success_message'] = 'Menu item deleted successfully!';
        } catch (PDOException $e) {
            // Rollback on error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['error_message'] = 'Failed to delete menu item: ' . $e->getMessage();
        }
        
        header("Location: menu.php");
        exit();
    }
}

// Get all menu items for this restaurant
$menuItems = [];
if ($restaurantId) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY name");
    $stmt->execute([$restaurantId]);
    $menuItems = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Menu Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .menu-card { transition: all 0.3s ease; }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .menu-item-image { height: 180px; object-fit: cover; }
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
                    <i class="fas fa-utensils text-orange-500 mr-2"></i>
                    Menu Management
                </h1>
                <a href="./settings.php?tab=menu" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                    <i class="fas fa-plus mr-2"></i> Add New Item
                </a>
            </div>

            <?php if (!$restaurantId): ?>
                <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-700">You need to create a restaurant first to manage menu items.</p>
                </div>
            <?php else: ?>
                <!-- Success Message -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                        <p class="text-green-700"><?= $_SESSION['success_message'] ?></p>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
                        <p class="text-red-700"><?= $_SESSION['error_message'] ?></p>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Menu Items Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <?php foreach ($menuItems as $item): ?>
                        <div class="menu-card bg-white rounded-lg shadow overflow-hidden">
                            <div class="relative">
                                <img src="../../<?= $item['image'] ?: 'assets/default-food.jpg' ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-full menu-item-image">
                                <span class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-semibold <?= $item['available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $item['available'] ? 'Available' : 'Unavailable' ?>
                                </span>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="text-orange-500 font-bold mt-1">$<?= number_format($item['price'], 2) ?></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="openEditModal(<?= $item['id'] ?>)" class="text-blue-500 hover:text-blue-700">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $item['id'] ?>)" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-gray-600 mt-2 text-sm"><?= htmlspecialchars($item['description']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($menuItems)): ?>
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-utensils text-gray-300 text-5xl mb-4"></i>
                            <p class="text-gray-500">No menu items found. Add your first item!</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="item-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center border-b px-6 py-4">
                <h3 class="text-lg font-semibold" id="modal-title">Add New Menu Item</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="menu-item-form" method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="item_id" id="item_id">
                <input type="hidden" name="add_item" id="add_item">
                <input type="hidden" name="update_item" id="update_item">
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                    <input type="text" id="name" name="name" required class="w-full px-3 py-2 border rounded-md">
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required class="w-full px-3 py-2 border rounded-md">
                </div>
                
                <div class="mb-4">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                    <input type="file" id="image" name="image" accept="image/*" class="w-full">
                    <div class="mt-2" id="image-preview-container" style="display: none;">
                        <img id="image-preview" src="#" alt="Image preview" class="max-w-full h-32 object-cover rounded">
                    </div>
                </div>
                
                <div class="mb-4 flex items-center">
                    <input type="checkbox" id="available" name="available" class="mr-2" checked>
                    <label for="available" class="text-sm font-medium text-gray-700">Available for ordering</label>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">
                        Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="flex justify-between items-center border-b px-6 py-4">
                <h3 class="text-lg font-semibold">Confirm Deletion</h3>
                <button onclick="closeDeleteModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4">Are you sure you want to delete this menu item? This action cannot be undone.</p>
                <form id="delete-form" method="POST">
                    <input type="hidden" name="item_id" id="delete_item_id">
                    <input type="hidden" name="delete_item">
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                            Delete Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add New Menu Item';
            document.getElementById('item_id').value = '';
            document.getElementById('add_item').value = '1';
            document.getElementById('update_item').removeAttribute('value');
            
            // Reset form
            document.getElementById('menu-item-form').reset();
            document.getElementById('image-preview-container').style.display = 'none';
            
            document.getElementById('item-modal').classList.remove('hidden');
        }
        
        function openEditModal(itemId) {
            document.getElementById('modal-title').textContent = 'Edit Menu Item';
            document.getElementById('item_id').value = itemId;
            document.getElementById('update_item').value = '1';
            document.getElementById('add_item').removeAttribute('value');
            
            // Fetch item data
            fetch(`./menu_api.php?action=get_item&id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('name').value = item.name;
                        document.getElementById('description').value = item.description;
                        document.getElementById('price').value = item.price;
                        document.getElementById('available').checked = item.available;
                        
                        // Show current image if exists
                        if (item.image) {
                            document.getElementById('image-preview').src = '../../' + item.image;
                            document.getElementById('image-preview-container').style.display = 'block';
                        } else {
                            document.getElementById('image-preview-container').style.display = 'none';
                        }
                        
                        document.getElementById('item-modal').classList.remove('hidden');
                    } else {
                        alert('Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to fetch menu item data');
                });
        }
        
        function closeModal() {
            document.getElementById('item-modal').classList.add('hidden');
        }
        
        function confirmDelete(itemId) {
            document.getElementById('delete_item_id').value = itemId;
            document.getElementById('delete-modal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
        }
        
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('image-preview').src = event.target.result;
                    document.getElementById('image-preview-container').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('image-preview-container').style.display = 'none';
            }
        });
        
        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Add item button
            document.getElementById('add-item-btn').addEventListener('click', openAddModal);
        });
    </script>
</body>
</html>