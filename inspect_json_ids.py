import json

path = r"C:\Users\administrator\.gemini\antigravity-ide\scratch\NexoPOS\vocatus_products.json"
with open(path, 'r', encoding='utf-8') as f:
    d = json.load(f)

products = d['products']
print(f"Total products in JSON: {len(products)}")

empty_ids = sum(1 for p in products if not p.get('id'))
print(f"Products with empty ID: {empty_ids}")

ids = [p.get('id') for p in products if p.get('id')]
print(f"Products with valid ID: {len(ids)}")
print(f"Unique IDs: {len(set(ids))}")

# Check sample IDs
print("Sample IDs:", ids[:10])
