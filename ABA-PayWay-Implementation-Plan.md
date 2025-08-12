# ABA PayWay Implementation Plan

## Overview
This document provides a comprehensive plan for implementing ABA PayWay payment gateway integration in Laravel projects, based on the complete working sample implementation.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Configuration Setup](#configuration-setup)
3. [Core Service Implementation](#core-service-implementation)
4. [Controller Implementation](#controller-implementation)
5. [Routes Configuration](#routes-configuration)
6. [Frontend Implementation](#frontend-implementation)
7. [Security Considerations](#security-considerations)
8. [Testing Strategy](#testing-strategy)
9. [Deployment Checklist](#deployment-checklist)
10. [Troubleshooting Guide](#troubleshooting-guide)

## Prerequisites

### Required Information from ABA Bank
- **Merchant ID**: Unique merchant identifier provided by ABA Bank
- **Secret Key**: API key for hash generation (also called public key in PayWay docs)
- **Base URL**: 
  - Sandbox: `https://checkout-sandbox.payway.com.kh`
  - Production: `https://checkout.payway.com.kh`

### Laravel Requirements
- Laravel 8.0 or higher
- PHP 8.0 or higher
- HTTP Client (Laravel's built-in HTTP facade)

## Configuration Setup

### 1. Environment Variables
Add to your `.env` file:
```env
# PayWay Configuration
PAYWAY_BASE_URL=https://checkout-sandbox.payway.com.kh
PAYWAY_MERCHANT_ID=your_merchant_id_here
PAYWAY_SECRET_KEY=your_secret_key_here
PAYWAY_DEFAULT_CURRENCY=USD
PAYWAY_DEFAULT_LIFETIME=30
PAYWAY_SANDBOX_MODE=true
PAYWAY_DEFAULT_RETURN_URL=/payment/success
PAYWAY_DEFAULT_CANCEL_URL=/payment/cancel
```

### 2. Configuration File
Create `config/payway.php`:
```php
<?php

return [
    'base_url' => env('PAYWAY_BASE_URL', 'https://checkout-sandbox.payway.com.kh'),
    'merchant_id' => env('PAYWAY_MERCHANT_ID'),
    'secret_key' => env('PAYWAY_SECRET_KEY'),
    'default_currency' => env('PAYWAY_DEFAULT_CURRENCY', 'USD'),
    'default_lifetime' => env('PAYWAY_DEFAULT_LIFETIME', 30),
    'sandbox_mode' => env('PAYWAY_SANDBOX_MODE', true),
    'default_return_url' => env('PAYWAY_DEFAULT_RETURN_URL', '/payment/success'),
    'default_cancel_url' => env('PAYWAY_DEFAULT_CANCEL_URL', '/payment/cancel'),
];
```

## Core Service Implementation

### 1. PayWay Service Class
Create `app/Services/PayWayService.php` with the following key methods:

#### Core Methods:
- `createPayment(array $paymentData)`: Creates payment request
- `generateHash(array $params)`: Generates security hash
- `getTransactionDetails($transactionId)`: Retrieves transaction details
- `verifyCallback(array $callbackData)`: Verifies callback authenticity

#### Key Implementation Details:

**Hash Generation Algorithm:**
```php
private function generateHash(array $params)
{
    $b4hash = 
        $params['req_time'] .
        $params['merchant_id'] .
        $params['tran_id'] .
        $params['amount'] .
        $params['items'] .
        $params['shipping'] .
        $params['firstname'] .
        $params['lastname'] .
        $params['email'] .
        $params['phone'] .
        $params['type'] .
        $params['payment_option'] .
        $params['return_url'] .
        $params['cancel_url'] .
        $params['continue_success_url'] .
        $params['return_deeplink'] .
        $params['currency'] .
        $params['custom_fields'] .
        $params['return_params'] .
        $params['payout'] .
        $params['lifetime'] .
        $params['additional_params'] .
        $params['google_pay_token'] .
        $params['skip_success_page'];

    return base64_encode(hash_hmac('sha512', $b4hash, $this->secretKey, true));
}
```

**Required Parameters:**
- `req_time`: YYYYMMDDHHmmss format
- `merchant_id`: From configuration
- `tran_id`: Unique transaction identifier
- `amount`: Float value
- `hash`: Generated security hash

**Optional Parameters:**
- `currency`: USD or KHR (default: USD)
- `payment_option`: cards, abapay_khqr, abapay_khqr_deeplink, etc.
- `items`: Base64 encoded JSON array
- `custom_fields`: Base64 encoded JSON object
- `lifetime`: Payment lifetime in minutes (3-43200)

### 2. API Endpoints Used

#### Purchase API
- **URL**: `/api/payment-gateway/v1/payments/purchase`
- **Method**: POST
- **Content-Type**: multipart/form-data
- **Response**: HTML content for checkout page

#### Transaction Details API
- **URL**: `/api/payment-gateway/v1/payments/transaction-detail`
- **Method**: POST
- **Content-Type**: application/json
- **Hash**: req_time + merchant_id + tran_id

## Controller Implementation

### 1. Payment Controller Structure
Create `app/Http/Controllers/PaymentController.php` with methods:

- `showPaymentForm()`: Display payment form
- `paymentSuccess(Request $request)`: Handle success callback
- `paymentCancel(Request $request)`: Handle cancel callback
- `handlePayWayCallback(Request $request)`: Process webhooks
- `getTransactionDetails($transactionId)`: API endpoint for transaction details

### 2. Key Implementation Points

**Success/Cancel Handlers:**
```php
public function paymentSuccess(Request $request)
{
    $transactionId = $request->get('tran_id');
    
    Log::info('PayWay Success Callback', [
        'transaction_id' => $transactionId,
        'all_params' => $request->all()
    ]);
    
    return view('payment.success', [
        'transaction_id' => $transactionId,
        'message' => 'Payment completed successfully!'
    ]);
}
```

## Routes Configuration

### Essential Routes
```php
Route::prefix('payment')->group(function () {
    // Payment form
    Route::get('/form', [PaymentController::class, 'showPaymentForm'])->name('payment.form');
    
    // PayWay callbacks (CRITICAL)
    Route::get('/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');
    Route::get('/cancel', [PaymentController::class, 'paymentCancel'])->name('payment.cancel');
    
    // Webhook handler
    Route::post('/callback', [PaymentController::class, 'handlePayWayCallback'])->name('payment.callback');
    Route::get('/callback', [PaymentController::class, 'handlePayWayCallback']);
    
    // API endpoints
    Route::get('/details/{transactionId}', [PaymentController::class, 'getTransactionDetails']);
});
```

## Frontend Implementation

### 1. Payment Flow
PayWay uses a redirect-based checkout flow:

1. **Customer initiates payment** on your site
2. **Your backend creates payment request** with PayWay
3. **Customer is redirected** to PayWay's secure checkout page
4. **Customer completes payment** on PayWay's domain
5. **PayWay redirects back** to your success/cancel URLs

### 2. Payment Form Implementation
Create a form that collects basic information and redirects to PayWay:

```html
<form action="{{ route('payway.redirect') }}" method="POST">
    @csrf
    <!-- Customer information fields -->
    <!-- Payment method selection -->
    <button type="submit">Proceed to PayWay Checkout</button>
</form>
```

### 3. Auto-Redirect Implementation
Create a redirect page that automatically submits to PayWay:

```html
<form id="payway-form" action="{{ $payway_url }}" method="POST" style="display: none;">
    @foreach($params as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach
</form>

<script>
    setTimeout(function() {
        document.getElementById('payway-form').submit();
    }, 2000);
</script>
```

## Security Considerations

### 1. Hash Verification
- Always verify callback hashes to ensure authenticity
- Use `hash_equals()` for secure hash comparison
- Log all hash verification attempts

### 2. Environment Security
- Store credentials in environment variables
- Use different credentials for sandbox/production
- Rotate API keys regularly

### 3. Transaction Validation
- Validate transaction amounts on callbacks
- Check transaction status via API before processing
- Implement idempotency for webhook processing

## Testing Strategy

### 1. Sandbox Testing
- Use PayWay sandbox environment for development
- Test all payment methods (cards, ABA Pay, KHQR)
- Verify callback handling

### 2. Test Cases
- Successful payments
- Failed payments
- Cancelled payments
- Network timeouts
- Invalid hash scenarios
- Duplicate transactions

### 3. Integration Testing
```php
// Test payment creation
$paymentData = [
    'transaction_id' => time(),
    'amount' => 15.99,
    'currency' => 'USD',
    // ... other required fields
];

$result = $payWayService->createPayment($paymentData);
$this->assertTrue($result['success']);
```

## Deployment Checklist

### Pre-Production
- [ ] Update PayWay base URL to production
- [ ] Update merchant credentials
- [ ] Test production webhook endpoints
- [ ] Verify SSL certificates
- [ ] Test callback URL accessibility

### Production Monitoring
- [ ] Set up logging for all PayWay interactions
- [ ] Monitor transaction success rates
- [ ] Set up alerts for failed payments
- [ ] Implement transaction reconciliation

## Troubleshooting Guide

### Common Issues

**1. Hash Mismatch Errors**
- Verify parameter order in hash generation
- Check for extra/missing parameters
- Ensure proper string concatenation
- Validate secret key configuration

**2. Callback Not Received**
- Verify callback URLs are publicly accessible
- Check for HTTPS requirements
- Ensure proper route configuration
- Test webhook endpoints manually

**3. Payment Redirect Issues**
- Verify form submission method (POST)
- Check parameter encoding (base64 for JSON)
- Ensure proper content-type headers
- Validate PayWay URL configuration

### Debug Tools
- Use PayWay's transaction detail API to verify payment status
- Log all API requests/responses
- Implement debug routes for testing
- Use browser developer tools to inspect form submissions

## Payment Method Options

### Available Options
- `cards`: Credit/Debit card payments
- `abapay_khqr`: QR payments via ABA Pay and KHQR banks
- `abapay_khqr_deeplink`: Deep-link QR payments
- `alipay`: Alipay wallet payments
- `wechat`: WeChat wallet payments
- `google_pay`: Google Pay wallet payments
- Empty string: Show all available methods

### Implementation Example
```php
$paymentData = [
    'payment_option' => 'abapay_khqr', // Specific method
    // or
    'payment_option' => '', // Show all methods
];
```

## Next Steps

1. **Review the complete sample implementation** in the `complete-working-payway-sample` folder
2. **Adapt the code** to your specific Laravel project structure
3. **Test thoroughly** in sandbox environment
4. **Implement proper error handling** and logging
5. **Set up monitoring** for production deployment

This plan provides a solid foundation for implementing ABA PayWay in any Laravel project while maintaining security and reliability standards.

## Advanced Implementation Details

### Database Schema (Optional)
While PayWay handles payment processing, you may want to track transactions locally:

```sql
CREATE TABLE payment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(255) UNIQUE NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'USD',
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) NULL,
    payway_response JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

### Model Implementation
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payway_response'
    ];

    protected $casts = [
        'payway_response' => 'array',
        'amount' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsCompleted($payWayResponse = null)
    {
        $this->update([
            'status' => 'completed',
            'payway_response' => $payWayResponse
        ]);
    }

    public function markAsFailed($payWayResponse = null)
    {
        $this->update([
            'status' => 'failed',
            'payway_response' => $payWayResponse
        ]);
    }
}
```

### Enhanced Service Methods

#### Transaction Status Checking
```php
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

#### Webhook Signature Verification
```php
public function verifyWebhookSignature($payload, $signature, $timestamp)
{
    // Implement webhook signature verification if PayWay provides it
    $expectedSignature = hash_hmac('sha256', $timestamp . $payload, $this->secretKey);

    return hash_equals($expectedSignature, $signature);
}
```

### Error Handling and Logging

#### Custom Exception Classes
```php
<?php

namespace App\Exceptions;

class PayWayException extends \Exception
{
    protected $payWayResponse;

    public function __construct($message, $payWayResponse = null, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->payWayResponse = $payWayResponse;
    }

    public function getPayWayResponse()
    {
        return $this->payWayResponse;
    }
}

class PayWayHashException extends PayWayException {}
class PayWayApiException extends PayWayException {}
class PayWayValidationException extends PayWayException {}
```

#### Enhanced Logging
```php
private function logPayWayInteraction($action, $data, $response = null, $error = null)
{
    Log::channel('payway')->info('PayWay Interaction', [
        'action' => $action,
        'transaction_id' => $data['tran_id'] ?? null,
        'amount' => $data['amount'] ?? null,
        'timestamp' => now()->toISOString(),
        'request_data' => $this->sanitizeLogData($data),
        'response' => $response,
        'error' => $error,
        'user_agent' => request()->userAgent(),
        'ip_address' => request()->ip()
    ]);
}

private function sanitizeLogData($data)
{
    // Remove sensitive information from logs
    $sanitized = $data;
    unset($sanitized['hash']);
    unset($sanitized['merchant_id']);

    return $sanitized;
}
```

### Queue Integration for Webhook Processing

#### Job Class
```php
<?php

namespace App\Jobs;

use App\Services\PayWayService;
use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPayWayWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhookData;

    public function __construct(array $webhookData)
    {
        $this->webhookData = $webhookData;
    }

    public function handle(PayWayService $payWayService)
    {
        $transactionId = $this->webhookData['tran_id'] ?? null;

        if (!$transactionId) {
            \Log::error('PayWay webhook missing transaction ID', $this->webhookData);
            return;
        }

        // Verify webhook authenticity
        if (!$payWayService->verifyCallback($this->webhookData)) {
            \Log::error('PayWay webhook verification failed', [
                'transaction_id' => $transactionId,
                'webhook_data' => $this->webhookData
            ]);
            return;
        }

        // Get latest transaction details from PayWay
        $result = $payWayService->getTransactionDetails($transactionId);

        if ($result['success']) {
            $this->updateLocalTransaction($transactionId, $result['data']);
        }
    }

    private function updateLocalTransaction($transactionId, $payWayData)
    {
        $transaction = PaymentTransaction::where('transaction_id', $transactionId)->first();

        if ($transaction) {
            $status = $this->mapPayWayStatus($payWayData['status']['code']);

            $transaction->update([
                'status' => $status,
                'payment_method' => $payWayData['payment_method'] ?? null,
                'payway_response' => $payWayData
            ]);

            // Trigger events based on status
            if ($status === 'completed') {
                event(new \App\Events\PaymentCompleted($transaction));
            } elseif ($status === 'failed') {
                event(new \App\Events\PaymentFailed($transaction));
            }
        }
    }

    private function mapPayWayStatus($payWayStatusCode)
    {
        return match($payWayStatusCode) {
            '00' => 'completed',
            '01' => 'pending',
            '02' => 'failed',
            '03' => 'cancelled',
            default => 'unknown'
        };
    }
}
```

### Event-Driven Architecture

#### Payment Events
```php
<?php

namespace App\Events;

use App\Models\PaymentTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}

class PaymentFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;

    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
```

#### Event Listeners
```php
<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Mail\PaymentConfirmation;
use Illuminate\Support\Facades\Mail;

class SendPaymentConfirmation
{
    public function handle(PaymentCompleted $event)
    {
        $transaction = $event->transaction;

        if ($transaction->user && $transaction->user->email) {
            Mail::to($transaction->user->email)
                ->send(new PaymentConfirmation($transaction));
        }
    }
}
```

### Configuration for Different Environments

#### Environment-Specific Configuration
```php
// config/payway.php - Enhanced version
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

    // Default settings
    'default_currency' => env('PAYWAY_DEFAULT_CURRENCY', 'USD'),
    'default_lifetime' => env('PAYWAY_DEFAULT_LIFETIME', 30),
    'sandbox_mode' => env('PAYWAY_SANDBOX_MODE', true),

    // URL settings
    'urls' => [
        'return_url' => env('PAYWAY_DEFAULT_RETURN_URL', '/payment/success'),
        'cancel_url' => env('PAYWAY_DEFAULT_CANCEL_URL', '/payment/cancel'),
        'webhook_url' => env('PAYWAY_WEBHOOK_URL', '/payment/callback'),
    ],

    // Logging
    'log_channel' => env('PAYWAY_LOG_CHANNEL', 'payway'),
    'log_level' => env('PAYWAY_LOG_LEVEL', 'info'),

    // Security
    'hash_algorithm' => 'sha512',
    'webhook_verification' => env('PAYWAY_WEBHOOK_VERIFICATION', true),

    // Retry settings
    'retry_attempts' => env('PAYWAY_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('PAYWAY_RETRY_DELAY', 1000), // milliseconds
];
```

### Monitoring and Analytics

#### Custom Metrics Collection
```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PayWayMetricsService
{
    public function recordPaymentAttempt($amount, $currency, $paymentMethod)
    {
        $key = "payway_metrics:" . now()->format('Y-m-d-H');

        Cache::increment("{$key}:attempts");
        Cache::increment("{$key}:amount", $amount);
        Cache::increment("{$key}:method:{$paymentMethod}");
    }

    public function recordPaymentSuccess($transactionId, $amount, $currency)
    {
        $key = "payway_metrics:" . now()->format('Y-m-d-H');

        Cache::increment("{$key}:success");
        Cache::increment("{$key}:success_amount", $amount);
    }

    public function recordPaymentFailure($reason, $amount, $currency)
    {
        $key = "payway_metrics:" . now()->format('Y-m-d-H');

        Cache::increment("{$key}:failures");
        Cache::increment("{$key}:failure_reason:{$reason}");
    }

    public function getHourlyMetrics($date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        $metrics = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $key = "payway_metrics:{$date}-" . str_pad($hour, 2, '0', STR_PAD_LEFT);

            $metrics[$hour] = [
                'attempts' => Cache::get("{$key}:attempts", 0),
                'success' => Cache::get("{$key}:success", 0),
                'failures' => Cache::get("{$key}:failures", 0),
                'total_amount' => Cache::get("{$key}:amount", 0),
                'success_amount' => Cache::get("{$key}:success_amount", 0),
            ];
        }

        return $metrics;
    }
}
```

This comprehensive plan now covers all aspects of implementing ABA PayWay integration, from basic setup to advanced monitoring and analytics. The implementation is production-ready and follows Laravel best practices.
