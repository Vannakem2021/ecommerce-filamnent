How it works (simple)
Single-SKU product (no variants): keep quantity in products.stock.

Product with variants: keep quantity per SKU in product_variants.stock.

If stock > 0 → show “In stock”. If stock = 0 → “Out of stock” (or “Backorder” if you allow it).

Tiny schema (2 tables)
sql
Copy
Edit
-- products: for specs + single-SKU stock
CREATE TABLE products (
id BIGINT PRIMARY KEY,
name TEXT NOT NULL,
attributes JSONB, -- specs for filters
has_variants BOOLEAN DEFAULT FALSE,
price NUMERIC(10,2), -- used when has_variants = FALSE
stock INT, -- used when has_variants = FALSE
default_image_url TEXT
);

-- product_variants: per-SKU price/stock
CREATE TABLE product_variants (
id BIGINT PRIMARY KEY,
product_id BIGINT REFERENCES products(id),
sku TEXT UNIQUE NOT NULL,
options JSONB, -- {"Color":"Black","Storage":"256 GB"}
price NUMERIC(10,2) NOT NULL,
stock INT NOT NULL DEFAULT 0,
image_url TEXT
);
Minimal inventory ops
Purchase (paid):

sql
Copy
Edit
UPDATE product_variants SET stock = stock - :qty WHERE id = :variant_id AND stock >= :qty;
-- or products.stock when has_variants = FALSE
Restock:

sql
Copy
Edit
UPDATE product_variants SET stock = stock + :qty WHERE id = :variant_id;
Show availability:

in_stock = stock > 0

Optional: low_stock_threshold column to show “Only 3 left”.

Keep it simple: decrement on payment. If you ever need “holds” to prevent oversells, add a reserved column later (available = stock - reserved). Until then, a single stock field per SKU is fine for small–medium stores.

Example
Headphones with two colors:

json
Copy
Edit
"products": {
"id": 101, "name": "Aurora ANC", "has_variants": true, "attributes": {"type":"Over-ear","anc":true}
},
"product_variants": [
{"id": 1, "product_id": 101, "sku":"HPD-AUR-BLK", "options":{"Color":"Black"}, "price":149, "stock":40},
{"id": 2, "product_id": 101, "sku":"HPD-AUR-WHT", "options":{"Color":"White"}, "price":149, "stock":22}
]
That’s all you need to keep inventory clean and simple. If you want, I can drop in a tiny migration + 3 API endpoints (GET product, GET variants, PATCH variant stock) tailored to your stack.
