<?php
// public/student_reports.php
// Student report export page (PDF/CSV)

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_role('student');

$user = current_user();
$studentId = $user['id'];

// Fetch performance data
$stmt = $pdo->prepare("
    SELECT a.title, a.due_date, s.submitted_at, s.score, s.feedback
    FROM assignments a
    LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
    ORDER BY a.due_date ASC
");
$stmt->execute([$studentId]);
$rows = $stmt->fetchAll();

// ======================== HANDLE DOWNLOAD ==========================
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
  require_once __DIR__ . '/../tcpdf/tcpdf.php';

  $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
  $pdf->SetCreator('TASKNEST');
  $pdf->SetAuthor('TASKNEST System');
  $pdf->SetTitle('Student Performance Report');
  $pdf->SetHeaderData('', 0, 'TASKNEST - Student Report', 'Generated on: ' . date('Y-m-d'));
  $pdf->setHeaderFont(['helvetica', '', 10]);
  $pdf->setFooterFont(['helvetica', '', 8]);
  $pdf->SetMargins(15, 27, 15);
  $pdf->AddPage();

  // Build the report HTML
  $html = '
    <h2 style="text-align:center;">Student Performance Report</h2>
    <p><b>Student Name:</b> ' . htmlspecialchars($user['name']) . '</p>
    <table border="1" cellpadding="5">
      <thead>
        <tr style="background-color:#f2f2f2;">
          <th><b>Assignment</b></th>
          <th><b>Due Date</b></th>
          <th><b>Submitted</b></th>
          <th><b>Score</b></th>
          <th><b>Feedback</b></th>
        </tr>
      </thead>
      <tbody>';

  if ($rows) {
    foreach ($rows as $r) {
      $html .= '<tr>
                        <td>' . htmlspecialchars($r['title']) . '</td>
                        <td>' . htmlspecialchars($r['due_date']) . '</td>
                        <td>' . htmlspecialchars($r['submitted_at'] ?? 'Not Submitted') . '</td>
                        <td>' . htmlspecialchars($r['score'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($r['feedback'] ?? 'No Feedback') . '</td>
                      </tr>';
    }
  } else {
    $html .= '<tr><td colspan="5" align="center">No records found</td></tr>';
  }

  $html .= '</tbody></table>';

  // Write HTML and output PDF
  $pdf->writeHTML($html, true, false, true, false, '');
  $pdf->Output('student_report.pdf', 'I');
  exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Reports - TASKNEST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      margin: 0;
      font-family: "Segoe UI", Tahoma, sans-serif;
      background: #f4f6f9;
      display: flex;
    }

    /* Sidebar */
    .sidebar {
      width: 250px;
      background: #1e2a38;
      color: #fff;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
    }

    .sidebar h2 {
      text-align: center;
      padding: 1.2rem;
      font-size: 1.4rem;
      color: #f1c40f;
      border-bottom: 1px solid #34495e;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar ul li {
      border-bottom: 1px solid #2c3e50;
    }

    .sidebar ul li a {
      display: flex;
      align-items: center;
      padding: 0.9rem 1.2rem;
      color: #ecf0f1;
      text-decoration: none;
      font-size: 15px;
      transition: all 0.3s;
    }

    .sidebar ul li a:hover,
    .sidebar ul li a.active {
      background: #34495e;
      padding-left: 1.6rem;
    }

    .sidebar ul li a i {
      margin-right: 12px;
      font-size: 1.1rem;
    }

    /* Main */
    .main {
      margin-left: 250px;
      padding: 20px;
      flex: 1;
    }

    .main h1 {
      margin-top: 0;
    }

    /* Reports */
    .report-actions {
      margin-bottom: 1.5rem;
    }

    .report-actions .btn {
      display: inline-block;
      padding: 0.7rem 1.2rem;
      margin-right: 10px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: 0.3s;
    }

    .report-actions .btn-danger {
      background: #e74c3c;
      color: #fff;
    }

    .report-actions .btn-danger:hover {
      background: #c0392b;
    }

    .report-actions .btn-success {
      background: #27ae60;
      color: #fff;
    }

    .report-actions .btn-success:hover {
      background: #1e8449;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      overflow: hidden;
    }

    .table th,
    .table td {
      padding: 0.8rem 1rem;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .table th {
      background: #2980b9;
      color: #fff;
      font-weight: bold;
    }

    .table tr:hover {
      background: #f9f9f9;
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <h2><i class="fa-solid fa-user-graduate"></i> TASKNEST</h2>
    <ul>
      <li><a href="student_dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
      <li><a href="student_assignments.php"><i class="fa fa-book-open"></i> My Assignments</a></li>
      <li><a href="student_submissions.php"><i class="fa fa-paper-plane"></i> Submissions</a></li>
      <li><a href="student_grades.php"><i class="fa fa-graduation-cap"></i> Grades & Feedback</a></li>
      <li><a href="student_reports.php" class="active"><i class="fa fa-file-alt"></i> Reports</a></li>
      <li><a href="student_profile.php"><i class="fa fa-user"></i> Profile</a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </div>

  <!-- Main -->
  <div class="main">
    <h1><i class="fa-solid fa-chart-line"></i> My Reports</h1>
    <p>Download your full performance report as <b>PDF</b>.</p>

    <div class="report-actions">
      <a href="?download=pdf" class="btn btn-danger"><i class="fa-solid fa-file-pdf"></i> Download PDF</a>
    </div>

    <h2>Recent Performance</h2>
    <table class="table">
      <thead>
        <tr>
          <th>Assignment</th>
          <th>Due Date</th>
          <th>Submitted</th>
          <th>Grade</th>
          <th>Feedback</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['title']); ?></td>
            <td><?= htmlspecialchars($r['due_date']); ?></td>
            <td><?= htmlspecialchars($r['submitted_at'] ?? 'Not Submitted'); ?></td>
            <td><?= htmlspecialchars($r['score'] ?? 'N/A'); ?></td>
            <td><?= htmlspecialchars($r['feedback'] ?? 'No Feedback'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>

</html>