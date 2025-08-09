<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SpecificationAttribute;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Products - ByteWebster')]

class ProductsPage extends Component
{
    use LivewireAlert;

    use WithPagination;

    #[Url]
    public $selected_categories = [];

    #[Url]
    public $selected_brands = [];

    #[Url]
    public $featured = [];

    #[Url]
    public $on_sale = [];

    #[Url]
    public $price_range = 0;

    #[Url]
    public $sort = 'latest';

    #[Url]
    public $specification_filters = [];

    // Method for adding the product in the cart
    public function addToCart($product_id)
    {
        $result = CartManagement::addItemToCart($product_id);

        // Check if there was an error (inventory validation failed)
        if (is_array($result) && isset($result['error'])) {
            $this->alert('error', $result['error'], [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        $total_count = CartManagement::calculateTotalQuantity($result);
        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Product added to the cart successfully!', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
        ]);
    }

    // Method for quick view functionality
    public function quickView($product_id)
    {
        $product = Product::find($product_id);

        if ($product) {
            $this->alert('info', 'Quick view for ' . $product->name, [
                'position' => 'top-end',
                'timer' => 2000,
                'toast' => true,
            ]);

            // In a real implementation, you might open a modal or redirect
            // For now, we'll just show a notification
        }
    }

    // Method for wishlist functionality
    public function toggleWishlist($product_id)
    {
        $product = Product::find($product_id);

        if ($product) {
            // In a real implementation, you would save/remove from wishlist in database
            // For now, we'll just show a notification
            $this->alert('success', 'Added ' . $product->name . ' to wishlist!', [
                'position' => 'top-end',
                'timer' => 2000,
                'toast' => true,
            ]);
        }
    }

    // Method to reset all filters
    public function resetFilters()
    {
        $this->selected_categories = [];
        $this->selected_brands = [];
        $this->featured = [];
        $this->on_sale = [];
        $this->price_range = 0;
        $this->specification_filters = [];
        $this->sort = 'latest';

        $this->alert('info', 'All filters have been reset!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function render()
    {
        $products = Product::query()
            ->where('is_active', 1)
            ->with([
                'category:id,name,slug',
                'brand:id,name,slug',
                'variants' => function ($query) {
                    $query->select('id', 'product_id', 'sku', 'price_cents', 'compare_price_cents', 'stock_quantity', 'stock_status', 'is_active', 'is_default')
                        ->where('is_active', true)
                        ->orderBy('is_default', 'desc');
                },
                'variants.attributeValues:id,value,color_code,product_attribute_id',
                'variants.attributeValues.attribute:id,name,slug,type',
                'defaultVariant:id,product_id,sku,price_cents,compare_price_cents,stock_quantity,is_active,is_default'
            ])
            ->withCount(['variants as active_variants_count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->selectRaw('products.*,
                CASE
                    WHEN has_variants = 1 THEN (
                        SELECT MIN(price_cents) FROM product_variants
                        WHERE product_variants.product_id = products.id
                        AND product_variants.is_active = 1
                    )
                    ELSE price_cents
                END as min_price_cents,
                CASE
                    WHEN has_variants = 1 THEN (
                        SELECT MAX(price_cents) FROM product_variants
                        WHERE product_variants.product_id = products.id
                        AND product_variants.is_active = 1
                    )
                    ELSE price_cents
                END as max_price_cents');

        $brands = Brand::where('is_active', 1)->get(['id', 'name', 'slug']);

        $categories = Category::where('is_active', 1)->get(['id', 'name', 'slug']);

        // Get filterable specifications
        $filterableSpecs = SpecificationAttribute::active()
            ->filterable()
            ->with(['options' => function ($query) {
                $query->active()->ordered();
            }])
            ->ordered()
            ->get();


        if(!empty($this->selected_categories))
        {
            $products->whereIn('category_id', $this->selected_categories);
        }

        if(!empty($this->selected_brands))
        {
            $products->whereIn('brand_id', $this->selected_brands);
        }

        if($this->featured){
            $products->where('is_featured', 1);
        }

        if($this->on_sale){
            $products->where('on_sale', 1);
        }

        if($this->price_range){
            $products->whereBetween('price', [0, $this->price_range]);
        }

        // Apply specification filters
        if (!empty($this->specification_filters)) {
            foreach ($this->specification_filters as $specCode => $filterValue) {
                if (empty($filterValue)) continue;

                $spec = $filterableSpecs->where('code', $specCode)->first();
                if (!$spec) continue;

                if ($spec->scope === 'product') {
                    // Filter by product-level specifications
                    $products->whereHas('specificationsWithAttributes', function ($query) use ($spec, $filterValue) {
                        $query->where('specification_attribute_id', $spec->id);

                        if ($spec->data_type === 'number') {
                            // Handle numeric range filters
                            if (is_array($filterValue) && count($filterValue) === 2) {
                                $query->whereBetween('value_number', $filterValue);
                            } else {
                                $query->where('value_number', '>=', $filterValue);
                            }
                        } elseif ($spec->data_type === 'enum') {
                            // Handle enum filters
                            if (is_array($filterValue)) {
                                $query->whereIn('specification_attribute_option_id', $filterValue);
                            } else {
                                $query->where('specification_attribute_option_id', $filterValue);
                            }
                        } else {
                            // Handle text filters
                            $query->where('value_text', 'LIKE', "%{$filterValue}%");
                        }
                    });
                } else {
                    // Filter by variant-level specifications
                    $products->whereHas('variants.specificationsWithAttributes', function ($query) use ($spec, $filterValue) {
                        $query->where('specification_attribute_id', $spec->id);

                        if ($spec->data_type === 'number') {
                            if (is_array($filterValue) && count($filterValue) === 2) {
                                $query->whereBetween('value_number', $filterValue);
                            } else {
                                $query->where('value_number', '>=', $filterValue);
                            }
                        } elseif ($spec->data_type === 'enum') {
                            if (is_array($filterValue)) {
                                $query->whereIn('specification_attribute_option_id', $filterValue);
                            } else {
                                $query->where('specification_attribute_option_id', $filterValue);
                            }
                        } else {
                            $query->where('value_text', 'LIKE', "%{$filterValue}%");
                        }
                    });
                }
            }
        }

        if($this->sort == 'latest'){
            $products->latest();
        }

        if($this->sort == 'price'){
            $products->orderBy('price');
        }

        return view('livewire.products-page', [
            'products' => $products->paginate(12),
            'brands' => $brands,
            'categories' => $categories,
            'filterableSpecs' => $filterableSpecs
        ]);
    }
}
