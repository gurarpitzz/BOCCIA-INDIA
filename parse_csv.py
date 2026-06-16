import csv
import collections

csv_path = r"c:\Users\HP\.gemini\antigravity-ide\scratch\boccia-india-landing\database.csv"

try:
    with open(csv_path, 'r', encoding='utf-8', errors='ignore') as f:
        reader = csv.reader(f)
        headers = next(reader)
        print("Headers:", headers)
        
        # Let's count representing states
        state_idx = -1
        for i, h in enumerate(headers):
            if 'REPRESENTING' in h or 'STATE' in h or 'REPRESENTING_FOR' in h:
                state_idx = i
                print(f"State column found at index {i}: {h}")
        
        states = []
        rows = []
        for r in reader:
            rows.append(r)
            if state_idx != -1 and len(r) > state_idx:
                states.append(r[state_idx].strip())
                
        print(f"Total rows: {len(rows)}")
        state_counts = collections.Counter(states)
        print("\nUnique states and counts:")
        for s, count in sorted(state_counts.items()):
            print(f"  {s}: {count}")
            
except Exception as e:
    print("Error:", e)
