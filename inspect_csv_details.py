import csv

csv_path = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\database.csv"

try:
    with open(csv_path, 'r', encoding='utf-8', errors='ignore') as f:
        reader = csv.reader(f)
        headers = next(reader)
        
        # Get indexes
        reg_idx = headers.index('REGN_NO')
        cname_idx = headers.index('CNAME')
        gender_idx = headers.index('GENDER')
        dob_idx = headers.index('DOB')
        state_idx = headers.index('REPRESENTING_FOR')
        
        disc_name_idx = headers.index('DISCIPLINE_NAME') if 'DISCIPLINE_NAME' in headers else -1
        disc1_name_idx = headers.index('DISCIPLINE1_NAME') if 'DISCIPLINE1_NAME' in headers else -1
        
        print(f"Index mapping: REGN_NO={reg_idx}, CNAME={cname_idx}, GENDER={gender_idx}, DOB={dob_idx}, REPRESENTING_FOR={state_idx}, DISCIPLINE_NAME={disc_name_idx}, DISCIPLINE1_NAME={disc1_name_idx}")
        
        genders = set()
        classes = set()
        classes_1 = set()
        dobs = []
        
        rows = list(reader)
        for r in rows[:10]:
            print(f"Sample row: Name='{r[cname_idx]}', Gender='{r[gender_idx]}', DOB='{r[dob_idx]}', State='{r[state_idx]}', Disc='{r[disc_name_idx] if disc_name_idx != -1 else ''}', Disc1='{r[disc1_name_idx] if disc1_name_idx != -1 else ''}'")
            
        for r in rows:
            genders.add(r[gender_idx].strip())
            dobs.append(r[dob_idx].strip())
            if disc_name_idx != -1:
                classes.add(r[disc_name_idx].strip())
            if disc1_name_idx != -1:
                classes_1.add(r[disc1_name_idx].strip())
                
        print("\nUnique Genders in CSV:", genders)
        print("Unique DISCIPLINE_NAME:", classes)
        print("Unique DISCIPLINE1_NAME:", classes_1)
        print("Sample raw DOB values:", dobs[:10])
            
except Exception as e:
    print("Error:", e)
