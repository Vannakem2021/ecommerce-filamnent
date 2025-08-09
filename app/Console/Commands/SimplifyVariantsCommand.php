<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\SpecificationAttribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SimplifyVariantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:simplify-variants {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate from complex normalized variant system to simple JSON-based approach with SKU inventory tracking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting migration to simple variant system...');
        $this->info('');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->info('');
        }

        try {
            // Step 1: Add new columns if they don't exist
            $this->addNewColumns($dryRun);

            // Step 2: Migrate product data
            $this->migrateProductData($dryRun);

            // Step 3: Simplify variant table structure
            $this->simplifyVariantTable($dryRun);

            // Step 4: Validate migration
            $this->validateMigration($dryRun);

            $this->info('');
            $this->info('✅ Migration completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Add new columns for simplified structure
     */
    protected function addNewColumns($dryRun = false)
    {
        $this->info('📝 Step 1: Adding new columns...');

        if (!Schema::hasColumn('products', 'attributes')) {
            $this->info('  - Adding attributes JSON column to products table');
            if (!$dryRun) {
                Schema::table('products', function ($table) {
                    $table->json('attributes')->nullable()->comment('Product specs for filtering/search');
                });
            }
        } else {
            $this->info('  - attributes column already exists');
        }

        if (!Schema::hasColumn('product_variants', 'options')) {
            $this->info('  - Adding options JSON column to product_variants table');
            if (!$dryRun) {
                Schema::table('product_variants', function ($table) {
                    $table->json('options')->nullable()->comment('Variant choices (Color, Storage, etc.)');
                    $table->string('image_url')->nullable()->comment('Variant-specific image');
                });
            }
        } else {
            $this->info('  - options column already exists');
        }

        $this->info('✅ Step 1 completed');
        $this->info('');
    }

    /**
     * Migrate product data to new structure
     */
    protected function migrateProductData($dryRun = false)
    {
        $this->info('🔄 Step 2: Migrating product data...');

        // First, fix has_variants flags based on actual variant count
        $this->fixHasVariantsFlags($dryRun);

        // Reload products with updated flags and relationships
        $products = Product::with([
            'variants.attributeValues.attribute',
            'brand'
        ])->withCount('variants')->get();

        $this->info("  - Found {$products->count()} products to migrate");

        foreach ($products as $product) {
            $this->info("  - Migrating product: {$product->name}");

            // Build attributes JSON from specifications
            $attributes = $this->buildAttributesFromSpecs($product);

            // Migrate variants to simplified structure
            $this->migrateVariantsForProduct($product, $dryRun);

            // Update product with attributes
            if (!$dryRun) {
                $product->update(['attributes' => $attributes]);
            }
        }

        $this->info('✅ Step 2 completed');
        $this->info('');
    }

    /**
     * Fix has_variants flags based on actual variant count
     */
    protected function fixHasVariantsFlags($dryRun = false)
    {
        $this->info('  - Fixing has_variants flags...');

        $products = Product::withCount('variants')->get();

        foreach ($products as $product) {
            $shouldHaveVariants = $product->variants_count > 0;

            if ($product->has_variants !== $shouldHaveVariants) {
                $this->info("    - Updating {$product->name}: has_variants = " . ($shouldHaveVariants ? 'true' : 'false') . " (found {$product->variants_count} variants)");

                if (!$dryRun) {
                    $product->update(['has_variants' => $shouldHaveVariants]);
                }
            }
        }
    }

    /**
     * Build attributes JSON from product specifications
     */
    protected function buildAttributesFromSpecs($product)
    {
        $attributes = [];

        // Add basic product info
        if ($product->brand) {
            $attributes['brand'] = $product->brand->name;
        }

        // For now, we'll add some basic attributes based on product data
        // In a real migration, you would extract from your specification system
        $attributes['name'] = $product->name;
        $attributes['description'] = $product->description ?? '';

        // Add category if available
        if ($product->category) {
            $attributes['category'] = $product->category->name;
        }

        return $attributes;
    }

    /**
     * Migrate variants for a specific product
     */
    protected function migrateVariantsForProduct($product, $dryRun = false)
    {
        // Use actual variant count from database
        $variantCount = $product->variants_count ?? 0;

        if ($variantCount === 0) {
            $this->info("    - Product {$product->name} has no variants, skipping");
            return;
        }

        $this->info("    - Found {$variantCount} variants for {$product->name}");

        // Get variants directly from database to avoid relationship issues
        $variants = DB::table('product_variants')
            ->where('product_id', $product->id)
            ->get();

        foreach ($variants as $variant) {
            // Get attribute values for this variant
            $attributeValues = DB::table('product_variant_attributes as pva')
                ->join('product_attribute_values as pav', 'pva.product_attribute_value_id', '=', 'pav.id')
                ->join('product_attributes as pa', 'pva.product_attribute_id', '=', 'pa.id')
                ->where('pva.product_variant_id', $variant->id)
                ->select('pa.name as attribute_name', 'pav.value as attribute_value')
                ->get();

            $options = [];
            foreach ($attributeValues as $attrValue) {
                $options[$attrValue->attribute_name] = $attrValue->attribute_value;
            }

            // Get first image if available
            $imageUrl = null;
            if ($variant->images) {
                $images = json_decode($variant->images, true);
                if (is_array($images) && !empty($images)) {
                    $imageUrl = $images[0];
                }
            }

            // Update variant with new structure
            if (!$dryRun) {
                DB::table('product_variants')
                    ->where('id', $variant->id)
                    ->update([
                        'options' => json_encode($options),
                        'image_url' => $imageUrl,
                        'updated_at' => now()
                    ]);
            }

            $this->info("    - Variant {$variant->sku}: " . json_encode($options));
        }
    }

    /**
     * Simplify variant table structure (remove unused columns)
     */
    protected function simplifyVariantTable($dryRun = false)
    {
        $this->info('🗑️  Step 3: Simplifying variant table structure...');

        // Mark complex tables as deprecated instead of dropping them
        // This preserves order history and allows rollback
        $tablesToDeprecate = [
            'product_variant_attributes',
            'product_attributes',
            'product_attribute_values',
            'specification_attributes',
            'product_specification_values',
            'variant_specification_values'
        ];

        foreach ($tablesToDeprecate as $table) {
            if (Schema::hasTable($table)) {
                $this->info("  - Marking {$table} as deprecated");
                if (!$dryRun && !Schema::hasColumn($table, 'deprecated_at')) {
                    Schema::table($table, function ($tableSchema) {
                        $tableSchema->timestamp('deprecated_at')->nullable();
                    });
                    DB::table($table)->update(['deprecated_at' => now()]);
                }
            }
        }

        $this->info('✅ Step 3 completed');
        $this->info('');
    }

    /**
     * Validate the migration results
     */
    protected function validateMigration($dryRun = false)
    {
        $this->info('🔍 Step 4: Validating migration...');

        $totalProducts = Product::count();
        $productsWithAttributes = Product::whereNotNull('attributes')->count();
        $totalVariants = DB::table('product_variants')->count();
        $variantsWithOptions = DB::table('product_variants')->whereNotNull('options')->count();

        $this->info("  - Total products: {$totalProducts}");
        $this->info("  - Products with attributes: {$productsWithAttributes}");
        $this->info("  - Total variants: {$totalVariants}");
        $this->info("  - Variants with options: {$variantsWithOptions}");

        if ($productsWithAttributes === $totalProducts) {
            $this->info('  ✅ All products have attributes');
        } else {
            $this->warn('  ⚠️  Some products missing attributes');
        }

        if ($variantsWithOptions === $totalVariants) {
            $this->info('  ✅ All variants have options');
        } else {
            $this->warn('  ⚠️  Some variants missing options');
        }

        $this->info('✅ Step 4 completed');
    }
}
