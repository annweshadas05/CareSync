<?php
$success=false;
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    require_once "../dbconnect.php";

    $success = false;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $dob = trim($_POST['dob']);
    $gender = trim($_POST['gender']);
    $aadhar = trim($_POST['aadhar']);
    $blood = trim($_POST['blood']);
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmpassword']);

    if ($password != $confirmPassword) {
        echo "<script>alert('Passwords do not match');</script>";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $check = $conn->prepare("SELECT id FROM patients WHERE email=? OR aadhar=?");
        $check->bind_param("ss", $email, $aadhar);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo "<script>alert('Email or Aadhar already registered');</script>";
        } else {
            $conn->begin_transaction();

            try {
                // Insert into patients table
                $qry = "INSERT INTO patients(full_name, email, mobile, dob, gender, aadhar, blood_group, city, address, password) 
                        VALUES(?,?,?,?,?,?,?,?,?,?)";
                $stmt = $conn->prepare($qry);
                $stmt->bind_param("ssssssssss", $name, $email, $mobile, $dob, $gender, $aadhar, $blood, $city, $address, $passwordHash);
                $stmt->execute();
                $last_id = $conn->insert_id;

                // Generate Patient Code (e.g., PAT-2026-001)
                $year = date("Y");
                $patient_code = "PAT-" . $year . "-" . str_pad($last_id, 3, "0", STR_PAD_LEFT);

                $update = $conn->prepare("UPDATE patients SET patient_code=? WHERE id=?");
                $update->bind_param("si", $patient_code, $last_id);
                $update->execute();

                // Insert into users table for login access
                $role = "patient";
                $userQry = "INSERT INTO users(name, email, password, role, patient_code) VALUES(?,?,?,?,?)";
                $userStmt = $conn->prepare($userQry);
                $userStmt->bind_param("sssss", $name, $email, $passwordHash, $role, $patient_code);
                $userStmt->execute();

                // Log Activity
                $activity = "New Patient Registered: " . $patient_code;
                $logUser = "System"; 
                $logQuery = "INSERT INTO activity_logs (activity, user) VALUES (?, ?)";
                $stmtLog = $conn->prepare($logQuery);
                $stmtLog->bind_param("ss", $activity, $logUser);
                $stmtLog->execute();

                $conn->commit();
                $success = true; // Trigger success modal

                // Attempt to send email without blocking success feedback
                try {
                    include_once "../php_mail.php";
                    if (function_exists('sendPatientMail')) {
                        sendPatientMail($email, $name, $patient_code);
                    }
                } catch (Exception $e) {
                    // Log error internally if needed, but don't stop the success message
                }

            } catch (Exception $e) {
                $conn->rollback();
                echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync - Patient Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/add_patient.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="patient-page-bg">
    <div class="container py-5">
        <div class="mb-4">
            <a href="../home.php" class="text-decoration-none text-secondary fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Home
            </a>
        </div>

        <div class="card registration-card border-0 shadow-lg">
            <div class="registration-header text-white" style="background: linear-gradient(135deg, #2563EB, #061727); padding: 30px; border-radius: 15px 15px 0 0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="header-icon-box bg-white p-2 rounded-3">
                        <img src="../icons/crowd.png" width="40" alt="icon">
                    </div>
                    <div>
                        <h3 class="mb-0 fw-bold">Patient Registration</h3>
                        <p class="mb-0 opacity-75 small">Create a secure digital health profile</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <form id="patientForm" action ="add_patient.php" onsubmit="return validateForm()" method="post" novalidate>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
                                <input type="text" id="name" class="form-control bg-light border-start-0" placeholder="John Doe" name="name">
                            </div>
                            <span id="nameError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="email" class="form-control bg-light border-start-0" placeholder="john@example.com" name="email">
                            </div>
                            <span id="emailError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-phone"></i></span>
                                <input type="tel" id="mobile" class="form-control bg-light border-start-0" placeholder="9876543210" name="mobile">
                            </div>
                            <span id="mobileError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Date of Birth</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-event"></i></span>
                                <input type="date" id="dob" class="form-control bg-light border-start-0" name="dob">
                            </div>
                            <span id="dobError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase d-block">Gender</label>
                            <div class="d-flex gap-3 py-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="male">
                                    <label class="form-check-label" for="male">Male</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="female">
                                    <label class="form-check-label" for="female">Female</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="gender" id="other" value="Other">
                                    <label class="form-check-label" for="other">Other</label>
                                </div>
                            </div>
                            <span id="genderError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Aadhar ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-card-heading"></i></span>
                                <input type="text" id="aadhar" class="form-control bg-light border-start-0" placeholder="12-digit number" name="aadhar">
                            </div>
                            <span id="aadharError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Blood Group</label>
                            <select id="blood" class="form-select bg-light" name="blood">
                                <option value="">Select Group</option>
                                <option>A+</option><option>B+</option><option>AB+</option><option>O+</option>
                                <option>A-</option><option>B-</option><option>AB-</option><option>O-</option>
                            </select>
                            <span id="bloodError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">City</label>
                            <input type="text" id="city" class="form-control bg-light" placeholder="Bhubaneswar" name="city">
                            <span id="cityError" class="text-danger small"></span>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-uppercase">Permanent Address</label>
                            <textarea id="address" class="form-control bg-light" rows="2" name="address" placeholder="Full address..."></textarea>
                            <span id="addressError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Create Password</label>
                            <input type="password" id="password" class="form-control bg-light" name="password">
                            <span id="passwordError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Confirm Password</label>
                            <input type="password" id="confirmPassword" class="form-control bg-light" name="confirmpassword">
                            <span id="confirmPasswordError" class="text-danger small"></span>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" style="border-radius: 12px;">
                            Complete Registration <i class="bi bi-check-circle ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 p-4 text-center rounded-4 shadow">
                <div class="modal-body">
                    <div class="mb-3 text-success" style="font-size: 3rem;"><i class="bi bi-check-circle-fill"></i></div>
                    <h3 class="fw-bold mb-2">Registration Successful!</h3>
                    <p class="text-muted mb-4">Redirecting to login...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/add_patient.js?v=<?= time() ?>"></script>
    <?php if ($success): ?>
        <script>
            var myModal = new bootstrap.Modal(document.getElementById('successModal'));
            myModal.show();
            setTimeout(function(){ window.location.href='../login.php'; }, 3000);
        </script>
    <?php endif; ?>
</body>
</html>