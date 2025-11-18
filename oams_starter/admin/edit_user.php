<?php
require_once __DIR__ . '/../src/auth.php';
require_login();

$user = current_user();

// Allow only admins (or officers if you prefer)
if (!in_array($user['role'], ['admin', 'officer'])) {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/../src/db.php';

// --- Get user ID to edit ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit('Invalid user ID.');
}

$user_id = (int)$_GET['id'];

// --- Fetch user info from database ---
$stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$edit_user = $stmt->fetch();

if (!$edit_user) {
    exit('User not found.');
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $status = $_POST['status'];

    // Validate input
    if ($full_name === '' || $email === '') {
        $error = "All fields are required.";
    } else {
        // Update user info
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, email = ?, role = ?, status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $email, $role, $status, $user_id]);
        $success = "User details updated successfully.";

        // Refresh data
        $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $edit_user = $stmt->fetch();
    }
}
?>


<style>
    /* === General Page Layout === */
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f6f8;
        color: #333;
        margin: 0;
        padding: 0;
    }

    .dashboard {
        max-width: 700px;
        margin: 50px auto;
        background: #fff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        color: #2c3e50;
        margin-bottom: 25px;
        font-size: 1.6rem;
    }

    /* === Form Styles === */
    .form-card {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #555;
    }

    input[type="text"],
    input[type="email"],
    select {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        outline: none;
        font-size: 15px;
        transition: border-color 0.3s ease;
    }

    input:focus,
    select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
    }

    /* === Buttons === */
    .btn {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 15px;
        cursor: pointer;
        transition: background 0.3s ease;
        margin-top: 10px;
        text-decoration: none;
        text-align: center;
    }

    .btn:hover {
        background-color: #0056b3;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
        text-decoration: none;
        padding: 10px 16px;
        border-radius: 6px;
        display: inline-block;
        margin-top: 10px;
        text-align: center;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
    }

    /* === Alerts === */
    .success,
    .error {
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 500;
        margin-bottom: 15px;
        text-align: center;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border-left: 5px solid #28a745;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 5px solid #dc3545;
    }

    /* === Responsive Design === */
    @media (max-width: 600px) {
        .dashboard {
            margin: 20px;
            padding: 20px;
        }

        h2 {
            font-size: 1.3rem;
        }

        input,
        select,
        .btn,
        .btn-secondary {
            font-size: 14px;
        }
    }
</style>

<main class="dashboard">
    <h2><i class="fa fa-user-edit"></i> Edit User</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php elseif (!empty($success)): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="post" class="form-card">
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($edit_user['name']) ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="student" <?= $edit_user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
            <option value="teacher" <?= $edit_user['role'] === 'teacher' ? 'selected' : '' ?>>Teacher</option>
            <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>

        <label>Status:</label>
        <select name="status" required>
            <option value="active" <?= $edit_user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $edit_user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>

        <button type="submit" class="btn">Save Changes</button>
        <a href="users.php" class="btn-secondary">Back</a>
    </form>
</main>
<footer style="text-align: center; margin-top: 20px; color: #777;">
    <p>&copy; <?= date('Y') ?> TASKNEST. All rights reserved.</p>
</footer>
</body>

</html>