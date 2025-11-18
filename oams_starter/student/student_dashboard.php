<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';

// Only students may access
require_role('student');
$user = current_user();
$role = $user['role'];  // define the variable for later use



function e($v)
{
  return htmlspecialchars($v ?? '');
}
$stmt = $pdo->prepare("
  SELECT COUNT(DISTINCT a.id) AS cnt
  FROM assignments a
  LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
  WHERE a.due_date >= NOW()
");
$stmt->execute([$user['id']]);
$assignmentsDue = (int) ($stmt->fetchColumn() ?: 0);

// Submitted: number of submissions by this student (any submitted row)
$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM submissions
  WHERE student_id = ? AND submitted_at IS NOT NULL
");
$stmt->execute([$user['id']]);
$submitted = (int) ($stmt->fetchColumn() ?: 0);

// Graded: submissions for student with status = 'score ' OR grade IS NOT NULL
$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM submissions
  WHERE student_id = ? AND (status = 'score' OR score IS NOT NULL)
");
$stmt->execute([$user['id']]);
$graded = (int) ($stmt->fetchColumn() ?: 0);

// Missed: assignments past due where student has no submission (or submission is null)
$stmt = $pdo->prepare("
  SELECT COUNT(DISTINCT a.id) FROM assignments a
  LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
  WHERE a.due_date < NOW() AND (s.id IS NULL OR (s.submitted_at IS NULL AND (s.status IS NULL OR s.status != 'submitted')))
");
$stmt->execute([$user['id']]);
$missed = (int) ($stmt->fetchColumn() ?: 0);

// ----------------------
// Upcoming assignments list (due in future) with submission status
// ----------------------
$stmt = $pdo->prepare("
  SELECT a.id, a.title, a.description, a.due_date, s.id AS submission_id, s.status AS sub_status, s.submitted_at, s.score
  FROM assignments a
  LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
  WHERE a.due_date >= NOW()
  ORDER BY a.due_date ASC
  LIMIT 20
");
$stmt->execute([$user['id']]);
$upcoming = $stmt->fetchAll();

// ----------------------
// Recent submissions (last 10)
// ----------------------
$stmt = $pdo->prepare("
  SELECT s.id, s.submitted_at, s.status, s.score, a.title AS assignment_title
  FROM submissions s
  LEFT JOIN assignments a ON a.id = s.assignment_id
  WHERE s.student_id = ?
  ORDER BY s.submitted_at DESC
  LIMIT 10
");
$stmt->execute([$user['id']]);
$recentSubs = $stmt->fetchAll();

// ----------------------
// ======================= FETCH ANNOUNCEMENTS ========================= //
// Fetch active announcements for students
$stmt = $pdo->prepare("
  SELECT title, content, sender_role, created_at
  FROM announcements
  WHERE (target_user = 'student' OR target_user = 'all')
    AND (expiry_date IS NULL OR expiry_date > NOW())
  ORDER BY created_at DESC
");
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title>Student Dashboard — TASKNEST</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* ---------- Layout / Reset ---------- */
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: "Inter", "Segoe UI", Tahoma, sans-serif;
      background: #f8f9fa;
      /* Lighter background */
      color: #212529;
      display: flex;
      min-height: 100vh;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    /* Custom Color Palette */
    :root {
      --sidebar-bg: #2c3e50;
      /* Dark Slate Blue */
      --brand-color: #3498db;
      /* Peter River Blue */
      --accent-color: #f39c12;
      /* Orange */
      --text-light: #ecf0f1;
      --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.08);
      --shadow-hover: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    /* ---------- Sidebar ---------- */
    .sidebar {
      width: 250px;
      background: var(--sidebar-bg);
      color: var(--text-light);
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      padding: 20px 0;
      overflow-y: auto;
      transition: all 0.3s;
    }

    .brand {
      text-align: center;
      font-weight: 700;
      font-size: 1.5rem;
      margin-bottom: 30px;
      color: #f39c12;
      padding: 0 20px;
    }

    .nav {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .nav li {
      margin-bottom: 0;
    }

    .nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 20px;
      color: #cfe3f0;
      transition: background 0.2s, color 0.2s;
    }

    .nav a:hover {
      background: #34495e;
      color: var(--text-light);
    }

    .nav .active {
      background: var(--brand-color);
      color: #fff;
      font-weight: 600;
      border-right: 4px solid var(--accent-color);
    }

    .nav i {
      width: 20px;
      text-align: center;
    }

    /* ---------- Main area ---------- */
    .main {
      margin-left: 250px;
      padding: 30px;
      flex: 1;
      transition: margin-left 0.3s;
    }

    h1 {
      margin: 0 0 20px 0;
      color: #2c3e50;
      font-size: 1.8rem;
    }

    /* Top row */
    .top-row {
      display: flex;
      gap: 20px;
      align-items: stretch;
      justify-content: space-between;
      flex-wrap: wrap;
      margin-bottom: 25px;
    }

    .profile-card {
      display: flex;
      gap: 20px;
      align-items: center;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      min-width: 320px;
      flex-grow: 1;
      transition: box-shadow 0.2s;
    }

    .profile-card:hover {
      box-shadow: var(--shadow-hover);
    }

    .avatar {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--brand-color);
      padding: 2px;
    }

    .profile-info h2 {
      margin: 0;
      font-size: 1.4rem;
      color: #212529;
    }

    .profile-info p {
      margin: 3px 0;
      color: #6c757d;
      font-size: 14px;
    }

    /* Stats cards */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
      gap: 16px;
      margin-top: 0;
    }

    .card {
      background: #fff;
      padding: 15px;
      border-radius: 10px;
      box-shadow: var(--shadow-light);
      display: flex;
      align-items: center;
      justify-content: space-between;
      transition: transform 0.2s, box-shadow 0.2s;
      border: 1px solid #eef1f4;
    }

    .card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-hover);
    }

    .card .meta {
      font-size: 13px;
      color: #6c757d;
      font-weight: 500;
    }

    .card .value {
      font-size: 24px;
      font-weight: 700;
      color: #212529;
      margin-top: 4px;
    }

    .card i {
      font-size: 26px;
    }

    /* Icon colors for stats */
    .fa-calendar-days {
      color: var(--brand-color);
    }

    .fa-paper-plane {
      color: var(--accent-color);
    }

    .fa-check-circle {
      color: #2ecc71;
      /* Emerald Green */
    }

    .fa-times-circle {
      color: #e74c3c;
      /* Alizarin Red */
    }


    /* Upcoming assignments table */
    .section {
      margin-top: 25px;
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: var(--shadow-light);
      border: 1px solid #eef1f4;
    }

    .section h3 {
      margin: 0 0 15px 0;
      color: var(--sidebar-bg);
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.25rem;
      border-bottom: 2px solid #f1f5f9;
      padding-bottom: 10px;
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      font-size: 14px;
    }

    thead th {
      text-align: left;
      padding: 12px 10px;
      background: #f8f9fa;
      color: #2c3e50;
      font-weight: 700;
      border-bottom: 2px solid #e6eef3;
    }

    tbody td {
      padding: 12px 10px;
      border-bottom: 1px solid #f1f5f9;
      color: #495057;
      vertical-align: top;
    }

    /* Last row has no bottom border */
    tbody tr:last-child td {
      border-bottom: none;
    }

    .small {
      font-size: 13px;
      color: #6c757d;
    }

    /* Status badges */
    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 12px;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .due {
      background: var(--brand-color);
    }

    .submitted {
      background: var(--accent-color);
    }

    .graded {
      background: #2ecc71;
    }

    .missed {
      background: #e74c3c;
    }

    /* Actions */
    .actions a {
      display: inline-block;
      margin-right: 8px;
      padding: 7px 12px;
      border-radius: 50px;
      background: #eef2f7;
      color: #2c3e50;
      font-size: 13px;
      font-weight: 600;
      transition: background 0.2s;
    }

    .actions a:hover {
      background: #d0d7e0;
    }

    .actions a.primary {
      background: var(--brand-color);
      color: #fff;
    }

    .actions a.primary:hover {
      background: #2980b9;
    }

    /* Bottom Grid Layout */
    .bottom-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      /* 2 parts wide for table, 1 part narrow for sidebar content */
      gap: 20px;
      margin-top: 20px;
    }

    /* Notification Styling */
    .notification-card {
      background: #fdfdff;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 10px;
      border-left: 5px solid;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
      transition: all 0.2s;
    }

    .notification-card:hover {
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      transform: translateY(-1px);
    }

    .notification-card.admin {
      border-left-color: #e74c3c;
      /* High importance/System */
    }

    .notification-card.teacher {
      border-left-color: var(--accent-color);
      /* Feedback/Teacher */
    }

    .notification-card.empty {
      border-left: 5px solid #bdc3c7;
      text-align: center;
      color: #94a3b8;
      padding: 20px;
      font-style: italic;
    }

    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 5px;
      font-weight: 600;
      color: var(--sidebar-bg);
    }

    .notification-type {
      font-size: 11px;
      padding: 3px 8px;
      border-radius: 50px;
      font-weight: 700;
      color: #fff;
      background: #7f8c8d;
    }

    .notification-card.admin .notification-type {
      background: #e74c3c;
    }

    .notification-card.teacher .notification-type {
      background: var(--accent-color);
    }

    .notification-card p {
      margin: 5px 0;
      font-size: 14px;
      color: #495057;
    }

    .notification-card small {
      color: #94a3b8;
      font-size: 12px;
      display: block;
      text-align: right;
      margin-top: 5px;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
      .bottom-grid {
        grid-template-columns: 1fr;
      }

      /* Stack vertically */
    }

    @media (max-width: 768px) {

      /* Sidebar collapses to the top */
      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
        padding: 0;
        display: flex;
        flex-direction: column;
      }

      .brand {
        padding: 15px;
        margin-bottom: 0;
        background: #34495e;
      }

      .nav {
        display: flex;
        overflow-x: auto;
        white-space: nowrap;
        padding: 8px 0;
        background: var(--sidebar-bg);
      }

      .nav li {
        flex-shrink: 0;
      }

      .nav a {
        padding: 8px 15px;
        border-radius: 0;
      }

      .nav .active {
        border-right: none;
        border-bottom: 3px solid var(--accent-color);
      }

      .main {
        margin-left: 0;
        padding: 15px;
      }

      .top-row {
        flex-direction: column;
        gap: 15px;
      }

      .profile-card {
        min-width: 100%;
      }
    }

    @media (max-width: 576px) {
      .cards {
        grid-template-columns: 1fr 1fr;
      }

      /* 2 columns on small mobile */
      table,
      thead,
      tbody,
      th,
      td,
      tr {
        display: block;
      }

      thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }

      /* Hide table header */
      tbody td {
        border: none;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
        padding-left: 50%;
        text-align: right;
      }

      tbody td:before {
        content: attr(data-label);
        position: absolute;
        left: 6px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: 600;
        color: #495057;
      }

      /* Ensure actions are full width on mobile table rows */
      .actions {
        display: flex;
        flex-direction: column;
        gap: 5px;
      }

      .actions a {
        margin-right: 0;
        width: 100%;
        text-align: center;
      }
    }

    /* small helper */
    .muted {
      color: #94a3b8;
      font-size: 13px;
    }
  </style>
</head>

<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand"><i class="fa-solid fa-user-graduate"></i> TASKNEST</div>

    <ul class="nav">
      <li><a class="active" href="student_dashboard.php"><i class="fa fa-tachometer-alt"></i><span> Dashboard</span></a></li>
      <li><a href="student_assignments.php"><i class="fa fa-book-open"></i><span> My Assignments</span></a></li>
      <li><a href="student_submissions.php"><i class="fa fa-paper-plane"></i><span> Submissions</span></a></li>
      <li><a href="student_grades.php"><i class="fa fa-award"></i><span> Grades & Feedback</span></a></li>
      <li><a href="student_reports.php"><i class="fa fa-file-alt"></i><span> Reports</span></a></li>
      <li><a href="student_profile.php"><i class="fa fa-user"></i><span> Profile</span></a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
    </ul>
  </aside>

  <!-- Main content -->
  <main class="main">
    <h1><i class="fa fa-tachometer-alt"></i> Student Dashboard</h1>
    <!-- Top: profile card + stats -->
    <div class="top-row">
      <!-- Profile card -->
      <div class="profile-card">
        <?php
        // Show profile picture if set, else placeholder
        $avatar = isset($user['profile_pic']) && $user['profile_pic'] ? e($user['profile_pic']) : 'https://placehold.co/90x90/3498db/ffffff?text=U';
        ?>
        <img src="<?= $avatar ?>" alt="avatar" class="avatar" />
        <div class="profile-info">
          <h2>Welcome Back, <?= e($user['name']) ?>!</h2>
          <p class="muted"><i class="fa fa-envelope"></i> <?= e($user['email']) ?></p>
          <p class="muted"><i class="fa fa-graduation-cap"></i> Role: Student</p>
          <p class="muted"><i class="fa fa-building"></i> Department: <?= e($user['department'] ?? 'N/A') ?></p>
        </div>
      </div>

      <!-- Quick stats container -->
      <div style="flex:1;">
        <div class="cards">
          <div class="card">
            <div>
              <div class="meta">Assignments Due</div>
              <div class="value"><?= $assignmentsDue ?></div>
            </div>
            <div><i class="fa fa-calendar-days"></i></div>
          </div>

          <div class="card">
            <div>
              <div class="meta">Total Submitted</div>
              <div class="value"><?= $submitted ?></div>
            </div>
            <div><i class="fa fa-paper-plane"></i></div>
          </div>

          <div class="card">
            <div>
              <div class="meta">Assignments Graded</div>
              <div class="value"><?= $graded ?></div>
            </div>
            <div><i class="fa fa-check-circle"></i></div>
          </div>

          <div class="card">
            <div>
              <div class="meta">Assignments Missed</div>
              <div class="value"><?= $missed ?></div>
            </div>
            <div><i class="fa fa-times-circle"></i></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming Assignments section -->
    <div class="section" aria-labelledby="upcoming-title">
      <h3 id="upcoming-title"><i class="fa fa-hourglass-start"></i> Upcoming Assignments</h3>

      <?php if (empty($upcoming)): ?>
        <div class="card empty-state">
          <p class="muted">No upcoming assignments — enjoy some free time! 🎉</p>
        </div>
      <?php else: ?>
        <div style="overflow-x: auto;">
          <table role="table" aria-describedby="upcoming-title">
            <thead>
              <tr>
                <th>Title</th>
                <th class="small">Due Date</th>
                <th class="small">Status</th>
                <th class="small">Grade</th>
                <th style="width:200px">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($upcoming as $row):
                // Determine status badge class
                $status = $row['sub_status'] ?? null;
                if ($status === 'graded' || $row['score'] !== null) $badge = 'graded';
                elseif ($status === 'submitted' || $status === 'pending') $badge = 'submitted';
                else $badge = 'due';
              ?>
                <tr>
                  <td data-label="Title"><strong><?= e($row['title']) ?></strong>
                    <div class="small muted"><?= e(substr($row['description'], 0, 120)) ?><?= strlen($row['description']) > 120 ? '...' : '' ?></div>
                  </td>
                  <td data-label="Due Date" class="small"><?= e(date("M d, Y H:i", strtotime($row['due_date']))) ?></td>
                  <td data-label="Status" class="small">
                    <span class="badge <?= $badge ?>"><?= e(ucfirst($status ?? 'Due')) ?></span>
                  </td>
                  <td data-label="Grade" class="small"><?= $row['score'] !== null ? e($row['score']) : '-' ?></td>
                  <td data-label="Action" class="small actions">
                    <a href="student_assignments.php?assignment_id=<?= (int)$row['id'] ?>"><i class="fa fa-eye"></i> View</a>
                    <?php if ($status !== 'submitted' && $status !== 'graded'): ?>
                      <a class="primary" href="student_submit.php?assignment_id=<?= (int)$row['id'] ?>"><i class="fa fa-upload"></i> Submit</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent submissions / quick actions / notifications -->
    <div class="bottom-grid">
      <!-- Recent submissions list -->
      <div class="section">
        <h3><i class="fa fa-history"></i> Recent Submissions</h3>
        <?php if (empty($recentSubs)): ?>
          <p class="muted" style="text-align: center; padding: 10px;">You haven't submitted anything yet. Start now!</p>
        <?php else: ?>
          <div style="overflow-x: auto;">
            <table>
              <thead>
                <tr>
                  <th>Assignment</th>
                  <th>Submitted At</th>
                  <th>Status</th>
                  <th>Grade</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentSubs as $s): ?>
                  <tr>
                    <td data-label="Assignment"><?= e($s['assignment_title']) ?></td>
                    <td data-label="Submitted At" class="small"><?= $s['submitted_at'] ? e(date("M d, Y H:i", strtotime($s['submitted_at']))) : 'N/A' ?></td>
                    <td data-label="Status" class="small">
                      <span class="badge <?= $s['status'] === 'graded' ? 'graded' : ($s['status'] === 'submitted' ? 'submitted' : 'due') ?>">
                        <?= e(ucfirst($s['status'] ?? 'N/A')) ?>
                      </span>
                    </td>
                    <td data-label="Grade" class="small"><?= $s['score'] !== null ? e($s['score']) : '-' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quick report / profile / Notifications -->
      <div>
        <!-- Reports & Profile Card -->
        <aside class="section">
          <h3><i class="fa fa-chart-bar"></i> Performance & Reports</h3>
          <p class="small muted">Download your overall performance report for review.</p>
          <p>
            <a class="actions" href="export_student_report.php?format=pdf">
              <span class="actions">
                <a class="primary" href="export_student_report.php?format=pdf"><i class="fa fa-download"></i> Download Full Report</a>
              </span>
            </a>
          </p>

          <hr style="margin:15px 0;border:none;border-top:1px solid #eef2f7" />

          <h4 style="margin:8px 0 6px 0; color: #2c3e50;"><i class="fa fa-user-edit"></i> Profile Settings</h4>
          <p class="small muted">Manage your personal details and change your profile photo.</p>
          <a class="actions" href="student_profile.php" style="width: 100%;"><a href="student_profile.php">Go to Profile</a></a>
        </aside>

        <!-- Notifications -->
        <aside class="section" style="margin-top: 20px;">
          <h3><i class="fa-solid fa-bell"></i> Notifications</h3>

          <?php if (empty($notifications)): ?>
            <div class="notification-card empty">No recent notifications.</div>
          <?php else: ?>
            <?php foreach ($notifications as $n): ?>
              <div class="notification-card <?= htmlspecialchars($n['sender_role']) ?>">
                <div class="notification-header">
                  <strong><?= htmlspecialchars($n['title']) ?></strong>
                  <span class="notification-type"><?= ucfirst($n['sender_role']) ?></span>
                </div>
                <p><?= htmlspecialchars($n['content']) ?></p>
                <small><?= date('M d, Y H:i', strtotime($n['created_at'])) ?></small>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </aside>
      </div>

    </div>
  </main>
</body>

</html>