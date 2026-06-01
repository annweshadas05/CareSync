<?php
// Simple Patient Check (Prepared Statement)
if (isset($_GET['check_patient'])) {
    require_once '../../dbconnect.php';
    $code = $_GET['code'];
    $stmt = $conn->prepare("SELECT id FROM patients WHERE patient_code=?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $res = $stmt->get_result();
    echo ($res->num_rows > 0) ? "valid" : "invalid";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync Attendee | Prescription Reader</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../../Bootstrap/bootstrap.min.css">
    <style>
        .sidebar { width: 260px; background: #061727; padding: 30px 20px; }
        .nav-item { padding: 12px 20px; color: #fff; text-decoration: none; display: block; border-radius: 8px; margin-bottom: 5px; }
        .nav-item.active { background:linear-gradient(135deg, #0061ff 0%, #061727 100%); }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="text-center mb-5">
        <img src="../../Assets/CareSyncLogo.png" width="50" alt="Logo">
        <h4 class="text-white mt-2">CareSync</h4>
    </div>   
    <a href="../../Attendee/attendee_dashboard.php" class="nav-item">Patient Records</a>
    <a href="index.php" class="nav-item active">Prescription Reader</a>
</div>

<div class="main-layout">
    <header class="top-header">
        <h1>Prescription Analyzer</h1>
        <div class="loader" id="loader" style="display:none;"><span>AI Analyzing...</span></div>
    </header>

    <div class="glass-card mb-4">
        <h2>Patient Details</h2>
        <input type="text" id="patientCodeInput" placeholder="Enter Patient Code" class="form-control" style="max-width: 300px;">
    </div>

    <div class="glass-card">
        <div class="file-drop-area" id="dropArea" onclick="document.getElementById('prescriptionFile').click()">
            <p>Drag prescription images here or <strong>browse files</strong></p>
            <p id="fileName" style="color: #0061ff;"></p>
            <input type="file" id="prescriptionFile" accept=".jpg, .jpeg, .png, .pdf" style="display: none;">
        </div>
        <div id="imagePreviewContainer" style="display:none; text-align:center;">
            <img id="prescriptionPreview" src="" style="max-width:100%; border-radius:10px; margin-top:10px;">
        </div>
        <button id="uploadBtn" class="btn btn-primary mt-3" onclick="analyzePrescription()" disabled>Run Analysis</button>
    </div>

    <div class="results-section" id="resultsSection" style="display: none;">
        <div class="glass-card">
            <h2>Extracted Data</h2>
            <table class="table" id="medicinesTable">
                <thead><tr><th>Medicine</th><th>Form</th><th>Dosage</th><th>Frequency</th><th>Duration</th></tr></thead>
                <tbody id="tableBody"></tbody>
            </table>
            <textarea id="prescriptionNotes" class="form-control mt-3" placeholder="Notes..."></textarea>
            <button class="btn btn-success mt-3" onclick="saveToDatabase()" id="saveBtn">Save to Patient Record</button>
            <div id="server-response" class="mt-2"></div>
        </div>
    </div>
</div>

<script>
    let extractedMedicinesData = [];

    document.getElementById('prescriptionFile').addEventListener('change', function() {
        if (this.files[0]) {
            document.getElementById('fileName').textContent = this.files[0].name;
            document.getElementById('uploadBtn').disabled = false;
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('prescriptionPreview').src = e.target.result;
                document.getElementById('imagePreviewContainer').style.display = 'block';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });

    async function analyzePrescription() {
        const code = document.getElementById('patientCodeInput').value.trim();
        if(!code) return alert("Enter Patient Code");

        // Simple Check
        const check = await fetch(`index.php?check_patient=1&code=${code}`);
        const status = await check.text();
        if(status === 'invalid') return alert("Error: Patient Code not found in database!");

        const file = document.getElementById('prescriptionFile').files[0];
        const formData = new FormData();
        formData.append("file", file);

        document.getElementById('loader').style.display = 'block';
        try {
            const response = await fetch("http://127.0.0.1:8001/api/extract-medicines", { method: "POST", body: formData });
            const result = await response.json();
            extractedMedicinesData = result.medicines;
            populateTable(result.medicines);
            document.getElementById('prescriptionNotes').value = result.notes || "";
            document.getElementById('resultsSection').style.display = 'block';
        } catch (e) { alert("API Error"); }
        document.getElementById('loader').style.display = 'none';
    }

    function populateTable(medicines) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = medicines.map((med, i) => `
            <tr>
                <td><input type="text" class="form-control " style="color:black;"value="${med.medicine || ''}" oninput="extractedMedicinesData[${i}].medicine=this.value"></td>
                <td><input type="text" class="form-control " style="color:black;" value="${med.form || ''}" oninput="extractedMedicinesData[${i}].form=this.value"></td>
                <td><input type="text" class="form-control" style="color:black;" value="${med.dosage || ''}" oninput="extractedMedicinesData[${i}].dosage=this.value"></td>
                <td><input type="text" class="form-control" style="color:black;" value="${med.frequency || ''}" oninput="extractedMedicinesData[${i}].frequency=this.value"></td>
                <td><input type="text" class="form-control" style="color:black;" value="${med.duration || ''}" oninput="extractedMedicinesData[${i}].duration=this.value"></td>
            </tr>
        `).join('');
    }

    async function saveToDatabase() {
        const code = document.getElementById('patientCodeInput').value.trim();
        if(!code) return alert("Enter Patient Code");

        // Simple Check before saving
        const check = await fetch(`index.php?check_patient=1&code=${code}`);
        const status = await check.text();
        if(status === 'invalid') return alert("Error: Patient Code not found in database! Saving cancelled.");

        const response = await fetch('save_medicines.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ patient_code: code, medicines: extractedMedicinesData, notes: document.getElementById('prescriptionNotes').value })
        });
        document.getElementById('server-response').innerText = await response.text();
    }
</script>
</body>
</html>
</body>
</html>