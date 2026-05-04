from fastapi import APIRouter, UploadFile, File, HTTPException
from services.ocr_service import extract_text_from_image
from services.llm_service import extract_medicines_from_text
from services.preprocess_service import preprocess_image
import tempfile, os, shutil

router = APIRouter()

@router.post("/extract-medicines")
async def extract_medicines(file: UploadFile = File(...)):
    """
    Upload a prescription image (JPG/PNG) or PDF.
    Returns structured medicine information extracted via OCR + LLM.
    """
    allowed_types = ["image/jpeg", "image/png", "image/jpg", "application/pdf"]
    if file.content_type not in allowed_types:
        raise HTTPException(status_code=400, detail="Only JPG, PNG, or PDF files are supported.")

    # Save uploaded file to temp location
    suffix = os.path.splitext(file.filename)[-1]
    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        shutil.copyfileobj(file.file, tmp)
        tmp_path = tmp.name

    try:
        # Step 1: Preprocess (only for images)
        if file.content_type != "application/pdf":
            processed_path = preprocess_image(tmp_path)
        else:
            processed_path = tmp_path

        # Step 2: OCR
        raw_text = extract_text_from_image(processed_path)
        if not raw_text.strip():
            raise HTTPException(status_code=422, detail="No text could be extracted from the image.")

        # Step 3: LLM extraction
        medicines = extract_medicines_from_text(raw_text)

        return {
            "success": True,
            "raw_ocr_text": raw_text,
            "medicines": medicines
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Processing error: {str(e)}")
    finally:
        os.unlink(tmp_path)
        if 'processed_path' in locals() and processed_path != tmp_path:
            try:
                os.unlink(processed_path)
            except Exception:
                pass