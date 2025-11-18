<?php
// teacher_submissions.php - Manage Student Submissions
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

// Ensure only teachers can access
require_role('teacher');
$user = current_user();

// ==================== HANDLE GRADING ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
    $submission_id = intval($_POST['submission_id']);
    $grade = trim($_POST['grade']);
    $feedback = trim($_POST['feedback']);

    $stmt = $pdo->prepare("UPDATE submissions SET score= ?, feedback = ?, status = 'graded' WHERE id = ?");
    $stmt->execute([$grade, $feedback, $submission_id]);

    header("Location: teacher_submissions.php?success=1");
    exit;
}

// ==================== FETCH SUBMISSIONS ====================
$stmt = $pdo->prepare("
  SELECT s.id, s.submitted_at, s.status, s.score, s.feedback,
         u.name AS student_name, 
         a.title AS assignment_title, a.due_date
  FROM submissions s
  JOIN assignments a ON s.assignment_id = a.id
  JOIN users u ON s.student_id = u.id
  WHERE a.teacher_id = ?
  ORDER BY s.submitted_at DESC
");
$stmt->execute([$user['id']]);
$submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Submissions - TASKNEST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* ===== General & Layout ===== */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    background-color: #f4f7f6;
    color: #333;
    line-height: 1.6;
}

a {
    text-decoration: none;
    color: #3498db;
}

a:hover {
    color: #2980b9;
}

/* --- Sidebar --- */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100%;
    background: #2c3e50; /* Dark Slate Blue */
    color: #ecf0f1;
    display: flex;
    flex-direction: column;
}

.sidebar h2 {
    font-family: 'Arial Black', Gadget, sans-serif;
    padding: 20px;
    margin: 0;
    text-align: center;
    background: #34495e; /* Wet Asphalt */
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
.sidebar ul li.active a {
    background: #3498db; /* Peter River Blue */
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
    margin-bottom: 20px;
    color: #2c3e50;
    padding-bottom: 15px;
    border-bottom: 2px solid #eee;
}

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

/* --- Table Styles --- */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th,
td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: middle;
}

thead th,
table th {
    background-color: #f2f2f2;
    font-weight: 600;
    color: #555;
}

tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

tbody tr:hover {
    background-color: #f1f1f1;
}

/* --- Badges for Status --- */
.badge {
    padding: 5px 12px;
    border-radius: 15px;
    color: #fff;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    display: inline-block;
}

.graded {
    background-color: #28a745; /* Green */
}
.pending {
    background-color: #ffc107; /* Amber */
    color: #212529;
}
.late {
    background-color: #dc3545; /* Red */
}


/* --- Grading Form Styles --- */
.grade-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-width: 220px;
}

.grade-form input[type="text"],
.grade-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-family: inherit;
    box-sizing: border-box;
}

.grade-form textarea {
    resize: vertical;
    min-height: 50px;
}

.grade-form button {
    background-color: #28a745;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    transition: background-color 0.3s;
    align-self: flex-start;
    font-weight: 600;
}

.grade-form button:hover {
    background-color: #218838;
}
.grade-form button i {
    margin-right: 5px;
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
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    /* Responsive Table */
    table, thead, tbody, th, td, tr {
        display: block;
    }
    thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
    tr {
        border: 1px solid #ccc;
        margin-bottom: 10px;
    }
    td {
        border: none;
        border-bottom: 1px solid #eee;
        position: relative;
        padding-left: 50%;
        min-height: 30px; /* Ensure space for label */
    }
    td:before {
        position: absolute;
        top: 50%;
        left: 10px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: bold;
        transform: translateY(-50%);
    }
    /* Labeling table cells */
    td:nth-of-type(1):before { content: "Student"; }
    td:nth-of-type(2):before { content: "Assignment"; }
    td:nth-of-type(3):before { content: "Submitted"; }
    td:nth-of-type(4):before { content: "Status"; }
    td:nth-of-type(5):before { content: "Grade"; }
    td:nth-of-type(6):before { content: "Feedback"; }
    td:nth-of-type(7):before { content: "Action"; }
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
      <li class="active"><a href="teacher_submissions.php"><i class="fa fa-folder-open"></i><span> Student Submissions</span></a></li>
      <li><a href="teacher_reports.php"><i class="fa fa-file-alt"></i><span> Reports</span></a></li>
      <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
      <li><a href="teacher_profile.php"><i class="fa fa-user-cog"></i><span> Profile</span></a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
    </ul>
  </div>

  <!-- Main -->
  <div class="main">
    <h1><i class="fa fa-folder-open"></i> Student Submissions</h1>

    <div class="section">
      <h2>All Submissions</h2>
      <?php if ($submissions): ?>
        <table>
          <tr>
            <th>Student</th>
            <th>Assignment</th>
            <th>Submitted At</th>
            <th>Status</th>
            <th>Grade</th>
            <th>Feedback</th>
            <th>Action</th>
          </tr>
          <?php foreach ($submissions as $s): ?>
            <tr>
              <td><?php echo htmlspecialchars($s['student_name']); ?></td>
              <td><?php echo htmlspecialchars($s['assignment_title']); ?></td>
              <td><?php echo $s['submitted_at'] ?: 'N/A'; ?></td>
              <td>
                <span class="badge <?php echo $s['status'] === 'graded' ? 'graded' : 'pending'; ?>">
                  <?php echo ucfirst($s['status']); ?>
                </span>
              </td>
              <td><?php echo $s['score'] !== null ? htmlspecialchars($s['score']) : '-'; ?></td>
              <td><?php echo $s['feedback'] !== null ? htmlspecialchars($s['feedback']) : '-'; ?></td>
              <td>
                <!-- Grading Form -->
                <?php if ($s['status'] !== 'graded'): ?>
                  <form method="POST" class="grade-form">
                    <input type="hidden" name="submission_id" value="<?php echo $s['id']; ?>">
                    <input type="text" name="grade" placeholder="Grade (e.g. A, 85)" required>
                    <textarea name="feedback" rows="2" placeholder="Feedback..." required></textarea>
                    <button type="submit"><i class="fa fa-check"></i> Submit</button>
                  </form>
                <?php else: ?>
                  <em>Already graded</em>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p>No submissions found.</p>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>
