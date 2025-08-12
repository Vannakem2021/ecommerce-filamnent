<?php

namespace App\Livewire;

use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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
    public $on_sale = [];

    #[Url]
    public $price_range = 0;

    #[Url]
    public $sort = 'latest';

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
        $this->on_sale = [];
        $this->price_range = 0;
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
                    $query->where('is_active', true)
                        ->orderBy('is_default', 'desc');
                },
                'defaultVariant'
            ]);

        $brands = Brand::where('is_active', 1)->get(['id', 'name', 'slug']);

        $categories = Category::where('is_active', 1)->get(['id', 'name', 'slug']);


        if(!empty($this->selected_categories))
        {
            $products->whereIn('category_id', $this->selected_categories);
        }

        if(!empty($this->selected_brands))
        {
            $products->whereIn('brand_id', $this->selected_brands);
        }

        if($this->on_sale){
            $products->where('on_sale', 1);
        }

        if($this->price_range){
            // Convert USD to cents for filtering
            $products->whereBetween('price_cents', [0, $this->price_range * 100]);
        }

        if($this->sort == 'latest'){
            $products->latest();
        }

        if($this->sort == 'price'){
            $products->orderBy('price_cents');
        }

        if($this->sort == 'price_desc'){
            $products->orderBy('price_cents', 'desc');
        }

        return view('livewire.products-page', [
            'products' => $products->paginate(12),
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }
}
