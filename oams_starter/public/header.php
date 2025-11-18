<?php
// public/header.php
// A modern, responsive, and shared header for all application pages.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Fetch Logged-in User's Name ---
// This makes the header more personal by displaying the user's name.
$user_name = null;
if (isset($_SESSION['user_id'])) {
    // We need to include the database connection to fetch user details.
    // Use a try-catch block for robust error handling.
    try {
        require_once __DIR__ . '/../src/db.php'; 
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            // Get the first name for a cleaner display.
            $user_name = htmlspecialchars(explode(' ', $user['name'])[0]);
        }
    } catch (PDOException $e) {
        // Log the error, but don't break the page.
        error_log("Header DB Error: " . $e->getMessage());
        $user_name = 'User'; // Fallback name
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TASKNEST - Assignment Management</title>
  
  <!-- Google Fonts (consistent with other pages) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Main Stylesheet Link -->
  <link rel="stylesheet" href="assets/css/style.css">

  <style>
    /* --- General Header & Body Styling --- */
    :root {
      --primary-color: #6a5af9;
      --secondary-color: #12123b;
      --text-color: #555;
      --light-text-color: #888;
      --border-color: #eee;
      --light-bg: #f4f7fc;
      --header-height: 70px;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--light-bg);
      padding-top: var(--header-height); /* Prevent content from hiding behind fixed header */
    }

    /* --- Main Header Container --- */
    .main-header {
      background: #fff;
      padding: 0 25px;
      height: var(--header-height);
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      transition: box-shadow 0.3s;
    }
    
    /* --- Logo/Branding --- */
    .logo a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: var(--secondary-color);
    }
    .logo .logo-icon {
      color: var(--primary-color);
      font-size: 1.8rem;
      margin-right: 10px;
    }
    .logo .logo-text {
      font-size: 1.5rem;
      font-weight: 600;
    }

    /* --- Desktop Navigation --- */
    .nav-bar .nav-menu {
      display: flex;
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .nav-bar .nav-item {
      margin-left: 20px;
    }
    .nav-bar .nav-link {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: var(--text-color);
      font-weight: 500;
      padding: 10px 5px;
      position: relative;
      transition: color 0.3s;
    }
    .nav-bar .nav-link:hover {
      color: var(--primary-color);
    }
    .nav-bar .nav-link::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 2px;
      background: var(--primary-color);
      transition: width 0.3s ease;
    }
    .nav-bar .nav-link:hover::after {
      width: 100%;
    }
    .nav-bar .nav-icon {
      margin-right: 8px;
    }

    /* --- Header Actions (User Profile & Mobile Menu) --- */
    .header-actions {
      display: flex;
      align-items: center;
    }

    /* --- User Profile Dropdown --- */
    .user-profile {
      position: relative;
      margin-left: 20px;
    }
    .profile-button {
      display: flex;
      align-items: center;
      background: none;
      border: none;
      cursor: pointer;
      padding: 5px;
      border-radius: 50px;
      transition: background-color 0.2s;
    }
    .profile-button:hover, .profile-button.active {
      background-color: var(--light-bg);
    }
    .profile-button .avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: var(--primary-color);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 1.2rem;
      margin-right: 10px;
    }
    .profile-button .user-name {
      font-weight: 500;
      color: var(--secondary-color);
    }
    .profile-button .fa-chevron-down {
      margin-left: 10px;
      transition: transform 0.3s;
    }
    .profile-button.active .fa-chevron-down {
      transform: rotate(180deg);
    }

    .dropdown-menu {
      position: absolute;
      top: 120%;
      right: 0;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      width: 200px;
      overflow: hidden;
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: opacity 0.3s, transform 0.3s, visibility 0.3s;
    }
    .dropdown-menu.active {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .dropdown-menu a {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      text-decoration: none;
      color: var(--text-color);
      font-size: 0.95rem;
      transition: background-color 0.2s, color 0.2s;
    }
    .dropdown-menu a:hover {
      background-color: var(--primary-light);
      color: var(--primary-color);
    }
    .dropdown-menu .nav-icon {
      margin-right: 12px;
      width: 20px;
      text-align: center;
    }
    .dropdown-menu .logout-link {
        color: #e74c3c;
    }
    .dropdown-menu .logout-link:hover {
        background-color: #fcebeb;
        color: #c0392b;
    }

    /* --- Mobile Menu --- */
    .mobile-menu-toggle {
      display: none; /* Hidden on desktop */
      font-size: 1.5rem;
      color: var(--secondary-color);
      background: none;
      border: none;
      cursor: pointer;
    }

    /* --- Responsive Design --- */
    @media (max-width: 820px) {
      .nav-bar .nav-menu {
        display: none; /* Hide desktop nav */
      }
      .user-profile .user-name {
        display: none; /* Hide name on mobile */
      }
      .mobile-menu-toggle {
        display: block; /* Show hamburger icon */
      }
      /* Styles for the mobile navigation panel when active */
      .nav-bar.mobile-active .nav-menu {
        display: flex;
        flex-direction: column;
        position: absolute;
        top: var(--header-height);
        left: 0;
        width: 100%;
        background: #fff;
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05);
        animation: slideDown 0.3s ease-in-out;
      }
      .nav-bar.mobile-active .nav-item {
        margin: 0;
        border-bottom: 1px solid var(--border-color);
      }
      .nav-bar.mobile-active .nav-link {
        padding: 15px 25px;
      }
       .nav-bar.mobile-active .nav-link:hover::after {
          width: 0; /* Disable underline on mobile */
       }
    }
    
    @keyframes slideDown {
        from { transform: translateY(-10px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    /* Content wrapper for main page content */
    .content-wrapper {
        padding: 25px;
        max-width: 1200px;
        margin: 0 auto;
    }
  </style>
</head>
<body>
  <header class="main-header">
    <div class="logo">
      <a href="index.php">
        <i class="fa-solid fa-book-open-reader logo-icon"></i>
        <span class="logo-text">TASKNEST</span>
      </a>
    </div>

    <div class="header-actions">
        <!-- Desktop Navigation -->
        <nav class="nav-bar" id="main-nav">
          <ul class="nav-menu">
            <?php if (!empty($_SESSION['role'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                  <li class="nav-item"><a href="admin/admin_dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high nav-icon"></i>Dashboard</a></li>
                  <li class="nav-item"><a href="admin/manage_users.php" class="nav-link"><i class="fa-solid fa-users-cog nav-icon"></i>Users</a></li>
                <?php elseif ($_SESSION['role'] === 'teacher'): ?>
                  <li class="nav-item"><a href="teacher_assignments.php" class="nav-link"><i class="fa-solid fa-chalkboard-user nav-icon"></i>My Assignments</a></li>
                <?php else: // Student ?>
                  <li class="nav-item"><a href="student/student_dashboard.php" class="nav-link"><i class="fa-solid fa-file-lines nav-icon"></i>Assignments</a></li>
                  <li class="nav-item"><a href="student_view_submissions.php" class="nav-link"><i class="fa-solid fa-graduation-cap nav-icon"></i>Grades</a></li>
                <?php endif; ?>
            <?php endif; ?>
          </ul>
        </nav>

        <?php if ($user_name): ?>
        <!-- User Profile Dropdown -->
        <div class="user-profile">
          <button class="profile-button" id="profile-button">
            <div class="avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
            <span class="user-name"><?php echo $user_name; ?></span>
            <i class="fa-solid fa-chevron-down"></i>
          </button>
          <div class="dropdown-menu" id="dropdown-menu">
            <a href="profile.php"><i class="fa-solid fa-user-circle nav-icon"></i>My Profile</a>
            <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket nav-icon"></i>Logout</a>
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" id="mobile-menu-toggle">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
  </header>

  <main class="content-wrapper">
    <!-- The rest of your page content will go here -->
    
  <script>
    // --- JavaScript for Interactive Header Elements ---
    document.addEventListener('DOMContentLoaded', function() {
        const profileButton = document.getElementById('profile-button');
        const dropdownMenu = document.getElementById('dropdown-menu');
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mainNav = document.getElementById('main-nav');

        // Toggle user profile dropdown
        if (profileButton && dropdownMenu) {
            profileButton.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent click from closing menu immediately
                profileButton.classList.toggle('active');
                dropdownMenu.classList.toggle('active');
            });
        }
        
        // Toggle mobile navigation
        if (mobileMenuToggle && mainNav) {
            mobileMenuToggle.addEventListener('click', () => {
                mainNav.classList.toggle('mobile-active');
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (event) => {
            if (profileButton && !profileButton.contains(event.target) && !dropdownMenu.contains(event.target)) {
                profileButton.classList.remove('active');
                dropdownMenu.classList.remove('active');
            }
        });
    });
  </script>
</body>
</html>
