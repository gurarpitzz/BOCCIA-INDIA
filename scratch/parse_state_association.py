import pandas as pd
import json

try:
    df = pd.read_excel("State Association.xlsx")
    print("Row 0:", df.iloc[0].tolist())
    print("Row 1:", df.iloc[1].tolist())
    print("Row 2:", df.iloc[2].tolist())
    
    # Let's set Row 1 as column headers
    df.columns = df.iloc[1]
    df = df.iloc[2:] # Keep data after header
    print("\nCleaned Columns:", df.columns.tolist())
    print("\nFirst 10 Cleaned rows:")
    print(df.head(10))
    
    records = df.to_dict(orient="records")
    # clean nan values to None
    for r in records:
        for k in list(r.keys()):
            if pd.isna(r[k]):
                r[k] = None
    with open("database/state_associations.json", "w", encoding="utf-8") as f:
        json.dump(records, f, indent=4, ensure_ascii=False)
    with open("database/state_associations.json", "w", encoding="utf-8") as f:
        json.dump(records, f, indent=4, ensure_ascii=False)
    print("\nSaved as JSON successfully to database/state_associations.json")
except Exception as e:
    print("Error:", e)
