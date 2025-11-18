<?php
require_once __DIR__ . '/src/auth.php';
require_once __DIR__ . '/src/db.php';

require_role('student'); 
$user = current_user();


// Fetch student submissions
$stmt = $pdo->prepare("
    SELECT a.title, a.due_date, s.submitted_at, s.score, s.feedback
    FROM assignments a
    LEFT JOIN submissions s 
        ON a.id = s.assignment_id AND s.student_id = ?
    ORDER BY a.due_date DESC
");
$stmt->execute([$user['id']]);
$rows = $stmt->fetchAll();

// Build HTML for PDF
$html = "
<h2 style='text-align:center;'>Student Report</h2>
<p><strong>Name:</strong> " . htmlspecialchars($user['name']) . "</p>
<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>
<table border='1' cellspacing='0' cellpadding='5' width='100%'>
<tr>
  <th>Assignment</th>
  <th>Submitted At</th>
  <th>Score</th>
  <th>Feedback</th>
  <th>Status</th>
</tr>";

foreach ($rows as $r) {
    // Determine status
    $now = new DateTime();
    $due = new DateTime($r['due_date']);
    if ($r['submitted_at']) {
        $status = "Submitted";
    } elseif ($now > $due) {
        $status = "Missed Deadline";
    } else {
        $status = "Open";
    }

    $html .= "<tr>
        <td>" . htmlspecialchars($r['title']) . "</td>
        <td>" . ($r['submitted_at'] ?? 'Not Submitted') . "</td>
        <td>" . ($r['score'] ?? '-') . "</td>
        <td>" . ($r['feedback'] ?? '-') . "</td>
        <td>" . $status . "</td>
    </tr>";
}


$html .= "</table>";

