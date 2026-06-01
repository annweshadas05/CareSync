<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Doctor | CareSync</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/add_doctor.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="patient-page-bg"> 
    <div class="container main-container d-flex flex-column align-items-center justify-content-center">
        <div class="w-100 mb-4" style="max-width: 800px;">
            <a href="../home.php" class="text-decoration-none text-secondary fw-medium">
                <i class="bi bi-arrow-left me-1"></i> Back to Home
            </a>
        </div>
        <div class="card doctor-card shadow-lg">

            <div class="doctor-header d-flex align-items-center gap-4">
                <div class="header-icon-box">
                    <img src="../icons/medical-staff.png" width="35" alt="Doctor Icon">
                </div>
                <div class="add text-white">
                    <h3 class="mb-0">Add New Doctor</h3>
                    <p class="mb-0 opacity-75">Enter professional medical details</p>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                <form id="doctorForm" method="post" onsubmit="return validateForm()" novalidate>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control-custom" name="name" id="name" placeholder="Dr. First Last">
                            <span id="nameError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select class="custom-select-style" name="department" id="department">
                                <option value="">Select Department</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="General Medicine">General Medicine</option>
                            </select>
                            <span id="departmentError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Specialization</label>
                            <input type="text" class="form-control-custom" name="specialization" id="specialization" placeholder="e.g. Heart Surgeon">
                            <span id="specializationError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Experience (Years)</label>
                            <input type="number" class="form-control-custom" name="experience" id="experience" placeholder="0">
                            <span id="experienceError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control-custom" name="contact" id="contact" placeholder="10-digit number">
                            <span id="contactError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control-custom" name="email" id="email" placeholder="doctor@caresync.com">
                            <span id="emailError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Create Password</label>
                            <input type="password" class="form-control-custom" name="password" id="password">
                            <span id="passwordError" class="text-danger small"></span>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control-custom" name="confirmpassword" id="confirmPassword">
                            <span id="confirmPasswordError" class="text-danger small"></span>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <input class="add-btn" type="submit" value="Register Doctor">
                    </div>
                </form>
            </div>

            <div class="card-footer footer-text">
                CareSync Admin Panel • Secure Doctor Registration
            </div>
        </div>
    </div>

    <div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content success-modal p-4">
                <div class="modal-body text-center">
                    <div class="success-icon-box">✔</div>
                    <h4 class="fw-bold mb-2">Registration Successful</h4>
                    <p class="text-muted mb-4">Doctor details have been securely added to the system.</p>
                    <button class="btn btn-dark px-5 py-2" style="border-radius:10px;" data-bs-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../js/add_doctor.js?v=<?= time() ?>"></script>

   

 <?php
    if ($_SERVER['REQUEST_METHOD'] == "POST") {

        require_once "../dbconnect.php";

        $success = false;
        $name = trim($_POST['name']);
        $department = trim($_POST['department']);
        $specialization = trim($_POST['specialization']);
        $experience = (int) $_POST['experience'];
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirmpassword']);

        if ($password != $confirmPassword) {
            echo "<script>alert('Passwords do not match');</script>";
            exit();
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Check if email exists
        $checkEmail = $conn->prepare("SELECT id FROM doctors WHERE email=?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        $resultEmail = $checkEmail->get_result();
        if ($resultEmail->num_rows > 0) {
            echo "<script>alert('Email already registered');</script>";
            exit();
        }

        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into doctors
            $qry = "INSERT INTO doctors(full_name, department, specialization, experience, contact, email, password)
                VALUES(?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($qry);
            $stmt->bind_param("sssisss", $name, $department, $specialization, $experience, $contact, $email, $passwordHash);
            $stmt->execute();
            $last_id = $conn->insert_id;

            // Generate doctor code
            $year = date("Y");
            $doctor_code = "DOC-" . $year . "-" . str_pad($last_id, 3, "0", STR_PAD_LEFT);

            $update = $conn->prepare("UPDATE doctors SET doctor_code=? WHERE id=?");
            $update->bind_param("si", $doctor_code, $last_id);
            $update->execute();

            // Insert into users table
            $role = "doctor";
            $userQry = "INSERT INTO users(name,email,password,role,doctor_code) VALUES(?,?,?,?,?)";
            $userStmt = $conn->prepare($userQry);
            $userStmt->bind_param("sssss", $name, $email, $passwordHash, $role, $doctor_code);
            $userStmt->execute();

            // Insert into activity_logs
            $activity = "New Doctor Added";
            $user = "Admin";
            $logQuery = "INSERT INTO activity_logs (activity, user) VALUES (?, ?)";
            $stmtLog = $conn->prepare($logQuery);
            $stmtLog->bind_param("ss", $activity, $user);
            $stmtLog->execute();

            // Commit transaction
            $conn->commit();

            // Send mail
            include "../doctor_mail.php";
            sendDoctorMail($email, $name, $doctor_code, $specialization);

           echo "<script>
            var myModal = new bootstrap.Modal(document.getElementById('successModal'));
            myModal.show();
            setTimeout(function(){ window.location.href='admin_dashboard.php'; }, 2500);
        </script>";

        } catch (Exception $e) {
            // Rollback on any error
            $conn->rollback();
            echo "<script>alert('Error registering doctor: " . $e->getMessage() . "');</script>";
        }

        $conn->close();
    }
    ?>
</body>
</html>