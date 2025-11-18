<?php
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Use an array to store multiple potential errors.
$errors = [];

// --- Form Submission Handling ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1. Validate CSRF Token
  if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
    $errors[] = 'Invalid security token. Please refresh and try again.';
  } else {
    // 2. Sanitize & Validate Inputs
    $name     = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone    = trim($_POST['phone'] ?? ''); // Phone is optional, so just trim.
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation checks
    if (empty($name)) {
      $errors[] = 'Full name is required.';
    }
    if (empty($department)) {
      $errors[] = 'Department is required.';
    }
    if (!$email) {
      $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 8) {
      $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
      $errors[] = 'Passwords do not match.';
    }

    // 3. Process Registration if no errors
    if (empty($errors)) {
      // Hash the password for secure storage.
      $hash = password_hash($password, PASSWORD_DEFAULT);

      try {
        // Prepare and execute the database insertion.
        $stmt = $pdo->prepare(
          "INSERT INTO users (role, name,department, email, password, phone, status)
                     VALUES ('student', ?, ?, ?, ?, ?, 'active')"
        );
        $stmt->execute([$name, $department, $email, $hash, $phone]);

        // Redirect to login page with a success message.
        header('Location: login.php?registered=1');
        exit;
      } catch (PDOException $e) {
        // Check for duplicate email error (SQLSTATE 23000).
        if ($e->getCode() === '23000') {
          $errors[] = 'This email address is already registered.';
        } else {
          // Handle other potential database errors.
          error_log('Registration database error: ' . $e->getMessage());
          $errors[] = 'Registration failed due to a server error. Please try again later.';
        }
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
  <title>Student Registration | TASKNEST</title>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Google Fonts for a modern typeface -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    /* --- General Styling & Resets (Consistent with login.php) --- */
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
      padding: 20px 0;
    }

    /* --- Main Registration Container --- */
    .register-container {
      display: flex;
      width: 100%;
      max-width: 1000px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      animation: fadeIn 0.8s ease-in-out;
    }

    /* --- Left Info Pane --- */
    .info-pane {
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

    .info-pane h1 {
      font-size: 2.5rem;
      margin-bottom: 15px;
    }

    .info-pane .logo {
      font-size: 4rem;
      margin-bottom: 20px;
      text-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .info-pane p {
      font-size: 1rem;
      max-width: 300px;
      line-height: 1.6;
    }

    /* --- Right Registration Form Pane --- */
    .form-pane {
      padding: 40px;
      width: 55%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-pane h2 {
      color: var(--secondary-color);
      font-size: 2rem;
      margin-bottom: 10px;
      font-weight: 600;
    }

    .form-pane .subtitle {
      margin-bottom: 25px;
      color: var(--text-color);
    }

    /* --- Form Elements Styling --- */
    .form-group {
      position: relative;
      margin-bottom: 20px;
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
      padding: 14px 15px 14px 45px;
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
    .alert-error {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 10px;
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
      font-size: 0.9rem;
    }

    .alert-error ul {
      list-style-position: inside;
      padding-left: 5px;
    }

    /* --- Login Link --- */
    .login-link {
      text-align: center;
      margin-top: 25px;
      font-size: 0.9rem;
    }

    .login-link a {
      color: var(--primary-color);
      font-weight: 600;
      text-decoration: none;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    /* --- Password Strength Meter --- */
    .strength-meter {
      display: flex;
      height: 5px;
      margin-top: 5px;
      border-radius: 5px;
      overflow: hidden;
    }

    .strength-meter div {
      flex: 1;
      height: 100%;
      background: #eee;
      transition: background-color 0.3s;
    }

    .strength-meter div:not(:first-child) {
      margin-left: 3px;
    }

    p.strength-text {
      font-size: 0.8rem;
      margin-top: 5px;
      height: 1em;
      color: #888;
      transition: color 0.3s;
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
    @media (max-width: 820px) {
      .register-container {
        flex-direction: column;
        max-width: 90%;
        margin: 20px;
      }

      .info-pane,
      .form-pane {
        width: 100%;
        padding: 40px 25px;
      }

      .info-pane {
        display: none;
      }
    }
  </style>
</head>

<body>

  <div class="register-container">
    <!-- Left Pane: Info & Branding -->
    <aside class="info-pane">
      <i class="fas fa-user-plus logo"></i>
      <h1>Create Your Account</h1>
      <p>Join our community to start your learning journey. Get access to assignments, resources, and connect with teachers.</p>
    </aside>

    <!-- Right Pane: Registration Form -->
    <main class="form-pane">
      <h2>Student Registration</h2>
      <p class="subtitle">Fill out the form to get started.</p>

      <!-- Display error messages if any exist -->
      <?php if (!empty($errors)): ?>
        <div class="alert-error">
          <strong>Oops! Please fix the following:</strong>
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Registration Form -->
      <form method="post" action="register_student.php" novalidate>
        <?php echo csrfInputField(); ?>

        <div class="form-group">
          <input type="text" id="name" name="name" required placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
          <i class="fas fa-user icon"></i>
        </div>

        <div class="form-group">
          <input type="text" id="department" name="department" required placeholder="Enter department" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
          <i class="fas fa-building icon"></i>
        </div>

        <div class="form-group">
          <input type="email" id="email" name="email" required placeholder="Email Address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          <i class="fas fa-envelope icon"></i>
        </div>

        <div class="form-group">
          <input type="tel" id="phone" name="phone" placeholder="Phone Number (Optional)" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
          <i class="fas fa-phone icon"></i>
        </div>

        <div class="form-group">
          <input type="password" id="password" name="password" required minlength="8" placeholder="Create Password">
          <i class="fas fa-lock icon"></i>
        </div>

        <!-- Password Strength UI -->
        <div class="strength-meter" id="strength-meter">
          <div></div>
          <div></div>
          <div></div>
          <div></div>
        </div>
        <p class="strength-text" id="strength-text"></p>

        <div class="form-group" style="margin-top: 20px;">
          <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Confirm Password">
          <i class="fas fa-lock icon"></i>
        </div>

        <button type="submit" class="submit-btn">Create Account</button>
      </form>

      <p class="login-link">
        Already have an account? <a href="login.php">Login here</a>
      </p>
    </main>
  </div>

  <script>
    // --- JavaScript for Password Strength Indicator ---
    const passwordInput = document.getElementById('password');
    const strengthMeter = document.getElementById('strength-meter');
    const strengthText = document.getElementById('strength-text');
    const strengthBars = strengthMeter.querySelectorAll('div');

    passwordInput.addEventListener('input', updateStrengthMeter);

    function updateStrengthMeter() {
      const password = passwordInput.value;
      let score = 0;

      if (password.length >= 8) score++;
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
      if (/[0-9]/.test(password)) score++;
      if (/[^A-Za-z0-9]/.test(password)) score++;

      const text = ['', 'Weak', 'Medium', 'Good', 'Strong'][score];
      const colors = ['', '#e74c3c', '#f1c40f', '#2ecc71', '#27ae60'];

      strengthBars.forEach((bar, index) => {
        bar.style.backgroundColor = index < score ? colors[score] : '#eee';
      });

      strengthText.textContent = `Strength: ${text}`;
      strengthText.style.color = colors[score] || '#888';
    }
  </script>

</body>

</html>