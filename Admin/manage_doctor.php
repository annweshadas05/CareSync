<?php
require "../dbconnect.php";
session_start();

if (!isset($_SESSION['email'])) {
    header('location:../login.php');
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$department = isset($_GET['department']) ? trim($_GET['department']) : "";

$sql = "SELECT * FROM doctors WHERE 1";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (doctor_code LIKE '%$search%' OR full_name LIKE '%$search%')";
}

if (!empty($department)) {
    $department = $conn->real_escape_string($department);
    $sql .= " AND LOWER(TRIM(department)) = LOWER(TRIM('$department'))";
}

$sql .= " ORDER BY doctor_code DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Manage Doctors</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/admin_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="mt-2">CareSync</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
            <a class="nav-link active" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
            <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
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
                <h2 class="fw-bold mb-0">Doctor Management</h2>
                <p class="text-muted">View, search, and manage healthcare professionals</p>
            </div>
            <a href="add_doctor.php" class="action-btn">
                <i data-lucide="plus-circle" class="me-1"></i> Add New Doctor
            </a>
        </div>

        <div class="glass-card mb-4 p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">SEARCH</label>
                    <input type="text" name="search" class="form-control rounded-pill px-3" 
                           placeholder="Name or Doctor ID..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">DEPARTMENT</label>
                    <select name="department" class="form-select rounded-pill">
                        <option value="">All Departments</option>
                        <?php 
                        $depts = ["Cardiology", "Neurology", "Orthopedics", "Gynecology", "Pediatrics", "General Medicine"];
                        foreach($depts as $d) {
                            $sel = ($department == $d) ? "selected" : "";
                            echo "<option value='$d' $sel>$d</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">
                        <i data-lucide="search" size="18" class="me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <div class="glass-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Doctor ID</th>
                            <th>Doctor Info</th>
                            <th>Department</th>
                            <th>Experience</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?php echo $row['doctor_code']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="icon-box bg-light text-primary" style="width:40px; height:40px;">
                                                <i data-lucide="user" size="18"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo $row['full_name']; ?></div>
                                                <small class="text-muted"><?php echo $row['specialization']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-info-soft text-info px-3">
                                            <?php echo $row['department']; ?>
                                        </span>
                                    </td>
                                    <td><span class="fw-medium"><?php echo $row['experience']; ?> Years</span></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="view_doctor.php?id=<?php echo $row['doctor_code']; ?>" 
                                               class="btn btn-sm btn-light rounded-circle me-2" title="View">
                                                <i data-lucide="eye" size="18" class="text-primary"></i>
                                            </a>
                                            <a href="edit_doctor.php?id=<?php echo $row['doctor_code']; ?>" 
                                               class="btn btn-sm btn-light rounded-circle me-2" title="Edit">
                                                <i data-lucide="edit-3" size="18" class="text-success"></i>
                                            </a>
                                            <button class="btn btn-sm btn-light rounded-circle" 
                                                    onclick="setDeleteId('<?php echo $row['doctor_code']; ?>')"
                                                    data-bs-toggle="modal" data-bs-target="#deleteModal">
                                                <i data-lucide="trash-2" size="18" class="text-danger"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No doctors found matching your criteria.</td>
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
                        <i data-lucide="alert-triangle" size="40"></i>
                    </div>
                    <h4 class="fw-bold">Confirm Deletion</h4>
                    <p class="text-muted">Are you sure you want to remove this doctor? This action cannot be undone.</p>
                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <a id="deleteBtn" class="btn btn-danger rounded-pill px-4">Delete Doctor</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function setDeleteId(id) {
            document.getElementById("deleteBtn").href = "delete_doctor.php?id=" + id;
        }
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>