<?php
// teacher_reports.php - Teacher Reports
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

// Ensure only teachers can access
require_role('teacher');
$user = current_user();

// ==================== FETCH STUDENT PERFORMANCE ====================
$stmt = $pdo->prepare("
  SELECT u.name AS student_name, a.title AS assignment_title, s.score, s.status
  FROM submissions s
  JOIN assignments a ON s.assignment_id = a.id
  JOIN users u ON s.student_id = u.id
  WHERE a.teacher_id = ?
  ORDER BY u.name ASC, a.title ASC
");
$stmt->execute([$user['id']]);
$reportData = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Reports - TASKNEST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* General Body Styles - Consistent with previous version */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    /* Updated slightly to match the provided PHP style's light background */
    background-color: #f4f6f9; 
    color: #333;
    line-height: 1.6;
    display: flex; /* Necessary for the fixed sidebar/main layout */
}

/* --- Sidebar --- (Based on original .sidebar, adjusted for new HTML structure) */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh; /* Use 100vh for full height */
    background: #2c3e50; /* Dark Slate Blue */
    color: #ecf0f1;
    display: flex;
    flex-direction: column;
    overflow-y: auto; /* Allows scrolling if navigation is long */
}

.sidebar h2 {
    /* Updated to match the original style's heading background */
    font-family: 'Arial Black', Gadget, sans-serif; 
    padding: 20px;
    margin: 0;
    text-align: center;
    color: #ecf0f1;
    background: #34495e; /* Wet Asphalt */
    letter-spacing: 2px;
    font-size: 1.4rem; /* Added from the second CSS for sizing */
}

.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar ul li a {
    display: flex;
    align-items: center;
    /* Adjusted padding to be closer to the second CSS but kept original hover transition focus */
    padding: 15px 20px; 
    color: #ecf0f1;
    text-decoration: none;
    transition: background 0.3s, padding-left 0.3s;
}

/* Sidebar hover/active - Using the original vibrant blue highlight */
.sidebar ul li a:hover, .sidebar ul li a.active {
    background: #3498db; /* Peter River Blue */
    padding-left: 25px;
}

.sidebar ul li a i {
    /* Kept original spacing */
    margin-right: 15px;
    width: 20px; 
    text-align: center;
}

/* --- Main Content --- */
.main {
    margin-left: 250px;
    padding: 30px; /* Use the larger original padding */
    flex: 1; /* Allows main content to take remaining width */
}

.section { /* Used in place of .container from the original CSS */
    background: #fff;
    padding: 25px 30px; /* Use the larger original padding */
    border-radius: 8px; /* Slightly smaller radius from original (8px vs 12px) for consistency */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

h1, h2 {
    color: #2c3e50;
    margin-top: 0;
}

h2 {
    border-bottom: 2px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

/* --- Table Styles --- (Blended original and new styles) */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px; /* Use original spacing */
}

th, td {
    padding: 12px 15px; /* Use original padding */
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: middle;
}

thead th {
    background-color: #f2f2f2; /* Kept original light background */
    font-weight: 600;
    color: #555;
}

tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

tbody tr:hover {
    background-color: #f1f1f1;
}

/* --- Badges for Status --- (Replicating the original badge style with the new colors) */
.badge {
    /* Replicate original badge padding/style */
    padding: 5px 12px; 
    border-radius: 15px;
    color: #fff;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    display: inline-block; /* Essential for proper display */
}

.graded { background-color: #28a745; } /* Changed to original .badge-graded green */
.pending { background-color: #f39c12; } /* Used the new pending color for contrast */


/* --- Buttons --- (Replicating the original button style with the new colors) */
.btn {
    /* Replicate original button style */
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: background-color 0.3s;
    color: white; /* Ensure text is white */
}

/* Specific button colors from the PHP file */
.btn-pdf { background: #e74c3c; } /* Red */
.btn-pdf:hover { background: #c0392b; }

.btn-csv { background: #27ae60; } /* Green */
.btn-csv:hover { background: #1e8449; }

/* Margin adjustment for button group */
.btn-pdf, .btn-csv {
    margin: 10px 10px 0 0;
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
    <h1><i class="fa fa-file-alt"></i> Reports</h1>

    <div class="section">
      <h2>Student Performance</h2>

      <!-- Export Buttons -->
      <a href="export_teacher_report.php?type=pdf" target="_blank" class="btn btn-pdf"><i class="fa fa-file-pdf"></i> Download PDF</a>
      <a href="export_teacher_report.php?type=csv" target="_blank" class="btn btn-csv"><i class="fa fa-file-csv"></i> Download CSV</a>

      <!-- Report Table -->
      <table>
        <tr>
          <th>Student</th>
          <th>Assignment</th>
          <th>Grade</th>
          <th>Status</th>
        </tr>
        <?php if ($reportData): ?>
          <?php foreach ($reportData as $row): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['student_name']); ?></td>
              <td><?php echo htmlspecialchars($row['assignment_title']); ?></td>
              <td><?php echo $row['score'] !== null ? htmlspecialchars($row['score']) : 'N/A'; ?></td>
              <td>
                <span class="badge <?php echo $row['status'] === 'graded' ? 'graded' : 'pending'; ?>">
                  <?php echo ucfirst($row['status']); ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No report data available.</td></tr>
        <?php endif; ?>
      </table>
    </div>
  </div>

</body>
</html>
