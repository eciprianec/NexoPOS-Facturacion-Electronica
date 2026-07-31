import json
import re
import urllib.parse

path = r"C:\Users\administrator\.gemini\antigravity-ide\scratch\NexoPOS\vocatus_products.json"
with open(path, 'r', encoding='utf-8') as f:
    data = json.load(f)

products = data['products']
categories = data['categories']

cleaned_products = []

for idx, p in enumerate(products):
    url = p.get('url', '')
    img = p.get('image', '')
    price_raw = p.get('price_raw', '')
    
    # 1. Product ID
    id_match = re.search(r'-(\d+)(?:\?|$)', url)
    p_id = id_match.group(1) if id_match else str(idx + 1)
    
    # 2. Product Name from Image URL or URL slug
    p_name = ""
    if img:
        img_match = re.search(r'/image_\d+/([^/?]+)', img)
        if img_match:
            p_name = urllib.parse.unquote(img_match.group(1)).replace('+', ' ').strip()
    
    if not p_name and url:
        slug_match = re.search(r'/shop/([^-]+(?:-[^-]+)*?)-\d+(?:\?|$)', url)
        if slug_match:
            slug = slug_match.group(1).replace('-bebidas-original-oferta-precio-tienda-envio-republica-dominicana', '')
            p_name = slug.replace('-', ' ').title()
            
    if not p_name:
        p_name = f"Producto Vocatus {p_id}"
        
    # 3. Price
    price_val = 0.0
    if price_raw:
        # e.g. RD$100.00100.0DOP -> extract first 100.00
        p_match = re.search(r'RD\$\s*([\d,]+\.\d{2})', price_raw) or re.search(r'([\d,]+\.\d{2})', price_raw) or re.search(r'([\d,]+)', price_raw)
        if p_match:
            try:
                price_val = float(p_match.group(1).replace(',', ''))
            except:
                pass

    cleaned_products.append({
        'id': p_id,
        'name': p_name,
        'category': p.get('category', 'General'),
        'price': price_val,
        'image': img,
        'url': url
    })

print(f"Cleaned {len(cleaned_products)} products.")
print("Sample cleaned products:")
for p in cleaned_products[:5]:
    print(p)

# Save cleaned dataset
out_path = r"C:\Users\administrator\.gemini\antigravity-ide\scratch\NexoPOS\vocatus_cleaned_products.json"
with open(out_path, 'w', encoding='utf-8') as f:
    json.dump({'categories': categories, 'products': cleaned_products}, f, ensure_ascii=False, indent=2)

print(f"\nSaved cleaned dataset to {out_path}")
