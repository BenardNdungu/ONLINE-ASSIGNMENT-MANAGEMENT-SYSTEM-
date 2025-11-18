<?php
// admin_dashboard.php
// Admin Dashboard for Online Assignment Management System (TASKNEST)

include_once __DIR__ . '/../src/config.php';
include_once __DIR__ . '/../src/db.php';
include_once __DIR__ . '/../src/auth.php';

require_role('admin');
$user = current_user();

// Fetch counts
$totalStudents = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role='student'")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$totalTeachers = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role='teacher'")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$activeAssignments = $pdo->query("SELECT COUNT(*) AS c FROM assignments WHERE due_date >= NOW()")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
$pendingSubmissions = $pdo->query("SELECT COUNT(*) AS c FROM submissions WHERE status='pending'")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;

// Fetch latest announcements
$stmt = $pdo->query("SELECT content, created_at FROM announcements ORDER BY created_at DESC LIMIT 5");
$announcements = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>TASKNEST Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
      /* Ensures full height coverage */
    }

    /* --- Sidebar --- (Consistent Dark Theme) */
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
      transition: width 0.3s;
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
      /* Retaining list item separator */
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

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #3498db;
      /* Peter River Blue highlight */
      padding-left: 25px;
      /* Subtle movement on hover */
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
      transition: margin-left 0.3s;
    }

    h1 {
      color: #2c3e50;
      margin-top: 0;
      margin-bottom: 20px;
    }

    /* --- Dashboard Cards --- (Refined for the established aesthetic) */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 25px;
      /* Consistent larger gap */
      margin-bottom: 30px;
    }

    .card {
      background: #fff;
      padding: 25px;
      /* Consistent container radius and shadow */
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: transform 0.2s;
      /* Added subtle hover effect */
    }

    .card:hover {
      transform: translateY(-3px);
    }

    .card i {
      font-size: 2.5rem;
      /* Slightly larger icon */
      color: #3498db;
      /* Using the primary blue accent color for icons */
    }

    .card h3 {
      margin: 0;
      font-size: 1em;
      color: #6c757d;
      /* Lighter text for the label */
    }

    .card p {
      margin: 5px 0 0 0;
      /* Added slight top margin */
      font-size: 1.8rem;
      /* Larger value for emphasis */
      font-weight: bold;
      color: #2c3e50;
    }

    /* --- Section/Container Styling --- (Consistent with the original container look) */
    .section {
      background: #fff;
      /* Consistent padding and radius */
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      margin-bottom: 30px;
    }

    .section h2 {
      color: #2c3e50;
      margin-top: 0;
      /* Consistent bottom border style for section headers */
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    /* --- Announcements List (Styling to match previous list/messages) --- */
    .section ul {
      list-style: disc;
      /* Use standard disc for readability */
      padding-left: 20px;
      margin-top: 15px;
    }

    .section li {
      padding: 8px 0;
      border-bottom: 1px dashed #eee;
      color: #333;
    }

    .section li:last-child {
      border-bottom: none;
    }

    .section small {
      color: #6c757d !important;
      font-style: italic;
      margin-left: 10px;
    }

    /* Ensure links within the report section are styled */
    .section a {
      color: #3498db;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .section a:hover {
      color: #2980b9;
      text-decoration: underline;
    }



    /* --- Responsive Design --- (Using the original mobile breakpoints) */
    @media (max-width: 768px) {

      /* Collapsed Sidebar on Tablet */
      .sidebar {
        width: 60px;
      }

      .sidebar h2 {
        display: none;
      }

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

      .cards {
        gap: 15px;
      }
    }

    @media (max-width: 576px) {

      /* Full collapse for phone view */
      .sidebar {
        position: relative;
        width: 100%;
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
      <li><a href="assignment.php"><i class="fa fa-tasks"></i><span> Manage Assignments</span></a></li>
      <li><a href="reports.php"><i class="fa fa-file-alt"></i><span> Reports & Analytics</span></a></li>
      <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
      <li><a href="settings.php"><i class="fa fa-cogs"></i><span> Settings & Security</span></a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main">
    <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?> 👋</h1>

    <!-- Stats Cards -->
    <div class="cards">
      <div class="card">
        <div>
          <h3>Total Students</h3>
          <p><?php echo $totalStudents; ?></p>
        </div>
        <i class="fa fa-user-graduate"></i>
      </div>
      <div class="card">
        <div>
          <h3>Total Teachers</h3>
          <p><?php echo $totalTeachers; ?></p>
        </div>
        <i class="fa fa-chalkboard-teacher"></i>
      </div>
      <div class="card">
        <div>
          <h3>Active Assignments</h3>
          <p><?php echo $activeAssignments; ?></p>
        </div>
        <i class="fa fa-file-alt"></i>
      </div>
      <div class="card">
        <div>
          <h3>Pending Submissions</h3>
          <p><?php echo $pendingSubmissions; ?></p>
        </div>
        <i class="fa fa-clock"></i>
      </div>
    </div>


    <!-- Reports -->
    <div class="section">
      <h2><i class="fa fa-file-export"></i> Reports & Analytics</h2>
      <p>Generate system reports:</p>
      <a href="export_report.php?type=all&format=pdf" class="btn btn-danger"><i class="fa fa-file-pdf"></i> Download Full System Report (PDF)</a>
    </div>
  </div>

</body>

</html>