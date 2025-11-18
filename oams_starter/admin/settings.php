<?php
// settings.php — Admin Settings Page (with Sidebar)

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_role('admin');

$upload_dir = __DIR__ . '/../uploads/settings/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

$cache_dir = __DIR__ . '/../cache/';
$log_dir = __DIR__ . '/../logs/';
if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
$log_file = $log_dir . 'system.log';
if (!file_exists($log_file)) file_put_contents($log_file, "System Log Initialized\n");

$message = "";

// Fetch settings
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$settings) {
    $settings = [
        'system_name' => 'Online Assignment Management System',
        'institution_name' => 'Your Institution Name',
        'system_logo' => '',
        'contact_email' => 'admin@example.com',
        'min_password_length' => 8,
        'account_lockout_limit' => 5,
        'session_timeout' => 30,
        'two_factor_auth' => 0,
        'maintenance_mode' => 0
    ];
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        $system_name = trim($_POST['system_name']);
        $institution_name = trim($_POST['institution_name']);
        $contact_email = trim($_POST['contact_email']);
        $min_password_length = (int)$_POST['min_password_length'];
        $account_lockout_limit = (int)$_POST['account_lockout_limit'];
        $session_timeout = (int)$_POST['session_timeout'];
        $two_factor_auth = isset($_POST['two_factor_auth']) ? 1 : 0;
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

        // Handle logo
        $system_logo = $settings['system_logo'];
        if (!empty($_FILES['system_logo']['name'])) {
            $filename = 'logo_' . time() . '.' . pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION);
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['system_logo']['tmp_name'], $target)) {
                $system_logo = 'uploads/settings/' . $filename;
            }
        }

        // Save
        $sql = "REPLACE INTO settings 
                (id, system_name, institution_name, system_logo, contact_email,
                 min_password_length, account_lockout_limit, session_timeout, 
                 two_factor_auth, maintenance_mode)
                VALUES (1,?,?,?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            $system_name,
            $institution_name,
            $system_logo,
            $contact_email,
            $min_password_length,
            $account_lockout_limit,
            $session_timeout,
            $two_factor_auth,
            $maintenance_mode
        ]);

        $message = $ok ? "<p class='success'>✅ Settings saved successfully.</p>"
            : "<p class='error'>❌ Failed to update settings.</p>";
    }

    if (isset($_POST['clear_cache'])) {
        foreach (glob($cache_dir . '*') as $file) if (is_file($file)) unlink($file);
        $message = "<p class='success'>🧹 Cache cleared successfully.</p>";
    }

    if (isset($_POST['view_logs'])) {
        $logs = nl2br(htmlspecialchars(file_get_contents($log_file)));
        $message = "<div class='section'><h2>System Logs</h2>
                    <pre style='background:#111;color:#0f0;padding:15px;border-radius:6px;
                    max-height:300px;overflow:auto;'>$logs</pre></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Admin Settings - TASKNEST</title>
    <style>
        /* General Body Styles - Consistent with previous versions */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f4f7f6;
            /* Original light background */
            color: #333;
            line-height: 1.6;
        }

        /* --- Layout --- */
        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- (Fully consistent with the established dark theme) */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            /* Using the standard 250px width */
            height: 100vh;
            background: #2c3e50;
            /* Dark Slate Blue */
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            flex-shrink: 0;
            transition: width 0.3s;
        }

        .sidebar h2 {
            /* Consistent brand styling */
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

        .sidebar ul li {
            border-bottom: 1px solid #34495e;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            /* Consistent padding */
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s, padding-left 0.3s;
        }

        /* Applying the Peter River Blue highlight */
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
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
            margin-left: 250px;
            /* Adjusted to 250px for consistency */
            flex-grow: 1;
            padding: 30px;
            /* Consistent padding */
            transition: margin-left 0.3s;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 30px;
            /* Consistent container padding */
            border-radius: 8px;
            /* Consistent radius */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            /* Consistent shadow */
        }

        h1 {
            color: #2c3e50;
            margin-top: 0;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }

        /* --- Sections --- */
        .section {
            background: #fcfcfc;
            /* Lighter background for sections */
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .section h2 {
            color: #3498db;
            /* Blue accent for subheadings */
            margin-top: 0;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ddd;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .section h2 i {
            margin-right: 10px;
        }

        /* --- Form Elements --- */
        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        input[type=text],
        input[type=email],
        input[type=number],
        select {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        input:focus {
            border-color: #3498db;
            /* Consistent blue focus color */
            outline: none;
        }

        input[type=file] {
            margin-top: 8px;
        }

        /* Checkbox styling */
        label input[type=checkbox] {
            width: auto;
            margin-right: 8px;
        }

        label:has(input[type=checkbox]) {
            display: flex;
            align-items: center;
            font-weight: normal;
        }


        /* Logo Preview */
        .section img {
            max-width: 150px;
            height: auto;
            margin-top: 15px;
            border: 1px solid #eee;
            padding: 5px;
            border-radius: 4px;
        }

        /* --- Buttons --- (Consistent Color Scheme) */
        .btn-primary,
        .btn-secondary,
        .btn-danger {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 18px;
            /* Consistent padding */
            border: none;
            border-radius: 4px;
            /* Consistent radius */
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        /* Primary Button (Save) -> Peter River Blue */
        .btn-primary {
            background: #3498db;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        /* Secondary Button (Clear Cache) -> Dark Gray */
        .btn-secondary {
            background: #95a5a6;
            /* Silver/Gray */
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        /* Danger Button (View Logs) -> Red */
        .btn-danger {
            background: #e74c3c;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        /* --- Alerts --- */
        .success,
        .error {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Log Display */
        .section pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: Consolas, monospace;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 768px) {

            /* Collapse Sidebar (Consistent with Admin Dashboard) */
            .sidebar {
                width: 60px;
                position: fixed;
                /* Keep it fixed on tablet/desktop */
                height: 100vh;
            }

            .sidebar h2,
            .sidebar ul li a span {
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

            /* Sidebar becomes full-width at the top on small screens */
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }

            .sidebar h2 {
                display: block;
                text-align: left;
                padding: 15px 20px;
            }

            .sidebar ul {
                display: flex;
                flex-wrap: wrap;
            }

            .sidebar ul li {
                width: 50%;
                border-bottom: none;
            }

            .sidebar ul li a {
                padding: 10px 15px;
            }

            .main {
                margin-left: 0;
                padding: 15px;
            }

            .btn-primary,
            .btn-secondary,
            .btn-danger {
                display: block;
                width: 100%;
                margin-top: 15px;
            }
        }
    </style>
</head>

<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>TASKNEST Admin</h2>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fa fa-chart-line"></i><span> Dashboard</span></a></li>
                <li><a href="users.php"><i class="fa fa-users"></i><span> Manage Users</span></a></li>
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
                <h1>⚙️ Admin Settings</h1>
                <?= $message ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="section">
                        <h2>General Settings</h2>
                        <label>System Name:</label>
                        <input type="text" name="system_name" value="<?= htmlspecialchars($settings['system_name']) ?>" required>

                        <label>Institution Name:</label>
                        <input type="text" name="institution_name" value="<?= htmlspecialchars($settings['institution_name']) ?>" required>

                        <label>System Logo:</label>
                        <input type="file" name="system_logo">
                        <?php if (!empty($settings['system_logo'])): ?>
                            <img src="../<?= htmlspecialchars($settings['system_logo']) ?>" alt="Logo">
                        <?php endif; ?>

                        <label>Contact Email:</label>
                        <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email']) ?>" required>
                    </div>

                    <div class="section">
                        <h2>Security Settings</h2>
                        <label>Minimum Password Length:</label>
                        <input type="number" name="min_password_length" min="6" value="<?= htmlspecialchars($settings['min_password_length']) ?>">

                        <label>Account Lockout Limit:</label>
                        <input type="number" name="account_lockout_limit" min="1" value="<?= htmlspecialchars($settings['account_lockout_limit']) ?>">

                        <label>Session Timeout (minutes):</label>
                        <input type="number" name="session_timeout" min="5" value="<?= htmlspecialchars($settings['session_timeout']) ?>">

                        <label><input type="checkbox" name="two_factor_auth" <?= $settings['two_factor_auth'] ? 'checked' : '' ?>> Enable Two-Factor Authentication</label>
                    </div>

                    <div class="section">
                        <h2>Backup & Maintenance</h2>
                        <button name="clear_cache" class="btn-secondary">🧹 Clear Cache</button>
                        <button name="view_logs" class="btn-danger">📜 View Logs</button>
                    </div>

                    <div style="text-align:center; margin-top:30px;">
                        <button type="submit" name="save_settings" class="btn-primary">💾 Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>