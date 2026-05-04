import os
import base64
from pathlib import Path

def extract_text_from_image(image_path: str) -> str:
    """
    Instead of local OCR, encode image to base64.
    The LLM service will handle vision directly.
    """
    ext = os.path.splitext(image_path)[-1].lower()
    if ext == ".pdf":
        return _extract_from_pdf(image_path)
    else:
        with open(image_path, "rb") as f:
            data = base64.b64encode(f.read()).decode("utf-8")
        return f"IMAGE_BASE64:{ext}:{data}"

def _extract_from_pdf(pdf_path: str) -> str:
    from pdf2image import convert_from_path
    import tempfile
    pages = convert_from_path(pdf_path, dpi=200)
    # Use first page only for now
    with tempfile.NamedTemporaryFile(delete=False, suffix=".png") as tmp:
        pages[0].save(tmp.name, "PNG")
        return extract_text_from_image(tmp.name)