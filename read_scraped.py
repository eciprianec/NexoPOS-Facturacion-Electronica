import os
import json

path = r"C:\Users\administrator\AppData\Local\VirtualStore\Users\administrator\.gemini\antigravity-ide\scratch\NexoPOS\vocatus_products.json"

if not os.path.exists(path):
    # search for it
    for root, dirs, files in os.walk(r"C:\Users\administrator"):
        if "vocatus_products.json" in files:
            path = os.path.join(root, "vocatus_products.json")
            break

print(f"Found file at: {path}")
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

products = data['products']
categories = data['categories']

print(f"Total Categories: {len(set(categories.values()))}")
print(f"Total Products: {len(products)}")
print("\nSample Product:")
print(json.dumps(products[0], indent=2, ensure_ascii=False))
