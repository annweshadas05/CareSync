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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pc = trim($_POST['patient_code'] ?? '');
    if (empty($pc)) {
        $error = "Patient code is required.";
    } else {
        try {
            $patientCheck = $conn->prepare("SELECT id FROM patients WHERE patient_code = ? LIMIT 1");
            $patientCheck->bind_param("s", $pc);
            $patientCheck->execute();
            $patientCheck->store_result();

            if ($patientCheck->num_rows === 0) {
                $error = "Patient not found. Please verify the patient code.";
                $patientCheck->close();
            } else {
                $patientCheck->close();

                // Check if vitals table exists to avoid silent failure
                $checkTable = $conn->query("SHOW TABLES LIKE 'vitals'");
                if ($checkTable->num_rows == 0) {
                    throw new Exception("The 'vitals' table does not exist in the database. Please run the migration script.");
                }

                // Handle optional fields: set to NULL if empty string
                $bp   = !empty($_POST['blood_pressure']) ? trim($_POST['blood_pressure']) : null;
                $hr   = !empty($_POST['heart_rate']) ? (int)$_POST['heart_rate'] : null;
                $temp = !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null;
                $rr   = !empty($_POST['respiratory_rate']) ? (int)$_POST['respiratory_rate'] : null;
                $spo2 = !empty($_POST['oxygen_saturation']) ? (float)$_POST['oxygen_saturation'] : null;
                $bs   = !empty($_POST['blood_sugar']) ? (float)$_POST['blood_sugar'] : null;
                $wt   = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
                $ht   = !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null;
                $notes= !empty($_POST['notes']) ? trim($_POST['notes']) : null;

                if (isset($_POST['update_vitals'])) {
                    if (empty($_POST['vital_id'])) {
                        $error = "No existing vitals record selected for update.";
                    } else {
                        $vid = (int)$_POST['vital_id'];
                        $checkVital = $conn->prepare("SELECT id FROM vitals WHERE id = ? AND patient_code = ? LIMIT 1");
                        $checkVital->bind_param("is", $vid, $pc);
                        $checkVital->execute();
                        $checkVital->store_result();
                        if ($checkVital->num_rows === 0) {
                            $error = "No existing vitals found to update for this patient.";
                        }
                        $checkVital->close();
                    }

                    if (!$error) {
                        $upd = $conn->prepare("\n                    UPDATE vitals SET \n                    patient_code = ?, attendee_code = ?, blood_pressure = ?, heart_rate = ?, temperature = ?, \n                    respiratory_rate = ?, oxygen_saturation = ?, blood_sugar = ?, weight_kg = ?, height_cm = ?, notes = ?\n                    WHERE id = ?\n                ");
                        $upd->bind_param("sssididdddsi", $pc, $attendee_code, $bp, $hr, $temp, $rr, $spo2, $bs, $wt, $ht, $notes, $vid);
                        if ($upd->execute()) {
                            $success = "Vitals updated successfully for patient <strong>" . htmlspecialchars($pc) . "</strong>.";
                            $_POST = array();
                            $pc = $bp = $hr = $temp = $rr = $spo2 = $bs = $wt = $ht = $notes = null;
                        } else {
                            $error = "Failed to update vitals: " . $upd->error;
                        }
                        $upd->close();
                    }
                } else {
                    $ins = $conn->prepare("\n                    INSERT INTO vitals \n                    (patient_code, attendee_code, blood_pressure, heart_rate, temperature, \n                     respiratory_rate, oxygen_saturation, blood_sugar, weight_kg, height_cm, notes) \n                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n                ");
                    $ins->bind_param("sssididddds", $pc, $attendee_code, $bp, $hr, $temp, $rr, $spo2, $bs, $wt, $ht, $notes);
                    if ($ins->execute()) {
                        $success = "Vitals recorded successfully for patient <strong>" . htmlspecialchars($pc) . "</strong>.";
                        $_POST = array();
                        $pc = $bp = $hr = $temp = $rr = $spo2 = $bs = $wt = $ht = $notes = null;
                    } else {
                        $error = "Failed to record vitals: " . $ins->error;
                    }
                    $ins->close();
                }
            }
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CareSync | Enter Vitals</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .lookup-message { margin-top: 10px; font-size: 0.94rem; }
        .lookup-message.success { color: #0f5132; }
        .lookup-message.error { color: #842029; }
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
            <h2 class="fw-bold mb-0">Record Patient Vitals</h2>
            <p class="text-muted">Enter the patient code and clinical parameters below.</p>
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
        <input type="hidden" name="vital_id" id="vital_id" value="">
        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="user" width="14" class="me-1"></i>Patient Identification</p>
            <div class="row g-4">
                <div class="col-md-6 vital-field">
                    <label for="patient_code">Patient Code <span class="unit-badge">Required</span></label>
                    <input type="text" id="patient_code" name="patient_code" 
                           placeholder="e.g. PAT-2026-002" required
                           value="<?= htmlspecialchars($_POST['patient_code'] ?? '') ?>">
                    <div id="patientLookupMessage" class="lookup-message"></div>
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="heart-pulse" width="14" class="me-1"></i>Cardiovascular</p>
            <div class="row g-4">
                <div class="col-md-4 vital-field">
                    <label for="blood_pressure">Blood Pressure <span class="unit-badge">mmHg</span></label>
                    <input type="text" id="blood_pressure" name="blood_pressure" placeholder="120/80">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="heart_rate">Heart Rate <span class="unit-badge">bpm</span></label>
                    <input type="number" id="heart_rate" name="heart_rate" min="20" max="300" placeholder="72">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="oxygen_saturation">Oxygen Saturation (SpO₂) <span class="unit-badge">%</span></label>
                    <input type="number" id="oxygen_saturation" name="oxygen_saturation" step="0.1" min="50" max="100" placeholder="98.0">
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="thermometer" width="14" class="me-1"></i>Respiratory & Temperature</p>
            <div class="row g-4">
                <div class="col-md-4 vital-field">
                    <label for="temperature">Temperature <span class="unit-badge">°C</span></label>
                    <input type="number" id="temperature" name="temperature" step="0.1" min="30" max="45" placeholder="37.0">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="respiratory_rate">Respiratory Rate <span class="unit-badge">/min</span></label>
                    <input type="number" id="respiratory_rate" name="respiratory_rate" min="1" max="100" placeholder="16">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="blood_sugar">Blood Sugar <span class="unit-badge">mg/dL</span></label>
                    <input type="number" id="blood_sugar" name="blood_sugar" step="0.1" min="0" placeholder="95.0">
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="scale" width="14" class="me-1"></i>Anthropometrics</p>
            <div class="row g-4">
                <div class="col-md-4 vital-field">
                    <label for="weight_kg">Weight <span class="unit-badge">kg</span></label>
                    <input type="number" id="weight_kg" name="weight_kg" step="0.1" min="0" placeholder="70.0">
                </div>
                <div class="col-md-4 vital-field">
                    <label for="height_cm">Height <span class="unit-badge">cm</span></label>
                    <input type="number" id="height_cm" name="height_cm" step="0.1" min="0" placeholder="170.0">
                </div>
            </div>
        </div>

        <div class="vitals-card mb-4">
            <p class="section-title"><i data-lucide="file-text" width="14" class="me-1"></i>Clinical Notes</p>
            <div class="vital-field">
                <label for="notes">Notes / Observations</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Any additional observations or symptoms..."></textarea>
            </div>
        </div>

        <div class="d-flex gap-3 align-items-center">
            <button type="submit" name="save_vitals" id="saveBtn" class="btn-save">
                <i data-lucide="save" width="16" class="me-2"></i>Save Vitals
            </button>
            <button type="submit" name="update_vitals" id="updateBtn" class="btn-save" style="display: none;" disabled>
                <i data-lucide="refresh-cw" width="16" class="me-2"></i>Update Vitals
            </button>
            <a href="attendee_dashboard.php" class="btn btn-light rounded-3 px-4 py-2 fw-600">Cancel</a>
        </div>
    </form>

</div>

<script src="../Bootstrap/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    const messageEl = document.getElementById('patientLookupMessage');
    const saveBtn = document.getElementById('saveBtn');
    const updateBtn = document.getElementById('updateBtn');

    function setLookupMessage(text, type) {
        messageEl.textContent = text;
        messageEl.className = 'lookup-message ' + (type === 'error' ? 'error' : 'success');
    }

    document.getElementById('patient_code').addEventListener('blur', function() {
        const pc = this.value.trim();
        if (pc) {
            fetch(`api_fetch_vitals.php?patient_code=${encodeURIComponent(pc)}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('vital_id').value = '';
                    document.getElementById('blood_pressure').value = '';
                    document.getElementById('heart_rate').value = '';
                    document.getElementById('oxygen_saturation').value = '';
                    document.getElementById('temperature').value = '';
                    document.getElementById('respiratory_rate').value = '';
                    document.getElementById('blood_sugar').value = '';
                    document.getElementById('weight_kg').value = '';
                    document.getElementById('height_cm').value = '';
                    document.getElementById('notes').value = '';

                    if (!data.patient_found) {
                        setLookupMessage('Patient not found. Please verify the patient code.', 'error');
                        saveBtn.style.display = 'inline-block';
                        saveBtn.disabled = true;
                        updateBtn.style.display = 'none';
                        updateBtn.disabled = true;
                        return;
                    }

                    if (data.vitals_found && data.data) {
                        const v = data.data;
                        document.getElementById('vital_id').value = v.id || '';
                        document.getElementById('blood_pressure').value = v.blood_pressure || '';
                        document.getElementById('heart_rate').value = v.heart_rate || '';
                        document.getElementById('oxygen_saturation').value = v.oxygen_saturation || '';
                        document.getElementById('temperature').value = v.temperature || '';
                        document.getElementById('respiratory_rate').value = v.respiratory_rate || '';
                        document.getElementById('blood_sugar').value = v.blood_sugar || '';
                        document.getElementById('weight_kg').value = v.weight_kg || '';
                        document.getElementById('height_cm').value = v.height_cm || '';
                        document.getElementById('notes').value = v.notes || '';

                        setLookupMessage('Patient found and vitals loaded. You can update the existing record.', 'success');
                        saveBtn.style.display = 'none';
                        saveBtn.disabled = true;
                        updateBtn.style.display = 'inline-block';
                        updateBtn.disabled = false;
                    } else {
                        setLookupMessage('Patient found. No vitals record exists yet, please fill and save.', 'success');
                        saveBtn.style.display = 'inline-block';
                        saveBtn.disabled = false;
                        updateBtn.style.display = 'none';
                        updateBtn.disabled = true;
                    }
                })
                .catch(err => {
                    console.error("Failed to fetch vitals:", err);
                    setLookupMessage('Unable to validate patient code right now. Please try again.', 'error');
                });
        } else {
            setLookupMessage('', '');
            saveBtn.disabled = false;
            updateBtn.style.display = 'none';
            updateBtn.disabled = true;
        }
    });
</script>
</body>
</html>
