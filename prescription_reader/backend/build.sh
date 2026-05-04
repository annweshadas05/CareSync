#!/usr/bin/env bash
set -e

echo "=== Installing system packages ==="
apt-get update -y
apt-get install -y tesseract-ocr tesseract-ocr-eng poppler-utils

echo "=== Tesseract location ==="
which tesseract
tesseract --version

echo "=== Installing Python packages ==="
pip install -r requirements.txt

echo "=== Build complete ==="