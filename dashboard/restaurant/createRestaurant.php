<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Your Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .error-message {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-error {
            border-color: #ef4444 !important;
        }
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto my-10 p-6 bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Form Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Add Your Restaurant</h1>
            <p class="text-gray-600 mt-2">Share your culinary vision with the world</p>
        </div>

        <!-- Server-side error message (if any) -->
        <?php if (isset($_GET['error'])): ?>
            <div id="server-error" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Client-side error container (hidden by default) -->
        <div id="client-error" class="hidden mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <p id="error-text"></p>
            </div>
        </div>

        <!-- Success message (if any) -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo htmlspecialchars(urldecode($_GET['success'])); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Restaurant Form -->
        <form id="restaurant-form" action="resPros.php" method="POST" enctype="multipart/form-data" class="space-y-6" novalidate>
            <!-- Image Upload Section -->
            <div class="mb-8 text-center">
                <div id="image-preview" class="hidden mb-4 relative">
                    <img id="preview" class="h-48 w-full object-cover rounded-lg mx-auto shadow-md" src="#" alt="Restaurant preview">
                    <button type="button" id="remove-image" class="absolute top-2 right-2 bg-white rounded-full p-2 shadow-md hover:bg-gray-100">
                        <i class="fas fa-times text-red-500"></i>
                    </button>
                </div>
                <label for="image-upload" class="cursor-pointer">
                    <div id="upload-area" class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-orange-400 transition-colors">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-camera text-4xl text-orange-500 mb-2"></i>
                            <p class="text-gray-700 font-medium">Upload Restaurant Image</p>
                            <p class="text-sm text-gray-500 mt-1">JPEG or PNG, Max 2MB</p>
                        </div>
                    </div>
                    <input id="image-upload" type="file" name="image" accept="image/jpeg, image/png" class="hidden">
                </label>
                <p id="image-error" class="hidden mt-2 text-sm text-red-600 error-message"></p>
            </div>

            <!-- Restaurant Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Restaurant Name *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-utensils"></i>
                    </span>
                    <input type="text" id="name" name="name" required
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                           placeholder="e.g. Gourmet Paradise">
                </div>
                <p id="name-error" class="hidden mt-2 text-sm text-red-600 error-message"></p>
            </div>

            <!-- Address -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-map-marker-alt"></i>
                    </span>
                    <textarea id="address" name="address" rows="3" required
                              class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                              placeholder="Full address including city and postal code"></textarea>
                </div>
                <p id="address-error" class="hidden mt-2 text-sm text-red-600 error-message"></p>
            </div>

            <!-- Phone Number -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Contact Number *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-phone"></i>
                    </span>
                    <input type="tel" id="phone" name="phone" required
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                           placeholder="e.g. +1 234 567 8900">
                </div>
                <p id="phone-error" class="hidden mt-2 text-sm text-red-600 error-message"></p>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button type="submit" id="submit-btn"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                    <i class="fas fa-plus-circle mr-2"></i> Create Restaurant
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('restaurant-form');
            const imageUpload = document.getElementById('image-upload');
            const imagePreview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview');
            const removeImageBtn = document.getElementById('remove-image');
            const uploadArea = document.getElementById('upload-area');
            const submitBtn = document.getElementById('submit-btn');
            
            // Error elements
            const clientError = document.getElementById('client-error');
            const errorText = document.getElementById('error-text');
            const imageError = document.getElementById('image-error');
            const nameError = document.getElementById('name-error');
            const addressError = document.getElementById('address-error');
            const phoneError = document.getElementById('phone-error');

            // Hide server error after 5 seconds
            const serverError = document.getElementById('server-error');
            if (serverError) {
                setTimeout(() => {
                    serverError.style.transition = 'opacity 0.5s ease';
                    serverError.style.opacity = '0';
                    setTimeout(() => serverError.remove(), 500);
                }, 5000);
            }

            // Image upload handling
            imageUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                
                if (!file) return;
                
                // Validate file type and size
                const validTypes = ['image/jpeg', 'image/png'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!validTypes.includes(file.type)) {
                    showError(imageError, 'Please upload a JPEG or PNG image');
                    imageUpload.value = '';
                    return;
                }
                
                if (file.size > maxSize) {
                    showError(imageError, 'Image size must be less than 2MB');
                    imageUpload.value = '';
                    return;
                }
                
                // If valid, show preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                    imagePreview.classList.remove('hidden');
                    uploadArea.classList.add('hidden');
                    hideError(imageError);
                }
                reader.readAsDataURL(file);
            });

            // Remove image
            removeImageBtn.addEventListener('click', function() {
                imageUpload.value = '';
                previewImg.src = '#';
                imagePreview.classList.add('hidden');
                uploadArea.classList.remove('hidden');
            });

            // Form validation
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Reset errors
                hideError(clientError);
                hideError(imageError);
                hideError(nameError);
                hideError(addressError);
                hideError(phoneError);
                
                // Remove error classes
                document.querySelectorAll('.input-error').forEach(el => {
                    el.classList.remove('input-error', 'shake');
                });
                
                // Validate form
                let isValid = true;
                
                // Validate image (optional)
                if (imageUpload.files.length > 0) {
                    const file = imageUpload.files[0];
                    const validTypes = ['image/jpeg', 'image/png'];
                    const maxSize = 2 * 1024 * 1024;
                    
                    if (!validTypes.includes(file.type)) {
                        showError(imageError, 'Please upload a JPEG or PNG image');
                        addErrorClass(imageUpload);
                        isValid = false;
                    } else if (file.size > maxSize) {
                        showError(imageError, 'Image size must be less than 2MB');
                        addErrorClass(imageUpload);
                        isValid = false;
                    }
                }
                
                // Validate name
                const name = document.getElementById('name').value.trim();
                if (!name) {
                    showError(nameError, 'Restaurant name is required');
                    addErrorClass(document.getElementById('name'));
                    isValid = false;
                } else if (name.length > 100) {
                    showError(nameError, 'Name must be less than 100 characters');
                    addErrorClass(document.getElementById('name'));
                    isValid = false;
                }
                
                // Validate address
                const address = document.getElementById('address').value.trim();
                if (!address) {
                    showError(addressError, 'Address is required');
                    addErrorClass(document.getElementById('address'));
                    isValid = false;
                }
                
                // Validate phone
                const phone = document.getElementById('phone').value.trim();
                if (!phone) {
                    showError(phoneError, 'Phone number is required');
                    addErrorClass(document.getElementById('phone'));
                    isValid = false;
                } else if (!/^[\d\s\+\-\(\)]{10,20}$/.test(phone)) {
                    showError(phoneError, 'Please enter a valid phone number');
                    addErrorClass(document.getElementById('phone'));
                    isValid = false;
                }
                
                if (!isValid) {
                    showError(clientError, 'Please fix the errors in the form');
                    errorText.textContent = 'Please fix the errors in the form';
                    return;
                }
                
                // If valid, submit form
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                
                // You could add AJAX submission here or let the form submit normally
                form.submit();
            });
            
            // Helper functions
            function showError(element, message) {
                element.textContent = message;
                element.classList.remove('hidden');
            }
            
            function hideError(element) {
                element.classList.add('hidden');
            }
            
            function addErrorClass(element) {
                element.classList.add('input-error', 'shake');
                setTimeout(() => element.classList.remove('shake'), 500);
            }
            
            // Real-time validation
            document.getElementById('name').addEventListener('input', function() {
                if (this.value.trim()) hideError(nameError);
            });
            
            document.getElementById('address').addEventListener('input', function() {
                if (this.value.trim()) hideError(addressError);
            });
            
            document.getElementById('phone').addEventListener('input', function() {
                if (this.value.trim()) hideError(phoneError);
            });
        });
    </script>
</body>
</html>