import os
import json

src_path = None
for root, dirs, files in os.walk(r"C:\Users\administrator"):
    if "vocatus_products.json" in files:
        src_path = os.path.join(root, "vocatus_products.json")
        break

if src_path:
    with open(src_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    
    dst_path = r"C:\Users\administrator\.gemini\antigravity-ide\scratch\NexoPOS\vocatus_scraped.json"
    with open(dst_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"Copied {len(data['products'])} products to {dst_path}")
else:
    print("Source not found!")
