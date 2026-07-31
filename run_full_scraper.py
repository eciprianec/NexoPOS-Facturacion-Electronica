import urllib.request
import urllib.parse
import http.cookiejar
import re
import json
import time
from bs4 import BeautifulSoup

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))
opener.addheaders = [
    ('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
]

# 1. Confirm age-gate
req = urllib.request.Request('https://vocatus.com.do/age-gate')
res = opener.open(req)
html = res.read().decode('utf-8')
match = re.search(r'csrf_token:\s*"([^"]+)"', html) or re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
csrf = match.group(1) if match else ''

data = urllib.parse.urlencode({'csrf_token': csrf, 'redirect': '/shop'}).encode('utf-8')
opener.open(urllib.request.Request('https://vocatus.com.do/age-gate/confirm', data=data))

# 2. Discover categories
res_shop = opener.open(urllib.request.Request('https://vocatus.com.do/shop'))
soup_shop = BeautifulSoup(res_shop.read().decode('utf-8'), 'html.parser')

categories_map = {}
for a in soup_shop.find_all('a', href=True):
    href = a['href']
    if '/shop/category/' in href:
        name = a.get_text(strip=True)
        if name:
            categories_map[href] = name

print(f"Categories ({len(categories_map)}): {list(categories_map.values())}")

all_products = []
seen_urls = set()

# Iterate over all categories
for cat_href, cat_name in categories_map.items():
    page = 1
    while True:
        cat_url = f"https://vocatus.com.do{cat_href}" if not cat_href.startswith('http') else cat_href
        if page > 1:
            cat_url += f"/page/{page}"
        
        try:
            req_c = urllib.request.Request(cat_url)
            res_c = opener.open(req_c)
            c_html = res_c.read().decode('utf-8')
            soup_c = BeautifulSoup(c_html, 'html.parser')
            
            # Products grid
            forms = soup_c.find_all('form', action=re.compile(r'/shop/cart/update'))
            if not forms:
                forms = soup_c.find_all('div', class_=re.compile(r'oe_product'))
                
            if not forms:
                break
                
            new_in_page = 0
            for item in forms:
                a_tag = item.find('a', class_=re.compile(r'o_wsale_products_item_title')) or item.find('a', href=re.compile(r'/shop/'))
                if not a_tag:
                    continue
                p_href = a_tag['href']
                if p_href in seen_urls or not p_href.startswith('/shop/'):
                    continue
                seen_urls.add(p_href)
                
                # Product name
                p_name = a_tag.get_text(strip=True)
                if not p_name:
                    continue
                
                # Product price
                price_tag = item.find(class_=re.compile(r'oe_price')) or item.find(class_=re.compile(r'product_price'))
                p_price_str = price_tag.get_text(strip=True) if price_tag else '0'
                price_numbers = re.findall(r'[\d\.,]+', p_price_str)
                price_val = 0.0
                if price_numbers:
                    clean_p = price_numbers[0].replace(',', '')
                    try:
                        price_val = float(clean_p)
                    except:
                        pass
                
                # Image
                img_tag = item.find('img')
                p_img = ''
                if img_tag:
                    p_img = img_tag.get('src') or img_tag.get('data-src') or ''
                    if p_img and not p_img.startswith('http'):
                        p_img = f"https://vocatus.com.do{p_img}"
                
                id_match = re.search(r'-(\d+)$', p_href)
                p_id = id_match.group(1) if id_match else ''
                
                product_data = {
                    'id': p_id,
                    'name': p_name,
                    'category': cat_name,
                    'category_href': cat_href,
                    'price': price_val,
                    'price_raw': p_price_str,
                    'image': p_img,
                    'url': f"https://vocatus.com.do{p_href}"
                }
                all_products.append(product_data)
                new_in_page += 1
                
            print(f"Cat '{cat_name}' Page {page}: fetched {new_in_page} new products (Total: {len(all_products)})")
            
            # Check pagination
            pag = soup_c.find('ul', class_=re.compile(r'pagination'))
            if not pag or not pag.find('a', href=re.compile(r'/page/')):
                break
            if f"/page/{page+1}" not in str(pag):
                break
                
            page += 1
            time.sleep(0.1)
        except Exception as e:
            print(f"Err fetching {cat_url}: {e}")
            break

out_path = r"C:\Users\administrator\.gemini\antigravity-ide\scratch\NexoPOS\vocatus_products.json"
with open(out_path, 'w', encoding='utf-8') as f:
    json.dump({'categories': categories_map, 'products': all_products}, f, ensure_ascii=False, indent=2)

print(f"\nSUCCESS! Saved {len(all_products)} products to {out_path}")
