<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareSync Admin | Prescription Reader</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href=".../Bootstrap/bootstrap.min.css">
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
      <div class="text-center mb-5">
        <img src=".../Assets/CareSyncLogo.png" width="40" alt="Logo">
        <h4 class="mt-2">CareSync</h4>
    </div>   
    <div class="nav-item">Patient Records</div>
    <div class="nav-item active">Prescription Reader</div>
</div>

<!-- Main Content Area -->
<div class="main-layout">
    <header class="top-header">
        <h1>Prescription Analyzer</h1>
        <div class="loader" id="loader">
            <div class="spinner"></div>
            <span>AI Analyzing...</span>
        </div>
    </header>

    <!-- Upload Section Card -->
    <div class="glass-card">
        <div class="file-drop-area" id="dropArea">
            <p class="file-message">Drag prescription images here or <strong>browse files</strong></p>
            <p id="fileName" style="color: #0061ff; font-weight: bold;"></p>
            <input type="file" id="prescriptionFile" accept=".jpg, .jpeg, .png, .pdf" style="display: none;">
        </div>

        <div id="imagePreviewContainer" style="display:none; text-align:center;">
            <img id="prescriptionPreview" src="" alt="Prescription Preview">
        </div>

        <button id="uploadBtn" class="btn btn-primary" style="margin-top:20px;" onclick="analyzePrescription()" disabled>Run Analysis</button>
    </div>

    <!-- Results Section Card -->
    <div class="results-section" id="resultsSection" style="display: none;">
        <div class="glass-card">
            <h2 style="font-size: 1.2rem; margin-bottom: 20px;">Extracted Data</h2>
            <div class="table-container">
                <table id="medicinesTable">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Form</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>

            <div style="margin-top: 30px;">
                <h3 style="font-size: 1rem; margin-bottom: 10px;">Detected Instructions & Notes</h3>
                <textarea id="prescriptionNotes" placeholder="AI extracted notes..."></textarea>
            </div>

            <div style="margin-top: 25px; display: flex; justify-content: flex-end;">
                <button class="btn btn-success" onclick="saveToDatabase()" id="saveBtn">Save to Patient Record</button>
            </div>
            
            <div id="server-response" style="margin-top: 15px; text-align: right;"></div>
        </div>
    </div>
</div>

<script>
    let extractedMedicinesData = [];

    const fileInput = document.getElementById('prescriptionFile');
    const fileNameDisplay = document.getElementById('fileName');
    const uploadBtn = document.getElementById('uploadBtn');
    const dropArea = document.getElementById('dropArea');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const previewImage = document.getElementById('prescriptionPreview');
    const notesArea = document.getElementById('prescriptionNotes');

    // Clicking the card triggers file selection
    dropArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            fileNameDisplay.textContent = file.name;
            uploadBtn.disabled = false;

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }
    });

    async function analyzePrescription() {
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append("file", file);

        document.getElementById('loader').style.display = 'flex';
        document.getElementById('resultsSection').style.display = 'none';
        uploadBtn.disabled = true;

        try {
            const response = await fetch("http://127.0.0.1:8001/api/extract-medicines", {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                extractedMedicinesData = result.medicines;
                populateTable(extractedMedicinesData);
                notesArea.value = result.notes || result.instructions || "";
                document.getElementById('resultsSection').style.display = 'block';
            } else {
                alert("AI could not extract data.");
            }
        } catch (error) {
            alert("API Connection Error.");
        } finally {
            document.getElementById('loader').style.display = 'none';
            uploadBtn.disabled = false;
        }
    }

    function populateTable(medicines) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = ''; 
        medicines.forEach((med, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" class="edit-input" style="font-weight:600" value="${med.medicine || ''}" oninput="updateMedData(${index}, 'medicine', this.value)"></td>
                <td><input type="text" class="edit-input" value="${med.form || ''}" oninput="updateMedData(${index}, 'form', this.value)"></td>
                <td><input type="text" class="edit-input" value="${med.dosage || ''}" oninput="updateMedData(${index}, 'dosage', this.value)"></td>
                <td><input type="text" class="edit-input" value="${med.frequency || ''}" oninput="updateMedData(${index}, 'frequency', this.value)"></td>
                <td><input type="text" class="edit-input" value="${med.duration || ''}" oninput="updateMedData(${index}, 'duration', this.value)"></td>
            `;
            tbody.appendChild(tr);
        });
    }

    function updateMedData(index, field, value) {
        extractedMedicinesData[index][field] = value;
    }

    async function saveToDatabase() {
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerText = "Syncing Data...";
        try {
            const response = await fetch('save_medicines.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ medicines: extractedMedicinesData, notes: notesArea.value })
            });
            const text = await response.text();
            document.getElementById('server-response').innerHTML = `<span style="color:green; font-weight:bold;">${text}</span>`;
        } catch (e) {
            alert("Save Error.");
        } finally {
            saveBtn.innerText = "Save to Patient Record";
        }
    }
</script>
</body>
</html>