from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from routers import prescription

app = FastAPI(
    title="Prescription Reader API",
    description="Extracts medicine information from prescription images using OCR + LLM",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(prescription.router, prefix="/api", tags=["Prescription"])

@app.get("/")
def root():
    return {"message": "Prescription Reader API is running ✅"}

@app.get("/health")
def health():
    return {"status": "healthy"}