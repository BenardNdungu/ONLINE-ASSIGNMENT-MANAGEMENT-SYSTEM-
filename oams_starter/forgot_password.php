<?php
require_once __DIR__ . '/src/db.php'; // PDO connection
$message = ''; // Variable to hold messages
$link = '';    // Variable to hold the link

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = rand(100000, 999999); // 6-digit OTP
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        // Save OTP and expiry
        $stmt = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE email = ?");
        $stmt->execute([$otp, $expiry, $email]);

        // Send OTP via email (demo: display OTP)
        $message = "<p><b>OTP Sent!</b> (For testing: $otp)</p>";
        $link = "<a href='verify_otp.php?email=" . urlencode($email) . "'>Proceed to Verify OTP</a>";
    } else {
        $message = "<p style='color:red;'>No account found with that email.</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - TASKNEST</title>
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
            padding: 1rem; /* Added padding for small screens */
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

        input[type="email"] {
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

        p {
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Styles for PHP messages */
        p b {
            color: #28a745;
            font-size: 1.1rem;
        }
        
        p[style*="color:red"] {
            color: #dc3545 !important;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 1rem;
            margin-bottom: 1rem; /* Added margin */
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>

        <?php
        // Display messages here
        if (!empty($message)) {
            echo $message;
            echo $link;
        }
        ?>

        <form method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>
            <button type="submit">Send OTP</button>
        </form>
        <p><a href="login.php">Back to Login</a></p>
    </div>
</body>
</html>