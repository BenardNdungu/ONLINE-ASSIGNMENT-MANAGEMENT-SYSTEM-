<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_role('admin'); // ✅ Only admins can access this page

// Fetch logs (latest first)
$stmt = $pdo->query("
    SELECT l.id, u.name AS username, u.role, l.action, l.details, l.created_at
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View System Logs - Admin Dashboard</title>
  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      background-color: #f4f6f8;
      margin: 0;
      padding: 0;
    }
    header {
      background-color: #283593;
      color: white;
      padding: 15px;
      text-align: center;
    }
    .container {
      max-width: 1100px;
      margin: 30px auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
      padding: 20px;
    }
    h1 {
      color: #333;
      font-size: 24px;
      margin-bottom: 20px;
      text-align: center;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px 10px;
      text-align: left;
      font-size: 14px;
    }
    th {
      background-color: #3949ab;
      color: white;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    tr:hover {
      background-color: #e8eaf6;
    }
    .no-data {
      text-align: center;
      padding: 20px;
      color: #777;
    }
    .btn-back {
      display: inline-block;
      background-color: #3949ab;
      color: white;
      padding: 8px 16px;
      border-radius: 4px;
      text-decoration: none;
      margin-bottom: 15px;
    }
    .btn-back:hover {
      background-color: #303f9f;
    }
  </style>
</head>
<body>

<header>
  <h2>Admin Dashboard</h2>
</header>

<div class="container">
  <a href="dashboard.php" class="btn-back">&larr; Back to Dashboard</a>
  <h1>System Activity Logs</h1>

  <?php if (empty($logs)): ?>
    <div class="no-data">No logs found.</div>
  <?php else: ?>
    <div style="max-height: 500px; overflow-y: auto;">
      <table>
        <tr>
          <th>ID</th>
          <th>User</th>
          <th>Role</th>
          <th>Action</th>
          <th>Details</th>
          <th>Timestamp</th>
        </tr>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><?= htmlspecialchars($log['id']) ?></td>
            <td><?= htmlspecialchars($log['username'] ?? 'Unknown') ?></td>
            <td><?= htmlspecialchars($log['role'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['action']) ?></td>
            <td><?= htmlspecialchars($log['details']) ?></td>
            <td><?= htmlspecialchars($log['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
