<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayWayService
{
    private $baseUrl;
    private $merchantId;
    private $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('payway.base_url');
        $this->merchantId = config('payway.merchant_id');
        // Use the public key as the secret key for hash generation (PayWay's approach)
        $this->secretKey = config('payway.secret_key');

        // Validate configuration on instantiation
        $this->validateConfiguration();
    }

    /**
     * Validate PayWay configuration
     */
    private function validateConfiguration()
    {
        if (empty($this->baseUrl)) {
            throw new \Exception('PayWay base URL is not configured. Please set PAYWAY_BASE_URL in your environment.');
        }

        if (empty($this->merchantId)) {
            throw new \Exception('PayWay merchant ID is not configured. Please set PAYWAY_MERCHANT_ID in your environment.');
        }

        if (empty($this->secretKey)) {
            throw new \Exception('PayWay secret key is not configured. Please set PAYWAY_SECRET_KEY in your environment.');
        }

        // Validate URL format
        if (!filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            throw new \Exception('PayWay base URL is not a valid URL: ' . $this->baseUrl);
        }
    }

    /**
     * Create a payment request
     */
    public function createPayment(array $paymentData)
    {
        // Validate required parameters
        $this->validateRequiredParameters($paymentData);

        // Prepare required parameters with proper data types
        $params = [
            'req_time' => now()->format('YmdHis'),
            'merchant_id' => $this->merchantId,
            'tran_id' => $paymentData['transaction_id'],
            'firstname' => $paymentData['firstname'] ?? '',
            'lastname' => $paymentData['lastname'] ?? '',
            'email' => $paymentData['email'] ?? '',
            'phone' => $paymentData['phone'] ?? '',
            'amount' => (float) $paymentData['amount'], // Keep as float per spec
            'currency' => $paymentData['currency'] ?? 'USD',
            'return_url' => $paymentData['return_url'] ?? '',
            'cancel_url' => $paymentData['cancel_url'] ?? '',
            'type' => $paymentData['type'] ?? 'purchase',
            'items' => base64_encode(json_encode($paymentData['items'] ?? [])),
            'shipping' => isset($paymentData['shipping']) ? (float) $paymentData['shipping'] : 0.00,
            'payment_option' => $paymentData['payment_option'] ?? '',
            'view_type' => $paymentData['view_type'] ?? 'checkout',
        ];

        // Add optional parameters (required for hash generation) with proper data types
        $params['custom_fields'] = isset($paymentData['custom_fields']) ? base64_encode(json_encode($paymentData['custom_fields'])) : '';
        $params['return_params'] = $paymentData['return_params'] ?? '';
        $params['lifetime'] = isset($paymentData['lifetime']) ? (string) $paymentData['lifetime'] : '';

        // Additional parameters for hash (set to empty if not provided)
        $params['continue_success_url'] = $paymentData['continue_success_url'] ?? '';
        $params['return_deeplink'] = isset($paymentData['return_deeplink']) && is_array($paymentData['return_deeplink'])
            ? base64_encode(json_encode($paymentData['return_deeplink']))
            : ($paymentData['return_deeplink'] ?? '');
        $params['payout'] = isset($paymentData['payout']) && is_array($paymentData['payout'])
            ? base64_encode(json_encode($paymentData['payout']))
            : ($paymentData['payout'] ?? '');
        $params['additional_params'] = isset($paymentData['additional_params']) && is_array($paymentData['additional_params'])
            ? base64_encode(json_encode($paymentData['additional_params']))
            : ($paymentData['additional_params'] ?? '');
        $params['google_pay_token'] = $paymentData['google_pay_token'] ?? '';
        $params['skip_success_page'] = isset($paymentData['skip_success_page']) ? (string) $paymentData['skip_success_page'] : '';
        $params['payment_gate'] = isset($paymentData['payment_gate']) ? (string) $paymentData['payment_gate'] : '0'; // 0 = use Checkout service

        // Generate hash
        $params['hash'] = $this->generateHash($params);

        try {
            // Validate configuration first
            if (empty($this->merchantId) || empty($this->secretKey)) {
                Log::error('PayWay configuration incomplete', [
                    'merchant_id_set' => !empty($this->merchantId),
                    'secret_key_set' => !empty($this->secretKey),
                    'base_url' => $this->baseUrl
                ]);
                throw new \Exception('PayWay configuration is incomplete. Please check merchant ID and secret key.');
            }

            Log::info('PayWay payment creation started', [
                'transaction_id' => $paymentData['transaction_id'],
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'USD'
            ]);

            // PayWay expects multipart/form-data for the Purchase API (as per documentation)
            Log::info('Sending PayWay API request', [
                'url' => $this->baseUrl . '/api/payment-gateway/v1/payments/purchase',
                'transaction_id' => $paymentData['transaction_id'],
                'params_count' => count($params),
                'hash_length' => strlen($params['hash'] ?? '')
            ]);

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

    /**
     * Validate required parameters according to PayWay specification
     */
    private function validateRequiredParameters(array $paymentData)
    {
        $requiredFields = ['transaction_id', 'amount'];

        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                throw new \InvalidArgumentException("Required parameter '{$field}' is missing or empty");
            }
        }

        // Validate amount is numeric and positive
        if (!is_numeric($paymentData['amount']) || $paymentData['amount'] <= 0) {
            throw new \InvalidArgumentException("Amount must be a positive number");
        }

        // Validate currency if provided
        if (isset($paymentData['currency']) && !in_array($paymentData['currency'], ['USD', 'KHR'])) {
            throw new \InvalidArgumentException("Currency must be either 'USD' or 'KHR'");
        }
    }

    /**
     * Generate security hash for PayWay API (Correct ABA Bank method)
     */
    public function generateHash(array $params)
    {
        // Validate required parameters for hash generation
        $requiredParams = ['req_time', 'merchant_id', 'tran_id', 'amount'];
        foreach ($requiredParams as $param) {
            if (!isset($params[$param]) || $params[$param] === '') {
                throw new \InvalidArgumentException("Required parameter '{$param}' is missing for hash generation");
            }
        }

        // PayWay uses direct string concatenation in specific order
        // Convert values to strings, handling null values properly
        $b4hash =
            (string) ($params['req_time'] ?? '') .
            (string) ($params['merchant_id'] ?? '') .
            (string) ($params['tran_id'] ?? '') .
            (string) ($params['amount'] ?? '') .
            (string) ($params['items'] ?? '') .
            (string) ($params['shipping'] ?? '') .
            (string) ($params['firstname'] ?? '') .
            (string) ($params['lastname'] ?? '') .
            (string) ($params['email'] ?? '') .
            (string) ($params['phone'] ?? '') .
            (string) ($params['type'] ?? '') .
            (string) ($params['payment_option'] ?? '') .
            (string) ($params['return_url'] ?? '') .
            (string) ($params['cancel_url'] ?? '') .
            (string) ($params['continue_success_url'] ?? '') .
            (string) ($params['return_deeplink'] ?? '') .
            (string) ($params['currency'] ?? '') .
            (string) ($params['custom_fields'] ?? '') .
            (string) ($params['return_params'] ?? '') .
            (string) ($params['payout'] ?? '') .
            (string) ($params['lifetime'] ?? '') .
            (string) ($params['additional_params'] ?? '') .
            (string) ($params['google_pay_token'] ?? '') .
            (string) ($params['skip_success_page'] ?? '');

        // Log hash generation for debugging
        Log::debug('PayWay Hash Generation', [
            'transaction_id' => $params['tran_id'] ?? 'unknown',
            'b4hash_length' => strlen($b4hash),
            'b4hash_preview' => substr($b4hash, 0, 100) . '...',
            'secret_key_length' => strlen($this->secretKey)
        ]);

        // Validate secret key
        if (empty($this->secretKey)) {
            throw new \Exception('PayWay secret key is not configured for hash generation');
        }

        // Generate hash using HMAC SHA512 with raw binary output and base64 encode
        $hash = base64_encode(hash_hmac('sha512', $b4hash, $this->secretKey, true));

        // Validate generated hash
        if (empty($hash)) {
            throw new \Exception('Failed to generate PayWay hash - hash is empty');
        }

        return $hash;
    }

    /**
     * Get transaction details
     */
    public function getTransactionDetails($transactionId)
    {
        $reqTime = now()->format('YmdHis');

        // Generate hash as per documentation: req_time + merchant_id + tran_id
        $b4hash = $reqTime . $this->merchantId . $transactionId;
        $hash = base64_encode(hash_hmac('sha512', $b4hash, $this->secretKey, true));

        $params = [
            'req_time' => $reqTime,
            'merchant_id' => $this->merchantId,
            'tran_id' => $transactionId,
            'hash' => $hash
        ];

        try {
            // Use JSON content type as per documentation
            $response = Http::asJson()->post($this->baseUrl . '/api/payment-gateway/v1/payments/transaction-detail', $params);

            if ($response->successful()) {
                $responseData = $response->json();

                // Check if the response has the expected structure
                if (isset($responseData['status']['code']) && $responseData['status']['code'] === '00') {
                    return [
                        'success' => true,
                        'data' => $responseData['data'] ?? $responseData,
                        'status' => $responseData['status']
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['status']['message'] ?? 'Transaction not found',
                        'status' => $responseData['status'] ?? null
                    ];
                }
            } else {
                Log::error('PayWay Transaction Details API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'transaction_id' => $transactionId
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to get transaction details: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('PayWay Get Transaction Error', [
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);

            return [
                'success' => false,
                'error' => 'Service unavailable'
            ];
        }
    }

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

    /**
     * Parse PayWay error response
     */
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
}
