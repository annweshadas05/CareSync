import os
import json
import re
import base64
from pathlib import Path
from dotenv import load_dotenv
from groq import Groq

load_dotenv(dotenv_path=Path(__file__).resolve().parent.parent / ".env")

api_key = os.getenv("GROQ_API_KEY")
if not api_key:
    raise RuntimeError("GROQ_API_KEY not found in environment")

client = Groq(api_key=api_key)

SYSTEM_PROMPT = """You are a medical prescription parser.
Extract medicine information from the prescription image.

Rules:
- Fix OCR spelling errors (e.g., "Paracitamol" → "Paracetamol")
- Return ONLY a valid JSON array, no extra text, no markdown, no backticks
- Frequency should be human-readable (e.g., "Once daily", "Twice daily")
- If dosage or frequency is missing, use null
- OD=Once daily, BD=Twice daily, TDS=Three times daily, QID=Four times daily
- Tab=Tablet, Cap=Capsule, Inj=Injection, Syr=Syrup

Output format (JSON array only):
[
  {
    "medicine": "Paracetamol",
    "form": "Tablet",
    "dosage": "500mg",
    "frequency": "Twice daily",
    "duration": "5 days",
    "notes": ""
  }
]"""


def extract_medicines_from_text(raw_text: str) -> list:
    if not raw_text.strip():
        return []

    # Check if it's a base64 image → use vision
    if raw_text.startswith("IMAGE_BASE64:"):
        return _extract_from_image(raw_text)
    else:
        return _extract_from_text(raw_text)


def _extract_from_image(raw_text: str) -> list:
    """Use Groq vision model to extract medicines directly from image."""
    try:
        _, ext, b64data = raw_text.split(":", 2)
        mime_map = {".jpg": "image/jpeg", ".jpeg": "image/jpeg",
                    ".png": "image/png", ".webp": "image/webp"}
        mime_type = mime_map.get(ext, "image/jpeg")

        response = client.chat.completions.create(
            model="meta-llama/llama-4-scout-17b-16e-instruct",
            messages=[
                {
                    "role": "user",
                    "content": [
                        {
                            "type": "image_url",
                            "image_url": {
                                "url": f"data:{mime_type};base64,{b64data}"
                            }
                        },
                        {
                            "type": "text",
                            "text": SYSTEM_PROMPT
                        }
                    ]
                }
            ],
            temperature=0.1,
            max_tokens=1000
        )

        content = response.choices[0].message.content.strip()
        content = re.sub(r"```json|```", "", content).strip()
        medicines = json.loads(content)
        return medicines if isinstance(medicines, list) else []

    except json.JSONDecodeError:
        return [{"medicine": "Parse error", "form": None, "dosage": None,
                 "frequency": None, "duration": None, "notes": raw_text[:200]}]
    except Exception as e:
        raise RuntimeError(f"Vision extraction failed: {str(e)}")


def _extract_from_text(raw_text: str) -> list:
    """Fallback: use text-based extraction."""
    try:
        response = client.chat.completions.create(
            model="llama-3.3-70b-versatile",
            messages=[
                {"role": "system", "content": SYSTEM_PROMPT},
                {"role": "user", "content": f"Extract medicines:\n\n{raw_text}"}
            ],
            temperature=0.1,
            max_tokens=1000
        )
        content = response.choices[0].message.content.strip()
        content = re.sub(r"```json|```", "", content).strip()
        medicines = json.loads(content)
        return medicines if isinstance(medicines, list) else []
    except Exception as e:
        raise RuntimeError(f"LLM extraction failed: {str(e)}")