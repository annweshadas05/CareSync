<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Recovery Sent</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <style>
        :root {
            --primary-blue: #0061ff;
            --navy-dark: #061727;
            --blue-gradient: linear-gradient(135deg, var(--primary-blue) 0%, var(--navy-dark) 100%);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--blue-gradient);
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 90%;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #dcfce7;
            color: #166534;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            margin: 0 auto 25px auto;
        }
        .action-btn {
            background: var(--blue-gradient);
            color: white;
            padding: 14px 30px;
            border-radius: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: 0.3s;
            border: none;
            width: 100%;
        }
        .action-btn:hover {
            box-shadow: 0 10px 20px rgba(0, 97, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body>

<?php
// Ensure this script only runs if data was posted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    require "../dbconnect.php";
    require "forgot_mail.php";
    
    $email = $_POST['email'];
    $token = bin2hex(random_bytes(50));
    $expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));

    $sql = "UPDATE users SET reset_token=?, token_expiry=? WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $token, $expiry, $email);
    $stmt->execute();

    $reset_link = "http://localhost:8888/CareSync/forgotpassword/reset_password.php?token=" . $token;
    sendResetEmail($email, $reset_link);
}
?>

<div class="glass-card">
    <div class="success-icon">✓</div>
    <h3 class="fw-bold text-dark mb-3">Email Sent!</h3>
    <p class="text-muted mb-4">We've sent a secure password reset link to your registered email address. Please check your inbox.</p>
    
    <a href="../login.php" class="action-btn">Return to Login</a>
</div>

</body>
</html>