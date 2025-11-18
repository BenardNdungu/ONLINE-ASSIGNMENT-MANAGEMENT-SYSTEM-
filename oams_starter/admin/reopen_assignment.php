<?php
require_once __DIR__ . '/../src/auth.php';
require_login();
$user = current_user();

// Allow only teachers or admins
if (!in_array($user['role'], ['teacher', 'admin'])) {
    http_response_code(403);
    exit('Access Denied');
}

require_once __DIR__ . '/../src/db.php';

// Get assignment ID from query string
$assignmentId = $_GET['id'] ?? null;
if (!$assignmentId) {
    exit('Invalid request: Missing assignment ID.');
}

// Fetch assignment details
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
$stmt->execute([$assignmentId]);
$assignment = $stmt->fetch();

if (!$assignment) {
    exit('Assignment not found.');
}

// If form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);
    $reopened_by = $user['id'];
    $date = date('Y-m-d H:i:s');

    if (empty($reason)) {
        $error = "Please provide a reason for reopening this assignment.";
    } else {
        // Update status in database
        $stmt = $pdo->prepare("
            UPDATE assignments 
            SET status = 'reopened', reopened_at = ?, reopened_by = ?, reopen_reason = ? 
            WHERE id = ?
        ");
        $stmt->execute([$date, $reopened_by, $reason, $assignmentId]);

        flash('success', 'Assignment has been reopened successfully.');
        header('Location: assignment.php');
        exit;
    }
}

?>

<main class="dashboard">
  <h2><i class="fa-solid fa-folder-open"></i> Reopen Assignment</h2>

  <?php if ($msg = flash('success')): ?>
    <div class="alert success"><?= htmlspecialchars($msg) ?></div>
  <?php elseif (!empty($error)): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="form-card">
    <h3><i class="fa fa-book"></i> Assignment Details</h3>
    <p><strong>Title:</strong> <?= htmlspecialchars($assignment['title']) ?></p>
    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
    <p><strong>Due Date:</strong> <?= date('M d, Y', strtotime($assignment['due_date'])) ?></p>
  </div>

  <div class="form-card">
    <h3><i class="fa fa-undo"></i> Reopen Form</h3>
    <form method="post">
      <label>Reason for Reopening</label><br>
      <textarea name="reason" rows="5" placeholder="Explain why you're reopening this assignment..." required></textarea><br>
      <button type="submit" class="btn-primary"><i class="fa fa-undo"></i> Confirm Reopen</button>
      <a href="assignments.php" class="btn-cancel">Cancel</a>
    </form>
  </div>
</main>

<style>
.dashboard { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
h2 { color: #004080; margin-bottom: 1rem; }
.form-card { background: #f9f9f9; padding: 1.5rem; margin-top: 1rem; border-radius: 8px; }
textarea { width: 100%; border-radius: 6px; border: 1px solid #ccc; padding: 0.75rem; resize: none; }
.btn-primary { background: #004080; color: #fff; padding: 0.7rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; }
.btn-primary:hover { background: #0059b3; }
.btn-cancel { background: #ccc; color: #000; padding: 0.7rem 1.5rem; border-radius: 6px; text-decoration: none; margin-left: 1rem; }
.alert { padding: 0.75rem; margin-bottom: 1rem; border-radius: 6px; }
.alert.success { background: #d4edda; color: #155724; }
.alert.error { background: #f8d7da; color: #721c24; }
</style>
<footer>
    <p>&copy; <?= date('Y') ?> TASKNEST. All rights reserved.</p>
</footer>
</body>
</html>
