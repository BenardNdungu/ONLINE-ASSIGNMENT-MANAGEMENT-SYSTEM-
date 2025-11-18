<?php
// teacher_create_assignment.php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/db.php';

require_login();
$user = current_user();

if (!in_array($user['role'], ['admin', 'teacher'])) {
    http_response_code(403);
    exit("Access Denied: Only Admins or Teachers can access this page.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid CSRF token.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $due = $_POST['due_date'] ?? '';
        $max_score = intval($_POST['max_score'] ?? 100);
        $filePath = null;

        if (empty($title) || empty($due)) {
            $error = 'Title and due date are required.';
        } else {
            if (!empty($_FILES['resource']['name'])) {
                $allowedMimes = [
                    'application/pdf',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png'
                ];
                $f = $_FILES['resource'];
                if ($f['error'] !== UPLOAD_ERR_OK) {
                    $error = 'File upload error.';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($f['tmp_name']);
                    if (!in_array($mime, $allowedMimes) || $f['size'] > 5*1024*1024) {
                        $error = 'Invalid file type or file too large (max 5MB).';
                    } else {
                        if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                        $newname = uniqid('res_', true) . '.' . $ext;
                        move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $newname);
                        $filePath = 'assets/uploads/' . $newname;
                    }
                }
            }

            if (!$error) {
                $stmt = $pdo->prepare(
                    'INSERT INTO assignments (teacher_id,title,description,file,due_date,max_score)
                     VALUES (?,?,?,?,?,?)'
                );
                $stmt->execute([$user['id'], $title, $desc, $filePath, $due, $max_score]);
                header('Location: teacher_assignments.php?created=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Assignment</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* === Reset & base === */
* { box-sizing: border-box; margin:0; padding:0; font-family: Arial, sans-serif; }
body { display:flex; min-height:100vh; background:#f4f6f8; }

/* === Sidebar === */
.sidebar {
    width: 250px; 
    background: #357ea8ff; 
    color: #fff; 
    display: flex; 
    flex-direction:column; 
    padding:30px;
}
.sidebar h2 { 
  font-size:1.3em; 
  margin-bottom:20px;
  display:flex;
  align-items:center;
  color: #f0ff19ff;
  padding: 10px;
}
.sidebar a {
    display:flex; 
    align-items:center; 
    padding:10px 15px; 
    color: #fff; 
    text-decoration:none; 
    border-radius:5px; 
    margin-bottom:5px;
    transition:0.2s;
}
.sidebar a i { margin-right:10px; }
.sidebar a:hover { background:#374151; }

/* === Main content === */
.main { flex:1; padding:30px; }
.header { margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center; }
.header strong { color:#111827; }
.form-section { background:#fff; padding:25px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
.form-section h2 { margin-bottom:20px; font-size:2rem; color:#111827; display:flex; align-items:center; }
.form-section h2 i { margin-right:10px; color:#2563eb; }
.card.error { background:#fee2e2; color:#b91c1c; padding:10px 15px; border-radius:5px; margin-bottom:15px; display:flex; align-items:center; }
.card.error i { margin-right:8px; }

/* === Form styling === */
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.form-grid label { display:flex; flex-direction:column; font-weight: 500; color: #141516ff; font-family: Arial, Helvetica, sans-serif;}
.form-grid input, .form-grid textarea { margin-top:15px; padding:10px; border:1px solid #d1d5db; border-radius:7px; font-family: 'Times New Roman', Times, serif;font-size: medium; }
.form-grid input[type="file"] { padding:3px; }
.form-grid button { grid-column:span 2; padding:12px; background: #2563eb; color: #fff; border: none; border-radius:15px; cursor: pointer; font-size:1rem; transition: 0.2s; }
.form-grid button:hover { background: #1e40af; }
.back-link { margin-top:15px; }
.back-link a { display:inline-block; padding:8px 12px; background:#6b7280; color:#fff; border-radius:5px; text-decoration:none; transition:0.2s; }
.back-link a:hover { background:#4b5563; }

/* === Responsive === */
@media(max-width:768px){
    body { flex-direction:column; }
    .sidebar { width:100%; flex-direction:row; overflow-x:auto; }
    .sidebar a { flex: 1; justify-content: center; margin-right:5px; }
    .form-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('due_date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;

    document.querySelector('form').addEventListener('submit', function(e) {
        if (dateInput.value < today) {
            e.preventDefault();
            alert('⚠️ Please select a due date that is today or in the future.');
        }
    });
});
</script>
<body>

<div class="sidebar">
      <h2><i class="fa-solid fa-chalkboard-user"></i>  Teacher</h2>
      <li><a href="teacher_dashboard.php"><i class="fa fa-chart-line"></i><span> Dashboard</span></a></li>
      <li><a href="teacher_assignments.php"><i class="fa fa-tasks"></i><span> My Assignments</span></a></li>
      <li><a href="teacher_submissions.php"><i class="fa fa-folder-open"></i><span> Student Submissions</span></a></li>
      <li><a href="teacher_reports.php"><i class="fa fa-file-alt"></i><span> Reports</span></a></li>
      <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
      <li><a href="teacher_profile.php"><i class="fa fa-user-cog"></i><span> Profile</span></a></li>
      <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
</div>

<div class="main">
  <div class="header">
    <div>
      <i class="fa-solid fa-chalkboard-user"></i>
      Teacher: <strong><?=htmlspecialchars($user['name']);?></strong>
    </div>
  </div>

  <section class="form-section">
    <h2><i class="fa-solid fa-plus-circle"></i> Create New Assignment</h2>

    <?php if (!empty($error)): ?>
      <div class="card error">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <?=htmlspecialchars($error);?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form-grid">
      <?=csrfInputField();?>

      <label>
        <i class="fa-solid fa-heading"></i> Subject /Unit
        <input name="title" required placeholder="Enter the subject or unit name">
      </label>

      <label>
        <i class="fa-solid fa-align-left"></i> Description
        <textarea name="description" rows="5" placeholder="Add details or instructions"></textarea>
      </label>

      <label>
        <i class="fa-solid fa-calendar-days"></i> Due Date
        <input type="datetime-local" id="due_date" name="due_date" required>
      </label>

      <label>
        <i class="fa-solid fa-star"></i> Max Score
        <input type="number" name="max_score" value="100" min="1" max="100" required>
      </label>

      <label>
        <i class="fa-solid fa-paperclip"></i> Resource (optional)
        <input type="file" name="resource" accept=".pdf,.docx,image/*">
      </label>

      <button type="submit"><i class="fa-solid fa-check"></i> Create Assignment</button>
    </form>

    <p class="back-link">
      <a href="teacher_assignments.php"><i class="fa-solid fa-arrow-left"></i> Back to Assignments</a>
    </p>
  </section>
</div>

</body>
</html>
