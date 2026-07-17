import os
import zipfile
import xml.etree.ElementTree as ET
import json
import sys

# Ensure UTF-8 printing
if sys.platform.startswith('win'):
    sys.stdout.reconfigure(encoding='utf-8')
    sys.stderr.reconfigure(encoding='utf-8')

def extract_text_from_docx(docx_path):
    try:
        with zipfile.ZipFile(docx_path) as z:
            xml_content = z.read('word/document.xml')
            root = ET.fromstring(xml_content)
            
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
        return f"Error: {str(e)}"

base_path = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\star players-20260714T053810Z-1-001\star players"
output_json_path = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\assets\star_players.json"

players_data = {}

# Ensure assets dir exists
os.makedirs(os.path.dirname(output_json_path), exist_ok=True)

# List of folders in base path
if os.path.exists(base_path):
    for folder in os.listdir(base_path):
        folder_path = os.path.join(base_path, folder)
        if os.path.isdir(folder_path):
            player_key = folder.lower().replace(" ", "_")
            player_info = {
                "folder_name": folder,
                "document_text": "",
                "images": []
            }
            
            # Find docx and images
            for f in os.listdir(folder_path):
                f_path = os.path.join(folder_path, f)
                if os.path.isfile(f_path):
                    if f.endswith(".docx") and not f.startswith("~$"):
                        player_info["document_text"] = extract_text_from_docx(f_path)
                    elif f.lower().endswith((".png", ".jpg", ".jpeg", ".webp")):
                        # Store relative path from workspace root
                        rel_path = os.path.relpath(f_path, r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing")
                        player_info["images"].append(rel_path.replace("\\", "/"))
            
            players_data[player_key] = player_info

with open(output_json_path, "w", encoding="utf-8") as f:
    json.dump(players_data, f, indent=4, ensure_ascii=False)

print(f"Successfully wrote JSON to {output_json_path}")
