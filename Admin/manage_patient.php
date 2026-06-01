<?php
require "../dbconnect.php";
session_start();

if (!isset($_SESSION['email'])) {
    header('location:../login.php');
    exit();
}

/* SEARCH LOGIC */
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql = "SELECT * FROM patients 
            WHERE patient_code LIKE '%$search%' 
            OR full_name LIKE '%$search%' 
            OR mobile LIKE '%$search%'
            ORDER BY patient_code DESC";
} else {
    $sql = "SELECT * FROM patients ORDER BY patient_code DESC";
}
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Manage Patients</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>
<body class="admin-bg">

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2 text-white">CareSync</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link active" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
            <a class="nav-link" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
            <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a href="../logout.php" class="nav-link logout-link">
                <i data-lucide="log-out"></i> <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Patient Management</h2>
                <p class="text-muted">Maintain and monitor patient registration records</p>
            </div>
            <a href="add_patient.php" class="action-btn">
                <i data-lucide="user-plus" class="me-1"></i> Register Patient
            </a>
        </div>

        <div class="glass-card mb-4 p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small fw-bold text-muted">SEARCH DIRECTORY</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-start-pill px-3">
                            <i data-lucide="search" size="18" class="text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 rounded-end-pill px-3" 
                               placeholder="Search by Patient Code, Name, or Mobile..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">
                        Find Patient
                    </button>
                </div>
            </form>
        </div>

        <div class="glass-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Patient Code</th>
                            <th>Patient Details</th>
                            <th>DOB / Gender</th>
                            <th>Contact</th>
                            <th>Location</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4"><span class="badge bg-primary-soft text-primary"><?php echo $row['patient_code']; ?></span></td>
                                    <td>
                                        <div class="fw-bold"><?php echo $row['full_name']; ?></div>
                                        <small class="text-muted text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Registered User</small>
                                    </td>
                                    <td>
                                        <div class="small fw-medium"><?php echo $row['dob']; ?></div>
                                        <div class="small text-muted"><?php echo $row['gender']; ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i data-lucide="phone" size="14" class="text-muted"></i>
                                            <span class="small"><?php echo $row['mobile']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i data-lucide="map-pin" size="14" class="text-muted"></i>
                                            <span class="small"><?php echo $row['city']; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="view_patient.php?id=<?php echo $row['patient_code']; ?>" 
                                               class="btn-icon text-primary" title="View Profile">
                                                <i data-lucide="user-circle" size="20"></i>
                                            </a>
                                            <a href="edit_patient.php?id=<?php echo $row['patient_code']; ?>" 
                                               class="btn-icon text-success mx-2" title="Edit">
                                                <i data-lucide="edit" size="20"></i>
                                            </a>
                                            <button class="btn-icon text-danger border-0 bg-transparent" 
                                                    onclick="setDeleteId('<?php echo $row['patient_code']; ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal" title="Delete">
                                                <i data-lucide="trash-2" size="20"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i data-lucide="search-x" size="40" class="text-muted mb-2"></i>
                                    <p class="text-muted">No patient records found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-body text-center p-5">
                    <div class="icon-box bg-danger-soft text-danger mx-auto mb-4" style="width:70px; height:70px;">
                        <i data-lucide="user-minus" size="40"></i>
                    </div>
                    <h4 class="fw-bold">Remove Patient?</h4>
                    <p class="text-muted">Are you sure you want to delete this patient profile? This will remove all associated history.</p>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <a id="deleteBtn" class="btn btn-danger rounded-pill px-4">Delete Permanently</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function setDeleteId(id) {
            document.getElementById("deleteBtn").href = "delete_patient.php?id=" + id;
        }
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>