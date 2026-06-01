<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != "admin") {
    header('location:../login.php');
    exit();
}

$patient_id = "";

if (isset($_GET['id'])) {
    $patient_id = $_GET['id'];

    $sql = "SELECT * FROM patients WHERE patient_code=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $name = $row['full_name'];
        $email = $row['email'];
        $mobile = $row['mobile'];
        $dob = $row['dob'];
        $gender = $row['gender'];
        $aadhar = $row['aadhar'];
        $blood = $row['blood_group'];
        $city = $row['city'];
        $address = $row['address'];
    } else {
        echo "Patient not found";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $patient_id = $_POST['patient_id'];

    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $aadhar = $_POST['aadhar'];
    $blood = $_POST['blood'];
    $city = $_POST['city'];
    $address = $_POST['address'];

    $update = "UPDATE patients SET 
                full_name=?, 
                email=?, 
                mobile=?, 
                dob=?, 
                gender=?, 
                aadhar=?, 
                blood_group=?, 
                city=?, 
                address=? 
               WHERE patient_code=?";

    $stmt = $conn->prepare($update);
    $stmt->bind_param("ssssssssss", $name, $email, $mobile, $dob, $gender, $aadhar, $blood, $city, $address, $patient_id);

    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Update Patient</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
     <script src="../js/lucide.js"></script>
</head>
<body class="admin-bg">

    <!-- SIDEBAR -->
    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2 text-white">CareSync</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link active" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
        </nav>
        <a href="../logout.php" class="nav-link logout-link">
            <i data-lucide="log-out"></i> <span>Logout</span>
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Update Patient Profile</h2>
                <p class="text-muted">Modify patient details and records</p>
            </div>
            <a href="manage_patient.php" class="action-btn text-decoration-none">
                <i data-lucide="arrow-left" class="me-1 d-inline-block align-middle"></i> <span class="align-middle">Back to Patients</span>
            </a>
        </div>

        <div class="glass-card mb-4 p-4">
            <form method="POST">
                <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">FULL NAME</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="user" size="18" class="text-muted"></i></span>
                            <input type="text" name="name" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($name); ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">EMAIL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="mail" size="18" class="text-muted"></i></span>
                            <input type="email" name="email" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">MOBILE</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="phone" size="18" class="text-muted"></i></span>
                            <input type="text" name="mobile" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($mobile); ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">DATE OF BIRTH</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="calendar" size="18" class="text-muted"></i></span>
                            <input type="date" name="dob" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($dob); ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted d-block">GENDER</label>
                        <div class="d-flex gap-3 py-2">
                            <div class="form-check">
                                <input type="radio" name="gender" value="male" class="form-check-input" <?php if($gender=="male") echo "checked"; ?>>
                                <label class="form-check-label">Male</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="gender" value="female" class="form-check-input" <?php if($gender=="female") echo "checked"; ?>>
                                <label class="form-check-label">Female</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" name="gender" value="Other" class="form-check-input" <?php if($gender=="Other") echo "checked"; ?>>
                                <label class="form-check-label">Other</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">AADHAR</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="credit-card" size="18" class="text-muted"></i></span>
                            <input type="text" name="aadhar" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($aadhar); ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">BLOOD GROUP</label>
                        <select name="blood" class="form-select bg-light">
                            <option <?php if($blood=="A+") echo "selected"; ?>>A+</option>
                            <option <?php if($blood=="B+") echo "selected"; ?>>B+</option>
                            <option <?php if($blood=="AB+") echo "selected"; ?>>AB+</option>
                            <option <?php if($blood=="O+") echo "selected"; ?>>O+</option>
                            <option <?php if($blood=="A-") echo "selected"; ?>>A-</option>
                            <option <?php if($blood=="B-") echo "selected"; ?>>B-</option>
                            <option <?php if($blood=="AB-") echo "selected"; ?>>AB-</option>
                            <option <?php if($blood=="O-") echo "selected"; ?>>O-</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">CITY</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="map-pin" size="18" class="text-muted"></i></span>
                            <input type="text" name="city" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($city); ?>">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">ADDRESS</label>
                        <textarea name="address" class="form-control bg-light" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>

                </div>

                <div class="text-end mt-5">
                    <button type="submit" class="action-btn border-0 px-4 py-2">
                        <i data-lucide="save" class="me-1 d-inline-block align-middle" size="18"></i> <span class="align-middle">Update Patient</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div class="modal fade" id="successModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 p-4 text-center rounded-4 shadow">
                <div class="modal-body">
                    <div class="mb-3 text-success mx-auto" style="width: 80px; height: 80px; background: rgba(40,167,69,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="check-circle" size="40"></i>
                    </div>
                    <h3 class="fw-bold mb-2">Success!</h3>
                    <p class="text-muted">Patient Details Updated Successfully</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>

    <?php if (isset($success) && $success) { ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();
        setTimeout(function(){
            window.location.href = "manage_patient.php";
        }, 2000);
    });
    </script>
    <?php } ?>

</body>
</html>