<?php
require_once __DIR__ . '/src/db.php';

if (!isset($_GET['email'])) {
    exit("Invalid request. No email provided.");
}

$email = $_GET['email'];
$success_message = ''; // Variable for the message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Update password and clear OTP
    $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires = NULL WHERE email = ?");
    $stmt->execute([$password, $email]);

    $success_message = "<p class='success-message'>Password reset successful! <a href='login.php'>Login now</a></p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - TASKNEST</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
            box-sizing: border-box;
        }

        .container {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-sizing: border-box;
        }

        h2 {
            color: #1c1e21;
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
        }

        form {
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4b4f56;
            font-weight: 600;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1.25rem;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
        }

        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .success-message {
            color: #28a745;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1rem;
        }

        .success-message a {
            color: #007bff;
            text-decoration: none;
            font-weight: 700;
        }

        .success-message a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>

        <?php if (!empty($success_message)): ?>
            <?php echo $success_message; ?>
        <?php else: ?>
            <form method="POST">
                <label for="password">New Password:</label>
                <input type="password" id="password" name="password" required>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

    </div>
</body>
</html>