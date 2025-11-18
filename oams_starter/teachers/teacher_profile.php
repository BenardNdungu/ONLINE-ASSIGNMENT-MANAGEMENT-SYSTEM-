<?php
// teacher_profile.php - Teacher Profile Management
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

// Ensure only teachers can access
require_role('teacher');
$user = current_user();

// ==================== HANDLE PROFILE UPDATE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $profile_pic = $user['profile_pic']; // keep old picture

    // Handle image upload
    if (!empty($_FILES['profile_pic']['name'])) {
    $fileTmp  = $_FILES['profile_pic']['tmp_name'];
    $fileName = basename($_FILES['profile_pic']['name']);
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileExt, $allowed)) {
        $newName = "teacher_" . $user['id'] . "_" . time() . "." . $fileExt;

        $uploadDir = __DIR__ . "/../uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $uploadPath = $uploadDir . $newName;

        if (move_uploaded_file($fileTmp, $uploadPath)) {
            // Use correct relative path for browser access
            $profile_pic = "public/uploads/" . $newName;
        } else {
            $error = "Failed to upload profile picture.";
        }
    } else {
        $error = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
    }
}

    if ($name && $email) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?,department = ?, profile_pic = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $department, $profile_pic, $user['id']]);
        header("Location: teacher_profile.php?updated=1");
        exit;
    }
}

// ==================== HANDLE PASSWORD CHANGE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new === $confirm) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if ($row && password_verify($current, $row['password'])) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);
            header("Location: teacher_profile.php?password_changed=1");
            exit;
        } else {
            $error = "Current password is incorrect.";
        }
    } else {
        $error = "New passwords do not match.";
    }
}

// Refresh user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Teacher Profile - TASKNEST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* General Body Styles - Consistent with original aesthetic */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            /* Use the light, neutral background from the first original CSS */
            background-color: #f4f7f6;
            color: #333;
            line-height: 1.6;
            display: flex;
            /* Necessary for the fixed sidebar/main layout */
            min-height: 100vh;
        }

        /* --- Sidebar --- (Fully consistent with the original dark theme) */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100%;
            /* Using 100% or 100vh for fixed sidebars */
            background: #2c3e50;
            /* Dark Slate Blue */
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .sidebar h2 {
            font-family: 'Arial Black', Gadget, sans-serif;
            padding: 20px;
            margin: 0;
            text-align: center;
            background: #34495e;
            /* Wet Asphalt */
            letter-spacing: 2px;
            font-size: 1.2rem;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s, padding-left 0.3s;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: #3498db;
            /* Peter River Blue */
            padding-left: 25px;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        /* --- Main Content --- */
        .main {
            margin-left: 250px;
            padding: 30px;
            /* Use the larger original padding */
            flex: 1;
        }

        h1 {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
        }

        /* --- Section/Container Styling --- (Reverting to original container look) */
        .section {
            background: #fff;
            /* White background */
            padding: 25px 30px;
            /* Original container padding */
            border-radius: 8px;
            /* Original container radius */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            /* Original subtle shadow */
            margin-bottom: 30px;
            /* Consistent spacing between sections */
            color: #333;
            /* Default text color */
        }

        .section h2 {
            color: #2c3e50;
            margin-top: 0;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        /* --- Form Input Styles --- (Reverting to original form aesthetic) */
        label {
            display: block;
            font-weight: bold;
            font-size: 0.9em;
            margin-bottom: 5px;
            margin-top: 10px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="file"] {
            /* Use small, consistent padding, full width (box-sizing), and small radius */
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
            font-family: inherit;
            font-size: 1em;
            /* Normal font size */
            box-sizing: border-box;
            /* Crucial for width: 100% with padding */
        }

        /* --- Buttons --- (Using the original blue button style) */
        button[type="submit"] {
            background-color: #3498db;
            /* Peter River Blue */
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.3s;
            font-weight: 600;
        }

        button[type="submit"]:hover {
            background-color: #2980b9;
            /* Darker blue on hover */
        }

        /* --- Messaging Styles --- */
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* --- Profile Image --- */
        .profile-pic {
            width: 100px;
            /* Slightly smaller for a cleaner look */
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            /* Use the primary blue as the border accent */
            border: 3px solid #3498db;
            margin-bottom: 20px;
            display: block;
            /* Centers the image if container is centered (not here, but good practice) */
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>TASKNEST</h2>
        <ul>
            <li><a href="teacher_dashboard.php"><i class="fa fa-chart-line"></i><span> Dashboard</span></a></li>
            <li><a href="teacher_assignments.php"><i class="fa fa-tasks"></i><span> My Assignments</span></a></li>
            <li><a href="teacher_submissions.php"><i class="fa fa-folder-open"></i><span> Student Submissions</span></a></li>
            <li><a href="teacher_reports.php"><i class="fa fa-file-alt"></i><span> Reports</span></a></li>
            <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
            <li><a href="teacher_profile.php"><i class="fa fa-user-cog"></i><span> Profile</span></a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
        </ul>
    </div>

    <!-- Main -->
    <div class="main">
        <h1><i class="fa fa-user"></i> Profile</h1>

        <?php if (isset($_GET['updated'])): ?>
            <p class="success">Profile updated successfully!</p>
        <?php endif; ?>
        <?php if (isset($_GET['password_changed'])): ?>
            <p class="success">Password changed successfully!</p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <!-- Profile Update -->
        <div class="section">
            <h2>Update Profile</h2>

            <!-- Display profile image -->
            <div>
                <img src="<?= $user['profile_pic'] ? htmlspecialchars($user['profile_pic']) : 'https://via.placeholder.com/120' ?>" alt="Profile Picture" class="profile-pic">
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_profile" value="1">
                <label>Name</label><br>
                <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" required><br>
                <label>Email</label><br>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required><br>
                <label>Department</label><br>
                <input type="text" name="department" value="<?= htmlspecialchars($user['department']); ?>"><br>
                <label>Phone</label><br>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']); ?>"><br>
                <label>Profile Picture</label><br>
                <input type="file" name="profile_pic" accept="image/*"><br>
                <button type="submit"><i class="fa fa-save"></i> Save Changes</button><br>
            </form>
        </div>

        <!-- Change Password -->
        <div class="section">
            <h2>Change Password</h2>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                <label>Current Password</label><br>
                <input type="password" name="current_password" required><br>
                <label>New Password</label><br>
                <input type="password" name="new_password" required><br>
                <label>Confirm New Password</label><br>
                <input type="password" name="confirm_password" required><br>
                <button type="submit"><i class="fa fa-key"></i> Update Password</button><br>
            </form>
        </div>
    </div>

</body>

</html>