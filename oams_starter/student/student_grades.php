<?php
// public/student_grades.php
// Student page to view grades and feedback with performance chart.

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';

require_role('student');
$user = current_user();

// Fetch graded submissions for logged-in student
$stmt = $pdo->prepare("
    SELECT s.score, s.feedback, a.title AS assignment_title, a.due_date, u.name AS teacher_name
    FROM submissions s
    JOIN assignments a ON a.id = s.assignment_id
    JOIN users u ON u.id = a.teacher_id
    WHERE s.student_id = ? AND s.score IS NOT NULL
    ORDER BY a.due_date ASC
");
$stmt->execute([$user['id']]);
$grades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>My Grades | TASKNEST</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      display: flex;
      min-height: 100vh;
      background: #f9f9f9;
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
      margin-bottom: 20px;
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

    .feedback {
      font-size: 0.9rem;
      color: #555;
    }

    #chart-container {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
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
      <h1><i class="fa-solid fa-star"></i> My Grades & Feedback</h1>
    </header>

    <!-- Chart Section -->
    <section id="chart-container">
      <h2><i class="fa-solid fa-chart-column"></i> Performance Overview</h2>
      <canvas id="gradesChart"></canvas>
    </section>

    <!-- Table Section -->
    <section>
      <h2><i class="fa-solid fa-list"></i> Graded Assignments</h2>
      <?php if (empty($grades)): ?>
        <p><i class="fa-regular fa-face-smile"></i> No graded assignments yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Assignment</th>
              <th>Teacher</th>
              <th>Due Date</th>
              <th>Grade</th>
              <th>Feedback</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($grades as $g): ?>
              <tr>
                <td data-label="Assignment"><?php echo htmlspecialchars($g['assignment_title']); ?></td>
                <td data-label="Teacher"><?php echo htmlspecialchars($g['teacher_name']); ?></td>
                <td data-label="Due Date"><?php echo htmlspecialchars($g['due_date']); ?></td>
                <td data-label="Grade"><?php echo (int)$g['score']; ?></td>
                <td data-label="Feedback" class="feedback"><?php echo $g['feedback'] ? htmlspecialchars($g['feedback']) : '—'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>

  <script>
    // Prepare data for Chart.js
    const labels = <?php echo json_encode(array_column($grades, 'assignment_title')); ?>;
    const data = <?php echo json_encode(array_column($grades, 'score')); ?>;

    const ctx = document.getElementById('gradesChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Grades (%)',
          data: data,
          backgroundColor: '#3498db',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    });
  </script>
</body>

</html>