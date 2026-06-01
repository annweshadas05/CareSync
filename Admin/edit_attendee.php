<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] != "admin") {
    header('location:../login.php');
    exit();
}

$attendee_id = "";

if (isset($_GET['id'])) {
    $attendee_id = $_GET['id'];

    $sql = "SELECT * FROM attendees WHERE attendee_code=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $attendee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $name = $row['full_name'];
        $email = $row['email'];
        $mobile = $row['mobile'];
        $hospital_branch = $row['hospital_branch'];
    } else {
        echo "Attendee not found";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $attendee_id = $_POST['attendee_id'];

    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $hospital_branch = $_POST['hospital_branch'];

    $conn->begin_transaction();
    try {
        $update1 = "UPDATE attendees SET 
                    full_name=?, 
                    email=?, 
                    mobile=?, 
                    hospital_branch=? 
                   WHERE attendee_code=?";

        $stmt1 = $conn->prepare($update1);
        $stmt1->bind_param("sssss", $name, $email, $mobile, $hospital_branch, $attendee_id);
        $stmt1->execute();

        $update2 = "UPDATE users SET 
                    name=?, 
                    email=? 
                   WHERE attendee_code=?";

        $stmt2 = $conn->prepare($update2);
        $stmt2->bind_param("sss", $name, $email, $attendee_id);
        $stmt2->execute();
        
        $conn->commit();
        $success = true;
    } catch (Exception $e) {
        $conn->rollback();
        $error = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Update Attendee</title>
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
            <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link active" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
            <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="reports.php"><i data-lucide="bar-chart-3"></i> <span>Reports</span></a>
            <a href="../logout.php" class="nav-link logout-link">
                <i data-lucide="log-out"></i> <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Update Attendee Profile</h2>
                <p class="text-muted">Modify attendee details</p>
            </div>
            <a href="manage_attendee.php" class="action-btn text-decoration-none">
                <i data-lucide="arrow-left" class="me-1 d-inline-block align-middle"></i> <span class="align-middle">Back to Attendees</span>
            </a>
        </div>

        <div class="glass-card mb-4 p-4">
            <form method="POST">
                <input type="hidden" name="attendee_id" value="<?php echo htmlspecialchars($attendee_id); ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">FULL NAME</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="user" size="18" class="text-muted"></i></span>
                            <input type="text" name="name" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">EMAIL</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="mail" size="18" class="text-muted"></i></span>
                            <input type="email" name="email" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">MOBILE</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="phone" size="18" class="text-muted"></i></span>
                            <input type="text" name="mobile" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($mobile); ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">HOSPITAL BRANCH</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i data-lucide="building" size="18" class="text-muted"></i></span>
                            <input type="text" name="hospital_branch" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($hospital_branch); ?>">
                        </div>
                    </div>
                </div>

                <div class="text-end mt-5">
                    <button type="submit" class="action-btn border-0 px-4 py-2">
                        <i data-lucide="save" class="me-1 d-inline-block align-middle" size="18"></i> <span class="align-middle">Update Attendee</span>
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
                    <p class="text-muted">Attendee Details Updated Successfully</p>
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
            window.location.href = "manage_attendee.php";
        }, 2000);
    });
    </script>
    <?php } ?>

</body>
</html>
