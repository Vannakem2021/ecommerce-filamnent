# PayWay Implementation Issues and Fixes

## Current State Analysis

After comparing the ecommerce-filament project with the working ABA PayWay implementation plan, I've identified several critical issues that need to be addressed to make the payment method work correctly.

## Critical Issues Found

### 1. **Configuration Mismatch**
**Issue**: The current implementation uses inconsistent environment variable naming.

**Current (.env.example)**:
```env
# Current - Inconsistent naming
ABA_PAY_MERCHANT_ID=your_merchant_id
ABA_PAY_API_KEY=your_api_key
ABA_PAY_ENVIRONMENT=sandbox
```

**Should be (following working sample)**:
```env
# Fixed - Consistent PayWay naming
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=your_merchant_id_here
PAYWAY_SECRET_KEY=your_secret_key_here
PAYWAY_DEFAULT_CURRENCY=USD
PAYWAY_DEFAULT_LIFETIME=30
PAYWAY_SANDBOX_MODE=true
```

### 2. **PayWay Service Implementation Issues**

#### Missing Methods
The current `PayWayService` is missing several critical methods from the working implementation:

**Missing Methods**:
- `verifyCallback(array $callbackData)`: For webhook verification
- `checkTransactionStatus($transactionId)`: For status checking
- `getTransactionList()`: For admin reconciliation

#### Incomplete Hash Generation
The current hash generation is missing some parameters and validation.

### 3. **Payment Flow Issues**

#### Problem with HTML Content Display
**Current Issue**: The implementation tries to display PayWay's HTML response directly, but PayWay actually expects a redirect-based flow.

**Current Flow (INCORRECT)**:
1. Get HTML content from PayWay API
2. Store in session
3. Display HTML content in view
4. Hope it works

**Correct Flow (from working sample)**:
1. Prepare payment parameters
2. Create auto-submit form to PayWay
3. Redirect user to PayWay's domain
4. PayWay handles payment and redirects back

### 4. **Route Structure Issues**

**Current Routes**:
```php
Route::any('/aba-pay/return', [PaymentController::class, 'abaPayReturn'])
Route::any('/aba-pay/cancel', [PaymentController::class, 'abaPayCancel'])
Route::any('/aba-pay/success', [PaymentController::class, 'abaPaySuccess'])
```

**Working Sample Routes**:
```php
Route::get('/success', [PaymentController::class, 'paymentSuccess'])
Route::get('/cancel', [PaymentController::class, 'paymentCancel'])
Route::post('/callback', [PaymentController::class, 'handlePayWayCallback'])
```

### 5. **Missing Webhook Implementation**
The current implementation has a webhook route but no proper webhook handling logic.

### 6. **Transaction Tracking Issues**
The current implementation doesn't properly track PayWay transactions in the database.

## Required Fixes

### Fix 1: Update Environment Configuration

**Update `.env.example`**:
```env
# PayWay Configuration (Replace ABA_PAY_* variables)
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=your_merchant_id_here
PAYWAY_SECRET_KEY=your_secret_key_here
PAYWAY_DEFAULT_CURRENCY=USD
PAYWAY_DEFAULT_LIFETIME=30
PAYWAY_SANDBOX_MODE=true
PAYWAY_DEFAULT_RETURN_URL=/payment/aba-pay/return
PAYWAY_DEFAULT_CANCEL_URL=/payment/aba-pay/cancel
```

### Fix 2: Update PayWay Configuration File

**Update `config/payway.php`**:
```php
<?php

return [
    'base_url' => env('PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
    'merchant_id' => env('PAYWAY_MERCHANT_ID'),
    'secret_key' => env('PAYWAY_SECRET_KEY'),
    
    // Environment-specific settings
    'environments' => [
        'sandbox' => [
            'base_url' => 'https://checkout-sandbox.payway.com.kh',
            'verify_ssl' => false,
            'timeout' => 30,
        ],
        'production' => [
            'base_url' => 'https://checkout.payway.com.kh', 
            'verify_ssl' => true,
            'timeout' => 15,
        ]
    ],
    
    'default_currency' => env('PAYWAY_DEFAULT_CURRENCY', 'USD'),
    'default_lifetime' => env('PAYWAY_DEFAULT_LIFETIME', 30),
    'sandbox_mode' => env('PAYWAY_SANDBOX_MODE', true),
    
    // URL settings
    'urls' => [
        'return_url' => env('PAYWAY_DEFAULT_RETURN_URL', '/payment/aba-pay/return'),
        'cancel_url' => env('PAYWAY_DEFAULT_CANCEL_URL', '/payment/aba-pay/cancel'),
        'webhook_url' => env('PAYWAY_WEBHOOK_URL', '/payment/aba-pay/webhook'),
    ],
];
```

### Fix 3: Complete PayWay Service Implementation

**Add missing methods to `app/Services/PayWayService.php`**:

```php
/**
 * Verify callback hash
 */
public function verifyCallback(array $callbackData)
{
    $receivedHash = $callbackData['hash'] ?? '';
    unset($callbackData['hash']);

    $calculatedHash = $this->generateHash($callbackData);

    return hash_equals($calculatedHash, $receivedHash);
}

/**
 * Check transaction status
 */
public function checkTransactionStatus($transactionId)
{
    $result = $this->getTransactionDetails($transactionId);
    
    if ($result['success']) {
        $data = $result['data'];
        
        // Map PayWay status to local status
        $statusMap = [
            '00' => 'completed',
            '01' => 'pending',
            '02' => 'failed',
            '03' => 'cancelled'
        ];
        
        return [
            'success' => true,
            'status' => $statusMap[$data['status']['code']] ?? 'unknown',
            'data' => $data
        ];
    }
    
    return $result;
}
```

### Fix 4: Fix Payment Flow Implementation

**Replace the current payment processing approach**:

**Current (WRONG)**:
```php
// In CheckoutPage.php - processPayment method
$result = $payWayService->createPayment($paymentData);

if ($result['success']) {
    // Store HTML content in session for ABA Pay
    session([
        'aba_pay_html' => $result['html_content'],
        'aba_pay_order_id' => $order->id
    ]);

    return [
        'success' => true,
        'redirect_url' => route('payment.aba-pay.process')
    ];
}
```

**Should be (CORRECT)**:
```php
// In CheckoutPage.php - processPayment method
$result = $payWayService->createPayment($paymentData);

if ($result['success']) {
    // Store payment data for redirect form
    session([
        'payway_payment_data' => $paymentData,
        'payway_order_id' => $order->id
    ]);

    return [
        'success' => true,
        'redirect_url' => route('payment.aba-pay.redirect')
    ];
}
```

### Fix 5: Create Proper Redirect View

**Create new view `resources/views/payment/payway-redirect.blade.php`**:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to PayWay...</title>
</head>
<body>
    <div style="text-align: center; padding: 50px;">
        <h2>Redirecting to PayWay...</h2>
        <p>Please wait while we redirect you to the secure payment page.</p>
    </div>

    <!-- Auto-submit form to PayWay -->
    <form id="payway-form" action="{{ config('payway.base_url') }}/api/payment-gateway/v1/payments/purchase" method="POST" style="display: none;">
        @php
            $paymentData = session('payway_payment_data');
            $payWayService = new \App\Services\PayWayService();
            
            // Generate all required parameters
            $params = [
                'req_time' => now()->format('YmdHis'),
                'merchant_id' => config('payway.merchant_id'),
                'tran_id' => $paymentData['transaction_id'],
                'firstname' => $paymentData['firstname'],
                'lastname' => $paymentData['lastname'],
                'email' => $paymentData['email'],
                'phone' => $paymentData['phone'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'return_url' => $paymentData['return_url'],
                'cancel_url' => $paymentData['cancel_url'],
                'type' => $paymentData['type'],
                'items' => base64_encode(json_encode($paymentData['items'])),
                'shipping' => $paymentData['shipping'],
                'payment_option' => '',
                'view_type' => 'checkout',
                // ... other required parameters
            ];
            
            // Generate hash using the service method
            $params['hash'] = $payWayService->generateHash($params);
        @endphp
        
        @foreach($params as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        // Auto-submit after 2 seconds
        setTimeout(function() {
            document.getElementById('payway-form').submit();
        }, 2000);
    </script>
</body>
</html>
```

### Fix 6: Update Routes

**Add new route and update existing ones in `routes/web.php`**:
```php
Route::prefix('payment')->name('payment.')->group(function () {
    // Add redirect route
    Route::get('/aba-pay/redirect', [PaymentController::class, 'redirectToPayWay'])->name('aba-pay.redirect');
    
    // Keep existing routes but improve handlers
    Route::get('/aba-pay/process', [PaymentController::class, 'processAbaPayment'])->name('aba-pay.process');
    Route::any('/aba-pay/return', [PaymentController::class, 'abaPayReturn'])->name('aba-pay.return');
    Route::any('/aba-pay/cancel', [PaymentController::class, 'abaPayCancel'])->name('aba-pay.cancel');
    Route::any('/aba-pay/success', [PaymentController::class, 'abaPaySuccess'])->name('aba-pay.success');
    Route::post('/aba-pay/webhook', [PaymentController::class, 'abaPayWebhook'])->name('aba-pay.webhook');
});
```

### Fix 7: Update Payment Controller

**Add new method to `app/Http/Controllers/PaymentController.php`**:
```php
/**
 * Redirect to PayWay with auto-submit form
 */
public function redirectToPayWay()
{
    $paymentData = session('payway_payment_data');
    $orderId = session('payway_order_id');

    if (!$paymentData || !$orderId) {
        return redirect()->route('cart')->with('error', 'Payment session expired');
    }

    // Clear session data
    session()->forget(['payway_payment_data', 'payway_order_id']);

    return view('payment.payway-redirect', compact('paymentData', 'orderId'));
}
```

### Fix 8: Improve Webhook Handler

**Update webhook method in PaymentController**:
```php
public function abaPayWebhook(Request $request)
{
    try {
        Log::info('PayWay Webhook Received', [
            'method' => $request->method(),
            'all_params' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $payWayService = new PayWayService();
        
        // Verify webhook authenticity
        if (!$payWayService->verifyCallback($request->all())) {
            Log::error('PayWay webhook verification failed', $request->all());
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $transactionId = $request->get('tran_id');
        $status = $request->get('status');

        // Get latest transaction details from PayWay
        $result = $payWayService->getTransactionDetails($transactionId);
        
        if ($result['success']) {
            $this->updateOrderFromWebhook($transactionId, $result['data']);
        }

        return response()->json(['status' => 'received'], 200);
        
    } catch (\Exception $e) {
        Log::error('PayWay webhook processing failed', [
            'error' => $e->getMessage(),
            'request' => $request->all()
        ]);
        
        return response()->json(['status' => 'error'], 500);
    }
}

private function updateOrderFromWebhook($transactionId, $payWayData)
{
    // Extract order ID from transaction ID (format: ORD-{order_id}-{timestamp})
    if (preg_match('/ORD-(\d+)-/', $transactionId, $matches)) {
        $orderId = $matches[1];
        $order = \App\Models\Order::find($orderId);
        
        if ($order) {
            $status = $this->mapPayWayStatus($payWayData['status']['code']);
            $order->update([
                'payment_status' => $status,
                'payment_data' => $payWayData
            ]);
            
            // Trigger events based on status
            if ($status === 'paid') {
                event(new \App\Events\PaymentCompleted($order));
            }
        }
    }
}

private function mapPayWayStatus($payWayStatusCode)
{
    return match($payWayStatusCode) {
        '00' => 'paid',
        '01' => 'pending', 
        '02' => 'failed',
        '03' => 'cancelled',
        default => 'pending'
    };
}
```

## Summary of Required Changes

1. **Environment Variables**: Update to use consistent `PAYWAY_*` naming
2. **Configuration**: Enhance `config/payway.php` with environment-specific settings
3. **Service Methods**: Add missing methods for verification and status checking
4. **Payment Flow**: Replace HTML content approach with proper redirect flow
5. **Views**: Create proper redirect view with auto-submit form
6. **Routes**: Add redirect route and improve existing handlers
7. **Controller**: Add redirect method and improve webhook handling
8. **Database**: Ensure proper transaction tracking and status mapping

## Next Steps

1. **Backup current implementation** before making changes
2. **Update environment variables** and configuration files
3. **Implement missing service methods** following the working sample
4. **Replace payment flow** with proper redirect approach
5. **Test thoroughly** in sandbox environment
6. **Implement proper error handling** and logging
7. **Set up webhook endpoint** for production use

This comprehensive fix will align the ecommerce-filament implementation with the proven working PayWay integration pattern.

## Additional Critical Issues

### 9. **Database Schema Issues**

**Current Issue**: The `AbaPayTransaction` model exists but isn't properly integrated with the PayWay flow.

**Current Model Issues**:
- Missing proper status mapping
- No integration with Order model updates
- Incomplete transaction tracking

**Fix**: Update the model to properly track PayWay transactions:

```php
// Update app/Models/AbaPayTransaction.php
protected $fillable = [
    'order_id',
    'transaction_id',
    'merchant_id',
    'amount',
    'currency',
    'status',
    'payment_option',
    'payway_status_code',
    'payway_response',
    'webhook_data',
    'processed_at',
    'webhook_received_at',
    'error_message',
];

protected $casts = [
    'amount' => 'decimal:2',
    'payway_response' => 'array',
    'webhook_data' => 'array',
    'processed_at' => 'datetime',
    'webhook_received_at' => 'datetime',
];

public function order()
{
    return $this->belongsTo(Order::class);
}

public function markAsCompleted($payWayResponse = null)
{
    $this->update([
        'status' => 'completed',
        'payway_status_code' => '00',
        'payway_response' => $payWayResponse,
        'processed_at' => now()
    ]);
}
```

### 10. **Missing Transaction Creation**

**Issue**: The current flow doesn't create `AbaPayTransaction` records.

**Fix**: Update the payment processing to create transaction records:

```php
// In CheckoutPage.php - processPayment method
if ($this->payment_method === 'aba_pay') {
    $payWayService = new PayWayService();

    // Create ABA Pay transaction record
    $abaTransaction = AbaPayTransaction::create([
        'order_id' => $order->id,
        'transaction_id' => $paymentData['transaction_id'],
        'merchant_id' => config('payway.merchant_id'),
        'amount' => $order->grand_total,
        'currency' => $order->currency ?? 'USD',
        'status' => 'pending',
        'payment_option' => '',
        'customer_info' => [
            'firstname' => $this->customer_firstname,
            'lastname' => $this->customer_lastname,
            'email' => $this->customer_email,
            'phone' => $this->customer_phone,
        ],
        'urls' => [
            'return_url' => route('payment.aba-pay.return'),
            'cancel_url' => route('payment.aba-pay.cancel'),
        ],
    ]);

    $result = $payWayService->createPayment($paymentData);
    // ... rest of the logic
}
```

### 11. **Error Handling Issues**

**Current Issue**: Poor error handling and user feedback.

**Fix**: Implement comprehensive error handling:

```php
// In PayWayService.php - createPayment method
public function createPayment(array $paymentData)
{
    try {
        // Validate configuration first
        if (empty($this->merchantId) || empty($this->secretKey)) {
            throw new \Exception('PayWay configuration is incomplete. Please check merchant ID and secret key.');
        }

        // Validate required parameters
        $this->validateRequiredParameters($paymentData);

        // ... existing code ...

        $response = Http::timeout(30)
            ->asMultipart()
            ->post($this->baseUrl . '/api/payment-gateway/v1/payments/purchase', $params);

        if ($response->successful()) {
            Log::info('PayWay payment created successfully', [
                'transaction_id' => $paymentData['transaction_id'],
                'amount' => $paymentData['amount']
            ]);

            return [
                'success' => true,
                'transaction_id' => $paymentData['transaction_id'],
                'payment_data' => $params
            ];
        } else {
            $errorMessage = $this->parsePayWayError($response);

            Log::error('PayWay API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'transaction_id' => $paymentData['transaction_id']
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'error_code' => $response->status()
            ];
        }
    } catch (\Exception $e) {
        Log::error('PayWay Service Exception', [
            'message' => $e->getMessage(),
            'transaction_id' => $paymentData['transaction_id'] ?? 'unknown',
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'error' => 'Payment service error: ' . $e->getMessage()
        ];
    }
}

private function parsePayWayError($response)
{
    $body = $response->body();

    // Try to parse JSON error response
    $json = json_decode($body, true);
    if ($json && isset($json['message'])) {
        return $json['message'];
    }

    // Fallback to HTTP status
    return match($response->status()) {
        400 => 'Invalid payment request. Please check your information.',
        401 => 'Payment gateway authentication failed.',
        403 => 'Payment request forbidden.',
        404 => 'Payment gateway endpoint not found.',
        500 => 'Payment gateway server error. Please try again.',
        default => 'Payment gateway error: ' . $response->status()
    };
}
```

### 12. **Missing Hash Validation**

**Issue**: The current implementation doesn't validate the hash generation properly.

**Fix**: Add hash validation and debugging:

```php
// In PayWayService.php - add hash validation
private function generateHash(array $params)
{
    // Ensure all required parameters are present
    $requiredForHash = [
        'req_time', 'merchant_id', 'tran_id', 'amount', 'items', 'shipping',
        'firstname', 'lastname', 'email', 'phone', 'type', 'payment_option',
        'return_url', 'cancel_url', 'continue_success_url', 'return_deeplink',
        'currency', 'custom_fields', 'return_params', 'payout', 'lifetime',
        'additional_params', 'google_pay_token', 'skip_success_page'
    ];

    // Ensure all parameters exist (set to empty string if missing)
    foreach ($requiredForHash as $param) {
        if (!isset($params[$param])) {
            $params[$param] = '';
        }
    }

    // Build hash string in exact order
    $b4hash =
        (string) $params['req_time'] .
        (string) $params['merchant_id'] .
        (string) $params['tran_id'] .
        (string) $params['amount'] .
        (string) $params['items'] .
        (string) $params['shipping'] .
        (string) $params['firstname'] .
        (string) $params['lastname'] .
        (string) $params['email'] .
        (string) $params['phone'] .
        (string) $params['type'] .
        (string) $params['payment_option'] .
        (string) $params['return_url'] .
        (string) $params['cancel_url'] .
        (string) $params['continue_success_url'] .
        (string) $params['return_deeplink'] .
        (string) $params['currency'] .
        (string) $params['custom_fields'] .
        (string) $params['return_params'] .
        (string) $params['payout'] .
        (string) $params['lifetime'] .
        (string) $params['additional_params'] .
        (string) $params['google_pay_token'] .
        (string) $params['skip_success_page'];

    // Validate secret key
    if (empty($this->secretKey)) {
        throw new \Exception('PayWay secret key is not configured');
    }

    // Log hash generation for debugging (remove in production)
    Log::debug('PayWay Hash Generation', [
        'b4hash_length' => strlen($b4hash),
        'b4hash_preview' => substr($b4hash, 0, 100) . '...',
        'secret_key_length' => strlen($this->secretKey),
        'transaction_id' => $params['tran_id'] ?? 'unknown'
    ]);

    // Generate hash using HMAC SHA512 with raw binary output and base64 encode
    $hash = base64_encode(hash_hmac('sha512', $b4hash, $this->secretKey, true));

    // Validate generated hash
    if (empty($hash)) {
        throw new \Exception('Failed to generate PayWay hash');
    }

    return $hash;
}
```

### 13. **Missing Configuration Validation**

**Issue**: No validation of PayWay configuration on startup.

**Fix**: Add configuration validation:

```php
// Create app/Services/PayWayConfigValidator.php
<?php

namespace App\Services;

class PayWayConfigValidator
{
    public static function validate(): array
    {
        $errors = [];

        // Check required configuration
        if (empty(config('payway.merchant_id'))) {
            $errors[] = 'PayWay merchant ID is not configured (PAYWAY_MERCHANT_ID)';
        }

        if (empty(config('payway.secret_key'))) {
            $errors[] = 'PayWay secret key is not configured (PAYWAY_SECRET_KEY)';
        }

        if (empty(config('payway.base_url'))) {
            $errors[] = 'PayWay base URL is not configured (PAYWAY_BASE_URL)';
        }

        // Validate URLs
        $baseUrl = config('payway.base_url');
        if ($baseUrl && !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'PayWay base URL is not a valid URL';
        }

        // Check environment consistency
        $environment = config('app.env');
        if ($environment === 'production' && str_contains($baseUrl, 'sandbox')) {
            $errors[] = 'Production environment is using sandbox PayWay URL';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
```

### 14. **Missing Filament Integration**

**Issue**: PayWay configuration is not properly integrated with Filament admin.

**Fix**: Update Filament resources to support PayWay configuration:

```php
// Update app/Filament/Resources/PaymentMethodResource.php
Forms\Components\Section::make('PayWay Configuration')
    ->schema([
        Forms\Components\TextInput::make('configuration.merchant_id')
            ->label('Merchant ID')
            ->required()
            ->visible(fn (Get $get) => $get('provider') === 'aba_pay'),
        Forms\Components\TextInput::make('configuration.secret_key')
            ->label('Secret Key')
            ->password()
            ->required()
            ->visible(fn (Get $get) => $get('provider') === 'aba_pay'),
        Forms\Components\Select::make('configuration.environment')
            ->label('Environment')
            ->options([
                'sandbox' => 'Sandbox',
                'production' => 'Production'
            ])
            ->default('sandbox')
            ->visible(fn (Get $get) => $get('provider') === 'aba_pay'),
        Forms\Components\TagsInput::make('configuration.payment_options')
            ->label('Payment Options')
            ->suggestions(['cards', 'abapay_khqr', 'google_pay', 'wechat', 'alipay'])
            ->visible(fn (Get $get) => $get('provider') === 'aba_pay'),
    ])
    ->visible(fn (Get $get) => $get('provider') === 'aba_pay')
    ->columns(2),
```

## Implementation Priority

### High Priority (Critical for functionality)
1. Fix environment variables and configuration
2. Update payment flow from HTML display to redirect
3. Complete PayWay service implementation
4. Fix hash generation and validation
5. Implement proper error handling

### Medium Priority (Important for production)
6. Update database transaction tracking
7. Implement webhook handling
8. Add configuration validation
9. Improve Filament integration

### Low Priority (Nice to have)
10. Add comprehensive logging
11. Implement retry mechanisms
12. Add payment analytics
13. Create admin dashboard for payments

## Testing Checklist

After implementing fixes:

- [ ] PayWay configuration loads correctly
- [ ] Hash generation matches PayWay requirements
- [ ] Payment redirect works properly
- [ ] Success/cancel callbacks function
- [ ] Webhook processing works
- [ ] Order status updates correctly
- [ ] Error handling provides useful feedback
- [ ] Transaction records are created properly
- [ ] Filament admin shows payment methods correctly
- [ ] All environment variables are documented

This comprehensive analysis provides a clear roadmap for fixing the PayWay implementation in the ecommerce-filament project.
