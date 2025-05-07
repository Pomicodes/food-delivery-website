<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> | Food Delivery Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }
        
        .hero-gradient {
            background: linear-gradient(135deg, #f97316 0%, #f59e0b 100%);
        }
        
        .food-card {
            transition: all 0.3s ease;
        }
        
        .food-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .testimonial-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .nav-link {
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #f97316;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .btn-primary {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(249, 115, 22, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(249, 115, 22, 0.4);
        }
    </style>
</head>
<body class="bg-white text-gray-800">
    <!-- Navigation -->
    <nav class="fixed w-full bg-white shadow-md z-50">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="#" class="text-2xl font-bold text-orange-500 flex items-center">
                        <i class="fas fa-utensils mr-2"></i>
                        Sweet Bite
                    </a>
                    
                </div>
                
                <div class="hidden md:flex space-x-8">
                    <a href="#" class="nav-link">Home</a>
                    <a href="#restaurants" class="nav-link">Restaurants</a>
                    <a href="#how-it-works" class="nav-link">How It Works</a>
                    <a href="#testimonials" class="nav-link">Testimonials</a>
                    <a href="#contact" class="nav-link">Contact</a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="auth/login.php" class="text-orange-500 hover:text-orange-600">Login</a>
                    <a href="onboarding.php" class="bg-orange-500 text-white px-4 py-2 rounded-full btn-primary">
                        Sign Up
                    </a>
                </div>
                
                <button class="md:hidden text-gray-600 focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-gradient pt-32 pb-20 text-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-10 md:mb-0">
                    <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
                        Delicious food delivered to your doorstep
                    </h1>
                    <p class="text-xl mb-8 opacity-90">
                        Order from your favorite local restaurants with just a few taps and enjoy restaurant-quality meals in the comfort of your home.
                    </p>
                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                        <a href="onboarding.php" class="bg-white text-orange-500 px-6 py-3 rounded-full text-center font-semibold btn-primary">
                            Order Now
                        </a>
                        <a href="auth/login.php" class="border-2 border-white text-white px-6 py-3 rounded-full text-center font-semibold hover:bg-white hover:text-orange-500 transition">
                            Browse Restaurants
                        </a>
                    </div>
                </div>
                <div class="md:w-1/2 flex justify-center">
                    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" 
                         alt="Delicious food delivery" 
                         class="rounded-lg shadow-2xl w-full max-w-md transform rotate-2 hover:rotate-0 transition duration-500">
                </div>
            </div>
        </div>
    </section>

    

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold mb-4">How It Works</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Getting your favorite meals delivered has never been easier. Just follow these simple steps.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center px-6">
                    <div class="bg-orange-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-orange-500 text-3xl font-bold">1</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Choose Your Restaurant</h3>
                    <p class="text-gray-600">
                        Browse through our selection of local restaurants and cuisines to find what you're craving.
                    </p>
                </div>
                
                <!-- Step 2 -->
                <div class="text-center px-6">
                    <div class="bg-orange-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-orange-500 text-3xl font-bold">2</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Customize Your Order</h3>
                    <p class="text-gray-600">
                        Select your favorite dishes, customize them to your liking, and add them to your cart.
                    </p>
                </div>
                
                <!-- Step 3 -->
                <div class="text-center px-6">
                    <div class="bg-orange-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-orange-500 text-3xl font-bold">3</span>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Track & Enjoy</h3>
                    <p class="text-gray-600">
                        Check out securely and track your order in real-time until it arrives at your doorstep.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Dishes -->
    

    <!-- Testimonials -->
    

    <!-- App Download -->
  
    <!-- Contact & Newsletter -->
    

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-utensils mr-2 text-orange-500"></i>
                        Savory
                    </h3>
                    <p class="text-gray-400">
                        Bringing your favorite restaurant meals to your doorstep with speed and care.
                    </p>
                    <div class="flex space-x-4 mt-4">
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Home</a></li>
                        <li><a href="#restaurants" class="text-gray-400 hover:text-white transition">Restaurants</a></li>
                        <li><a href="#how-it-works" class="text-gray-400 hover:text-white transition">How It Works</a></li>
                        <li><a href="#testimonials" class="text-gray-400 hover:text-white transition">Testimonials</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-4">Support</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Refund Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">FAQs</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-lg mb-4">Partners</h4>
                    <p class="text-gray-400 mb-4">
                        Are you a restaurant owner interested in joining our platform?
                    </p>
                    <a href="#" class="inline-block border border-orange-500 text-orange-500 px-4 py-2 rounded hover:bg-orange-500 hover:text-white transition">
                        Partner With Us
                    </a>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> Savory. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle functionality would go here
        document.addEventListener('DOMContentLoaded', function() {
            // You can add any JavaScript interactions here
            // For example, mobile menu toggle, smooth scrolling, etc.
        });
    </script>
</body>
</html>