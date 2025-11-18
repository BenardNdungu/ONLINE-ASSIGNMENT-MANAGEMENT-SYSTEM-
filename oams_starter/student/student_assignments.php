<?php
// public/student_assignments.php
// Standalone Student Assignments Page with inline CSS

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/db.php';

require_role('student');
$user = current_user();

// Fetch assignments
$stmt = $pdo->query("
    SELECT a.*, u.name AS teacher_name
    FROM assignments a
    JOIN users u ON u.id = a.teacher_id
    ORDER BY a.due_date ASC
");
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Student Assignments | TASKNEST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ===== Global Reset ===== */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      display: flex;
      min-height: 100vh;
      background: #f9f9f9;
      color: #333;
    }

    /* ===== Sidebar ===== */
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

    /* ===== Main Content ===== */
    main {
      flex: 1;
      padding: 20px;
      overflow-y: auto;
    }

    .page-header {
      margin-bottom: 20px;
    }

    .page-header h1 {
      font-size: 1.8rem;
      color: #2c3e50;
    }

    .lead {
      margin-bottom: 20px;
      font-size: 1rem;
      color: #555;
    }

    /* ===== Assignment Cards ===== */
    .assignment-card {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .assignment-title {
      font-size: 1.3rem;
      margin-bottom: 10px;
      color: #34495e;
    }

    .assignment-meta {
      font-size: 0.9rem;
      color: #888;
      margin-bottom: 10px;
    }

    .assignment-desc {
      margin: 10px 0;
    }

    /* ===== Badges ===== */
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      margin-top: 5px;
    }

    .badge.overdue {
      background: #e74c3c;
      color: #fff;
    }

    .badge.today {
      background: #f39c12;
      color: #fff;
    }

    .badge.upcoming {
      background: #27ae60;
      color: #fff;
    }

    /* ===== Forms ===== */
    .submission-form {
      margin-top: 15px;
    }

    .submission-form label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .submission-form input[type="file"] {
      margin: 10px 0;
    }

    .btn {
      display: inline-block;
      padding: 10px 15px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }

    .btn-primary {
      background: #3498db;
      color: #fff;
    }

    .btn-primary:hover {
      background: #2980b9;
    }

    .download-link {
      color: #3498db;
      text-decoration: none;
      font-weight: bold;
    }

    .download-link:hover {
      text-decoration: underline;
    }

    /* ===== Responsive ===== */
    @media(max-width:768px) {
      .sidebar {
        display: none;
      }

      body {
        flex-direction: column;
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

  <!-- Main Content -->
  <main>
    <header class="page-header">
      <h1><i class="fa-solid fa-book-open"></i> Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
    </header>

    <p class="lead">Here are your available assignments. Please submit before the due dates.</p>

    <h2><i class="fa-solid fa-list-check"></i> Available Assignments</h2>

    <?php if (empty($assignments)): ?>
      <p><i class="fa-regular fa-face-smile"></i> No assignments posted yet.</p>
    <?php else: ?>
      <?php foreach ($assignments as $a): ?>
        <?php
        $due = new DateTime($a['due_date']);
        $now = new DateTime();
        $daysLeft = $now->diff($due)->format('%r%a');
        ?>
        <article class="assignment-card">
          <h3 class="assignment-title"><i class="fa-solid fa-file-lines"></i> <?php echo htmlspecialchars($a['title']); ?></h3>

          <p class="assignment-meta">
            <i class="fa-solid fa-chalkboard-user"></i> <?php echo htmlspecialchars($a['teacher_name']); ?> &nbsp;|&nbsp;
            <i class="fa-regular fa-calendar-days"></i> Due: <?php echo htmlspecialchars($a['due_date']); ?>
          </p>

          <!-- Badge -->
          <?php if ($daysLeft < 0): ?>
            <span class="badge overdue">❌ Overdue</span>
          <?php elseif ($daysLeft == 0): ?>
            <span class="badge today">⚡ Due Today</span>
          <?php else: ?>
            <span class="badge upcoming">⏳ <?php echo $daysLeft; ?> days left</span>
          <?php endif; ?>

          <p class="assignment-desc"><?php echo nl2br(htmlspecialchars($a['description'])); ?></p>

          <?php if (!empty($a['file'])): ?>
            <p>
              <a href="<?php echo htmlspecialchars('../src/assets/uploads/' . basename($a['file'])); ?>" target="_blank" class="download-link">
                <i class="fa-solid fa-download"></i> Download Resource
              </a>
            </p>
          <?php endif; ?>

          <!-- Submission Form -->
          <form method="post" action="student_submit.php" enctype="multipart/form-data" class="submission-form">
            <?php echo csrfInputField(); ?>
            <input type="hidden" name="assignment_id" value="<?php echo (int)$a['id']; ?>">

            <label for="submission-<?php echo (int)$a['id']; ?>"><i class="fa-solid fa-upload"></i> Upload Submission</label>
            <input type="file" id="submission-<?php echo (int)$a['id']; ?>" name="submission" required accept=".pdf,.docx,image/*">

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Assignment</button>

          </form>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>
</body>

</html>