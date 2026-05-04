# Integration Guide: Prescription Reader Python API with PHP

This guide outlines how to integrate the standalone Python `prescription-reader-main` backend into your PHP CareSync application. 

The Python project uses **FastAPI** to expose an endpoint (`/api/extract-medicines`) that takes an image/PDF upload, processes it using OCR and an LLM, and returns structured JSON data containing the extracted medicines.

Since Python and PHP are different ecosystems, they need to communicate over an HTTP API. Here are the two best approaches to achieve this integration:

---

## Prerequisites: Running the Python Backend

Before your PHP app can talk to the Python app, the Python backend must be running.

1. **Install Python** on the server where you want to run the service.
2. Navigate to the `backend` directory in terminal.
3. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```
4. Start the FastAPI server (by default it runs on `http://127.0.0.1:8000`):
   ```bash
   uvicorn main:app --reload --host 127.0.0.1 --port 8000
   ```
5. *Note:* Make sure you set any required environment variables (like your Gemini API Key) before running the server, as `llm_service.py` likely requires it.

---

## Method 1: Backend Integration (PHP cURL)
**Best for:** Security and keeping API keys hidden. The file is uploaded to your PHP server first, then PHP forwards it to the Python API.

### 1. The PHP Controller (`upload_prescription.php`)
When a user submits a form with a file upload to your PHP backend, you catch the file and forward it using cURL.

```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['prescription'])) {
    
    // 1. Get file details
    $file_tmp = $_FILES['prescription']['tmp_name'];
    $file_name = $_FILES['prescription']['name'];
    $file_type = $_FILES['prescription']['type'];
    
    // The URL of your running Python FastAPI service
    $python_api_url = "http://127.0.0.1:8000/api/extract-medicines";
    
    // 2. Prepare the file for cURL
    $cfile = new CURLFile($file_tmp, $file_type, $file_name);
    $data = array('file' => $cfile);
    
    // 3. Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $python_api_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // 4. Execute the request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 5. Handle the response
    if ($http_code == 200) {
        $result = json_decode($response, true);
        
        // $result['medicines'] will contain the structured extracted data
        // You can now save this data to your CareSync MySQL Database!
        
        echo "Successfully extracted medicines!";
        echo "<pre>" . print_r($result['medicines'], true) . "</pre>";
        
    } else {
        echo "Error calling Prescription Reader API. HTTP Code: " . $http_code;
        echo "<br>Response: " . $response;
    }
}
?>
```

---

## Method 2: Frontend Integration (JavaScript Fetch)
**Best for:** Better user experience (showing loading states without page reloads) and reducing load on your PHP server (the user's browser sends the image directly to the Python API).

### 1. The HTML/JS Form in your PHP View (e.g. `patient_dashboard.php`)
You bypass PHP entirely for the extraction step, calling the Python API directly from JS, and then you send the *results* back to PHP to save to the database.

```html
<!-- The Upload Form -->
<div class="upload-container">
    <input type="file" id="prescriptionFile" accept=".jpg, .jpeg, .png, .pdf">
    <button onclick="uploadPrescription()" id="uploadBtn">Analyze Prescription</button>
    <div id="loading" style="display:none;">Extracting data... Please wait...</div>
</div>

<script>
async function uploadPrescription() {
    const fileInput = document.getElementById('prescriptionFile');
    const file = fileInput.files[0];
    
    if (!file) {
        alert("Please select a file first.");
        return;
    }

    // Prepare FormData
    const formData = new FormData();
    formData.append("file", file);

    document.getElementById('loading').style.display = 'block';
    document.getElementById('uploadBtn').disabled = true;

    try {
        // 1. Call the Python API directly
        // Ensure FastAPI has CORS enabled for your PHP domain (it does in main.py)
        const response = await fetch("http://127.0.0.1:8000/api/extract-medicines", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (response.ok) {
            console.log("Extracted Data:", result.medicines);
            alert("Success! " + result.medicines.length + " medicines found.");
            
            // 2. Now send the EXTRACTED data to your PHP backend to save it
            saveToCareSyncDatabase(result.medicines);
        } else {
            alert("Error: " + (result.detail || "Unknown error"));
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Failed to connect to the Prescription API.");
    } finally {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('uploadBtn').disabled = false;
    }
}

async function saveToCareSyncDatabase(medicinesData) {
    // Send the structured JSON to a PHP script to INSERT into your MySQL DB
    await fetch('save_medicines.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ medicines: medicinesData })
    });
}
</script>
```

---

## Recommended Architecture for CareSync

For a production environment, I recommend **Method 2 (Frontend Integration)** combined with a secure backend save:

1. The patient uploads a prescription on the patient dashboard.
2. JavaScript sends the image directly to the Python FastAPI service.
3. The Python service processes the image with OCR and LLM, returning the structured medicine list to the JavaScript.
4. The JavaScript populates a UI table (using the glassmorphism theme) showing the extracted medicines so the patient can review them.
5. The patient clicks a final "Save" button, which sends the JSON array to a PHP controller (`save_prescription.php`) that inserts the records into the CareSync MySQL database.

> [!WARNING] 
> In production, you shouldn't hardcode `127.0.0.1`. You will need to host the Python API on a platform (like Render, Heroku, or a VPS) and use the public URL in your PHP/JS code. The Python code already includes a `render.yaml` file, indicating it's ready to be easily deployed to [Render.com](https://render.com).
