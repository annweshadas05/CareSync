# 💊 Prescription Reader

An AI-powered web app that reads doctor's prescription images and extracts structured medicine information using OCR + LLM.

---

## 🌐 Live Demo

| Service | URL |
|---|---|
| Frontend |[https://prescription-reader-f2.streamlit.app/](https://prescription-reader-f2.streamlit.app/) |
| Backend API | https://prescription-reader-x714.onrender.com/ |
| API Docs | https://prescription-reader-x714.onrender.com/docs |

---

## 🖼️ How It Works

```
Upload Image/PDF
      ↓
Image Preprocessing (OpenCV)
      ↓
OCR Text Extraction (EasyOCR)
      ↓
AI Medicine Extraction (Groq Llama 3.3)
      ↓
Structured Output → Streamlit UI
```

---

## 🗂️ Project Structure

```
prescription-reader/
├── .gitignore
├── README.md
├── backend/
│   ├── main.py                        ← FastAPI entry point
│   ├── requirements.txt
│   ├── render.yaml                    ← Render deployment config
│   ├── .env.example                   ← Copy to .env for local dev
│   ├── routers/
│   │   └── prescription.py            ← POST /api/extract-medicines
│   └── services/
│       ├── preprocess_service.py      ← OpenCV image cleanup
│       ├── ocr_service.py             ← EasyOCR text extraction
│       └── llm_service.py             ← Groq LLM extraction
└── frontend/
    ├── app.py                         ← Streamlit UI
    ├── requirements.txt
    └── .streamlit/
        └── secrets.toml.example       ← Copy to secrets.toml for local dev
```

---

## ⚙️ Tech Stack

| Layer | Tool |
|---|---|
| Frontend | Streamlit |
| Backend | FastAPI + Uvicorn |
| Image Preprocessing | OpenCV |
| OCR | EasyOCR |
| AI / LLM | Groq (Llama 3.3 70B) — Free |
| Backend Deployment | Render |
| Frontend Deployment | Streamlit Cloud |

---

## 🚀 Local Development

### 1. Clone the repo
```bash
git clone https://github.com/yourusername/prescription-reader.git
cd prescription-reader
```

### 2. Backend setup
```bash
cd backend
python -m venv venv

# Activate (Windows)
venv\Scripts\activate

# Activate (Mac/Linux)
source venv/bin/activate

pip install -r requirements.txt
```

Create your `.env` file:
```bash
cp .env.example .env
```

Add your Groq API key in `.env`:
```
GROQ_API_KEY=gsk_your-groq-key-here
PORT=8000
```

Start the backend:
```bash
uvicorn main:app --reload --port 8000
```

Backend runs at → http://localhost:8000  
Swagger UI → http://localhost:8000/docs

---

### 3. Frontend setup
Open a new terminal:
```bash
cd frontend
pip install -r requirements.txt
```

Create secrets file:
```bash
mkdir .streamlit
cp .streamlit/secrets.toml.example .streamlit/secrets.toml
```

Edit `.streamlit/secrets.toml`:
```toml
API_BASE_URL = "http://localhost:8000"
```

Start the frontend:
```bash
streamlit run app.py
```

Frontend runs at → http://localhost:8501

---

## ☁️ Deployment

### Backend → Render

1. Go to https://render.com → New Web Service
2. Connect your GitHub repo
3. Set **Root Directory** → `backend`
4. Set **Build Command** → `pip install -r requirements.txt`
5. Set **Start Command** → `uvicorn main:app --host 0.0.0.0 --port $PORT`
6. Add environment variable:
   - `GROQ_API_KEY` = your Groq key
7. Click **Deploy**
8. Copy your Render URL

### Frontend → Streamlit Cloud

1. Go to https://share.streamlit.io
2. Connect GitHub → select repo
3. Set **Main file path** → `frontend/app.py`
4. Go to **Advanced Settings → Secrets** and paste:
```toml
API_BASE_URL = "https://your-app.onrender.com"
```
5. Click **Deploy**

---

## 🔑 Environment Variables

### Backend (`.env` / Render Dashboard)

| Variable | Required | Description |
|---|---|---|
| `GROQ_API_KEY` | ✅ Yes | Get free at https://console.groq.com |
| `PORT` | No | Default: 8000 |

### Frontend (`.streamlit/secrets.toml` / Streamlit Cloud Secrets)

| Variable | Required | Description |
|---|---|---|
| `API_BASE_URL` | ✅ Yes | Your deployed Render backend URL |

---

## 📡 API Reference

### `GET /health`
```json
{ "status": "healthy" }
```

### `POST /api/extract-medicines`
**Request:** `multipart/form-data` with `file` (JPG / PNG / PDF)

**Response:**
```json
{
  "success": true,
  "raw_ocr_text": "Tab Paracetamol 500mg BD...",
  "medicines": [
    {
      "medicine": "Paracetamol",
      "form": "Tablet",
      "dosage": "500mg",
      "frequency": "Twice daily",
      "duration": "5 days",
      "notes": ""
    }
  ]
}
```

---

## 🔒 Security Notes

- Never commit `.env` or `secrets.toml` to GitHub
- Both files are listed in `.gitignore`
- Rotate your API keys immediately if accidentally exposed

---

## ⚠️ Disclaimer

This tool is for educational and portfolio purposes only. AI-generated output may contain errors. Always consult a licensed medical professional before taking any medication.

---

## 👨‍💻 Author

Built by [Loralin Sahoo](https://github.com/loralin27)
