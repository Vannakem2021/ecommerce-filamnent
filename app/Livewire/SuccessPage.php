<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Order Success - ByteWebster')]

class SuccessPage extends Component
{
    public $order;
    public $order_items = [];
    public $recommended_products = [];
    public $user_email;
    public $order_number;
    public $order_date;
    public $order_total;
    public $shipping_address;
    public $payment_method;
    public $estimated_delivery;

    public function mount($order_id = null)
    {
        // In a real application, you would get the order_id from the session or URL parameter
        // For now, we'll simulate order data or get the latest order for the authenticated user

        if (auth()->check()) {
            $this->user_email = auth()->user()->email;

            // Get the latest order for the user (in real app, this would be passed from checkout)
            $this->order = Order::with(['items.product', 'address', 'user'])
                ->where('user_id', auth()->id())
                ->latest()
                ->first();

            if ($this->order) {
                $this->loadOrderData();
            } else {
                $this->generateSampleOrderData();
            }
        } else {
            $this->generateSampleOrderData();
        }

        // Load recommended products
        $this->loadRecommendedProducts();
    }

    private function loadOrderData()
    {
        $this->order_number = 'TS-' . date('Y') . '-' . str_pad($this->order->id, 6, '0', STR_PAD_LEFT);
        $this->order_date = $this->order->created_at->format('F j, Y \a\t g:i A');
        $this->order_total = $this->order->grand_total;
        $this->payment_method = ucfirst($this->order->payment_method);

        // Load order items
        $this->order_items = $this->order->items->map(function ($item) {
            return [
                'name' => $item->product->name ?? 'Unknown Product',
                'sku' => $item->product->sku ?? 'N/A',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_amount,
                'total_price' => $item->total_amount,
                'image' => $item->product->images ? (is_string($item->product->images) ? json_decode($item->product->images, true)[0] ?? null : $item->product->images[0] ?? null) : null,
            ];
        })->toArray();

        // Load shipping address
        if ($this->order->address) {
            $this->shipping_address = [
                'name' => $this->order->address->full_name,
                'street' => $this->order->address->street_address,
                'city' => $this->order->address->city,
                'state' => $this->order->address->state,
                'zip' => $this->order->address->zip_code,
                'phone' => $this->order->address->phone,
            ];
        }

        // Calculate estimated delivery (5-7 business days from now)
        $startDate = $this->addBusinessDays(now(), 5);
        $endDate = $this->addBusinessDays(now(), 7);
        $this->estimated_delivery = $startDate->format('F j') . '-' . $endDate->format('j, Y');
    }

    private function generateSampleOrderData()
    {
        // Generate sample data for demonstration
        $this->order_number = 'TS-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $this->order_date = now()->format('F j, Y \a\t g:i A');
        $this->order_total = 483.95;
        $this->payment_method = 'Credit Card';
        $this->user_email = $this->user_email ?? 'john.doe@example.com';

        $this->order_items = [
            [
                'name' => 'Premium Wireless Headphones',
                'sku' => 'PWH-001',
                'quantity' => 1,
                'unit_price' => 149.99,
                'total_price' => 149.99,
                'image' => null,
            ],
            [
                'name' => 'Smart Watch Series 5',
                'sku' => 'SW5-002',
                'quantity' => 1,
                'unit_price' => 299.99,
                'total_price' => 299.99,
                'image' => null,
            ],
            [
                'name' => 'Professional Yoga Mat',
                'sku' => 'PYM-003',
                'quantity' => 2,
                'unit_price' => 49.99,
                'total_price' => 99.98,
                'image' => null,
            ],
        ];

        $this->shipping_address = [
            'name' => 'John Doe',
            'street' => '123 Main Street, Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'phone' => '+1 (555) 123-4567',
        ];

        $startDate = $this->addBusinessDays(now(), 5);
        $endDate = $this->addBusinessDays(now(), 7);
        $this->estimated_delivery = $startDate->format('F j') . '-' . $endDate->format('j, Y');
    }

    private function loadRecommendedProducts()
    {
        // Get 4 random products for recommendations
        $this->recommended_products = Product::where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->get()
            ->map(function ($product) {
                $images = is_string($product->images) ? json_decode($product->images, true) : $product->images;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => is_array($images) ? ($images[0] ?? null) : null,
                    'on_sale' => $product->on_sale,
                    'is_featured' => $product->is_featured,
                ];
            })
            ->toArray();
    }

    /**
     * Add business days to a date (excluding weekends)
     */
    private function addBusinessDays($date, $businessDays)
    {
        $currentDate = $date->copy();
        $addedDays = 0;

        while ($addedDays < $businessDays) {
            $currentDate->addDay();

            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($currentDate->dayOfWeek !== 0 && $currentDate->dayOfWeek !== 6) {
                $addedDays++;
            }
        }

        return $currentDate;
    }

    public function render()
    {
        return view('livewire.success-page');
    }
}
