import sys
from pdf2docx import Converter

# Verifica argumentos
if len(sys.argv) != 3:
    print("Uso: python3 pdf_to_docx.py <arquivo_pdf> <arquivo_docx>")
    sys.exit(1)

pdf_file = sys.argv[1]
docx_file = sys.argv[2]

try:
    cv = Converter(pdf_file)
    cv.convert(docx_file, start=0, end=None)
    cv.close()
    print(f"Conversão concluída! Arquivo salvo como {docx_file}")
except Exception as e:
    print(f"Erro ao converter: {e}")
