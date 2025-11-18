<?php
// announcements.php — Unified for Admins & Teachers (final working version)
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/auth.php';

// ✅ Require login
require_login();
$user = current_user();

// ✅ Restrict access to only Admins and Teachers
if (!in_array($user['role'], ['admin', 'teacher'])) {
  http_response_code(403);
  exit("Access Denied: Only Admins or Teachers can access this page.");
}

$message = "";
$error = "";

// ==================== DELETE ANNOUNCEMENT ====================
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];

  try {
    if ($user['role'] === 'admin') {
      $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
      $stmt->execute([$id]);
    } else {
      // Teachers can delete only their own announcements
      $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ? AND sender_id = ? AND sender_role = 'teacher'");
      $stmt->execute([$id, $user['id']]);
    }

    $message = ($stmt->rowCount() > 0)
      ? "🗑️ Announcement deleted successfully!"
      : "⚠️ Could not delete announcement (not found or no permission).";
  } catch (PDOException $e) {
    $error = "Database Error (delete): " . $e->getMessage();
  }
}

// ==================== ADD / UPDATE ANNOUNCEMENT ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
  $title = trim($_POST['title'] ?? 'New Announcement');
  $content = trim($_POST['content']);
  $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
  $target_user = $_POST['target_user'] ?? 'all';

  if ($content === '') {
    $error = "⚠️ Please enter some text before posting.";
  } else {
    try {
      if (!empty($_POST['id'])) {
        // Update existing announcement
        $id = (int)$_POST['id'];

        if ($user['role'] === 'teacher') {
          // Prevent teachers from editing others’ announcements
          $check = $pdo->prepare("SELECT id FROM announcements WHERE id = ? AND sender_id = ? AND sender_role = 'teacher'");
          $check->execute([$id, $user['id']]);
          if ($check->rowCount() === 0) {
            throw new Exception("Permission denied to update this announcement.");
          }
        }

        $stmt = $pdo->prepare("
                    UPDATE announcements 
                    SET title = ?, content = ?, target_user = ?, expiry_date = ?, updated_at = NOW()
                    WHERE id = ?
                ");
        $stmt->execute([$title, $content, $target_user, $expiry_date, $id]);

        $message = "✅ Announcement updated successfully!";
      } else {
        // Insert new announcement
        $stmt = $pdo->prepare("
                    INSERT INTO announcements (sender_id, sender_role, title, content, target_user, created_at, expiry_date)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)
                ");
        $stmt->execute([$user['id'], $user['role'], $title, $content, $target_user, $expiry_date]);

        // Insert into notifications for students if target = student or all
        if (in_array($target_user, ['student', 'all'])) {
          $stmt2 = $pdo->prepare("
                        INSERT INTO notifications (sender_id, sender_type, target_user, title, message, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
          $stmt2->execute([$user['id'], $user['role'], $target_user, $title, $content]);
        }

        $message = "✅ Announcement posted successfully!";
      }
    } catch (Exception $e) {
      $error = "Error: " . $e->getMessage();
    }
  }
}

// ==================== FETCH ACTIVE ANNOUNCEMENTS ====================
try {
  if ($user['role'] === 'admin') {
    $stmt = $pdo->query("
            SELECT a.*, u.name AS author_name
            FROM announcements a
            LEFT JOIN users u ON a.sender_id = u.id
            WHERE a.expiry_date IS NULL OR a.expiry_date >= NOW()
            ORDER BY a.created_at DESC
        ");
  } else {
    $stmt = $pdo->prepare("
            SELECT a.*, u.name AS author_name
            FROM announcements a
            LEFT JOIN users u ON a.sender_id = u.id
            WHERE a.sender_id = :id 
              AND a.sender_role = 'teacher'
              AND (a.expiry_date IS NULL OR a.expiry_date >= NOW())
            ORDER BY a.created_at DESC
        ");
    $stmt->execute(['id' => $user['id']]);
  }
  $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $error = "Database Error (fetch): " . $e->getMessage();
  $announcements = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Announcements - TASKNEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background-color: #f4f7f6;
      color: #333;
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 250px;
      height: 100vh;
      background: #2c3e50;
      color: #ecf0f1;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      background: #34495e;
      margin: 0;
      padding: 20px;
      text-align: center;
      font-family: 'Arial Black', sans-serif;
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
      transition: background 0.3s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #3498db;
    }

    .sidebar ul li a i {
      margin-right: 10px;
    }

    .main {
      margin-left: 250px;
      padding: 30px;
      flex: 1;
    }

    .main h1 {
      color: #2c3e50;
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
    }

    .section {
      background: #fff;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }

    textarea,
    input[type="date"],
    input[type='text'],
    select {
      width: 100%;
      padding: 10px;
      border-radius: 4px;
      border: 1px solid #ccc;
      margin-bottom: 15px;
      font-size: 1em;
      box-sizing: border-box;
    }

    button {
      background: #3498db;
      color: #fff;
      padding: 10px 18px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: 600;
      transition: background 0.3s;
    }

    button:hover {
      background: #2980b9;
    }

    .alert {
      padding: 15px;
      border-radius: 4px;
      margin-bottom: 25px;
    }

    .alert.success {
      background: #d4edda;
      color: #155724;
    }

    .alert.error {
      background: #f8d7da;
      color: #721c24;
    }

    .announcement-list {
      list-style: none;
      padding: 0;
    }

    .announcement-list li {
      background: #fff;
      border: 1px solid #ddd;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .announcement-actions a {
      margin-left: 15px;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.9em;
    }

    a.edit {
      color: #28a745;
    }

    a.delete {
      color: #dc3545;
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 60px;
      }

      .sidebar h2,
      .sidebar ul li a span {
        display: none;
      }

      .main {
        margin-left: 60px;
        padding: 20px;
      }

      .announcement-list li {
        flex-direction: column;
        align-items: flex-start;
      }
    }
  </style>
</head>

<body>

  <div class="sidebar">
    <h2><?= strtoupper($user['role']); ?> PANEL</h2>
    <ul>
      <li><a href="<?= $user['role'] === 'admin' ? 'admin/admin_dashboard.php' : 'teachers/teacher_dashboard.php' ?>"><i class="fa fa-chart-line"></i><span> Dashboard</span></a></li>
      <li><a href="announcements.php" class="active"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
      <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
    </ul>
  </div>

  <div class="main">
    <h1><i class="fa fa-bullhorn"></i> Manage Announcements</h1>

    <?php if ($message): ?>
      <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="section">
      <h2><i class="fa fa-plus-circle"></i> Create New Announcement</h2>
      <form method="POST" action="">
        <input type="text" name="title" placeholder="Enter title..." required>
        <textarea name="content" rows="3" placeholder="Write your announcement..." required></textarea>
        <label>Expiry Date:</label>
        <input type="date" name="expiry_date" min="<?= date('Y-m-d') ?>">
        <label>Target Audience:</label>
        <select name="target_user" required>
          <?php if ($user['role'] === 'admin'): ?>
            <option value="all">Everyone</option>
            <option value="student">Students Only</option>
            <option value="teacher">Teachers Only</option>
          <?php else: ?>
            <option value="student">My Students</option>
          <?php endif; ?>
        </select>
        <button type="submit"><i class="fa fa-paper-plane"></i> Post Announcement</button>
      </form>
    </div>

    <div class="section">
      <h2><i class="fa fa-list"></i> Active Announcements</h2>
      <?php if ($announcements): ?>
        <ul class="announcement-list">
          <?php foreach ($announcements as $a): ?>
            <li>
              <div class="announcement-content">
                <strong><?= htmlspecialchars($a['title']) ?></strong>
                <p><?= nl2br(htmlspecialchars($a['content'])) ?></p>
                <small>
                  📅 <?= htmlspecialchars($a['created_at']) ?>
                  <?php if ($a['expiry_date']): ?> | ⏳ Expires: <?= htmlspecialchars($a['expiry_date']) ?><?php endif; ?>
                    <br>👤 Posted by: <?= htmlspecialchars($a['author_name']) ?>
                </small>
              </div>
              <div class="announcement-actions">
                <a href="?delete=<?= $a['id'] ?>" class="delete" onclick="return confirm('Delete this announcement?');"><i class="fa fa-trash"></i> Delete</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No active announcements found.</p>
      <?php endif; ?>
    </div>
  </div>

</body>

</html>