<?php
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/csrf.php';

// Start session if not already started.
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Initialize error message variable.
$error = '';

// --- Form Submission Handling ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1. Validate CSRF Token: Protects against cross-site request forgery attacks.
  if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    $error = 'Invalid security token. Please refresh and try again.';
  } else {
    // 2. Sanitize & Validate Inputs: Ensures data is in the expected format.
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');

    // Basic validation for presence and password length.
    if (!$email || strlen($password) < 6) {
      $error = 'Please enter a valid email and a password of at least 6 characters.';
    } else {
      // 3. Database Verification: Check if the user exists.
      try {
        $stmt = $pdo->prepare('SELECT id, role, password, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 4. Authentication Check: Verify password and account status.
        if ($user && $user['status'] === 'active' && password_verify($password, $user['password'])) {
          // 5. Successful Login: Regenerate session ID and store user data.
          session_regenerate_id(true); // Prevents session fixation attacks.
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['role']    = $user['role'];

          // 6. Role-Based Redirection: Send user to the correct dashboard.
          switch ($user['role']) {
            case 'admin':
              header('Location: admin/admin_dashboard.php');
              break;
            case 'teacher':
              header('Location: teachers/teacher_dashboard.php');
              break;
            default:
              header('Location: student/student_dashboard.php');
              break;
          }
          exit; // Stop script execution after redirection.
        } else {
          // Login failed: incorrect details or inactive account.
          $error = 'The credentials provided are incorrect or the account is inactive.';
        }
      } catch (PDOException $e) {
        // Database error handling
        error_log('Login database error: ' . $e->getMessage());
        $error = 'A server error occurred. Please try again later.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | TASKNEST</title>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Google Fonts for a modern typeface -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* --- General Styling & Resets --- */
    :root {
      --primary-color: #6a5af9;
      --primary-light: #f0edff;
      --secondary-color: #12123b;
      --text-color: #555;
      --border-color: #ddd;
      --success-color: #28a745;
      --error-color: #dc3545;
      --light-bg: #f4f7fc;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background: url('assets/image/login-bg2.png') no-repeat center center/cover;
      font-family: "Poppins", sans-serif;
      background-color: var(--light-bg);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      color: var(--text-color);
    }

    /* --- Main Login Container --- */
    .login-container {
      display: flex;
      width: 100%;
      max-width: 1000px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      animation: fadeIn 0.8s ease-in-out;
    }

    /* --- Left Welcome Pane --- */
    .welcome-pane {
      background: url('assets/image/login-bg2.png') no-repeat center center/cover;
      color: #fff;
      padding: 60px 40px;
      width: 45%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .welcome-pane h1 {
      font-size: 2.5rem;
      margin-bottom: 15px;
    }

    .welcome-pane .logo {
      font-size: 4rem;
      margin-bottom: 20px;
      text-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .welcome-pane p {
      font-size: 1rem;
      max-width: 300px;
    }

    /* --- Right Login Form Pane --- */
    .login-pane {
      padding: 60px 40px;
      width: 55%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-pane h2 {
      color: var(--secondary-color);
      font-size: 2rem;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .login-pane .subtitle {
      margin-bottom: 30px;
      color: var(--text-color);
    }

    /* --- Form Elements Styling --- */
    .form-group {
      position: relative;
      margin-bottom: 25px;
    }

    .form-group .icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--border-color);
      transition: color 0.3s;
    }

    .form-group input {
      width: 100%;
      padding: 15px 15px 15px 45px;
      /* Padding for icon */
      border: 1px solid var(--border-color);
      border-radius: 10px;
      font-size: 1rem;
      outline: none;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-group input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px var(--primary-light);
    }

    .form-group input:focus+.icon {
      color: var(--primary-color);
    }

    /* Password toggle */
    .password-wrapper {
      position: relative;
    }

    .toggle-password {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #aaa;
    }

    /* --- Submit Button --- */
    .submit-btn {
      width: 100%;
      padding: 15px;
      background: var(--primary-color);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s, transform 0.2s;
    }

    .submit-btn:hover {
      background: #5a48e3;
      transform: translateY(-2px);
    }

    .submit-btn:active {
      transform: translateY(0);
    }

    /* --- Alert/Error Message --- */
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 10px;
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .forgot-password {
      text-align: right;
      font-size: 0.9rem;
      margin-top: -15px;
      margin-bottom: 15px;
    }

    .forgot-password a {
      color: var(--primary-color);
      text-decoration: none;
    }

    .forgot-password a:hover {
      text-decoration: underline;
    }


    /* --- Register Link --- */
    .register-link {
      text-align: center;
      margin-top: 25px;
      font-size: 0.9rem;
    }

    .register-link a {
      color: var(--primary-color);
      font-weight: 600;
      text-decoration: none;
    }

    .register-link a:hover {
      text-decoration: underline;
    }

    /* --- Animations --- */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.95);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* --- Responsive Design --- */
    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
        max-width: 90%;
        margin: 20px;
      }

      .welcome-pane,
      .login-pane {
        width: 100%;
        padding: 40px 25px;
      }

      .welcome-pane {
        display: none;
        /* Hide decorative pane on small screens for focus */
      }
    }
  </style>
</head>

<body>

  <div class="login-container">
    <!-- Left Pane: Welcome Message & Branding -->
    <aside class="welcome-pane">
      <i class="fas fa-graduation-cap logo"></i>
      <h1>Welcome Back!</h1>
      <p>Sign in to access your dashboard, manage assignments, and stay updated.</p>
    </aside>

    <!-- Right Pane: Login Form -->
    <main class="login-pane">
      <h2>Sign In</h2>
      <p class="subtitle">Please enter your details to continue.</p>

      <!-- Display error message if it exists -->
      <?php if (!empty($error)): ?>
        <div class="alert">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="post" action="login.php" novalidate>
        <!-- CSRF Token for security -->
        <?php echo csrfInputField(); ?>

        <div class="form-group">
          <input type="email" id="email" name="email" required placeholder="Email Address">
          <i class="fas fa-envelope icon"></i>
        </div>

        <div class="form-group password-wrapper">
          <input type="password" id="password" name="password" required minlength="6" placeholder="Password">
          <i class="fas fa-lock icon"></i>
          <i class="fas fa-eye-slash toggle-password" id="togglePassword"></i>
        </div>

        <button type="submit" class="submit-btn">Login</button>
      </form>
      <br>
      <p class="forgot-password">
        <a href="forgot_password.php">Forgot Password?</a>
      </p>

      <p class="register-link">
        New student? <a href="register_student.php">Register here</a>
      </p>
    </main>
  </div>

  <script>
    // --- JavaScript for Password Visibility Toggle ---
    const passwordInput = document.getElementById('password');
    const togglePasswordIcon = document.getElementById('togglePassword');

    if (togglePasswordIcon && passwordInput) {
      togglePasswordIcon.addEventListener('click', function() {
        // Toggle the type attribute
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle the icon
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
      });
    }
  </script>

</body>

</html>