@php
    // Use passed variables or fallback to session
    $paymentData = $paymentData ?? session('payway_payment_data');
    $orderId = $orderId ?? session('payway_order_id');
    $hasError = false;
    $errorMessage = '';
    $params = [];

    if (!$paymentData || !$orderId) {
        \Log::error('PayWay redirect: Missing payment data', [
            'has_payment_data' => !empty($paymentData),
            'has_order_id' => !empty($orderId),
            'session_id' => session()->getId(),
            'passed_payment_data' => isset($paymentData),
            'passed_order_id' => isset($orderId)
        ]);
        $hasError = true;
        $errorMessage = 'Payment session expired. Please try again.';
    } else {
        try {
            // Generate all required parameters for PayWay
            $params = [
                'req_time' => now()->format('YmdHis'),
                'merchant_id' => config('payway.merchant_id'),
                'tran_id' => $paymentData['transaction_id'],
                'firstname' => $paymentData['firstname'] ?? '',
                'lastname' => $paymentData['lastname'] ?? '',
                'email' => $paymentData['email'] ?? '',
                'phone' => $paymentData['phone'] ?? '',
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'USD',
                'return_url' => url($paymentData['return_url']),
                'cancel_url' => url($paymentData['cancel_url']),
                'continue_success_url' => url($paymentData['continue_success_url'] ?? $paymentData['return_url']),
                'type' => $paymentData['type'] ?? 'purchase',
                'items' => base64_encode(json_encode($paymentData['items'] ?? [])),
                'shipping' => $paymentData['shipping'] ?? '0.00',
                'payment_option' => $paymentData['payment_option'] ?? '',
                'view_type' => 'checkout',
                'return_deeplink' => '',
                'custom_fields' => '',
                'return_params' => '',
                'payout' => '',
                'lifetime' => '',
                'additional_params' => '',
                'google_pay_token' => '',
                'skip_success_page' => '',
                'payment_gate' => '0'
            ];

            // Generate hash using the service method (now public)
            $payWayService = new \App\Services\PayWayService();
            $params['hash'] = $payWayService->generateHash($params);

            // Validate that hash was generated successfully
            if (empty($params['hash'])) {
                throw new \Exception('Failed to generate PayWay security hash');
            }

            \Log::info('PayWay redirect: Generated parameters', [
                'transaction_id' => $params['tran_id'],
                'amount' => $params['amount'],
                'hash_length' => strlen($params['hash']),
                'merchant_id' => $params['merchant_id']
            ]);

            // Clear session data
            session()->forget(['payway_payment_data', 'payway_order_id']);

        } catch (\Exception $e) {
            \Log::error('PayWay redirect: Parameter generation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payment_data' => $paymentData ?? 'null'
            ]);
            $hasError = true;
            $errorMessage = 'Payment setup failed: ' . $e->getMessage();
        }
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to PayWay...</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            color: #333;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            margin-bottom: 20px;
        }
        .security-note {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($hasError)
            <h2 style="color: red;">Payment Error</h2>
            <p>{{ $errorMessage }}</p>
            <a href="{{ route('checkout') }}" style="background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Back to Checkout</a>

            <!-- Debug info for development -->
            @if(config('app.debug'))
            <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; font-size: 12px;">
                <strong>Debug Info:</strong><br>
                Session ID: {{ session()->getId() }}<br>
                Has Payment Data: {{ session('payway_payment_data') ? 'Yes' : 'No' }}<br>
                Has Order ID: {{ session('payway_order_id') ? 'Yes' : 'No' }}<br>
                Error: {{ $errorMessage }}
            </div>
            @endif
        @else
            <div class="spinner"></div>
            <h2>Redirecting to PayWay...</h2>
            <p>Please wait while we redirect you to the secure payment page.</p>
            <div class="security-note">
                🔒 You will be redirected to ABA Bank's secure payment gateway
            </div>

            <!-- Debug info for development -->
            @if(config('app.debug'))
            <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; font-size: 12px;">
                <strong>Debug Info:</strong><br>
                Transaction ID: {{ $params['tran_id'] ?? 'Not set' }}<br>
                Amount: {{ $params['amount'] ?? 'Not set' }}<br>
                Hash Length: {{ isset($params['hash']) ? strlen($params['hash']) : 'Not generated' }}<br>
                Form Action: {{ config('payway.base_url') }}/api/payment-gateway/v1/payments/purchase
            </div>
            @endif
        @endif
    </div>



    @if(!$hasError && !empty($params))
    <!-- Auto-submit form to PayWay -->
    <form id="payway-form" action="{{ config('payway.base_url') }}/api/payment-gateway/v1/payments/purchase" method="POST" enctype="multipart/form-data" style="display: none;">
        @foreach($params as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>

    @if(config('app.debug'))
    <!-- Debug: Show form data -->
    <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; font-size: 11px; max-height: 200px; overflow-y: auto;">
        <strong>Form Parameters:</strong><br>
        @foreach($params as $key => $value)
            <strong>{{ $key }}:</strong> {{ $key === 'hash' ? substr($value, 0, 20) . '...' : $value }}<br>
        @endforeach
    </div>
    @endif
    @endif

    <script>
        console.log('PayWay redirect page loaded');
        console.log('Has error:', {{ $hasError ? 'true' : 'false' }});
        console.log('Params empty:', {{ empty($params) ? 'true' : 'false' }});
        @if(!empty($params))
        console.log('Params count:', {{ count($params) }});
        @endif

        @if(!$hasError && !empty($params))
        // Check if form exists
        const form = document.getElementById('payway-form');
        if (!form) {
            console.error('PayWay form not found!');
            document.querySelector('.container').innerHTML = '<h2 style="color: red;">Error: Payment form not generated</h2><p>Please check the browser console and contact support.</p>';
        } else {
            console.log('PayWay form found, will submit in 2 seconds');
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            console.log('Form enctype:', form.enctype);
            console.log('Form inputs count:', form.querySelectorAll('input').length);

            // Log all form data
            const formData = new FormData(form);
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                if (key === 'hash') {
                    console.log(key + ':', value.substring(0, 20) + '...');
                } else {
                    console.log(key + ':', value);
                }
            }

            // Auto-submit after 2 seconds
            setTimeout(function() {
                try {
                    console.log('Submitting PayWay form...');
                    document.getElementById('payway-form').submit();
                } catch (error) {
                    console.error('Error submitting form:', error);
                    document.querySelector('.container').innerHTML += '<div style="color: red; margin-top: 20px;"><h3>Error submitting form:</h3><p>' + error.message + '</p></div>';
                }
            }, 2000);

            // Fallback: if form doesn't submit automatically, show manual submit button
            setTimeout(function() {
                const container = document.querySelector('.container');
                if (container && !container.innerHTML.includes('Manual submit')) {
                    container.innerHTML += '<button onclick="manualSubmit()" style="background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-top: 20px;">Continue to Payment</button>';
                    container.innerHTML += '<div style="margin-top: 10px; font-size: 12px; color: #666;">If the page doesn\'t redirect automatically, click the button above.</div>';
                }
            }, 5000);
        }

        function manualSubmit() {
            try {
                console.log('Manual form submission triggered');
                document.getElementById('payway-form').submit();
            } catch (error) {
                console.error('Manual submit error:', error);
                alert('Error submitting payment form: ' + error.message);
            }
        }
        @else
        console.log('PayWay redirect page loaded with error - no form submission');
        @endif

        // Log any JavaScript errors
        window.addEventListener('error', function(e) {
            console.error('JavaScript error on PayWay redirect page:', e.error);
        });
    </script>
</body>
</html>
