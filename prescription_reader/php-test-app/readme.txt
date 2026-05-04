virtual python environment 
    create(new): python -m venv .venv
activate venv
    .\.venv\Scripts\activate.bat
    output: (venv) home/manish$

GOTO backend
install requirements: pip install -r requirements.txt
    (for new servers run once.)

start uvicorn server: uvicorn main:app --reload --host 127.0.0.1 --port 8001 
    
    *port is hardcoded in index.php and while running the uvicorn.


stop server: CTRL+C
to exit venv: exit
