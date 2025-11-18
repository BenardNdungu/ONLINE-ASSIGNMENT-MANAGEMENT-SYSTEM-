<?php
require_once __DIR__ . '/../src/auth.php';
require_login();
$user = current_user();

// Only admin can delete users
if ($user['role'] !== 'admin') {
  http_response_code(403);
  exit('Access denied');
}

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// Get user ID from query
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  flash('error', 'Invalid user ID.');
  header('Location: users.php');
  exit;
}

$delete_id = intval($_GET['id']);

// Prevent admin from deleting themselves
if ($delete_id === intval($user['id'])) {
  flash('error', 'You cannot delete your own account.');
  header('Location: users.php');
  exit;
}

// Fetch user info for confirmation
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$delete_id]);
$target = $stmt->fetch();

if (!$target) {
  flash('error', 'User not found.');
  header('Location: users.php');
  exit;
}

// Handle delete confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
  $pdo->beginTransaction();
  try {
    // Delete any linked member or officer records
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_id]);

    $pdo->commit();
    flash('success', 'User deleted successfully.');
    header('Location: users.php');
    exit;
  } catch (Exception $e) {
    $pdo->rollBack();
    flash('error', 'Error deleting user: ' . $e->getMessage());
  }
}

?>
<style>
  /* === General Layout === */
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f6f9;
    color: #333;
    margin: 0;
    padding: 0;
  }

  main.dashboard {
    max-width: 800px;
    margin: 3rem auto;
    background: #fff;
    border-radius: 12px;
    padding: 2rem 3rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  main h2 {
    font-size: 1.8rem;
    color: #003366;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* === Flash Messages === */
  .success,
  .error {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
  }

  .success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
  }

  .error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
  }

  /* === Card Container === */
  .form-card {
    background: #ffffff;
    border-radius: 10px;
    padding: 2rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
  }

  .form-card p {
    font-size: 1.1rem;
    margin-bottom: 1rem;
    color: #333;
  }

  .form-card ul {
    list-style: none;
    padding: 0;
    margin-bottom: 1.5rem;
  }

  .form-card li {
    margin-bottom: 0.5rem;
    font-size: 1rem;
    color: #555;
  }

  /* === Buttons === */
  .btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .btn:hover {
    transform: scale(1.05);
  }

  .btn-secondary {
    background-color: #6c757d;
    color: white;
    margin-left: 0.75rem;
  }

  .btn-secondary:hover {
    background-color: #5a6268;
  }

  .btn[style*="background:#dc3545"] {
    color: #fff;
  }

  .btn[style*="background:#dc3545"]:hover {
    background-color: #c82333 !important;
  }

  /* === Responsive === */
  @media (max-width: 600px) {
    main.dashboard {
      padding: 1.5rem;
    }

    .form-card {
      padding: 1.5rem;
    }

    .btn {
      display: block;
      width: 100%;
      margin-bottom: 1rem;
    }

    .btn-secondary {
      margin-left: 0;
    }
  }
</style>

<main class="dashboard">
  <h2><i class="fa fa-user-minus"></i> Delete User</h2>

  <?php if ($msg = flash('error')): ?>
    <div class="error"><?= $msg ?></div>
  <?php elseif ($msg = flash('success')): ?>
    <div class="success"><?= $msg ?></div>
  <?php endif; ?>

  <div class="form-card">
    <p>Are you sure you want to delete the following user?</p>

    <ul>
      <li><strong>Name:</strong> <?= htmlspecialchars($target['name']) ?></li>
      <li><strong>Email:</strong> <?= htmlspecialchars($target['email']) ?></li>
      <li><strong>Role:</strong> <?= htmlspecialchars(ucfirst($target['role'])) ?></li>
    </ul>

    <form method="post" onsubmit="return confirm('Are you sure you want to permanently delete this user?');">
      <input type="hidden" name="confirm_delete" value="1">
      <button type="submit" class="btn" style="background:#dc3545;"><i class="fa fa-trash"></i> Confirm Delete</button>
      <a href="users.php" class="btn-secondary">Cancel</a>
    </form>
  </div>
</main>
<footer style="text-align: center; margin-top: 20px; color: #777;">
  <p>&copy; <?= date('Y') ?> TASKNEST. All rights reserved.</p>
</footer>
</body>

</html>