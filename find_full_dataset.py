import os
import json

found_files = []
for root, dirs, files in os.walk(r"C:\Users\administrator"):
    for file in files:
        if file.endswith('.json') and 'vocatus' in file.lower():
            full = os.path.join(root, file)
            found_files.append(full)

print(f"Found {len(found_files)} potential json files:")
max_prods = 0
best_file = None

for f in set(found_files):
    try:
        with open(f, 'r', encoding='utf-8') as fp:
            d = json.load(fp)
            if isinstance(d, dict) and 'products' in d:
                count = len(d['products'])
                print(f" - {f}: {count} products")
                if count > max_prods:
                    max_prods = count
                    best_file = f
    except Exception as e:
        pass

print(f"\nBest file with maximum products ({max_prods}): {best_file}")

if best_file and max_prods > 0:
    target_path = r"C:\Users\administrator\.gemini\antigravity-ide\scratch\NexoPOS\vocatus_products.json"
    with open(best_file, 'r', encoding='utf-8') as fp:
        best_data = json.load(fp)
    with open(target_path, 'w', encoding='utf-8') as fp:
        json.dump(best_data, fp, ensure_ascii=False, indent=2)
    print(f"Successfully copied {max_prods} products dataset to {target_path}")
