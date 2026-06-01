<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once "dbconnect.php";
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $qry = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($qry);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $hashFromDb = trim($row['password']);

        if (password_verify($password, $hashFromDb)) {

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] == "admin") {
                $_SESSION['email']=$row['email'];
                header("Location: ./Admin/admin_dashboard.php");
                exit();
    
            } elseif ($row['role'] == "doctor") {

                if (empty($row['doctor_code'])) {
                    echo "<script>alert('Doctor ID missing');</script>";
                    exit();
                }
                $_SESSION['doctor_id'] = $row['doctor_code'];
                header("Location: ./Doctor/doctor_dashboard.php");
                exit();
            } elseif ($row['role'] == "patient") {
                if (empty($row['patient_code'])) {
                    echo "<script>alert('Patient ID missing');</script>";
                    exit();
                }
                $_SESSION['patient_id'] = $row['patient_code'];
                header("Location: ./Patient/patient_dashboard.php");
                exit();
            } elseif ($row['role'] == "attendee") {
                if (empty($row['attendee_code'])) {
                    echo "<script>alert('Attendee ID missing');</script>";
                    exit();
                }

                $_SESSION['attendee_id'] = $row['attendee_code'];
                header("Location: ./Attendee/attendee_dashboard.php");
                exit();
            }

        } else {
            echo "<script>alert('Incorrect Password');</script>";
        }

    } else {
        echo "<script>alert('User not found');</script>";
    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CareSync - Secure Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="styles/login.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="./js/lucide.js"></script>
<script>
    lucide.createIcons();
</script>
</head>

<body class="login-page">
    <div class="back-to-home">
        <a href="home.php" class="fw-bold text-decoration-none text-muted">
            <i data-lucide="arrow-left"></i> Back to Home
        </a>
    </div>

    <div class="login-container d-flex align-items-center justify-content-center">
        <div class="login-card shadow-lg border-0">
            
            <div class="login-header text-center">
                <div class="logo-wrapper mb-3">
                    <img src="./Assets/CareSyncLogo.png" class="login-logo" alt="CareSync">
                </div>
                <h2 class="fw-bold h4 mb-1">Welcome Back</h2>
                <p class="text-muted small fw-bold">Please enter your credentials to access CareSync</p>
            </div>

            <div class="login-body p-4 p-md-5">
                <form method="POST" onsubmit="return validateLogin()">
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Email or Username</label>
                        <div class="input-group-custom">
                            <i class="bi bi-envelope icon-input"></i>
                            <input type="text" name="email" id="email" class="form-control-custom" placeholder="name@example.com">
                        </div>
                        <small id="emailError" class="text-danger"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Password</label>
                        <div class="input-group-custom">
                            <i class="bi bi-lock icon-input"></i>
                            <input type="password" name="password" id="password" class="form-control-custom" placeholder="••••••••">
                        </div>
                        <small id="passwordError" class="text-danger"></small>
                    </div>

                    <div class="mb-4 text-end">
                        <a href="#" class="forgot-link small" data-bs-toggle="modal" data-bs-target="#forgotModal">
                            Forgot Password?
                        </a>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary login-submit-btn py-3">
                            Sign In <i class="bi bi-box-arrow-in-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="login-footer text-center p-4 border-top bg-light-soft">
                <small class="text-muted fw-bold">
                    <i data-lucide="shield" class="text-primary"></i> Authorized Medical Access Only
                </small>
            </div>
        </div>
    </div>  

    <script src="Bootstrap/bootstrap.bundle.min.js"></script>
    <script src="./js/login.js?v=<?= time() ?>"></script>
   <div class="modal fade" id="forgotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" 
             style="border-radius: 24px; background: #ffffff; overflow: hidden;">

            <div class="text-center p-4"
                 style="background: linear-gradient(135deg, #0061ff, #061727 100%);">

                <button type="button" 
                        class="btn-close btn-close-white float-end"
                        data-bs-dismiss="modal">
                </button>

                <div class="mb-3 mt-2">
                    <img src="./Assets/CareSyncLogo.png" width="55"
                         class="bg-white p-2 rounded-circle shadow-sm">
                </div>

                <h4 class="fw-bold text-white mb-1" style="font-family: 'Custom';">
                    CareSync Recovery
                </h4>

                <p style="color: rgba(255,255,255,0.8); font-size: 0.85rem;">
                    Secure password reset service
                </p>
            </div>

            <div class="modal-body p-4">

                <div class="text-center mb-4">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                         style="width: 65px; height: 65px; border-radius: 50%;
                         background: linear-gradient(135deg, #DBEAFE, #BFDBFE);">

                        <i data-lucide="mail" size="30" 
                           style="color:#1E3A8A; stroke-width:2.5;"></i>
                    </div>

                    <h5 class="fw-bold" style="color: #1E293B;">
                        Trouble Logging In?
                    </h5>

                    <p class="text-muted small mb-0">
                        Enter your email and we’ll send you a recovery link
                    </p>
                </div>

                <form action="./forgotpassword/send_reset.php" method="POST">

                    <div class="mb-4">
                        <div class="input-group shadow-sm" style="border-radius: 12px; overflow: hidden;">
                            
                            <span class="input-group-text border-0 bg-light">
                                <i data-lucide="at-sign" size="18" style="color: #64748b;"></i>
                            </span>

                            <input type="email" name="email"
                                   class="form-control border-0 bg-light py-3"
                                   placeholder="Enter your email"
                                   required>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-100 border-0 py-3 fw-semibold"
                            style="
                                border-radius: 12px;
                                background: linear-gradient(135deg, #0061ff, #061727 100%);
                                color: white;
                                box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
                                transition: 0.3s;
                            "
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='translateY(0)'">

                        Send Recovery Link
                    </button>

                </form>
            </div>

        </div>
    </div>
</div>
   <script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>