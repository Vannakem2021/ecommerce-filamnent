gotcha—if most options (like Color) don’t change price, use the “product price + variant inherits” pattern. It’s the simplest.

The simple pattern (recommended)
Price lives on the product.

Variants only carry stock/images/options (e.g., Color).

If you hit a rare case where an option does change price, put an optional override_price on that variant.

Tables
products
column what it’s for
id (PK) product id
name display name
price base price (all variants inherit this)
stock used only when there are no variants
has_variants (bool) false = single-SKU product
sku used when has_variants=false
attributes specs for filters (CPU, battery…)
default_image_url default image

product_variants
column what it’s for
id (PK) variant id
product_id (FK) links to product
sku (unique) buyable SKU
color (or options JSON) shopper choice(s)
stock per-variant inventory
image_url variant image
override_price (nullable) only fill if this variant’s price differs

How pricing works
Normal case: final_price = product.price

Exception: final_price = variant.override_price (if not null)

One-liner query
sql
Copy
Edit
SELECT
COALESCE(v.override_price, p.price) AS final_price,
v.stock, v.sku
FROM product_variants v
JOIN products p ON p.id = v.product_id
WHERE v.id = :variant_id;
Examples
A) Color-only variants (same price for all colors)
products

id name price has_variants sku stock attributes
900 iPhone 15 1100 true NULL NULL …

product_variants

id product_id sku color stock image_url override_price
1 900 IP15-BLK Black 12 /img/ip15-black.jpg NULL
2 900 IP15-BLU Blue 9 /img/ip15-blue.jpg NULL

Final price for both = $1100 (inherits from product)

B) Rare exception (one variant is pricier)
product_variants

id product_id sku color stock image_url override_price
3 900 IP15-RED-LTD Red 3 /img/ip15-red-ltd.jpg 1200

Red Limited Edition shows $1200; others still show $1100.

C) No variants (single SKU)
products

id name price has_variants sku stock
200 Power Bank 20K 39.90 false PB-20K-BLK 120

No variant rows. Use product price + stock.

Frontend logic (tiny)
Load product and its variants.

If there are variants, show pickers (e.g., Color).

When a variant is selected, show

price = override_price ?? product.price

stock = variant.stock

If no variants, show product price + stock.
