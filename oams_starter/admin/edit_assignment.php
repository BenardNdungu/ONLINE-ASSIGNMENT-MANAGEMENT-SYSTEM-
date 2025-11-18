<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';

// Ensure the user is logged in and is a teacher or admin
if (!is_logged_in()) {
    header("Location: login.php");
    exit();
}

$user = current_user();
if (!in_array($user['role'], ['teacher', 'admin'])) {
    http_response_code(403);
    exit("Access Denied: Unauthorized User");
}

// ✅ Get assignment ID from URL
if (!isset($_GET['id'])) {
    exit("Invalid Request: Assignment ID is missing.");
}

$assignment_id = (int)$_GET['id'];

// ✅ Fetch existing assignment
$stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    exit("Error: Assignment not found.");
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    // File upload (optional). Use the existing DB column `file` (not file_path).
    $file_path = isset($assignment['file']) ? $assignment['file'] : null;
    $error = ""; // Initialize error message
    $success = ""; // Initialize success message

    if (!empty($_FILES['file']['name'])) {
        // ensure upload directory exists
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
                $error = "Failed to create upload directory.";
            }
        }

        // sanitize filename and build target path
        $original_name = basename($_FILES['file']['name']);
        $safe_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
        $file_name = time() . '_' . $safe_name;
        $target_path = $upload_dir . $file_name;

        if (empty($error)) {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
                // store a relative path so existing rows using 'uploads/...' remain consistent
                $file_path = 'uploads/' . $file_name;
            } else {
                $error = "File upload failed! Check directory permissions.";
            }
        }
    }

    if (empty($title) || empty($description) || empty($due_date)) {
        $error = "All fields are required!";
    } else if (empty($error)) {
        $stmt = $pdo->prepare("UPDATE assignments 
            SET title = ?, description = ?, due_date = ?, `file` = ? 
            WHERE id = ?");
        $stmt->execute([$title, $description, $due_date, $file_path, $assignment_id]);

        $success = "Assignment updated successfully!";
        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assignment</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Global Reset & Body */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6; /* Consistent light background */
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }
        
        /* Container Styling */
        .container {
            max-width: 700px; /* Slightly wider for form elements */
            width: 90%;
            margin: 40px auto;
            background: white;
            border-radius: 8px; /* Consistent radius */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Consistent shadow */
            padding: 30px;
        }
        
        /* Header */
        h2 {
            color: #2c3e50; /* Dark color for headers */
            text-align: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        /* Form Labels and Inputs */
        label {
            font-weight: 600;
            margin-top: 15px;
            display: block;
            color: #444;
            font-size: 0.95em;
        }
        
        input[type=text],
        input[type=date],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 5px;
            margin-bottom: 15px;
            box-sizing: border-box;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        input:focus, textarea:focus {
            border-color: #3498db; /* Peter River Blue focus */
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }

        /* Current File Display */
        .current-file {
            margin: 10px 0 20px 0;
            color: #555;
            font-size: 0.9em;
        }
        .current-file a {
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }
        .current-file a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        /* Buttons */
        .btn-update {
            padding: 10px 20px;
            background: #3498db; /* Peter River Blue */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-update:hover {
            background: #2980b9;
        }
        
        .btn-cancel {
            /* Styled as a secondary button/link */
            display: inline-block;
            margin-left: 15px;
            padding: 10px 20px;
            color: #7f8c8d; /* Darker gray for cancel */
            text-decoration: none;
            font-weight: 600;
            border: 1px solid transparent;
            border-radius: 4px;
            transition: color 0.3s, background 0.3s;
        }
        .btn-cancel:hover {
            color: #34495e;
            background-color: #ecf0f1;
        }

        /* Messages/Alerts */
        .msg {
            margin: 0 0 20px 0;
            padding: 12px;
            border-radius: 4px;
            font-weight: 600;
            border: 1px solid transparent;
        }
        .msg i {
            margin-right: 8px;
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            border-color: #c3e6cb;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border-color: #f5c6cb;
        }
        
        /* Responsive Styling */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                margin: 20px auto;
                padding: 20px;
            }
            .btn-update, .btn-cancel {
                display: block;
                width: 100%;
                margin: 10px 0;
                text-align: center;
            }
            .btn-cancel {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-edit"></i> Edit Assignment</h2>

    <?php if (!empty($error)): ?>
        <div class="msg error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="msg success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Assignment Title</label>
        <input type="text" name="title" value="<?= htmlspecialchars($assignment['title']) ?>" required>

        <label>Description</label>
        <textarea name="description" rows="5" required><?= htmlspecialchars($assignment['description']) ?></textarea>

        <label>Due Date</label>
        <input type="date" name="due_date" value="<?= htmlspecialchars($assignment['due_date']) ?>" required>

        <label>Attachment (optional)</label>
        <?php if (!empty($assignment['file'])): ?>
            <!-- Corrected path with '../' assuming this file is in admin/ and uploads/ is one level up -->
            <p class="current-file">Current File: <a href="../<?= htmlspecialchars($assignment['file']) ?>" target="_blank"><i class="fas fa-file-alt"></i> View File</a></p>
        <?php endif; ?>
        <input type="file" name="file" accept=".pdf,.docx,.txt,.zip">

        <!-- Updated buttons with new classes -->
        <button type="submit" class="btn-update"><i class="fas fa-save"></i> Update Assignment</button>
        <a href="assignment.php" class="btn-cancel">Cancel</a>
    </form>
</div>

</body>
</html>
