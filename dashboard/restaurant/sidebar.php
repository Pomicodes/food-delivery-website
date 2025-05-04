<?php
 
  include_once '../../includes/dbh.inc.php';
  $userId = $_SESSION['user_id'];
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$userId]);
  $usr = $stmt->fetch();
  $image = $usr['image'];

  if(!isset($image)){
    $image = "https://dovercourt.org/wp-content/uploads/2019/11/610-6104451_image-placeholder-png-user-profile-placeholder-image-png.jpg";
  }

?>

<div class="sidebar">
    <!-- Close Button (for mobile) -->
    <i data-lucide="x" class="sidebar-close-btn"></i>
    
    <!-- Brand Section -->
    <div class="brand-section">
        <div class="brand-logo">
            <i class="fas fa-utensils"></i>
        </div>
        <h3 class="brand-name">Sweet Bite</h3>
        <p class="brand-tagline">Restaurant Dashboard</p>
    </div>
    
    <!-- Navigation Links -->
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="./index.php" class="nav-link">
                <div class="nav-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <span class="nav-text">Orders</span>
                <span class="nav-badge">5</span> <!-- Example badge for new orders -->
            </a>
        </li>

        <li class="nav-item">
            <a href="./settings.php" class="nav-link">
                <div class="nav-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <span class="nav-text">Settings</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="./menu.php" class="nav-link">
                <div class="nav-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <span class="nav-text">Menu</span>
            </a>
        </li>
        
        <li class="nav-item">
            <a href="./analytics.php" class="nav-link">
                <div class="nav-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="nav-text">Analytics</span>
            </a>
        </li>
    </ul>
    
    <!-- User Profile Section -->
    <div class="user-profile">
        <div class="user-avatar">
            <img src="../../<?php echo $image ?>" alt="User Avatar">
        </div>
        <div class="user-info">
            <span class="user-name">Admin</span>
            <span class="user-role">Restaurant Owner</span>
        </div>
        <a href="../../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<style>
    /* Sidebar Styles */
    .sidebar {
        width: 280px;
        height: 100vh;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .sidebar-close-btn {
        display: none;
        position: absolute;
        right: 15px;
        top: 15px;
        font-size: 1.5rem;
        cursor: pointer;
        color: rgba(255, 255, 255, 0.7);
        transition: all 0.2s ease;
    }
    
    .sidebar-close-btn:hover {
        color: white;
        transform: rotate(90deg);
    }
    
    /* Brand Section */
    .brand-section {
        padding: 25px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
    }
    
    .brand-logo {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.8rem;
        color: #f97316;
    }
    
    .brand-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 5px;
        color: white;
    }
    
    .brand-tagline {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.6);
        margin: 0;
    }
    
    /* Navigation Links */
    .sidebar-nav {
        flex: 1;
        padding: 20px 0;
        overflow-y: auto;
    }
    
    .nav-item {
        margin: 5px 15px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.2s ease;
    }
    
    .nav-item:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .nav-item.active {
        background: rgba(249, 115, 22, 0.2);
    }
    
    .nav-item.active .nav-link {
        color: white;
    }
    
    .nav-item.active .nav-icon {
        color: #f97316;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    
    .nav-link:hover {
        color: white;
    }
    
    .nav-icon {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.6);
    }
    
    .nav-text {
        flex: 1;
    }
    
    .nav-badge {
        background: #f97316;
        color: white;
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 10px;
        font-weight: 600;
    }
    
    /* User Profile Section */
    .user-profile {
        padding: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        background: rgba(0, 0, 0, 0.1);
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        margin-right: 12px;
    }
    
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .user-info {
        flex: 1;
    }
    
    .user-name {
        display: block;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .user-role {
        display: block;
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
    }
    
    .logout-btn {
        color: rgba(255, 255, 255, 0.6);
        font-size: 1.1rem;
        padding: 5px;
        transition: all 0.2s ease;
    }
    
    .logout-btn:hover {
        color: white;
        transform: translateX(2px);
    }
    
    /* Responsive Styles */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: 260px;
        }
        
        .sidebar.active {
            transform: translateX(0);
        }
        
        .sidebar-close-btn {
            display: block;
        }
    }
</style>

<script>
    // Add active class to current page link
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const linkHref = link.getAttribute('href').split('/').pop();
            if (currentPage === linkHref) {
                link.parentElement.classList.add('active');
            }
        });
        
        // Mobile toggle functionality
        const sidebar = document.querySelector('.sidebar');
        const closeBtn = document.querySelector('.sidebar-close-btn');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('active');
            });
        }
    });
</script>