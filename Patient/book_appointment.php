<?php
include __DIR__ . '/../dbconnect.php';
session_start();

if (!isset($_SESSION['patient_id'])) {
    header('location:../login.php');
    exit();
}

$patient_id = $_SESSION['patient_id'];

$selected_doctor_id = isset($_GET['doctor_id']) ? $_GET['doctor_id'] : '';
$doctors = $conn->query("SELECT * FROM doctors");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - CareSync</title>
    <link href="../Bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/patient_dashboard.css?v=<?php echo time(); ?>">
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
            <a class="nav-link" href="./search_doctor.php"><i data-lucide="search"></i> <span>Search Doctor</span></a>
            <a class="nav-link" href="../appointment_scheduling/appointments.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
            <a class="nav-link" href="medical_records.php"><i data-lucide="pill"></i> <span>Prescriptions</span></a>
            <a class="nav-link" href="medical_records.php"><i data-lucide="file-text"></i> <span>Health Reports</span></a>
            <a href="../logout.php" class="nav-link logout-link">
                <i data-lucide="log-out"></i> <span>Logout</span>
            </a>
        </nav>
    
    </div>

    <div class="main-content">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0">Book Appointment</h2>
                <p class="text-muted">Schedule your visit with our top specialists</p>
            </div>
            <a href="search_doctor.php" class="btn border border-primary text-primary rounded-pill px-4 fw-bold shadow-sm" style="background-color: white;">
                <i data-lucide="arrow-left" class="me-2" style="width: 18px; height: 18px; vertical-align: text-bottom;"></i> Go Back
            </a>
        </div>

        <div class="container-fluid py-2">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="glass-card p-5 border-0 shadow-sm rounded-4 position-relative overflow-hidden" style="background: rgba(255, 255, 255, 0.9);">
                        
                        <!-- Header Graphic -->
                        <div class="position-absolute top-0 end-0 opacity-10" style="transform: translate(20%, -20%); pointer-events: none;">
                            <i data-lucide="stethoscope" size="250" class="text-primary"></i>
                        </div>

                        <div class="mb-5 position-relative z-index-1">
                            <h3 class="fw-bold text-primary mb-1">Schedule Visit</h3>
                            <p class="text-secondary fw-medium">Please fill in the details below to check availability and book a slot.</p>
                        </div>

                        <form action="book_appointment_process.php" method="POST" onsubmit="return validateBooking()" class="position-relative z-index-1">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Select Doctor</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-primary text-primary">
                                        <i data-lucide="user"></i>
                                    </span>
                                    <select name="doctor_id" id="doctor" class="form-select form-select-lg border-primary" required>
                                        <option value="">Choose Doctor</option>
                                        <?php while($row = $doctors->fetch_assoc()) { 
                                            // Check if this doctor should be selected based on GET param
                                            $selected = ($selected_doctor_id == $row['id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $row['id'] ?>" <?= $selected ?>>
                                                Dr <?= htmlspecialchars($row['full_name']) ?> - <?= htmlspecialchars($row['department']) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Appointment Date</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-primary text-primary">
                                        <i data-lucide="calendar-days"></i>
                                    </span>
                                    <input type="date" id="date" name="date" class="form-control form-control-lg border-primary" min="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-bold text-dark mb-3">Available Slots</label>
                                <div id="slots-container">
                                    <div class="form-text mt-3 text-muted"><i data-lucide="info" size="14" class="me-1" style="vertical-align: text-bottom;"></i> Please select a doctor and appointment date to view available time slots.</div>
                                </div>
                            </div>

                            <div class="text-center mt-5">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow border-0 w-100" style="background: var(--blue-gradient);">
                                    <i data-lucide="check-circle" class="me-2" style="vertical-align: text-bottom;"></i> Confirm Appointment
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AJAX SCRIPT -->
    <script>
    document.getElementById("date").addEventListener("change", checkSlots);
    document.getElementById("doctor").addEventListener("change", checkSlots);

    function checkSlots() {
        let doctor = document.getElementById("doctor").value;
        let date = document.getElementById("date").value;
        let container = document.getElementById("slots-container");

        if(doctor && date){
            container.innerHTML = '<div class="spinner-border text-primary spinner-border-sm" role="status"></div> <span class="ms-2 fw-bold text-primary">Loading available slots...</span>';
            
            fetch(`get_available_slots.php?doctor_id=${doctor}&date=${date}`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                if(window.lucide) {
                    lucide.createIcons();
                }
            })
            .catch(err => {
                container.innerHTML = '<div class="text-danger fw-bold">Failed to load slots. Please try again.</div>';
            });
        }
    }

    // Prevent wrong booking
    function validateBooking(){
        let slot = document.querySelector("input[name='slot_id']:checked");
        if(!slot) {
            alert("Please select a time slot to proceed.");
            return false;
        }

        return true;
    }

    // Trigger slot check on load if doctor is pre-selected and date happens to be filled (e.g. from browser cache)
    window.addEventListener('DOMContentLoaded', () => {
        if(document.getElementById("doctor").value && document.getElementById("date").value) {
           checkSlots();
        }
    });
    </script>

    <script>
        lucide.createIcons();
    </script>
    <script src="../Bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>