import pandas as pd
import json
import re

def clean_phone(val):
    if pd.isna(val) or val is None:
        return "Not Available"
    val_str = str(val).strip()
    if val_str.endswith(".0"):
        val_str = val_str[:-2]
    # Remove non-digits
    digits = re.sub(r"\D", "", val_str)
    if len(digits) == 10:
        return f"+91 {digits[:5]} {digits[5:]}"
    elif len(digits) > 10:
        return f"+{digits}"
    return val_str if val_str else "Not Available"

def clean_email(val):
    if pd.isna(val) or val is None:
        return "Not Available"
    val_str = str(val).strip()
    return val_str if val_str else "Not Available"

def clean_person(val):
    if pd.isna(val) or val is None:
        return "Not Available"
    val_str = str(val).strip()
    return val_str if val_str else "Not Available"

try:
    df = pd.read_excel("State Association.xlsx")
    # Row 1 is header
    df.columns = df.iloc[1]
    df = df.iloc[2:]
    
    clean_data = {}
    
    for _, row in df.iterrows():
        state_raw = row.get("State")
        if pd.isna(state_raw) or state_raw is None:
            continue
        
        state_raw = str(state_raw).strip()
        
        # Split Association - State
        if " - " in state_raw:
            parts = state_raw.split(" - ")
            assoc_name = parts[0].strip()
            state_name = parts[1].strip()
        else:
            state_name = state_raw
            assoc_name = "Not Available"
            
        # Standardize state names to match SVG / DB standard keys
        state_mapping = {
            "Sikkam": "Sikkim",
            "West Bangal": "West Bengal",
            "Utter Pradesh": "Uttar Pradesh",
            "Meghalya": "Meghalaya",
            "Andaman & Nicobar Island": "Andaman & Nicobar Islands",
            "Andaman & Nicobar": "Andaman & Nicobar Islands",
            "Jammu & Kashmir": "Jammu & Kashmir",
            "Dadra and Nagar Haveli and Daman & Diu": "Dadra and Nagar Haveli and Daman and Diu",
        }
        if state_name in state_mapping:
            state_name = state_mapping[state_name]
            
        # Determine status
        reg = row.get("Registred")
        under = row.get("Under Process")
        
        # Normalize status text
        if not pd.isna(reg) and str(reg).strip().lower() in ["done", "registered"]:
            status = "Registered"
        elif not pd.isna(under) and str(under).strip().lower() in ["under process", "underprocess"]:
            status = "Under Process"
        else:
            status = "Not Available"
            
        # Contact details
        email = clean_email(row.get("Email"))
        phone = clean_phone(row.get("Phone"))
        person = clean_person(row.get("Contact Person"))
        
        clean_data[state_name] = {
            "state_name": state_name,
            "association_name": assoc_name if assoc_name != "Not Available" else f"Boccia Association of {state_name}" if status != "Not Available" else "Not Available",
            "contact_person": person,
            "email": email,
            "phone": phone,
            "status": status
        }
        
    print(f"Successfully processed {len(clean_data)} states.")
    
    # Save output JSON
    with open("database/state_associations_clean.json", "w", encoding="utf-8") as f:
        json.dump(clean_data, f, indent=4, ensure_ascii=False)
        
    print("Saved to database/state_associations_clean.json")
    
except Exception as e:
    print("Error:", e)
