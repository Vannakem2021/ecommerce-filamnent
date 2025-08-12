<?php

use App\Livewire\Auth\ForgotPasswordPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\RegisterPage;
use App\Livewire\Auth\ResetPasswordPage;
use App\Livewire\CancelPage;
use App\Livewire\CartPage;
use App\Livewire\CategoriesPage;
use App\Livewire\CheckoutPage;
use App\Livewire\HomePage;
use App\Livewire\MyOrderDetailPage;
use App\Livewire\MyOrdersPage;
use App\Livewire\ProductDetailPage;
use App\Livewire\ProductsPage;
use App\Livewire\SuccessPage;
use App\Livewire\UserProfilePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/', HomePage::class)->name('index');

// Test route for filter functionality
Route::get('/test-filters', function () {
    $categories = \App\Models\Category::withCount('products')->get();
    return view('test-filters', compact('categories'));
})->name('test-filters');

Route::get('/categories', CategoriesPage::class)->name('product-categories');

Route::get('/products', ProductsPage::class)->name('all-products');

Route::get('/product/{slug}', ProductDetailPage::class)->name('product-details');

Route::get('/cart', CartPage::class)->name('cart-products');

// Add alias for cart route to prevent route not found errors
Route::get('/cart-alias', function() {
    return redirect()->route('cart-products');
})->name('cart');

Route::get('/checkout', CheckoutPage::class)->name('checkout')->middleware('auth');

Route::get('/my-orders', MyOrdersPage::class)->name('my-orders')->middleware('auth');

Route::get('/my-orders/{order}', MyOrderDetailPage::class)->name('my-order-details')->middleware('auth');

Route::get('/profile', UserProfilePage::class)->name('profile')->middleware('auth');

// Payment routes
Route::prefix('payment')->name('payment.')->group(function () {
    // PayWay redirect route - main payment flow
    Route::get('/aba-pay/redirect', [App\Http\Controllers\PaymentController::class, 'redirectToPayWay'])->name('aba-pay.redirect');

    // PayWay callback routes
    Route::any('/aba-pay/return', [App\Http\Controllers\PaymentController::class, 'abaPayReturn'])->name('aba-pay.return');
    Route::any('/aba-pay/cancel', [App\Http\Controllers\PaymentController::class, 'abaPayCancel'])->name('aba-pay.cancel');
    Route::any('/aba-pay/success', [App\Http\Controllers\PaymentController::class, 'abaPaySuccess'])->name('aba-pay.success');

    // PayWay webhook for payment status updates
    Route::any('/aba-pay/webhook', [App\Http\Controllers\PaymentController::class, 'abaPayWebhook'])->name('aba-pay.webhook');

    // Legacy route - redirects to correct flow (deprecated)
    Route::get('/aba-pay/process', [App\Http\Controllers\PaymentController::class, 'processAbaPayment'])->name('aba-pay.process');
});

// Test route for payment methods (simplified)
Route::get('/test-payment', function () {
    try {
        // Simple hardcoded payment methods since we removed the complex service
        $methods = [
            [
                'code' => 'aba_pay',
                'name' => 'ABA Pay',
                'description' => 'Pay securely with ABA Pay',
                'icon' => 'fas fa-credit-card',
                'provider' => 'payway'
            ],
            [
                'code' => 'cod',
                'name' => 'Cash on Delivery',
                'description' => 'Pay when your order is delivered',
                'icon' => 'fas fa-money-bill-wave',
                'provider' => 'manual'
            ]
        ];

        return response()->json([
            'success' => true,
            'methods' => $methods
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Debug checkout state
Route::get('/debug-checkout', function () {
    try {
        $cartItems = \App\Helpers\CartManagement::getValidatedCartItems();
        $grandTotal = \App\Helpers\CartManagement::calculateGrandTotal($cartItems);

        return response()->json([
            'success' => true,
            'cart_items' => $cartItems,
            'grand_total' => $grandTotal,
            'cart_count' => count($cartItems)
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Enhanced PayWay Debug Routes
Route::prefix('debug-payway')->group(function () {
    // Debug dashboard
    Route::get('/', function () {
        return view('debug.payway');
    });
    // Session debug
    Route::get('/session', function () {
        return response()->json([
            'session_id' => session()->getId(),
            'payway_payment_data' => session('payway_payment_data'),
            'payway_order_id' => session('payway_order_id'),
            'all_session' => session()->all()
        ]);
    });

    // Configuration check
    Route::get('/config', function () {
        try {
            return response()->json([
                'config_status' => 'loaded',
                'base_url' => config('payway.base_url'),
                'merchant_id' => config('payway.merchant_id') ? 'SET' : 'NOT SET',
                'secret_key' => config('payway.secret_key') ? 'SET (' . strlen(config('payway.secret_key')) . ' chars)' : 'NOT SET',
                'sandbox_mode' => config('payway.sandbox_mode'),
                'environment' => config('app.env'),
                'all_config' => config('payway')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Configuration Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    // Test redirect page directly
    Route::get('/test-redirect', function () {
        // Simulate payment data in session
        session([
            'payway_payment_data' => [
                'transaction_id' => 'TEST-' . time(),
                'amount' => 1.00,
                'currency' => 'USD',
                'firstname' => 'Test',
                'lastname' => 'User',
                'email' => 'test@example.com',
                'phone' => '123456789',
                'return_url' => route('payment.aba-pay.return'),
                'cancel_url' => route('payment.aba-pay.cancel'),
                'items' => [['name' => 'Test Product', 'quantity' => 1, 'price' => 1.00]],
                'shipping' => 0.00,
                'type' => 'purchase',
                'payment_option' => '',
            ],
            'payway_order_id' => 999
        ]);

        return redirect()->route('payment.aba-pay.redirect');
    });

    // Service test
    Route::get('/service', function () {
        try {
            $payWayService = new \App\Services\PayWayService();

            $paymentData = [
                'transaction_id' => 'DEBUG-' . time(),
                'amount' => 1.00,
                'currency' => 'USD',
                'firstname' => 'Debug',
                'lastname' => 'Test',
                'email' => 'debug@test.com',
                'phone' => '123456789',
                'return_url' => route('payment.aba-pay.return'),
                'cancel_url' => route('payment.aba-pay.cancel'),
                'items' => [
                    ['name' => 'Debug Product', 'quantity' => 1, 'price' => 1.00]
                ],
                'shipping' => 0.00,
                'type' => 'purchase',
                'payment_option' => '',
            ];

            $result = $payWayService->createPayment($paymentData);

            return response()->json([
                'service_status' => 'working',
                'payment_result' => $result,
                'payment_data_sent' => $paymentData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Service Error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    });

    // Test transaction creation
    Route::get('/test-transaction', function () {
        try {
            $testTransaction = \App\Models\AbaPayTransaction::create([
                'order_id' => null, // Test without order
                'transaction_id' => 'TEST-' . time(),
                'merchant_id' => config('payway.merchant_id') ?: 'test_merchant',
                'amount' => 1.00,
                'currency' => 'USD',
                'status' => \App\Models\AbaPayTransaction::STATUS_PENDING,
                'payment_option' => '',
                'payment_gate' => 'payway',
                'request_time' => now()->format('YmdHis'),
                'hash' => null,
                'shipping' => 0.00,
                'type' => 'purchase',
                'view_type' => 'checkout',
                'customer_info' => [
                    'firstname' => 'Test',
                    'lastname' => 'User',
                    'email' => 'test@test.com',
                    'phone' => '123456789',
                ],
                'urls' => [
                    'return_url' => '/test/return',
                    'cancel_url' => '/test/cancel',
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'transaction_id' => $testTransaction->id,
                'data' => $testTransaction->toArray()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    });

    // Run migrations (for development only)
    Route::get('/run-migrations', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Migrations completed',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Test cart route
    Route::get('/test-cart-route', function () {
        try {
            $cartUrl = route('cart');
            $cartProductsUrl = route('cart-products');

            return response()->json([
                'success' => true,
                'cart_route' => $cartUrl,
                'cart_products_route' => $cartProductsUrl,
                'message' => 'Both routes are working'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    });

    // Debug checkout access
    Route::get('/test-checkout-access', function () {
        try {
            $isAuthenticated = auth()->check();
            $user = auth()->user();
            $cartItems = \App\Helpers\CartManagement::getValidatedCartItems();

            return response()->json([
                'success' => true,
                'authenticated' => $isAuthenticated,
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'cart_items_count' => count($cartItems),
                'checkout_url' => route('checkout'),
                'can_access_checkout' => $isAuthenticated && !empty($cartItems),
                'cart_items' => $cartItems
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
});

// Auth routes

Route::get('/login', LoginPage::class)->name('login')->middleware('guest');

Route::get('/register', RegisterPage::class)->name('register')->middleware('guest');

Route::get('/forgot-password', ForgotPasswordPage::class)->name('forgot-password')->middleware('guest');

Route::get('/reset-password', ResetPasswordPage::class)->name('reset-password')->middleware('guest');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout')->middleware('auth');

Route::get('/success', SuccessPage::class)->name('success');

Route::get('/cancelled', CancelPage::class)->name('cancelled');



