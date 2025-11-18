<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';

// ✅ Ensure user is logged in as student
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user = current_user();
if ($user['role'] !== 'student') {
    http_response_code(403);
    exit("Access Denied: Only students can submit assignments.");
}

// ✅ Get assignment ID
$assignment_id = $_GET['id'] ?? $_POST['id'] ?? $_GET['assignment_id'] ?? $_POST['assignment_id'] ?? null;
$assignment_id = $assignment_id !== null ? (int)$assignment_id : null;
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

// ✅ Check if already submitted
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->execute([$assignment_id, $user['id']]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($existing)) {
        $error = "You have already submitted this assignment.";
    } elseif (strtotime($assignment['due_date']) < time()) {
        $error = "Deadline has passed. Submission not allowed.";
    } elseif (empty($_FILES['file']['name'])) {
        $error = "Please select a file to upload.";
    } else {
        $upload_dir = __DIR__ . '/../uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES['file']['name']);
        $target_path = $upload_dir . $file_name;
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed = ['pdf', 'doc', 'docx', 'txt', 'zip'];
        if (!in_array($file_ext, $allowed)) {
            $error = "Invalid file type. Allowed: PDF, DOC, DOCX, TXT, ZIP.";
        } elseif ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            $error = "File size exceeds 10MB limit.";
        } elseif (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file, submitted_at, status) 
                                   VALUES (?, ?, ?, NOW(), 'Submitted')");
            $stmt->execute([$assignment_id, $user['id'], 'uploads/submissions/' . $file_name]);

            $success = "✅ Assignment submitted successfully!";
            $stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
            $stmt->execute([$assignment_id, $user['id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Error uploading file. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f7faff, #e3eefc);
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 600px;
            margin: 60px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h2 {
            text-align: center;
            color: #2b4c7e;
            margin-bottom: 25px;
        }
        label {
            font-weight: 600;
            display: block;
            margin-top: 12px;
            color: #333;
        }
        input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .info {
            background: #eef5ff;
            border-left: 4px solid #2b4c7e;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .msg {
            margin: 10px 0;
            padding: 10px;
            border-radius: 6px;
            font-weight: 500;
        }
        .success {
            background: #d1f7d1;
            color: #1b5e20;
            border-left: 4px solid #1b5e20;
        }
        .error {
            background: #ffd4d4;
            color: #a60000;
            border-left: 4px solid #a60000;
        }
        button, .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2b4c7e;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
            margin-top: 10px;
        }
        button:hover, .btn:hover {
            background: #1f3a5f;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .status-box {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .status-box p {
            margin: 6px 0;
            color: #333;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Submit Assignment</h2>

    <div class="info">
        <p><strong>Title:</strong> <?= htmlspecialchars($assignment['title']) ?></p>
        <p><strong>Due Date:</strong> <?= htmlspecialchars($assignment['due_date']) ?></p>
        <?php if (!empty($assignment['file_path'])): ?>
            <p><strong>Assignment File:</strong>
                <a href="<?= htmlspecialchars($assignment['file_path']) ?>" target="_blank">Download</a>
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="msg success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (empty($existing)): ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($assignment_id) ?>">
            <label>Upload Your Work (PDF, DOC, DOCX, TXT, ZIP)</label>
            <input type="file" name="file" required>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                <a href="student_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                <button type="submit">Submit Assignment</button>
            </div>
        </form>
    <?php else: ?>
        <div class="status-box">
            <p><strong>Status:</strong> <?= htmlspecialchars($existing['status']) ?></p>
            <p><strong>Submitted At:</strong> <?= htmlspecialchars($existing['submitted_at']) ?></p>
            <a href="student_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
