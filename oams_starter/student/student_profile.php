<?php
// student/student_profile.php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';

require_role('student');
$student = current_user();
$message = '';
$error = '';
// Get current logged-in student
$user = current_user();


// Fetch fresh user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$user = $stmt->fetch();

$user_id = $user['id'];
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);


// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $phone = trim($_POST['phone']);
  $department = trim($_POST['department']);
  $file_path = $user['profile_pic']; // keep current image if not updated

  // Handle profile image upload
  if (!empty($_FILES['file_pic']['name'])) {
    $target_dir = __DIR__ . "/uploads/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $filename = basename($_FILES["file_pic"]["name"]);
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($_FILES["file_pic"]["tmp_name"], $target_file)) {
      $file_path = "uploads/" . $filename;
    }
  }

  // Update user record
  $update = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, department=?, profile_pic=? WHERE id=?");
  $update->execute([$name, $email, $phone, $department, $file_path, $user_id]);

  $_SESSION['message'] = "Profile updated successfully!";
  header("Location: student_profile.php");
  exit;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  $current = $_POST['current_password'];
  $new = $_POST['new_password'];
  $confirm = $_POST['confirm_password'];

  if ($new !== $confirm) {
    $error = "New passwords do not match.";
  } else {
    if (password_verify($current, $user['password'])) {
      $hashed = password_hash($new, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
      $stmt->execute([$hashed, $user_id]);
      $_SESSION['message'] = "Password changed successfully!";
      header("Location: profile.php");
      exit;
    } else {
      $error = "Current password is incorrect.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Profile - TASKNEST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* Global Styles */
    body {
      margin: 0;
      font-family: "Poppins", "Segoe UI", Tahoma, sans-serif;
      background: #f8fafc;
      color: #2d3436;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background: #1e2a38;
      color: #fff;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
    }

    .sidebar h2 {
      text-align: center;
      padding: 1.2rem;
      font-size: 1.4rem;
      color: #f1c40f;
      border-bottom: 1px solid #34495e;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar ul li {
      border-bottom: 1px solid #2c3e50;
    }

    .sidebar ul li a {
      display: flex;
      align-items: center;
      padding: 0.9rem 1.2rem;
      color: #ecf0f1;
      text-decoration: none;
      font-size: 15px;
      transition: all 0.3s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #34495e;
      padding-left: 1.6rem;
    }

    .sidebar ul li a i {
      margin-right: 12px;
      font-size: 1.1rem;
    }

    /* Main Section */
    .main {
      margin-left: 250px;
      padding: 30px;
      flex: 1;
    }

    .main h1 {
      font-size: 1.8rem;
      color: #2c3e50;
      margin-bottom: 15px;
    }

    /* Notification Message */
    .message {
      color: #16a085;
      background: #eafaf1;
      border-left: 5px solid #16a085;
      padding: 10px 15px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-weight: 500;
    }

    /* Profile Card */
    .profile-card {
      display: flex;
      align-items: center;
      background: #ffffff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
      width: 80%;
      transition: transform 0.3s ease;
    }

    .profile-card:hover {
      transform: translateY(-4px);
    }

    .profile-card img {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      margin-right: 20px;
      object-fit: cover;
      border: 4px solid #1e2a38;
    }

    .profile-card h2 {
      margin: 0 0 8px;
      color: #1e2a38;
      font-size: 1.4rem;
    }

    .profile-card p {
      margin: 4px 0;
      font-size: 0.95rem;
      color: #555;
    }

    /* Form Sections */
    .section {
      background: #fff;
      width: 80%;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
      margin-bottom: 25px;
    }

    .section h2 {
      margin-bottom: 15px;
      color: #1e2a38;
      font-size: 1.3rem;
      border-bottom: 2px solid #f1c40f;
      padding-bottom: 5px;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #2c3e50;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="file"] {
      width: 95%;
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 15px;
      transition: border-color 0.3s ease;
    }

    input:focus {
      border-color: #1abc9c;
      outline: none;
      box-shadow: 0 0 4px rgba(26, 188, 156, 0.4);
    }

    button {
      background: #1abc9c;
      color: #fff;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 15px;
      transition: all 0.3s ease;
    }

    button:hover {
      background: #16a085;
      transform: translateY(-2px);
    }

    .fa-key,
    .fa-save {
      margin-right: 5px;
    }

    @media (max-width: 900px) {
      .main {
        margin-left: 0;
        padding: 15px;
      }

      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
      }

      .profile-card,
      .section {
        width: 100%;
      }
    }
  </style>

  </style>
</head>

<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h2><i class="fa-solid fa-user-graduate"></i> TASKNEST</h2>
    <ul>
      <li><a href="student_dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
      <li><a href="student_assignments.php"><i class="fa fa-book-open"></i> My Assignments</a></li>
      <li><a href="student_submissions.php"><i class="fa fa-paper-plane"></i> Submissions</a></li>
      <li><a href="student_grades.php"><i class="fa fa-graduation-cap"></i> Grades & Feedback</a></li>
      <li><a href="student_reports.php"><i class="fa fa-file-alt"></i> Reports</a></li>
      <li><a href="student_profile.php" class="active"><i class="fa fa-user"></i> Profile</a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </div>

  <!-- Main -->
  <div class="main">
    <h1><i class="fa fa-user"></i> Profile</h1>
    <?php if ($message): ?><p class="message"><?= htmlspecialchars($message); ?></p><?php endif; ?>

    <!-- Profile Card -->
    <div class="profile-card">
      <?php
      // Show profile picture if set, else placeholder
      $avatar = isset($user['profile_pic']) && $user['profile_pic'] ? htmlspecialchars($user['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
      ?>
      <img src="<?= $avatar; ?>" alt="Profile Picture" />
      <div>
        <h2><?= htmlspecialchars($user['name']); ?></h2>
        <p><i class="fa fa-envelope"></i> <?= htmlspecialchars($user['email']); ?></p>
        <p><i class="fa fa-building"></i> <?= htmlspecialchars($user['department'] ?? 'N/A'); ?></p>
      </div>
    </div>

    <!-- Update Profile -->
    <div class="section">
      <h2>Update Profile</h2>
      <form method="POST" enctype="multipart/form-data">
        <?= csrfInputField(); ?>
        <input type="hidden" name="update_profile" value="1">
        <label>Name</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
        <label>Email</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        <label>Phone</label><br>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']); ?>">
        <label>Department</label><br>
        <input type="text" name="department" value="<?= htmlspecialchars($user['department']); ?>"><br>
        <label>Profile Image</label><br>
        <input type="file" name="file_pic" accept="image/*">
        <button type="submit"><i class="fa fa-save"></i> Save Changes</button>
      </form>
    </div>

    <!-- Change Password -->
    <div class="section">
      <h2>Change Password</h2>
      <form method="POST">
        <?= csrfInputField(); ?>
        <input type="hidden" name="change_password" value="1">
        <label>Current Password</label>
        <input type="password" name="current_password" required>
        <label>New Password</label>
        <input type="password" name="new_password" required>
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
        <button type="submit"><i class="fa fa-key"></i> Update Password</button>
      </form>
    </div>
  </div>

</body>

</html>