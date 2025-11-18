<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';

// ✅ Ensure only admin or teacher can access
if (!is_logged_in()) {
  header("Location: login.php");
  exit();
}

$user = current_user();
if (!in_array($user['role'], ['admin', 'teacher'])) {
  http_response_code(403);
  exit("Access Denied: Only admins or teachers can view submissions.");
}

// ✅ Get assignment ID safely
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : null);
if (empty($assignment_id)) {
  exit("Invalid Request: Missing assignment ID.");
}

// ✅ Fetch assignment details
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$assignment) {
  exit("Assignment not found.");
}

// ✅ Handle grading submission
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_id'])) {
  $submission_id = (int)$_POST['submission_id'];
  $grade = trim($_POST['score']);
  $feedback = trim($_POST['feedback']);

  $stmt = $pdo->prepare("UPDATE submissions SET score = ?, feedback = ?, status = 'Graded' WHERE id = ?");
  $stmt->execute([$grade, $feedback, $submission_id]);

  $success = "✅ Grade updated successfully!";
}

// ✅ Fetch all submissions for this assignment
$stmt = $pdo->prepare("
    SELECT s.*, u.name AS student_name, u.email AS student_email
    FROM submissions s
    JOIN users u ON s.student_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$assignment_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    /* --- Global --- */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      background-color: #f4f7f6;
      color: #333;
      line-height: 1.6;
    }

    /* --- Sidebar --- */
    .sidebar {
      position: fixed;
      left: 0;
      top: 0;
      width: 250px;
      height: 100%;
      background: #2c3e50;
      color: #ecf0f1;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
    }

    .sidebar h2 {
      padding: 20px;
      text-align: center;
      margin: 0;
      background: #34495e;
      letter-spacing: 1px;
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
      transition: 0.3s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #3498db;
      padding-left: 25px;
    }

    .sidebar ul li a i {
      margin-right: 10px;
      width: 20px;
      text-align: center;
    }

    /* --- Main Content --- */
    .main {
      margin-left: 250px;
      padding: 30px;
    }

    .container {
      background: #fff;
      padding: 25px 30px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    h2 {
      color: #2c3e50;
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    /* --- Info Box --- */
    .info {
      background: #eaf2f8;
      border-left: 4px solid #3498db;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }

    .msg-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      text-align: center;
    }

    /* --- Table --- */
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

    thead th {
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

    /* --- Status Badges --- */
    .badge {
      padding: 5px 12px;
      border-radius: 15px;
      color: #fff;
      font-size: 0.8em;
      font-weight: bold;
      text-transform: uppercase;
    }

    .badge-graded {
      background-color: #28a745;
    }

    .badge-late {
      background-color: #dc3545;
    }

    .badge-submitted {
      background-color: #6c757d;
    }

    /* --- Buttons --- */
    .btn {
      background-color: #3498db;
      color: white;
      padding: 8px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      transition: background-color 0.3s;
    }

    .btn:hover {
      background-color: #2980b9;
    }

    /* --- Grading Form --- */
    .grade-form {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .grade-form label {
      font-weight: bold;
      font-size: 0.9em;
    }

    .grade-form input[type="text"],
    .grade-form textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-family: inherit;
    }

    .grade-form textarea {
      resize: vertical;
      min-height: 60px;
    }

    /* --- Responsive --- */
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
        margin-bottom: 10px;
        background: #fff;
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 10px;
      }

      td {
        border: none;
        display: flex;
        justify-content: space-between;
        padding: 8px;
      }

      td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #2c3e50;
      }
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
      <li><a href="teacher_submissions.php" class="active"><i class="fa fa-folder-open"></i><span> Student Submissions</span></a></li>
      <li><a href="teacher_reports.php"><i class="fa fa-file-alt"></i><span> Reports</span></a></li>
      <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
      <li><a href="teacher_profile.php"><i class="fa fa-user-cog"></i><span> Profile</span></a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
    </ul>
  </div>

  <!-- Main Content -->
  <div class="main">
    <div class="container">
      <h2>Submissions for: <?= htmlspecialchars($assignment['title']) ?></h2>

      <div class="info">
        <p><strong>Due Date:</strong> <?= htmlspecialchars($assignment['due_date']) ?></p>
        <?php if (!empty($assignment['file_path'])): ?>
          <p><strong>Assignment File:</strong>
            <a href="<?= htmlspecialchars($assignment['file_path']) ?>" target="_blank">Download</a>
          </p>
        <?php endif; ?>
      </div>

      <?php if (!empty($success)): ?>
        <div class="msg-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <?php if (count($submissions) > 0): ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Student</th>
              <th>Email</th>
              <th>File</th>
              <th>Submitted At</th>
              <th>Status</th>
              <th>Grade / Feedback</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($submissions as $index => $sub): ?>
              <?php
              $submitted_time = strtotime($sub['submitted_at']);
              $due_time = strtotime($assignment['due_date']);
              $is_late = $submitted_time > $due_time;
              ?>
              <tr>
                <td data-label="#"> <?= $index + 1 ?> </td>
                <td data-label="Student"> <?= htmlspecialchars($sub['student_name']) ?> </td>
                <td data-label="Email"> <?= htmlspecialchars($sub['student_email']) ?> </td>
                <td data-label="File">
                  <a href="../<?= htmlspecialchars($sub['file']) ?>" class="btn" target="_blank">View / Download</a>
                </td>
                <td data-label="Submitted At"> <?= htmlspecialchars($sub['submitted_at']) ?> </td>
                <td data-label="Status">
                  <span class="badge <?= $sub['status'] === 'Graded' ? 'badge-graded' : ($is_late ? 'badge-late' : 'badge-submitted') ?>">
                    <?= htmlspecialchars($sub['status'] ?? 'Submitted') ?>
                  </span>
                </td>
                <td data-label="Grade / Feedback">
                  <form method="POST" class="grade-form">
                    <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                    <label>Grade:</label>
                    <input type="text" name="score" value="<?= htmlspecialchars($sub['score'] ?? '') ?>" placeholder="e.g. 85%">
                    <label>Feedback:</label>
                    <textarea name="feedback" placeholder="Write feedback..."><?= htmlspecialchars($sub['feedback'] ?? '') ?></textarea>
                    <button type="submit" class="btn">Save</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="info">No submissions have been made for this assignment yet.</div>
      <?php endif; ?>
    </div>
  </div>

</body>

</html>