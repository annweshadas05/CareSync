<?php
session_start();
require_once '../dbconnect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['attendee_id'])) {
    header('location:../login.php');
    exit();
}

$attendee_code = $_SESSION['attendee_id'];
$success = $error = null;

$vital_id = $_GET['id'] ?? null;
if (!$vital_id) {
    header('location:attendee_dashboard.php');
    exit();
}

// Fetch existing record
$stmt = $conn->prepare("SELECT * FROM vitals WHERE id = ?");
$stmt->bind_param("i", $vital_id);
$stmt->execute();
$vital = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vital) {
    echo "<script>alert('Vital record not found.'); window.location.href='attendee_dashboard.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = trim($_POST['patient_code'] ?? '');
    if (empty($pc)) {
        $error = "Patient code is required.";
    } else {
        try {
            $bp   = !empty($_POST['blood_pressure']) ? trim($_POST['blood_pressure']) : null;
            $hr   = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
            $temp = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
            $rr   = !empty($_POST['respiratory_rate']) ? (int)$_POST['respiratory_rate'] : null;
            $spo2 = !empty($_POST['oxygen_saturation']) ? (float)$_POST['oxygen_saturation'] : null;
            $bs   = !empty($_POST['blood_sugar']) ? (float)$_POST['blood_sugar'] : null;
            $wt   = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
            $ht   = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null;
            $notes= !empty($_POST['notes']) ? trim($_POST['notes']) : null;

            $upd = $conn->prepare("
                UPDATE vitals SET 
                patient_code = ?, blood_pressure = ?, heart_rate = ?, temperature = ?, 
                respiratory_rate = ?, oxygen_saturation = ?, blood_sugar = ?, weight_kg = ?, height_cm = ?, notes = ?
                WHERE id = ?
            ");
            
            $upd->bind_param("ssdidddddsi", $pc, $bp, $hr, $temp, $rr, $spo2, $bs, $wt, $ht, $notes, $vital_id);
            
            if ($upd->execute()) {
                $success = "Vitals updated successfully for patient <strong>" . htmlspecialchars($pc) . "</strong>.";
                
                // Refresh $vital to reflect changes
                $stmt = $conn->prepare("SELECT * FROM vitals WHERE id = ?");
                $stmt->bind_param("i", $vital_id);
                $stmt->execute();
                $vital = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $error = "Failed to update vitals: " . $upd->error;
            }
            $upd->close();
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>CareSync | Edit Vitals</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/attendee_dashboard.css?v=<?php echo time(); ?>">
    <script src="../js/lucide.js"></script>
    <style>
        .vitals-card {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,97,255,0.07);
            border: 1px solid rgba(0,97,255,0.08);
        }
        .patient-banner {
            background: linear-gradient(135deg,#0061ff 0%,#061727 100%);
            border-radius: 16px;
            padding: 22px 28px;
            color: #fff;
            margin-bottom: 28px;
        }
        .vital-field label { font-weight: 800; font-size: 0.92rem; color: #111827; margin-bottom: 7px; display: block; letter-spacing: 0.01em; }
        .vital-field input, .vital-field textarea {
            border-radius: 10px;
            border: 2px solid #cbd5e1;
            padding: 11px 14px;
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
            width: 100%;
            transition: border 0.2s;
            background: #f8fafc;
        }
        .vital-field input::placeholder, .vital-field textarea::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        .vital-field input:focus, .vital-field textarea:focus {
            border-color: #0061ff;
            outline: none;
            background: #fff;
            color: #111827;
        }
        .unit-badge {
            display: inline-block;
            background: rgba(0,97,255,0.08);
            color: #0061ff;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            padding: 2px 8px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .btn-save {
            background: linear-gradient(135deg,#0061ff,#003db3);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 12px 36px;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: 0.25s;
        }
        .btn-save:hover { opacity: 0.9; transform: translateY(-2px); }
        .section-title {
            font-size: 0.82rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #0050d0;
            border-bottom: 2px solid rgba(0,97,255,0.2);
            padding-bottom: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .lookup-box {
            background: linear-gradient(135deg,rgba(0,97,255,0.04),rgba(6,23,39,0.04));
            border: 2px dashed rgba(0,97,255,0.25);
            border-radius: 18px;
            padding: 36px;
            text-align: center;
        }
        .lookup-box input {
            border-radius: 12px;
            border: 2px solid #cbd5e1;
            padding: 12px 20px;
            font-size: 1rem;
            width: 300px;
            text-align: center;
            letter-spacing: 1.5px;
            font-weight: 700;
            color: #111827;
        }
        .lookup-box input::placeholder { color: #94a3b8; font-weight: 400; letter-spacing: 0.5px; }
        .lookup-box input:focus { border-color: #0061ff; outline: none; }
        .lookup-box h5 { color: #111827; font-weight: 800; }
        .lookup-box p.text-muted { color: #4b5563 !important; font-weight: 500; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="40" alt="Logo">
        <h4 class="mt-2">CareSync</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="attendee_dashboard.php"><i data-lucide="layout-dashboard"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="../prescription_reader/php-test-app/index.php"><i data-lucide="file-up"></i> <span>Upload Records</span></a>
        <a class="nav-link active" href="enter_vitals.php"><i data-lucide="activity"></i> <span>Enter Vitals</span></a>
        <a href="../logout.php" class="nav-link logout-link"><i data-lucide="log-out"></i> <span>Logout</span></a>
    </nav>
</div>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Edit Patient Vitals</h2>
            <p class="text-muted">Update the patient code and clinical parameters below.</p>
        </div>
        <div class="glass-card py-2 px-3 d-flex align-items-center gap-3">
            <i data-lucide="activity" size="20" class="text-primary"></i>
            <img src="../icons/medical-staff.png" width="35" height="35" class="rounded-circle border">
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i data-lucide="check-circle" width="18" class="me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i data-lucide="alert-circle" width="18" class="me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="vitalsForm">
        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="user" width="14" class="me-1"></i>Patient Identification</p>
            <div class="row g-4">
                <div class="col-md-6 vital-field">
                    <label for="patient_code">Patient Code <span class="unit-badge">Required</span></label>
                    <input type="text" id="patient_code" name="patient_code" 
                           placeholder="e.g. PAT-2026-002" required
                           value="<?= htmlspecialchars($_POST['patient_code'] ?? $vital['patient_code'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="heart-pulse" width="14" class="me-1"></i>Cardiovascular</p>
            <div class="row g-4">
                <div class="col-md-4 vital-field">
                    <label for="blood_pressure">Blood Pressure <span class="unit-badge">mmHg</span></label>
                    <input type="text" id="blood_pressure" name="blood_pressure" placeholder="120/80" value="<?= htmlspecialchars($_POST['blood_pressure'] ?? $vital['blood_pressure'] ?? '') ?>">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="heart_rate">Heart Rate <span class="unit-badge">bpm</span></label>
                    <input type="number" id="heart_rate" name="heart_rate" min="20" max="300" placeholder="72" value="<?= htmlspecialchars($_POST['heart_rate'] ?? $vital['heart_rate'] ?? '') ?>">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="oxygen_saturation">Oxygen Saturation (SpO₂) <span class="unit-badge">%</span></label>
                    <input type="number" id="oxygen_saturation" name="oxygen_saturation" step="0.1" min="50" max="100" placeholder="98.0" value="<?= htmlspecialchars($_POST['oxygen_saturation'] ?? $vital['oxygen_saturation'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="thermometer" width="14" class="me-1"></i>Respiratory & Temperature</p>
            <div class="row g-4">
                <div class="col-md-4 vital-field">
                    <label for="temperature">Temperature <span class="unit-badge">°C</span></label>
                    <input type="number" id="temperature" name="temperature" step="0.1" min="30" max="45" placeholder="37.0" value="<?= htmlspecialchars($_POST['temperature'] ?? $vital['temperature'] ?? '') ?>">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="respiratory_rate">Respiratory Rate <span class="unit-badge">/min</span></label>
                    <input type="number" id="respiratory_rate" name="respiratory_rate" min="1" max="100" placeholder="16" value="<?= htmlspecialchars($_POST['respiratory_rate'] ?? $vital['respiratory_rate'] ?? '') ?>">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="blood_sugar">Blood Sugar <span class="unit-badge">mg/dL</span></label>
                    <input type="number" id="blood_sugar" name="blood_sugar" step="0.1" min="0" placeholder="95.0" value="<?= htmlspecialchars($_POST['blood_sugar'] ?? $vital['blood_sugar'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="scale" width="14" class="me-1"></i>Anthropometrics</p>
            <div class="row g-4">
                <div class="col-md-4 vital-field">
                    <label for="weight_kg">Weight <span class="unit-badge">kg</span></label>
                    <input type="number" id="weight_kg" name="weight_kg" step="0.1" min="0" placeholder="70.0" value="<?= htmlspecialchars($_POST['weight_kg'] ?? $vital['weight_kg'] ?? '') ?>">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="height_cm">Height <span class="unit-badge">cm</span></label>
                    <input type="number" id="height_cm" name="height_cm" step="0.1" min="0" placeholder="170.0" value="<?= htmlspecialchars($_POST['height_cm'] ?? $vital['height_cm'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="file-text" width="14" class="me-1"></i>Clinical Notes</p>
            <div class="vital-field">
                <label for="notes">Notes / Observations</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Any additional observations or symptoms..."><?= htmlspecialchars($_POST['notes'] ?? $vital['notes'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <button type="submit" name="save_vitals" class="btn-save">
                <i data-lucide="save" width="16" class="me-2"></i>Update Vitals
            </button>
            <a href="attendee_dashboard.php" class="btn btn-light rounded-3 px-4 py-2 fw-600">Cancel</a>
        </div>
    </form>

</div>

<script src="../Bootstrap/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();
</script>
</body>
</html>
