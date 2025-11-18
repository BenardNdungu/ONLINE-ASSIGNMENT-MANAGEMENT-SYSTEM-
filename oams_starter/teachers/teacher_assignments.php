<?php
// public/teacher_assignments.php
// Teacher dashboard listing assignments with clear visuals and maintainable code.

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/csrf.php';

require_role('teacher');
$user = current_user();

// Fetch this teacher's assignments
$stmt = $pdo->prepare(
    'SELECT * FROM assignments WHERE teacher_id = ? ORDER BY due_date DESC'
);
$stmt->execute([$user['id']]);
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher - My Assignments | TASKNEST</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* General Body Styles */
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
    transition: color 0.3s;
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
.container {
    margin-left: 250px;
    padding: 30px;
    flex: 1;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eee;
}

.page-header h1 {
    font-size: 26px;
    color: #2c3e50;
    margin: 0;
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    font-size: 22px;
    color: #2c3e50;
}

/* --- Buttons --- */
.btn {
    padding: 10px 18px;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    border: none;
    transition: background-color 0.3s, transform 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    text-decoration: none;
}
.btn:hover {
    transform: translateY(-2px);
}
.btn-primary {
    background: #28a745; /* Consistent Green for Create/Success */
    color: #fff;
}
.btn-primary:hover {
    background: #218838;
}
.btn-secondary {
    background: #3498db; /* Consistent Blue for View/Info */
    color: #fff;
}
.btn-secondary:hover {
    background: #2980b9;
}

/* --- Cards --- */
.card {
    background: #fff;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-left: 5px solid transparent;
    transition: box-shadow 0.3s;
}
.card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
}

.assignment-card {
    border-left-color: #3498db; /* Use secondary blue for emphasis */
}

.assignment-title {
    font-size: 20px;
    margin-bottom: 10px;
    color: #2c3e50;
    font-weight: 600;
}
.assignment-title i {
    margin-right: 8px;
    color: #3498db;
}

.assignment-meta {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 15px;
}
.assignment-meta i {
    margin-right: 5px;
}
.assignment-desc {
    margin-bottom: 15px;
    color: #555;
}

.attachment {
    margin-top: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
    border: 1px solid #e9ecef;
    font-size: 14px;
}
.attachment a {
    font-weight: 600;
}
.attachment i {
    margin-right: 8px;
}

/* --- Empty State --- */
.empty-state {
    text-align: center;
    color: #888;
    padding: 40px;
    border: 2px dashed #ddd;
}
.empty-state i {
    font-size: 40px;
    margin-bottom: 15px;
    color: #ccc;
}

/* --- Responsive Design --- */
@media (max-width: 992px) {
    .sidebar {
        width: 220px;
    }
    .container {
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
    }
    .sidebar ul {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }
    .sidebar ul li a {
        padding: 10px;
    }
    .sidebar ul li a span {
        display: none; /* Hide text on mobile nav to save space */
    }
    .sidebar ul li a i {
        margin-right: 0;
        font-size: 1.2em;
    }
    .container {
        margin-left: 0;
        padding: 20px;
    }
    .page-header, .section-title {
        flex-direction: column;
        gap: 15px;
        text-align: center;
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
    <li class="active"><a href="teacher_assignments.php"><i class="fa fa-tasks"></i><span> My Assignments</span></a></li>
    <li><a href="teacher_submissions.php"><i class="fa fa-folder-open"></i><span> Student Submissions</span></a></li>
    <li><a href="teacher_reports.php"><i class="fa fa-file-alt"></i><span> Reports</span></a></li>
    <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
    <li><a href="teacher_profile.php"><i class="fa fa-user-cog"></i><span> Profile</span></a></li>
    <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
  </ul>
</div>

<!-- Main Content -->
<main class="container teacher-dashboard">
  <!-- Page Header -->
  <header class="page-header">
    <h1>
      <i class="fa-solid fa-chalkboard-user"></i>
      Welcome, <?php echo htmlspecialchars($user['name']); ?>
    </h1>
  </header>

  <!-- Assignments Section -->
  <section class="assignments">
    <h2 class="section-title">
      <span><i class="fa-solid fa-list-check"></i> Your Assignments</span>
      <a href="teacher_create_assignment.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> Create New
      </a>
    </h2>

    <?php if (empty($assignments)): ?>
      <div class="card empty-state">
        <i class="fa-regular fa-file-circle-plus fa-2x"></i>
        <p>No assignments yet. Click <strong>Create New</strong> to add one.</p>
      </div>
    <?php else: ?>
      <?php foreach ($assignments as $a): ?>
        <article class="card assignment-card">
          <h3 class="assignment-title">
            <i class="fa-solid fa-book"></i>
            <?php echo htmlspecialchars($a['title']); ?>
          </h3>

          <div class="assignment-meta">
            <i class="fa-solid fa-calendar-days"></i>
            Due: <?php echo htmlspecialchars($a['due_date']); ?>
            &nbsp;|&nbsp;
            <i class="fa-solid fa-star"></i>
            Max Score: <?php echo (int)$a['max_score']; ?>
          </div>

          <p class="assignment-desc">
            <?php echo nl2br(htmlspecialchars($a['description'])); ?>
          </p>

          <?php if (!empty($a['file'])): ?>
            <div class="attachment">
              <i class="fa-solid fa-paperclip"></i>
              Attached File:
              <a href="assets/uploads/<?php echo htmlspecialchars($a['file']); ?>" target="_blank">
                <?php echo htmlspecialchars(basename($a['file'])); ?>
              </a>
            </div>
          <?php endif; ?>

          <a href="view_submissions.php?assignment_id=<?php echo $a['id']; ?>" class="btn btn-secondary">
            <i class="fa fa-folder-open"></i> View Submissions
          </a>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>

</body>
</html>
