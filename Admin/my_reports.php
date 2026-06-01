<?php
session_start();
require_once '../dbconnect.php';

if (!isset($_SESSION['email'])) {
    header('location:../login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('location:manage_patient.php');
    exit();
}

$patient_code = $_GET['id'];
$qry  = "SELECT * FROM patients WHERE patient_code = ?";
$stmt = $conn->prepare($qry);
$stmt->bind_param("s", $patient_code);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) die("Patient not found.");

$vqry  = "SELECT * FROM vitals WHERE patient_code = ? ORDER BY recorded_at DESC";
$vstmt = $conn->prepare($vqry);
$vstmt->bind_param("s", $patient_code);
$vstmt->execute();
$vitals_result = $vstmt->get_result();
$vstmt->close();

$pqry  = "SELECT * FROM prescription_medicines WHERE patient_code = ? ORDER BY created_at DESC";
$pstmt = $conn->prepare($pqry);
$pstmt->bind_param("s", $patient_code);
$pstmt->execute();
$prescriptions = $pstmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pstmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync | My Health Reports</title>
    <meta name="description" content="View your personal health reports and vital records on CareSync.">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/patient_dashboard.css">
    <script src="../js/lucide.js"></script>
    <style>
       :root {
    --primary-blue: #0061ff;
    --navy-dark: #061727;
    --sidebar-w: 260px;
    --blue-gradient: linear-gradient(135deg, var(--primary-blue) 0%, var(--navy-dark) 100%);
     }

        .report-hero {
            background: var(--blue-gradient);
            border-radius: 22px;
            padding: 36px 40px;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 32px;
        }
        .report-hero .bg-icon {
            position: absolute;
            right: -20px;
            bottom: -30px;
            opacity: 0.08;
        }
        .tab-btn {
            border: none;
            background: none;
            padding: 10px 22px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #6b7280;
            cursor: pointer;
            transition: 0.2s;
        }
        .tab-btn.active {
            background: var(--blue-gradient);
            color: #fff;
            box-shadow: 0 4px 14px #061727;
        }
        .vitals-entry {
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            border: 1px solid rgba(13,148,136,0.12);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .vitals-entry:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(13,148,136,0.12);
        }
        .vitals-header {
            background: rgba(0, 97, 255, 0.05);
            padding: 14px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 97, 255, 0.1);
        }
        .vitals-header .date-badge {
            background: var(--blue-gradient);
            color: #fff;
            border-radius: 8px;
            padding: 4px 12px;
            font-size: 0.8rem;
            font-weight: 800;
        }
        .vitals-body {
            padding: 20px 22px;
        }
        .vital-chip {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 12px 18px;
            min-width: 110px;
            margin: 6px;
            text-align: center;
        }
        .vital-chip .val {
            font-size: 1.25rem;
            font-weight: 800;
            color: #061727;
            line-height: 1.1;
        }
        .vital-chip .lbl {
            font-size: 0.7rem;
            font-weight: 800;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 4px;
        }
        .vital-chip .unit {
            font-size: 0.72rem;
            font-weight: 700;
            color: #374151;
        }
        .notes-box {
            background: #f8fafc;
            border-left: 4px solid #061727;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 0.92rem;
            font-weight: 700;
            color: #111827;
            margin-top: 14px;
            border: 1px solid #e2e8f0;
            border-left-width: 5px;
        }
        .presc-card {
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            border: 1px solid rgba(13,148,136,0.12);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .presc-card:hover { transform: translateY(-3px); }
        .presc-header {
            background: rgba(0, 97, 255, 0.05);
            padding: 14px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .presc-table th {
            color: #061727;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 10px 14px;
            background: #f0fdfa;
            border-bottom: 2px solid rgba(13,148,136,0.12);
        }
        .presc-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.88rem;
        }
        .print-btn {
            background: var(--blue-gradient);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .print-btn:hover { opacity: 0.88; color: #fff; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        .empty-state i { opacity: 0.3; margin-bottom: 14px; }
        @media print { .sidebar, .print-btn, .tab-bar { display:none!important; } }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="text-center mb-5">
        <img src="../Assets/CareSyncLogo.png" width="45" alt="Logo">
        <h4 class="text-white fw-bold mt-2" style="font-family:'Custom';">CareSync</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link active" href="admin_dashboard.php"><i data-lucide="layout-grid"></i> <span>Dashboard</span></a>
        <a class="nav-link" href="manage_doctor.php"><i data-lucide="user-cog"></i> <span>Doctors</span></a>
        <a class="nav-link" href="manage_patient.php"><i data-lucide="users"></i> <span>Patients</span></a>
        <a class="nav-link" href="manage_attendee.php"><i data-lucide="user-check"></i> <span>Attendees</span></a>
        <a class="nav-link" href="doctor_schedule.php"><i data-lucide="calendar"></i> <span>Appointments</span></a>
        <a class="nav-link" href="reports.php"><i data-lucide="bar-chart-3"></i> <span>Reports</span></a>
        <a href="../logout.php" class="nav-link logout-link mt-auto">
        <i data-lucide="log-out"></i> <span>Logout</span>
    </a>
    </nav>
   
</div>  

<div class="main-content">

    <div class="report-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white text-success mb-3 px-3 py-2 rounded-pill fw-bold" style="color:#0d9488!important;">
                    Health Dashboard
                </span>
                <h1 class="display-6 fw-bold mb-2">My Health Reports</h1>
                <p class="opacity-75 mb-3">
                    <?= htmlspecialchars($patient['full_name']) ?> &nbsp;•&nbsp;
                    <?= htmlspecialchars($patient['patient_code']) ?> &nbsp;•&nbsp;
                    Blood Group: <strong><?= htmlspecialchars($patient['blood_group'] ?? '—') ?></strong>
                </p>
                    <a href="download_report.php?patient_code=<?= urlencode($patient['patient_code']) ?>" class="print-btn">
                        <i data-lucide="download" width="16"></i> Download Report
                    </a>
                
            </div>
        </div>
    </div>

    <div class="tab-bar d-flex gap-2 mb-4">
        <button class="tab-btn active" id="tab-vitals" onclick="showTab('vitals')">
            <i data-lucide="heart-pulse" width="15" class="me-1"></i> Vital Records
        </button>
        <button class="tab-btn" id="tab-prescriptions" onclick="showTab('prescriptions')">
            <i data-lucide="pill" width="15" class="me-1"></i> Prescriptions
        </button>
    </div>

    <div id="section-vitals">
        <?php if ($vitals_result->num_rows === 0): ?>
            <div class="empty-state">
                <i data-lucide="activity" width="64" height="64"></i>
                <h5 class="fw-bold text-dark mb-2">No Vital Records Yet</h5>
                <p>Your attendee hasn't recorded any vitals yet. Please visit the clinic to get checked.</p>
            </div>
        <?php else: ?>
            <?php while ($v = $vitals_result->fetch_assoc()): ?>
            <div class="vitals-entry">
                <div class="vitals-header">
                    <div>
                        <span class="fw-bold" style="color:#061727;">
                            <i data-lucide="clipboard-heart" width="16" class="me-1"></i>Vitals Check
                        </span>
                        <?php if ($v['attendee_code']): ?>
                            <small class="text-muted ms-2">by <?= htmlspecialchars($v['attendee_code']) ?></small>
                        <?php endif; ?>
                    </div>
                    <span class="date-badge"><?= date('d M Y, h:i A', strtotime($v['recorded_at'])) ?></span>
                </div>
                <div class="vitals-body">
                    <div class="d-flex flex-wrap fw-bold text-dark">
                        <?php
                        $chips = [
                            ['val' => $v['blood_pressure'] ?? '—',       'lbl' => 'Blood Pressure',   'unit' => 'mmHg'],
                            ['val' => $v['heart_rate'] ?? '—',           'lbl' => 'Heart Rate',        'unit' => 'bpm'],
                            ['val' => $v['temperature'] ?? '—',          'lbl' => 'Temperature',       'unit' => '°C'],
                            ['val' => $v['respiratory_rate'] ?? '—',     'lbl' => 'Resp. Rate',        'unit' => '/min'],
                            ['val' => $v['oxygen_saturation'] ?? '—',    'lbl' => 'SpO₂',              'unit' => '%'],
                            ['val' => $v['blood_sugar'] ?? '—',          'lbl' => 'Blood Sugar',       'unit' => 'mg/dL'],
                            ['val' => $v['weight_kg'] ?? '—',            'lbl' => 'Weight',            'unit' => 'kg'],
                            ['val' => $v['height_cm'] ?? '—',            'lbl' => 'Height',            'unit' => 'cm'],
                        ];
                        foreach ($chips as $c):
                            if ($c['val'] === '—' || $c['val'] === null) continue;
                        ?>
                        <div class="vital-chip">
                            <span class="val"><?= htmlspecialchars((string)$c['val']) ?></span>
                            <span class="unit"><?= $c['unit'] ?></span>
                            <span class="lbl"><?= $c['lbl'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($v['notes'])): ?>
                        <div class="notes-box">
                            <strong>Clinical Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($v['notes'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <div id="section-prescriptions" style="display:none;">
        <?php if (empty($prescriptions)): ?>
            <div class="empty-state">
                <i data-lucide="pill" width="64" height="64"></i>
                <h5 class="fw-bold text-dark mb-2">No Prescriptions Found</h5>
                <p>No prescription records have been uploaded for your account yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($prescriptions as $presc): ?>
            <div class="presc-card">
                <div class="presc-header">
                    <span class="fw-bold" style="color:#061727;">
                        <i data-lucide="file-text" width="16" class="me-1"></i>Prescription
                    </span>
                    <span class="date-badge" style="background:var(--blue-gradient);color:#fff;border-radius:8px;padding:4px 12px;font-size:0.8rem;font-weight:800;">
                        <?= date('d M Y', strtotime($presc['created_at'])) ?>
                    </span>
                </div>
                <?php if (!empty($presc['notes'])): ?>
                <div class="px-4 pt-3 pb-0">
                    <div class="notes-box">
                        <strong>Doctor's Notes:</strong><br>
                        <?= nl2br(htmlspecialchars($presc['notes'])) ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="p-3">
                    <table class="presc-table w-100">
                        <thead>
                            <tr>
                                <th>Medicine</th><th>Form</th><th>Dosage</th>
                                <th>Frequency</th><th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($presc['medicine'] ?? '—') ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($presc['form'] ?? '—') ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($presc['dosage'] ?? '—') ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($presc['frequency'] ?? '—') ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($presc['duration'] ?? '—') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script src="../Bootstrap/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    function showTab(tab) {
        document.getElementById('section-vitals').style.display       = tab === 'vitals' ? 'block' : 'none';
        document.getElementById('section-prescriptions').style.display = tab === 'prescriptions' ? 'block' : 'none';
        document.getElementById('tab-vitals').classList.toggle('active', tab === 'vitals');
        document.getElementById('tab-prescriptions').classList.toggle('active', tab === 'prescriptions');
    }
</script>
</body>
</html>
