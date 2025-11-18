<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_role('admin');

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($name && $email && $role && $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, role, password) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $role, $hashedPassword]);
            $message = "<div class='msg success'><i class='fas fa-check-circle'></i> User added successfully!</div>";
        } catch (PDOException $e) {
            // Check for duplicate entry error (common for unique email constraint)
            if ($e->getCode() === '23000') {
                 $message = "<div class='msg error'><i class='fas fa-exclamation-triangle'></i> Error: A user with this email already exists.</div>";
            } else {
                 $message = "<div class='msg error'><i class='fas fa-exclamation-triangle'></i> Error: Failed to add user.</div>";
            }
           
        }
    } else {
        $message = "<div class='msg error'><i class='fas fa-exclamation-triangle'></i> ⚠️ Please fill all fields.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add User - Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
<style>
    /* General Body Styles - Consistent with previous versions */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        background-color: #f4f7f6;
        color: #333;
        line-height: 1.6;
        display: flex;
        min-height: 100vh;
    }
    
    /* --- Sidebar --- (Standardized Admin Theme) */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 250px; 
        height: 100vh;
        background: #2c3e50; /* Dark Slate Blue */
        color: #ecf0f1;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        flex-shrink: 0;
        transition: width 0.3s;
    }

    .sidebar h2 {
        font-family: 'Arial Black', Gadget, sans-serif; 
        padding: 20px;
        margin: 0;
        text-align: center;
        background: #34495e; /* Wet Asphalt */
        letter-spacing: 1.5px;
        font-size: 1.2rem;
        border-bottom: 1px solid #3c546c;
    }

    .sidebar ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar ul li {
        border-bottom: 1px solid #3c546c;
    }

    .sidebar ul li a {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: #ecf0f1;
        text-decoration: none;
        transition: background 0.3s, padding-left 0.3s;
    }

    /* Peter River Blue highlight for active/hover */
    .sidebar ul li a:hover, .sidebar ul li a.active {
        background: #3498db; 
        padding-left: 25px; 
    }

    .sidebar ul li a i {
        margin-right: 15px;
        width: 20px;
        text-align: center;
    }

    /* --- Main Content --- */
    .main {
        margin-left: 250px; /* Offset for fixed sidebar */
        flex-grow: 1;
        padding: 30px;
        transition: margin-left 0.3s;
    }

    .container {
        max-width: 600px;
        margin: auto;
        background: #fff;
        padding: 30px;
        border-radius: 8px; /* Consistent radius */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    h1 {
        color: #2c3e50;
        font-size: 1.8rem;
        text-align: center;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 25px;
    }

    /* --- Form Elements --- */
    label {
        display: block;
        margin-top: 15px;
        margin-bottom: 5px;
        font-weight: 600;
        color: #555;
    }

    input, select {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border-radius: 4px;
        border: 1px solid #ccc;
        transition: border-color 0.3s, box-shadow 0.3s;
        box-sizing: border-box; /* Crucial for width: 100% */
        font-size: 14px;
    }

    input:focus, select:focus {
        border-color: #3498db; /* Peter River Blue focus */
        outline: none;
        box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
    }

    /* --- Buttons --- */
    .btn {
        display: block;
        width: 100%;
        margin-top: 25px;
        padding: 12px 20px;
        font-size: 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.3s;
        text-align: center;
    }

    .btn-primary { 
        background: #3498db; /* Peter River Blue */
        color: #fff; 
    }
    .btn-primary:hover { 
        background: #2980b9; 
    }

    /* --- Messages/Alerts --- (Standardized Theme) */
    .msg {
        text-align: left;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-weight: 600;
        border: 1px solid transparent;
        font-size: 0.9em;
    }

    .msg i {
        margin-right: 8px;
    }

    .msg.success {
        background: #d4edda; 
        color: #155724; 
        border-color: #c3e6cb;
    }

    .msg.error {
        background: #f8d7da; 
        color: #721c24; 
        border-color: #f5c6cb;
    }

    /* --- Responsive Styling --- */
    @media (max-width: 768px) {
        /* Collapse Sidebar to icon-only */
        .sidebar { 
            width: 60px; 
        }
        .sidebar h2, .sidebar ul li a span { 
            display: none; 
        }
        .sidebar ul li a { 
            justify-content: center; 
            padding: 15px 5px; 
        }
        .sidebar ul li a i { 
            margin-right: 0; 
        }
        .main { 
            margin-left: 60px; 
            padding: 20px;
        }
        .container {
            padding: 20px;
        }
    }

    @media (max-width: 576px) {
        /* Sidebar becomes full-width at the top for very small screens */
        body { display: block; }
        .sidebar {
            width: 100%;
            position: relative;
            height: auto;
            flex-direction: row;
            overflow-x: auto;
            border-bottom: 1px solid #3c546c;
        }
        .sidebar h2 { display: none; }
        .sidebar ul { display: flex; }
        .sidebar ul li { flex: 1; border-bottom: none; border-right: 1px solid #3c546c; }
        .sidebar ul li:last-child { border-right: none; }
        .sidebar ul li a span { display: none; }
        .sidebar ul li a { padding: 10px; justify-content: center; }
        .main { 
            margin-left: 0; 
            padding: 15px;
        }
    }
</style>
</head>
<body>

<!-- Sidebar -->
    <div class="sidebar">
        <h2>TASKNEST Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fa fa-chart-line"></i><span> Dashboard</span></a></li>
            <li><a href="users.php" class="active"><i class="fa fa-users"></i><span> Manage Users</span></a></li>
            <li><a href="assignment.php"><i class="fa fa-tasks"></i><span> Manage Assignments</span></a></li>
            <li><a href="reports.php"><i class="fa fa-file-alt"></i><span> Reports & Analytics</span></a></li>
            <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
            <li><a href="settings.php"><i class="fa fa-cogs"></i><span> Settings & Security</span></a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
        </ul>
    </div>

<!-- Main Content -->
<div class="main">
    <div class="container">
        <h1><i class="fas fa-user-plus"></i> Add New User</h1>
        <?= $message ?>

        <form method="POST">
            <label for="name">Full Name:</label>
            <input type="text" name="name" id="name" required placeholder="Enter full name">

            <label for="email">Email Address:</label>
            <input type="email" name="email" id="email" required placeholder="e.g., user@institution.edu">

            <label for="role">Role:</label>
            <select name="role" id="role" required>
                <option value="">-- Select Role --</option>
                <option value="admin">Admin</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
            </select>

            <label for="password">Initial Password:</label>
            <input type="password" name="password" id="password" required placeholder="Minimum 8 characters">

            <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add User</button>
        </form>
    </div>
</div>

</body>
</html>
