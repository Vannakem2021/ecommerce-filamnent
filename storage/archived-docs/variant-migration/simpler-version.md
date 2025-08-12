# Migration Plan: Complex → Simple Variant System

## 🎯 Objective

Migrate from the current complex normalized variant system (8+ tables) to the simple JSON-based approach (2 tables) as outlined in `product-variant-attribute-simple.md`.

## 📊 Current vs Target Architecture

### Current System (Complex)

```
products (base product data)
├── product_attributes (Color, Size, etc.)
├── product_attribute_values (Red, Large, etc.)
├── product_variants (individual SKUs)
├── product_variant_attributes (pivot table)
├── specification_attributes (CPU, Display, etc.)
├── product_specification_values (product specs)
├── variant_specification_values (variant specs)
└── inventory_reservations (advanced inventory)
```

### Target System (Simple)

```
products
├── id, name, slug, description
├── attributes (JSON) → specs for filtering
├── images (JSON array)
├── price_cents, stock_quantity (for single-SKU products)
├── has_variants (boolean)
└── variants (JSON array) → choices that affect price/stock

product_variants (for SKU-based inventory tracking)
├── id, product_id, sku
├── options (JSON) → variant choices
├── price_cents, stock_quantity
└── image_url

orders/order_items (unchanged - for historical data)
```

## � SKU-based Inventory Tracking (Prevents Overselling)

### Simple Inventory Operations

**For Single-SKU Products (no variants):**

-   Stock tracked in `products.stock_quantity`
-   Operations: `UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :product_id AND stock_quantity >= :qty`

**For Products with Variants:**

-   Stock tracked per SKU in `product_variants.stock_quantity`
-   Operations: `UPDATE product_variants SET stock_quantity = stock_quantity - :qty WHERE id = :variant_id AND stock_quantity >= :qty`

### Inventory Management Examples

```php
// Purchase operation (prevents overselling)
$variant = ProductVariant::where('sku', 'PHN-IP15P-BLK-128')->first();
if ($variant->stock_quantity >= $requestedQty) {
    $variant->decrement('stock_quantity', $requestedQty);
    // Order successful
} else {
    // Insufficient stock - prevent overselling
    throw new Exception("Only {$variant->stock_quantity} items available");
}

// Restock operation
$variant->increment('stock_quantity', $restockQty);

// Check availability
$inStock = $variant->stock_quantity > 0;
$lowStock = $variant->stock_quantity <= 5; // configurable threshold
```

### Stock Status Display

```php
// Simple stock status logic
public function getStockStatus()
{
    if ($this->stock_quantity <= 0) {
        return 'out_of_stock';
    } elseif ($this->stock_quantity <= 5) {
        return 'low_stock';
    }
    return 'in_stock';
}

// Frontend display
if ($variant->stock_quantity > 0) {
    echo "In Stock ({$variant->stock_quantity} available)";
} else {
    echo "Out of Stock";
}
```

## �🔄 Data Transformation Examples

### Current Product with Variants

```php
// Product: iPhone 15 Pro
Product {
    id: 1,
    name: "iPhone 15 Pro",
    has_variants: true,
    variants: [
        ProductVariant {
            id: 1,
            sku: "PHN-IP15P-BLK-128",
            price_cents: 99900,
            stock_quantity: 20,
            attributeValues: [
                {attribute: "Color", value: "Black"},
                {attribute: "Storage", value: "128 GB"}
            ]
        }
    ],
    specifications: [
        {attribute: "CPU", value: "A17 Pro"},
        {attribute: "Display", value: "6.1\" Super Retina XDR"}
    ]
}
```

### Target Simple Product

```php
// Same product in simple format
Product {
    id: 1,
    name: "iPhone 15 Pro",
    has_variants: true,
    attributes: {
        "brand": "Apple",
        "cpu": "A17 Pro",
        "display": "6.1\" Super Retina XDR",
        "camera": "48MP Main + 12MP Ultra Wide",
        "battery_hours": 23,
        "ip_rating": "IP68"
    }
}

// Separate table for SKU-based inventory tracking
ProductVariant {
    id: 1,
    product_id: 1,
    sku: "PHN-IP15P-BLK-128",
    options: {"Color": "Black", "Storage": "128 GB"},
    price_cents: 99900,
    stock_quantity: 20,
    image_url: "iphone-15-pro-black.jpg"
}
```

## 📉 What We'll Lose vs Gain

### Advanced Features Being Removed

-   ❌ **Complex Inventory Reservations** - Advanced reservation system removed
-   ❌ **Complex Relationships** - No attribute dependencies
-   ❌ **Performance Optimizations** - No composite indexes
-   ❌ **Rich Admin Interface** - JSON editors instead of forms
-   ❌ **Data Validation** - Less strict validation
-   ❌ **Audit Trails** - No change tracking

### What We'll Gain

-   ✅ **Simplicity** - Much easier to understand
-   ✅ **Flexibility** - Easy attribute changes without migrations
-   ✅ **Speed** - Faster development
-   ✅ **Reduced Complexity** - Fewer moving parts
-   ✅ **Human-readable Data** - JSON is easy to debug
-   ✅ **Simple Inventory Tracking** - SKU-based stock management to prevent overselling

## ⚠️ Risk Assessment

### HIGH RISK Areas

1. **Data Loss**: Current normalized data needs careful migration to JSON
2. **Admin Panel**: Filament resources will break completely
3. **Cart System**: Variant handling logic needs complete rewrite
4. **Order History**: Existing orders reference current variant structure
5. **Frontend Components**: ProductDetailPage needs major refactoring

### MEDIUM RISK Areas

1. **API Endpoints**: Specification endpoints will be obsolete
2. **Search/Filtering**: Current attribute-based filtering needs redesign
3. **Performance**: JSON queries are less efficient than normalized queries
4. **Inventory**: Advanced reservation system will be lost (but basic SKU-based tracking retained)

### LOW RISK Areas

1. **Basic Product Display**: Core product listing should work
2. **Categories/Brands**: Unaffected by variant changes
3. **User Authentication**: No impact
4. **Static Pages**: No impact

## 📋 Migration Strategy (Phase-by-Phase)

### Phase 1: Preparation & Backup (CRITICAL)

**Duration: 1-2 hours**
**Risk Level: LOW**

#### 1.1 Create Full Database Backup

```bash
# Create timestamped backup
php artisan db:backup --filename="pre_simplification_$(date +%Y%m%d_%H%M%S).sql"

# Export current variant data for reference
php artisan tinker
>>> DB::table('product_variants')->with(['attributeValues'])->get()->toJson()
```

#### 1.2 Document Current Data Structure

-   Export all variant combinations to CSV
-   Document all attribute relationships
-   Create mapping of current SKUs to new format

#### 1.3 Create Feature Branch

```bash
git checkout -b feature/simplify-variant-system
git add .
git commit -m "Backup: Current complex variant system before simplification"
```

### Phase 2: Data Migration Script (HIGH RISK)

**Duration: 2-3 hours**
**Risk Level: HIGH**

#### 2.1 Create Migration Command

```php
// Command: php artisan migrate:simplify-variants
class SimplifyVariantsCommand extends Command
{
    public function handle()
    {
        // 1. Migrate product attributes to JSON
        // 2. Migrate variants to JSON array
        // 3. Preserve order history compatibility
        // 4. Validate data integrity
    }
}
```

#### 2.2 Data Transformation Logic

```php
// Transform current normalized data to JSON format
foreach (Product::with(['variants.attributeValues']) as $product) {
    // Convert specifications to attributes JSON
    $attributes = $this->buildAttributesJson($product);

    // Convert variants to JSON array
    $variants = $this->buildVariantsJson($product->variants);

    // Update product with new structure
    $product->update([
        'attributes' => $attributes,
        'variants' => $variants
    ]);
}
```

### Phase 3: Database Schema Changes (HIGH RISK)

**Duration: 1 hour**
**Risk Level: HIGH**

#### 3.1 Add New JSON Column & Simplify Variants Table

```sql
-- Add attributes JSON column to products
ALTER TABLE products ADD COLUMN attributes JSON;

-- Simplify product_variants table (keep for SKU-based inventory)
-- Remove complex columns, keep essential fields for inventory tracking
ALTER TABLE product_variants DROP COLUMN name;
ALTER TABLE product_variants DROP COLUMN compare_price_cents;
ALTER TABLE product_variants DROP COLUMN cost_price_cents;
ALTER TABLE product_variants DROP COLUMN stock_status;
ALTER TABLE product_variants DROP COLUMN low_stock_threshold;
ALTER TABLE product_variants DROP COLUMN track_inventory;
ALTER TABLE product_variants DROP COLUMN weight;
ALTER TABLE product_variants DROP COLUMN dimensions;
ALTER TABLE product_variants DROP COLUMN barcode;
ALTER TABLE product_variants DROP COLUMN images;
ALTER TABLE product_variants DROP COLUMN is_active;
ALTER TABLE product_variants DROP COLUMN is_default;

-- Add options JSON column for variant choices
ALTER TABLE product_variants ADD COLUMN options JSON;
ALTER TABLE product_variants ADD COLUMN image_url TEXT;
```

#### 3.2 Preserve Historical Data

```sql
-- Keep complex variant tables for order history
-- Mark as deprecated but don't drop yet
ALTER TABLE product_variant_attributes ADD COLUMN deprecated_at TIMESTAMP;
UPDATE product_variant_attributes SET deprecated_at = NOW();
```

### Phase 4: Model Simplification (MEDIUM RISK)

**Duration: 2-3 hours**
**Risk Level: MEDIUM**

#### 4.1 Update Product Model

```php
class Product extends Model
{
    protected $casts = [
        'attributes' => 'array',  // Specs for filtering
        'variants' => 'array',    // Choices affecting price/stock
        'images' => 'array'
    ];

    // Remove complex variant relationships
    // Add simple JSON-based methods
}
```

#### 4.2 Create Simple Variant Helper

```php
class SimpleVariantHelper
{
    public static function findVariant($product, $selectedOptions)
    {
        foreach ($product->variants as $variant) {
            if ($variant['options'] === $selectedOptions) {
                return $variant;
            }
        }
        return null;
    }
}
```

### Phase 5: Frontend Refactoring (HIGH RISK)

**Duration: 3-4 hours**
**Risk Level: HIGH**

#### 5.1 Simplify ProductDetailPage

-   Remove complex attribute selection logic
-   Replace with simple option dropdowns
-   Use JSON-based variant matching

#### 5.2 Update Product Card Component

-   Remove complex variant display logic
-   Show simple option count from JSON array
-   Simplify price calculation

#### 5.3 Update Cart System with SKU-based Inventory

-   Remove complex inventory reservation logic
-   Implement simple SKU-based stock checking
-   Update cart item structure to use simplified variants
-   Add overselling prevention at checkout

### Phase 6: Admin Panel Reconstruction (HIGH RISK)

**Duration: 4-5 hours**
**Risk Level: HIGH**

#### 6.1 Remove Complex Resources

-   Delete ProductAttributeResource
-   Delete ProductAttributeValueResource
-   Remove VariantsRelationManager

#### 6.2 Create Simple Variant Management

```php
// Simple JSON editor for variants in ProductResource
JsonEditor::make('variants')
    ->label('Product Variants')
    ->schema([
        'sku' => 'string',
        'options' => 'object',
        'price' => 'number',
        'stock' => 'number',
        'image' => 'string'
    ])
```

### Phase 7: Testing & Validation (CRITICAL)

**Duration: 2-3 hours**
**Risk Level: MEDIUM**

#### 7.1 Data Integrity Tests

-   Verify all products migrated correctly
-   Check variant option combinations
-   Validate price/stock data

#### 7.2 Functionality Tests

-   Test product detail page variant selection
-   Test cart operations with variants
-   Test order creation with variants
-   Test admin panel variant management

#### 7.3 Performance Tests

-   Compare query performance (JSON vs normalized)
-   Test with large product catalogs
-   Validate search/filtering performance

## 🚨 Breaking Changes Checklist

### Will Break Immediately:

-   [ ] All Filament variant management interfaces
-   [ ] ProductDetailPage variant selection
-   [ ] Cart variant handling
-   [ ] API endpoints for specifications
-   [ ] Advanced inventory reservations
-   [ ] Performance optimizations

### Requires Manual Updates:

-   [ ] Any custom code using ProductVariant model
-   [ ] Frontend JavaScript variant matching
-   [ ] Search/filtering logic
-   [ ] Reporting queries using variant data

## 🔄 Rollback Plan

### Emergency Rollback (if migration fails)

1. **Restore database backup**
2. **Revert git branch**
3. **Clear application cache**
4. **Restart services**

### Partial Rollback (if issues found later)

1. **Keep JSON columns but restore relationships**
2. **Run reverse migration script**
3. **Restore complex admin interfaces**

## 📈 Success Metrics

### Data Migration Success

-   [ ] 100% of products have valid attributes JSON
-   [ ] 100% of variants converted to JSON format
-   [ ] 0 data loss during migration
-   [ ] All existing orders remain intact

### Functionality Success

-   [ ] Product detail pages work with new system
-   [ ] Cart operations function correctly
-   [ ] Admin can manage variants via JSON editor
-   [ ] Search/filtering works with attributes JSON

### Performance Success

-   [ ] Page load times ≤ current performance
-   [ ] Database query count reduced
-   [ ] Memory usage optimized

## ⏱️ Estimated Timeline

| Phase               | Duration  | Risk   | Dependencies |
| ------------------- | --------- | ------ | ------------ |
| 1. Preparation      | 1-2 hours | LOW    | None         |
| 2. Data Migration   | 2-3 hours | HIGH   | Phase 1      |
| 3. Schema Changes   | 1 hour    | HIGH   | Phase 2      |
| 4. Model Updates    | 2-3 hours | MEDIUM | Phase 3      |
| 5. Frontend Updates | 3-4 hours | HIGH   | Phase 4      |
| 6. Admin Panel      | 4-5 hours | HIGH   | Phase 5      |
| 7. Testing          | 2-3 hours | MEDIUM | Phase 6      |

**Total Estimated Time: 15-21 hours**

## 🎯 Go/No-Go Decision Points

### Before Phase 2 (Data Migration)

-   [ ] Full backup completed and verified
-   [ ] Migration script tested on copy of database
-   [ ] Rollback procedure tested

### Before Phase 5 (Frontend Changes)

-   [ ] Data migration 100% successful
-   [ ] Models working with new JSON structure
-   [ ] Basic functionality tests passing

### Before Phase 6 (Admin Panel)

-   [ ] Frontend working with simplified system
-   [ ] Cart operations functioning
-   [ ] No critical bugs in core functionality

## 🚀 Next Steps

1. **Review this plan** and approve/modify as needed
2. **Set up development environment** for safe testing
3. **Create database backup** and test restoration
4. **Begin Phase 1** preparation work

## 🔧 Technical Implementation Details

### New Database Schema (Simplified with SKU-based Inventory)

```sql
-- Updated products table (for specs and single-SKU products)
ALTER TABLE products ADD COLUMN attributes JSON COMMENT 'Product specs for filtering/search';

-- Keep simplified product_variants table for SKU-based inventory tracking
-- This prevents overselling while maintaining simplicity
CREATE TABLE product_variants (
    id BIGINT PRIMARY KEY,
    product_id BIGINT REFERENCES products(id),
    sku TEXT UNIQUE NOT NULL,
    options JSON, -- {"Color":"Black","Storage":"256 GB"}
    price_cents INT NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Example data structure:
-- products.attributes: {"brand":"Zephyr","cpu":"Snapdragon 8","display":"6.7\" OLED"}
-- product_variants: {"sku":"PHN-ZEP-BLK-128","options":{"Color":"Black","Storage":"128GB"},"price_cents":79900,"stock_quantity":20}
```

### Data Migration Script Template

```php
class MigrateToSimpleVariants extends Command
{
    public function handle()
    {
        $this->info('Starting migration to simple variant system...');

        DB::transaction(function () {
            $products = Product::with([
                'variants.attributeValues.attribute',
                'specificationsWithAttributes.specificationAttribute'
            ])->get();

            foreach ($products as $product) {
                // Build attributes JSON from specifications
                $attributes = $this->buildAttributesFromSpecs($product);

                // Migrate variants to simplified product_variants table
                $this->migrateVariantsToSimpleTable($product);

                // Update product with attributes only
                $product->update([
                    'attributes' => $attributes
                ]);

                $this->info("Migrated product: {$product->name}");
            }
        });

        $this->info('Migration completed successfully!');
    }

    private function buildAttributesFromSpecs($product)
    {
        $attributes = [];

        // Add basic product info
        $attributes['brand'] = $product->brand->name ?? 'Unknown';

        // Add specifications as attributes
        foreach ($product->specificationsWithAttributes as $spec) {
            $key = $spec->specificationAttribute->code;
            $value = $spec->getFormattedValue();
            $attributes[$key] = $value;
        }

        return $attributes;
    }

    private function migrateVariantsToSimpleTable($product)
    {
        if (!$product->has_variants) {
            return;
        }

        // Clear existing simple variants for this product
        DB::table('product_variants')->where('product_id', $product->id)->delete();

        foreach ($product->variants as $variant) {
            $options = [];

            // Build options from attribute values
            foreach ($variant->attributeValues as $attributeValue) {
                $attributeName = $attributeValue->attribute->name;
                $options[$attributeName] = $attributeValue->value;
            }

            // Insert into simplified product_variants table
            DB::table('product_variants')->insert([
                'product_id' => $product->id,
                'sku' => $variant->sku,
                'options' => json_encode($options),
                'price_cents' => $variant->price_cents,
                'stock_quantity' => $variant->stock_quantity,
                'image_url' => $variant->images[0] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
```

### Simplified Model Structure with SKU-based Inventory

```php
class Product extends Model
{
    protected $casts = [
        'attributes' => 'array',
        'images' => 'array',
        'has_variants' => 'boolean'
    ];

    // Relationship to simplified variants
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Get available options for dropdowns
    public function getAvailableOptions()
    {
        $options = [];

        foreach ($this->variants as $variant) {
            foreach ($variant->options ?? [] as $key => $value) {
                if (!isset($options[$key])) {
                    $options[$key] = [];
                }
                if (!in_array($value, $options[$key])) {
                    $options[$key][] = $value;
                }
            }
        }

        return $options;
    }

    // Get price range from variants
    public function getPriceRange()
    {
        if (!$this->has_variants) {
            return ['min' => $this->price_cents / 100, 'max' => $this->price_cents / 100];
        }

        $prices = $this->variants->pluck('price_cents')->map(fn($p) => $p / 100);
        return ['min' => $prices->min(), 'max' => $prices->max()];
    }
}

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'options', 'price_cents',
        'stock_quantity', 'image_url'
    ];

    protected $casts = [
        'options' => 'array'
    ];

    // Simple inventory operations
    public function reduceStock($quantity)
    {
        if ($this->stock_quantity < $quantity) {
            return false; // Prevent overselling
        }

        $this->decrement('stock_quantity', $quantity);
        return true;
    }

    public function increaseStock($quantity)
    {
        $this->increment('stock_quantity', $quantity);
    }

    public function isInStock()
    {
        return $this->stock_quantity > 0;
    }
}
```

## 🛠️ Implementation Steps

### Step 1: Create Migration Command

```bash
php artisan make:command MigrateToSimpleVariants
```

### Step 2: Add JSON Columns

```bash
php artisan make:migration add_json_columns_to_products_table
```

### Step 3: Run Data Migration

```bash
php artisan migrate:simple-variants --dry-run  # Test first
php artisan migrate:simple-variants            # Actual migration
```

### Step 4: Update Models

-   Remove complex relationships
-   Add JSON-based methods
-   Update accessors/mutators

### Step 5: Update Frontend

-   Simplify ProductDetailPage
-   Update product card component
-   Modify cart handling

### Step 6: Update Admin Panel

-   Remove complex Filament resources
-   Add simple JSON editors for attributes
-   Create basic variant management with SKU-based inventory
-   Add simple stock management interface

## 📝 Files That Need Changes

### Models (6 files)

-   [ ] `app/Models/Product.php` - Major simplification
-   [ ] `app/Models/ProductVariant.php` - Remove (keep for orders)
-   [ ] `app/Models/ProductAttribute.php` - Remove
-   [ ] `app/Models/ProductAttributeValue.php` - Remove
-   [ ] `app/Models/OrderItem.php` - Update variant handling
-   [ ] `app/Models/InventoryReservation.php` - Remove

### Controllers/Livewire (3 files)

-   [ ] `app/Livewire/ProductDetailPage.php` - Major refactor
-   [ ] `app/Livewire/ProductsPage.php` - Simplify queries
-   [ ] `app/Helpers/CartManagement.php` - Simplify variant logic

### Frontend Templates (4 files)

-   [ ] `resources/views/livewire/product-detail-page.blade.php` - Simplify
-   [ ] `resources/views/components/product-card.blade.php` - Simplify
-   [ ] `resources/views/livewire/cart-page.blade.php` - Update variant display
-   [ ] `resources/js/components/variant-matcher.js` - Remove

### Admin Resources (8 files)

-   [ ] `app/Filament/Resources/ProductResource.php` - Add JSON editors
-   [ ] `app/Filament/Resources/ProductAttributeResource.php` - Remove
-   [ ] `app/Filament/Resources/ProductAttributeValueResource.php` - Remove
-   [ ] `app/Filament/Resources/ProductResource/RelationManagers/VariantsRelationManager.php` - Remove
-   [ ] All specification-related Filament resources - Remove

### Services (3 files)

-   [ ] `app/Services/InventoryReservationService.php` - Remove
-   [ ] `app/Services/OrderService.php` - Simplify variant handling
-   [ ] `app/Services/CartValidationService.php` - Simplify

## 🎯 Success Criteria

### Functional Requirements

-   [ ] Products display correctly with JSON attributes
-   [ ] Variant selection works with simple dropdowns
-   [ ] Cart handles variants with SKU-based inventory
-   [ ] Orders can be placed with variants
-   [ ] Admin can manage variants and stock levels
-   [ ] Inventory tracking prevents overselling
-   [ ] Stock status displays correctly (in stock/out of stock)

### Performance Requirements

-   [ ] Page load times ≤ current performance
-   [ ] Database size reduced by removing normalized tables
-   [ ] Simpler queries for basic operations

### Data Integrity Requirements

-   [ ] No data loss during migration
-   [ ] All existing orders remain accessible
-   [ ] SKU patterns maintained
-   [ ] Price/stock data preserved

## 🚀 Immediate Action Items

### Before Starting Migration

1. **Review and Approve Plan**

    - [ ] Confirm you want to lose advanced features (reservations, complex admin, etc.)
    - [ ] Understand the trade-offs (simplicity vs functionality)
    - [ ] Approve the 15-21 hour time investment

2. **Prepare Development Environment**

    - [ ] Create full database backup
    - [ ] Set up separate development branch
    - [ ] Test backup restoration process
    - [ ] Document current system state

3. **Stakeholder Communication**
    - [ ] Inform team about upcoming changes
    - [ ] Plan for temporary admin panel downtime
    - [ ] Schedule migration during low-traffic period

### Critical Decision Points

**🤔 Key Questions to Answer:**

1. **Are you willing to lose inventory reservations?** (Prevents overselling in high-traffic)
2. **Can you live with JSON editors instead of rich admin forms?** (Less user-friendly)
3. **Is the simplicity worth losing performance optimizations?** (May be slower with large catalogs)
4. **Do you need the advanced specification system?** (Separate from variants)

### Alternative: Hybrid Approach

If you want simplicity but keep some advanced features:

**Option A1: Simple Frontend, Complex Backend**

-   Keep current database structure
-   Simplify frontend to match simple document
-   Hide complexity from users but maintain power

**Option A2: Gradual Simplification**

-   Start with frontend simplification
-   Gradually remove unused backend complexity
-   Preserve critical features like inventory management

## 📋 Final Checklist Before Migration

-   [ ] **Full database backup created and tested**
-   [ ] **Migration plan reviewed and approved**
-   [ ] **Development environment prepared**
-   [ ] **Team notified of upcoming changes**
-   [ ] **Rollback procedure tested**
-   [ ] **Success criteria defined and agreed upon**

**Ready to proceed with Phase 1?** This migration will significantly simplify your system but requires careful execution to avoid data loss.

---

## 🎯 Key Benefits of SKU-based Inventory Approach

### ✅ Prevents Overselling

-   Each variant has its own `stock_quantity` field
-   Atomic stock updates with `WHERE stock_quantity >= :qty` condition
-   No complex reservation system needed

### ✅ Simple but Effective

-   Two-table approach: `products` (specs) + `product_variants` (SKU inventory)
-   JSON for flexible attributes and variant options
-   Standard SQL operations for inventory management

### ✅ Maintains Essential Features

-   **Inventory Tracking**: Per-SKU stock quantities
-   **Overselling Prevention**: Database-level stock validation
-   **Stock Status**: Simple in_stock/out_of_stock logic
-   **Low Stock Alerts**: Configurable thresholds per variant

### ✅ Easy to Understand & Maintain

-   Clear separation: specs vs. inventory
-   Human-readable JSON for variant options
-   Simple model relationships
-   Straightforward admin interface

---

**⚠️ IMPORTANT:** Once we start Phase 2 (Data Migration), there's no easy way back without restoring from backup. Make sure you're 100% committed to the simplified approach before proceeding.
