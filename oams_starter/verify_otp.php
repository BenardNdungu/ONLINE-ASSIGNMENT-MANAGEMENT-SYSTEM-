<?php
require_once __DIR__ . '/src/db.php';

if (!isset($_GET['email'])) {
    exit("Invalid request. No email provided.");
}

$email = $_GET['email'];
$error_message = ''; // Variable to hold the error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);

    // Verify OTP and expiry
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND otp_code = ? AND otp_expires > NOW()");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch();

    if ($user) {
        // OTP verified, redirect to reset password page
        // Note: Store a token here instead of just email for better security
        header("Location: reset_password.php?email=" . urlencode($email));
        exit;
    } else {
        $error_message = "Invalid or expired OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP - TASKNEST</title>
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
            margin-bottom: 1rem;
            font-size: 1.75rem;
        }

        p {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #4b4f56;
            font-size: 1rem;
            line-height: 1.4;
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

        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1.25rem;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1.25rem;
            text-align: center;
            letter-spacing: 4px;
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
        
        .error-message {
            color: #dc3545;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify OTP</h2>
        <p>Enter the 6-digit OTP sent to your email address.</p>

        <?php
        // Display the error message here, inside the container
        if (!empty($error_message)) {
            echo "<p class='error-message'>" . htmlspecialchars($error_message) . "</p>";
        }
        ?>

        <form method="POST">
            <label for="otp">OTP:</label>
            <input type="text" id="otp" name="otp" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required>
            <button type="submit">Verify OTP</button>
        </form>
    </div>
</body>
</html>