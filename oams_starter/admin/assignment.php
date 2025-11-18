<?php
// assignments.php - Manage Assignments
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_role('admin');

// Fetch assignments
$stmt = $pdo->query("SELECT id, title, description, due_date, created_at FROM assignments ORDER BY due_date DESC");
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Assignments - TASKNEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <style>
    /* General Body Styles - Consistent with previous versions */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background-color: #f4f7f6;
      /* Original light background */
      color: #333;
      line-height: 1.6;
      display: flex;
      min-height: 100vh;
    }

    /* --- Sidebar --- (Fully consistent with the original dark theme) */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 250px;
      height: 100vh;
      background: #2c3e50;
      /* Dark Slate Blue */
      color: #ecf0f1;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }

    .sidebar h2 {
      /* Reverting to the original bolder font and clear spacing */
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
      /* Consistent padding and transition for hover effect */
      padding: 15px 20px;
      color: #ecf0f1;
      text-decoration: none;
      transition: background 0.3s, padding-left 0.3s;
    }

    /* Applying the vibrant blue highlight and movement to active/hover states */
    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #3498db;
      /* Peter River Blue */
      padding-left: 25px;
    }

    .sidebar ul li a i {
      margin-right: 15px;
      /* Consistent original spacing */
      width: 20px;
      text-align: center;
    }

    /* --- Main Content --- */
    .main {
      margin-left: 250px;
      padding: 30px;
      /* Using the larger, consistent padding */
      flex: 1;
    }

    .main h1 {
      color: #2c3e50;
      margin-top: 0;
      /* Adding consistent padding/border for a section-like title */
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    .main p {
      margin-bottom: 20px;
      color: #555;
    }

    /* --- Button Styling --- (Reverting to original blue primary button) */
    .btn {
      background-color: #3498db;
      /* Peter River Blue */
      color: white;
      padding: 10px 18px;
      /* Consistent button padding */
      border: none;
      border-radius: 4px;
      /* Consistent button radius */
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: background-color 0.3s;
      font-weight: 600;
    }

    .btn:hover {
      background-color: #2980b9;
      /* Darker blue on hover */
    }

    /* --- Table Styling --- (Consistent with Users/Reports tables) */
    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      /* Using original container shadow and radius for the table wrapper */
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      margin-top: 20px;
    }

    thead {
      background: #2c3e50;
      color: #fff;
    }

    thead th {
      padding: 12px 15px;
      text-align: left;
      font-weight: 600;
    }

    tbody td {
      padding: 12px 15px;
      border-bottom: 1px solid #ddd;
      vertical-align: middle;
    }

    tbody tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tbody tr:hover {
      background: #f1f1f1;
    }

    /* --- Status badges --- (Using standard consistent colors) */
    .status {
      font-weight: 600;
      padding: 5px 12px;
      border-radius: 15px;
      /* Pill shape */
      font-size: 0.8em;
      text-transform: uppercase;
      display: inline-block;
    }

    .status.active {
      background: #27ae60;
      /* Success Green */
      color: #fff;
    }

    .status.closed {
      background: #e74c3c;
      /* Alert Red */
      color: #fff;
    }

    /* --- Action links --- */
    .actions a {
      margin-right: 15px;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s;
    }

    .actions a:hover {
      text-decoration: underline;
    }

    .actions a.edit {
      color: #3498db;
      /* Peter River Blue */
    }

    .actions a.reopen {
      color: #f39c12;
      /* Orange/Warning */
    }

    .actions a.close {
      color: #95a5a6;
      /* Light Gray/Default Action */
    }

    .actions a.delete {
      color: #dc3545;
      /* Red/Danger */
    }


    /* --- Responsive Table Overhaul (Matching the previous detailed method) --- */
    @media (max-width: 768px) {

      /* Collapse Sidebar (Consistent with Admin Dashboard) */
      .sidebar {
        width: 60px;
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

      /* Table Mobile Reformat */
      table,
      thead,
      tbody,
      th,
      td,
      tr {
        display: block;
      }

      thead {
        display: none;
      }

      tr {
        border: 1px solid #ccc;
        margin-bottom: 15px;
      }

      td {
        border: none;
        border-bottom: 1px solid #eee;
        position: relative;
        padding-left: 50%;
        text-align: right;
      }

      td:before {
        position: absolute;
        top: 12px;
        left: 15px;
        width: 40%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: bold;
        text-align: left;
      }

      /* Labeling table cells (Matching the column order) */
      td:nth-of-type(1):before {
        content: "ID";
      }

      td:nth-of-type(2):before {
        content: "Title";
      }

      td:nth-of-type(3):before {
        content: "Description";
      }

      td:nth-of-type(4):before {
        content: "Due Date";
      }

      td:nth-of-type(5):before {
        content: "Created At";
      }

      td:nth-of-type(6):before {
        content: "Status";
      }

      td:nth-of-type(7):before {
        content: "Actions";
      }

      .actions {
        display: block !important;
        text-align: left;
      }

      .actions a {
        display: inline-block;
        margin-right: 10px;
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
      <li><a href="users.php"><i class="fa fa-users"></i><span> Manage Users</span></a></li>
      <li><a href="assignment.php" class="active"><i class="fa fa-tasks"></i><span> Manage Assignments</span></a></li>
      <li><a href="reports.php"><i class="fa fa-file-alt"></i><span> Reports & Analytics</span></a></li>
      <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
      <li><a href="settings.php"><i class="fa fa-cogs"></i><span> Settings & Security</span></a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
    </ul>
  </div>

  <!-- Main -->
  <div class="main">
    <h1><i class="fa fa-tasks"></i> Manage Assignments</h1>
    <p>View, add, edit, reopen, or close assignment deadlines.</p>

    <!-- Add Assignment -->
    <a href="../teachers/teacher_create_assignment.php" class="btn"><i class="fa fa-plus-circle"></i> Add New Assignment</a>

    <!-- Assignments Table -->
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Description</th>
          <th>Due Date</th>
          <th>Created At</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($assignments): ?>
          <?php foreach ($assignments as $a): ?>
            <?php
            $status = (strtotime($a['due_date']) >= time()) ? "Active" : "Closed";
            ?>
            <tr>
              <td data-label="ID"><?= htmlspecialchars($a['id']) ?></td>
              <td data-label="Title"><?= htmlspecialchars($a['title']) ?></td>
              <td data-label="Description"><?= htmlspecialchars(substr($a['description'], 0, 50)) ?>...</td>
              <td data-label="Due Date"><?= htmlspecialchars($a['due_date']) ?></td>
              <td data-label="Created At"><?= htmlspecialchars($a['created_at']) ?></td>
              <td data-label="Status">
                <span class="status <?= strtolower($status) ?>"><?= $status ?></span>
              </td>
              <td data-label="Actions" class="actions">
                <a href="edit_assignment.php?id=<?= $a['id'] ?>" class="edit"><i class="fa fa-edit"></i> Edit</a>
                <?php if ($status === "Closed"): ?>
                  <a href="reopen_assignment.php?id=<?= $a['id'] ?>" class="reopen"><i class="fa fa-redo"></i> Reopen</a>
                <?php else: ?>
                  <a href="close_assignment.php?id=<?= $a['id'] ?>" class="close"><i class="fa fa-ban"></i> Close</a>
                <?php endif; ?>
                <a href="?delete=<?= $a['id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this announcement?');"><i class="fa fa-trash"></i> Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center;">No assignments found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</body>

</html>