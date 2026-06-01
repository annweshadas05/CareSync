<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['patient_id'])) {
    header('location:../login.php');
    exit();
}

$id = $_SESSION['patient_id'];

$search_query = "";
$dept_filter = "";
$exp_filter = "1";

$where_clauses = [];
$params = [];
$types = "";

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
    $where_clauses[] = "(full_name LIKE ? OR specialization LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $types .= "ss";
}

if (isset($_GET['department']) && !empty($_GET['department'])) {
    $dept_filter = $_GET['department'];
    $where_clauses[] = "department = ?";
    $params[] = $dept_filter;
    $types .= "s";
}

if (isset($_GET['experience']) && !empty($_GET['experience'])) {
    $exp_filter = $_GET['experience'];
    $where_clauses[] = "experience >= ?";
    $params[] = $exp_filter;
    $types .= "i";
}

$doctor_query = "SELECT * FROM doctors";
if (count($where_clauses) > 0) {
    $doctor_query .= " WHERE " . implode(" AND ", $where_clauses);
}

$stmt_doc = $conn->prepare($doctor_query);
if (!empty($params)) {
    $stmt_doc->bind_param($types, ...$params);
}
$stmt_doc->execute();
$doctor_result = $stmt_doc->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | Search Doctor</title>
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/patient_dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../styles/search_doctor.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
</head>
<body>

    <div class="sidebar shadow">
        <div class="text-center mb-5">
            <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
            <h4 class="text-white fw-bold mt-2" style="font-family: 'Custom';">CareSync</h4>
        </div>
        
        <nav class="nav flex-column">
            <a class="nav-link" href="./patient_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
            <a class="nav-link active" href="./search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="medical_records.php"><i data-lucide="pill"></i> <span>Prescriptions</span></a>
            <a class="nav-link" href="medical_records.php"><i data-lucide="file-text"></i> <span>Health Reports</span></a>
             <a href="../logout.php" class="nav-link logout-link mt-auto">
        <i data-lucide="log-out"></i> <span>Logout</span>
    </a>
        </nav>
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Search Doctor</h2>
                <p class="text-muted">Find and book appointments with our top specialists</p>
            </div>
            <div class="glass-card p-2 px-3 d-flex align-items-center gap-3 shadow-sm">
                <i data-lucide="bell" size="20" class="text-primary"></i>
                <div class="vr"></div>
                <img src="../icons/crowd.png" width="35" class="rounded-circle shadow-sm">
            </div>
        </div>

        <div class="container-fluid py-2">

            <!-- Search & Filter Card -->
            <div class="card shadow-sm border-0 p-4 mb-5 search-card rounded-4">
                <form method="GET" action="">
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-primary text-white border-primary">
                            <i data-lucide="search"></i>
                        </span>
                        <input type="text" name="search" class="form-control form-control-lg border-primary" placeholder="Search by name or specialization" value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>

                    <div class="row g-4 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Department</label>
                            <select name="department" class="form-select form-select-lg">
                                <option value="">Select Department</option>
                                <option value="Cardiology" <?php echo ($dept_filter == 'Cardiology') ? 'selected' : ''; ?>>Cardiology</option>
                                <option value="Neurology" <?php echo ($dept_filter == 'Neurology') ? 'selected' : ''; ?>>Neurology</option>
                                <option value="Orthopedics" <?php echo ($dept_filter == 'Orthopedics') ? 'selected' : ''; ?>>Orthopedics</option>
                                <option value="Gynecology" <?php echo ($dept_filter == 'Gynecology') ? 'selected' : ''; ?>>Gynecology</option>
                                <option value="Pediatrics" <?php echo ($dept_filter == 'Pediatrics') ? 'selected' : ''; ?>>Pediatrics</option>
                                <option value="General Medicine" <?php echo ($dept_filter == 'General Medicine') ? 'selected' : ''; ?>>General Medicine</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                Minimum Experience: <span id="expValue"><?php echo htmlspecialchars($exp_filter ?: '1'); ?></span> Years
                            </label>
                            <input type="range" name="experience" class="form-range" min="1" max="30" value="<?php echo htmlspecialchars($exp_filter ?: '1'); ?>"
                                oninput="document.getElementById('expValue').innerText = this.value">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Doctor Results -->
            <div class="row g-4">
                <?php if ($doctor_result->num_rows > 0): ?>
                    <?php while($row = $doctor_result->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card doctor-card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                                            <i data-lucide="user"></i>
                                        </div>
                                    </div>
                                    <h5 class="fw-bold text-primary mb-1">Dr. <?php echo htmlspecialchars($row['full_name']); ?></h5>
                                    <p class="text-muted mb-2 fw-medium">
                                        <?php echo htmlspecialchars($row['department']); ?> | <?php echo htmlspecialchars($row['experience']); ?> Years Exp
                                    </p>
                                    <?php if(!empty($row['specialization'])): ?>
                                        <p class="text-secondary small mb-3"><?php echo htmlspecialchars($row['specialization']); ?></p>
                                    <?php endif; ?>
                                    <span class="badge bg-warning text-dark mb-4 py-2 px-3 rounded-pill fw-bold">
                                        ⭐ 4.8 Rating
                                    </span>

                                    <div class="d-grid gap-2 mt-auto">
                                        <a href="./view_doctor.php?id=<?php echo urlencode($row['doctor_code']); ?>" class="btn btn-outline-primary rounded-pill fw-bold py-2">
                                            View Profile
                                        </a>
                                        <a href="./book_appointment.php?doctor_id=<?php echo urlencode($row['id']); ?>" class="btn btn-primary rounded-pill fw-bold py-2">
                                            Check Availability
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i data-lucide="search-x" size="64" class="text-muted mb-3 opacity-50"></i>
                        <h4 class="text-muted fw-bold">No doctors found</h4>
                        <p class="text-secondary">Try adjusting your search criteria</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
    
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>