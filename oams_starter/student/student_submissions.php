<?php
// public/student_submissions.php
// Page for students to view all their submissions, grading status, and feedback.

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/db.php';

require_role('student');
$user = current_user();

// Fetch submissions for the logged-in student
$stmt = $pdo->prepare("
    SELECT s.*, a.title AS assignment_title, a.due_date, u.name AS teacher_name
    FROM submissions s
    JOIN assignments a ON a.id = s.assignment_id
    JOIN users u ON u.id = a.teacher_id
    WHERE s.student_id = ?
    ORDER BY a.due_date DESC
");
$stmt->execute([$user['id']]);
$submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>My Submissions | TASKNEST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      display: flex;
      min-height: 100vh;
      background: #f9f9f9;
      color: #333;
      margin: 0;
    }

    .sidebar {
      width: 240px;
      background: #2c3e50;
      color: #ecf0f1;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      padding: 20px;
    }

    .sidebar h2 {
      font-size: 1.4rem;
      margin-bottom: 20px;
      text-align: center;
      color: #f39c12;
    }

    .sidebar a {
      color: #ecf0f1;
      text-decoration: none;
      padding: 10px;
      margin: 5px 0;
      display: flex;
      align-items: center;
      border-radius: 6px;
      transition: background 0.3s;
    }

    .sidebar a i {
      margin-right: 10px;
    }

    .sidebar a:hover {
      background: #34495e;
    }

    main {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .page-header h1 {
      font-size: 1.8rem;
      color: #2c3e50;
      margin-bottom: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    table th,
    table td {
      padding: 12px 15px;
      text-align: left;
    }

    table th {
      background: #2c3e50;
      color: #fff;
      font-weight: 600;
    }

    table tr:nth-child(even) {
      background: #f4f6f8;
    }

    .badge {
      padding: 6px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      display: inline-block;
    }

    .badge.graded {
      background: #27ae60;
      color: #fff;
    }

    .badge.pending {
      background: #f39c12;
      color: #fff;
    }

    .badge.missed {
      background: #e74c3c;
      color: #fff;
    }

    .feedback {
      font-size: 0.9rem;
      color: #555;
    }

    .download-link {
      color: #3498db;
      text-decoration: none;
      font-weight: bold;
    }

    .download-link:hover {
      text-decoration: underline;
    }

    @media(max-width:768px) {
      .sidebar {
        display: none;
      }

      body {
        flex-direction: column;
      }

      table,
      table thead,
      table tbody,
      table th,
      table td,
      table tr {
        display: block;
        width: 100%;
      }

      table tr {
        margin-bottom: 15px;
      }

      table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
      }

      table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: 45%;
        text-align: left;
        font-weight: bold;
      }
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <aside class="sidebar">
    <h2><i class="fa-solid fa-user-graduate"></i> TASKNEST</h2>
    <a href="student_dashboard.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <a href="student_assignments.php"><i class="fa-solid fa-book-open"></i> My Assignments</a>
    <a href="student_submissions.php"><i class="fa-solid fa-upload"></i> Submissions</a>
    <a href="student_grades.php"><i class="fa-solid fa-star"></i> Grades & Feedback</a>
    <a href="student_reports.php"><i class="fa-solid fa-chart-line"></i> Reports</a>
    <a href="student_profile.php"><i class="fa-solid fa-user"></i> Profile</a>
    <a href="../logout.php"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
  </aside>

  <!-- Main -->
  <main>
    <header class="page-header">
      <h1><i class="fa-solid fa-upload"></i> My Submissions</h1>
    </header>

    <?php if (empty($submissions)): ?>
      <p><i class="fa-regular fa-face-smile"></i> You haven’t submitted any assignments yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Assignment</th>
            <th>Teacher</th>
            <th>Due Date</th>
            <th>Submitted File</th>
            <th>Submitted On</th>
            <th>Status</th>
            <th>Grade</th>
            <th>Feedback</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($submissions as $s): ?>
            <?php
            $due = new DateTime($s['due_date']);
            $submitted = new DateTime($s['submitted_at']);
            $status = '';
            if (!empty($s['score'])) {
              $status = '<span class="badge graded">✅ Graded</span>';
            } elseif ($submitted > $due) {
              $status = '<span class="badge missed">❌ Late</span>';
            } else {
              $status = '<span class="badge pending">⏳ Pending</span>';
            }
            ?>
            <tr>
              <td data-label="Assignment"><?php echo htmlspecialchars($s['assignment_title']); ?></td>
              <td data-label="Teacher"><?php echo htmlspecialchars($s['teacher_name']); ?></td>
              <td data-label="Due Date"><?php echo htmlspecialchars($s['due_date']); ?></td>
              <td data-label="Submitted File">
                <a href="<?php echo htmlspecialchars($s['file']); ?>" target="_blank" class="download-link"><i class="fa-solid fa-file"></i> View</a>
              </td>
              <td data-label="Submitted On"><?php echo htmlspecialchars($s['submitted_at']); ?></td>
              <td data-label="Status"><?php echo $status; ?></td>
              <td data-label="Grade"><?php echo $s['score'] ? (int)$s['score'] : '-'; ?></td>
              <td data-label="Feedback" class="feedback"><?php echo $s['feedback'] ? htmlspecialchars($s['feedback']) : '—'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
</body>

</html>