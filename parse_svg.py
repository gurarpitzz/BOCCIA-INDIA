import xml.etree.ElementTree as ET
import os

svg_path = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\india svg.svg"
out_path = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\states_list.txt"

try:
    tree = ET.parse(svg_path)
    root = tree.getroot()
    
    paths = root.findall('.//{http://www.w3.org/2000/svg}path')
    if not paths:
        paths = root.findall('.//path')
        
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write(f"Found {len(paths)} paths:\n")
        for p in paths:
            id_attr = p.attrib.get('id')
            name_attr = p.attrib.get('name')
            if id_attr or name_attr:
                f.write(f"ID: {id_attr} | Name: {name_attr}\n")
    print(f"Saved to {out_path}")
except Exception as e:
    print("Error:", e)
