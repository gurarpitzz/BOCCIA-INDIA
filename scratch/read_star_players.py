import os
import zipfile
import xml.etree.ElementTree as ET
import sys

# Ensure UTF-8 printing on Windows command line
if sys.platform.startswith('win'):
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def extract_text_from_docx(docx_path):
    try:
        with zipfile.ZipFile(docx_path) as z:
            xml_content = z.read('word/document.xml')
            root = ET.fromstring(xml_content)
            
            # Namespaces
            ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
            
            text_parts = []
            for paragraph in root.iter('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}p'):
                para_text = []
                for run in paragraph.iter('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}t'):
                    if run.text:
                        para_text.append(run.text)
                if para_text:
                    text_parts.append("".join(para_text))
            return "\n".join(text_parts)
    except Exception as e:
        return f"Error reading {docx_path}: {str(e)}"

base_path = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\star players-20260714T053810Z-1-001\star players"
if not os.path.exists(base_path):
    print(f"Path does not exist: {base_path}")
else:
    for folder in os.listdir(base_path):
        folder_path = os.path.join(base_path, folder)
        if os.path.isdir(folder_path):
            print("="*40)
            print(f"ATHLETE FOLDER: {folder}")
            print("="*40)
            # List files
            for f in os.listdir(folder_path):
                print(f" - {f}")
                if f.endswith(".docx") and not f.startswith("~$"):
                    docx_path = os.path.join(folder_path, f)
                    text = extract_text_from_docx(docx_path)
                    print("\n--- Document Text Content ---")
                    print(text)
                    print("-" * 30)
