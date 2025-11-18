<?php
// teacher_dashboard.php
// Teacher Dashboard for Online Assignment Management System (TASKNEST)

require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

// Ensure only teachers can access
require_role('teacher');
$user = current_user();

// ==================== FETCH DATA ====================

// Total assignments created by this teacher
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM assignments WHERE teacher_id = ?");
$stmt->execute([$user['id']]);
$totalAssignments = $stmt->fetch()['c'] ?? 0;

// Pending submissions (not yet graded)
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM submissions s 
                       JOIN assignments a ON s.assignment_id = a.id
                       WHERE a.teacher_id = ? AND s.status = 'pending'");
$stmt->execute([$user['id']]);
$pendingSubmissions = $stmt->fetch()['c'] ?? 0;

// Graded submissions
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM submissions s 
                       JOIN assignments a ON s.assignment_id = a.id
                       WHERE a.teacher_id = ? AND s.status = 'graded'");
$stmt->execute([$user['id']]);
$gradedSubmissions = $stmt->fetch()['c'] ?? 0;

// Upcoming deadlines
$stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM assignments WHERE teacher_id = ? AND due_date >= NOW()");
$stmt->execute([$user['id']]);
$upcomingDeadlines = $stmt->fetch()['c'] ?? 0;

// Upcoming assignments list
$stmt = $pdo->prepare("SELECT title, due_date FROM assignments 
                       WHERE teacher_id = ? AND due_date >= NOW() 
                       ORDER BY due_date ASC LIMIT 5");
$stmt->execute([$user['id']]);
$upcomingAssignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>TASKNEST - Teacher Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* ===== General & Layout ===== */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background-color: #f4f7f6;
      color: #333;
      line-height: 1.6;
      display: flex;
    }

    a {
      text-decoration: none;
      color: #3498db;
    }

    /* --- Sidebar --- */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 250px;
      height: 100%;
      background: #2c3e50;
      /* Dark Slate Blue */
      color: #ecf0f1;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      font-family: 'Arial Black', Gadget, sans-serif;
      padding: 20px;
      margin: 0;
      text-align: center;
      background: #34495e;
      /* Wet Asphalt */
      letter-spacing: 2px;
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
      flex: 1;
    }

    .main h1 {
      font-size: 26px;
      margin-top: 0;
      margin-bottom: 5px;
      color: #2c3e50;
    }

    .main .muted {
      color: #6c757d;
      margin-top: 0;
      margin-bottom: 30px;
      font-size: 0.9em;
    }

    /* --- Stats Cards --- */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .card {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .card h3 {
      margin: 0;
      font-size: 0.9rem;
      color: #555;
      font-weight: 600;
      text-transform: uppercase;
    }

    .card p {
      margin: 5px 0 0;
      font-size: 2.2rem;
      font-weight: 700;
      color: #2c3e50;
    }

    .card i {
      font-size: 2.5rem;
      opacity: 0.7;
    }

    /* Color coding for card icons */
    .card:nth-child(1) i {
      color: #3498db;
    }

    /* Blue */
    .card:nth-child(2) i {
      color: #f39c12;
    }

    /* Orange */
    .card:nth-child(3) i {
      color: #28a745;
    }

    /* Green */
    .card:nth-child(4) i {
      color: #9b59b6;
    }

    /* Purple */

    /* --- Table Section --- */
    .section {
      background: #fff;
      border-radius: 8px;
      padding: 25px 30px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .section h2 {
      margin-top: 0;
      margin-bottom: 20px;
      font-size: 22px;
      color: #333;
    }

    .section h2 i {
      margin-right: 10px;
      color: #3498db;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 12px 15px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }

    thead th,
    table th {
      background-color: #f2f2f2;
      font-weight: 600;
      color: #555;
      border-bottom-width: 2px;
    }

    tbody tr:hover {
      background-color: #f9f9f9;
    }

    td[colspan="2"] {
      text-align: center;
      color: #888;
      padding: 20px;
    }


    /* --- Responsive Design --- */
    @media (max-width: 992px) {
      .sidebar {
        width: 220px;
      }

      .main {
        margin-left: 220px;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
      }

      .sidebar h2 {
        background: none;
        padding-left: 20px;
      }

      .sidebar ul {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
      }

      .sidebar ul li a {
        padding: 15px;
      }

      .sidebar ul li a span {
        display: none;
        /* Hide text, show only icons */
      }

      .sidebar ul li a i {
        margin-right: 0;
        font-size: 1.2em;
      }

      .main {
        margin-left: 0;
        padding: 20px;
      }
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h2>TASKNEST</h2><br>
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

  <!-- Main Content -->
  <div class="main">
    <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
    <p class="muted">Role: Teacher | Department: <?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></p>

    <!-- Stats Cards -->
    <div class="cards">
      <div class="card">
        <div>
          <h3>Total Assignments</h3>
          <p><?php echo $totalAssignments; ?></p>
        </div>
        <i class="fa fa-file-alt"></i>
      </div>
      <div class="card">
        <div>
          <h3>Pending Submissions</h3>
          <p><?php echo $pendingSubmissions; ?></p>
        </div>
        <i class="fa fa-hourglass-half"></i>
      </div>
      <div class="card">
        <div>
          <h3>Graded Submissions</h3>
          <p><?php echo $gradedSubmissions; ?></p>
        </div>
        <i class="fa fa-check-circle"></i>
      </div>
      <div class="card">
        <div>
          <h3>Upcoming Deadlines</h3>
          <p><?php echo $upcomingDeadlines; ?></p>
        </div>
        <i class="fa fa-calendar-alt"></i>
      </div>
    </div>

    <!-- Upcoming Assignments Table -->
    <div class="section">
      <h2><i class="fa fa-calendar"></i> DUE Assignments</h2>
      <table>
        <tr>
          <th>Title</th>
          <th>Due Date</th>
        </tr>
        <?php if ($upcomingAssignments): ?>
          <?php foreach ($upcomingAssignments as $a): ?>
            <tr>
              <td><?php echo htmlspecialchars($a['title']); ?></td>
              <td><?php echo date("M d, Y", strtotime($a['due_date'])); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="2">No DUE assignments.</td>
          </tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

</body>

</html>