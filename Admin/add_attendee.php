<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Attendee | CareSync</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">

    <!-- Your CSS -->
    <link rel="stylesheet" href="../styles/add_attendee.css?v=<?php echo time(); ?>">
</head>

<body class="patient-page-bg">

<div class="container main-container d-flex align-items-center justify-content-center">
    <div class="card doctor-card shadow-lg">

        <!-- Header -->
        <div class="doctor-header d-flex align-items-center gap-4">
            <div class="header-icon-box">
                <img src="../icons/medical-staff.png" width="35">
            </div>
            <div class="add text-white">
                <h3 class="mb-0">Add New Attendee</h3>
                <p class="mb-0 opacity-75">Enter professional medical details</p>
            </div>
        </div>

        <!-- Form -->
        <div class="card-body p-4 p-md-5">
            <form id="doctorForm" method="post" onsubmit="return validateForm()" novalidate>

                <div class="row g-4">

                    <!-- Name -->
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control-custom" name="name" id="name">
                        <span id="nameError" class="text-danger small"></span>
                    </div>

                    <!-- Contact -->
                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" class="form-control-custom" name="contact" id="contact">
                        <span id="contactError" class="text-danger small"></span>
                    </div>

                    <!-- Branch -->
                    <div class="col-md-6">
                        <label class="form-label">Hospital Branch</label>
                        <input type="text" class="form-control-custom" name="branch" id="branch">
                        <span id="branchError" class="text-danger small"></span>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control-custom" name="email" id="email">
                        <span id="emailError" class="text-danger small"></span>
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control-custom" name="password" id="password">
                        <span id="passwordError" class="text-danger small"></span>
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control-custom" name="confirmpassword" id="confirmPassword">
                        <span id="confirmPasswordError" class="text-danger small"></span>
                    </div>

                </div>

                <div class="text-center mt-5">
                    <input class="add-btn" type="submit" value="Register Attendee">
                </div>

            </form>
        </div>

        <div class="card-footer footer-text">
            CareSync Admin Panel • Secure Attendee Registration
        </div>

    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content success-modal p-4">
            <div class="modal-body text-center">
                <div class="success-icon-box">✔</div>
                <h4 class="fw-bold mb-2">Registration Successful</h4>
                <p class="text-muted mb-4">Attendee added successfully.</p>
                <button class="btn btn-dark" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>

<script src="../Bootstrap/bootstrap.bundle.min.js"></script>

<script>
function validateForm() {
    let isValid = true;

    let name = document.getElementById("name").value.trim();
    let contact = document.getElementById("contact").value.trim();
    let branch = document.getElementById("branch").value.trim();
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();
    let confirmPassword = document.getElementById("confirmPassword").value.trim();

    // Clear errors
    document.getElementById("nameError").innerText = "";
    document.getElementById("contactError").innerText = "";
    document.getElementById("branchError").innerText = "";
    document.getElementById("emailError").innerText = "";
    document.getElementById("passwordError").innerText = "";
    document.getElementById("confirmPasswordError").innerText = "";

    if (name === "") {
        document.getElementById("nameError").innerText = "Name required";
        isValid = false;
    }

    if (!/^[0-9]{10}$/.test(contact)) {
        document.getElementById("contactError").innerText = "Enter valid 10-digit number";
        isValid = false;
    }

    if (branch === "") {
        document.getElementById("branchError").innerText = "Branch required";
        isValid = false;
    }

    let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailPattern.test(email)) {
        document.getElementById("emailError").innerText = "Invalid email format";
        isValid = false;
    }

    let passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    if (!passwordPattern.test(password)) {
        document.getElementById("passwordError").innerText = "Password must be min 8 chars, include uppercase, lowercase, number, and special char (@$!%*?&)";
        isValid = false;
    }

    if (password !== confirmPassword) {
        document.getElementById("confirmPasswordError").innerText = "Passwords do not match";
        isValid = false;
    }

    return isValid;
}
</script>

<?php
require_once "../dbconnect.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['contact']);
    $branch = trim($_POST['branch']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmpassword']);

    if ($password != $confirmPassword) {
        echo "<script>alert('Passwords do not match');</script>";
        exit();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Check email
    $checkEmail = $conn->prepare("SELECT id FROM attendees WHERE email=?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $resultEmail = $checkEmail->get_result();

    if ($resultEmail->num_rows > 0) {
        echo "<script>alert('Email already exists');</script>";
        exit();
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("INSERT INTO attendees (full_name, email, mobile, hospital_branch, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $mobile, $branch, $passwordHash);
        $stmt->execute();

        $last_id = $conn->insert_id;
        $attendee_code = "ATTN-" . date("Y") . "-" . str_pad($last_id, 3, "0", STR_PAD_LEFT);

        $update = $conn->prepare("UPDATE attendees SET attendee_code=? WHERE id=?");
        $update->bind_param("si", $attendee_code, $last_id);
        $update->execute();

        // Insert into users table for login access
                $role = "attendee";
                $userQry = "INSERT INTO users(name, email, password, role, attendee_code) VALUES(?,?,?,?,?)";
                $userStmt = $conn->prepare($userQry);
                $userStmt->bind_param("sssss", $name, $email, $passwordHash, $role, $attendee_code);
                $userStmt->execute();

         // Log Activity
                $activity = "New Attendee Registered: " . $attendee_code;
                $logUser = "Admin"; 
                $logQuery = "INSERT INTO activity_logs (activity, user) VALUES (?, ?)";
                $stmtLog = $conn->prepare($logQuery);
                $stmtLog->bind_param("ss", $activity, $logUser);
                $stmtLog->execute();

        $conn->commit();

        include "../attendee_mail.php";
        sendAttendeeMail($email, $name, $attendee_code); // ✅ FIXED

       
        echo "<script>
            var myModal = new bootstrap.Modal(document.getElementById('successModal'));
            myModal.show();
            setTimeout(function(){ window.location.href='admin_dashboard.php'; }, 2500);
        </script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Registration failed');</script>";
    }
}
?>

</body>
</html>