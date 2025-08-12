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



