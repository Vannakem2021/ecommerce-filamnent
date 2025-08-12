<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            [
                'provider' => 'aba_pay',
                'environment' => 'sandbox',
                'merchant_id' => env('ABA_PAY_MERCHANT_ID', 'sandbox_merchant'),
                'api_key' => env('ABA_PAY_API_KEY', 'sandbox_api_key'),
                'webhook_secret' => env('ABA_PAY_WEBHOOK_SECRET', 'sandbox_webhook_secret'),
                'api_url' => 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase',
                'checkout_url' => 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase',
                'configuration' => json_encode([
                    'default_currency' => 'USD',
                    'default_payment_option' => 'aba_pay',
                    'default_view_type' => 'hosted_view',
                    'timeout_minutes' => 30,
                    'auto_capture' => true,
                    'enable_logging' => true,
                ]),
                'supported_currencies' => json_encode(['USD', 'KHR']),
                'supported_payment_options' => json_encode([
                    'aba_pay', 'khqr', 'cards', 'google_pay', 'wechat_pay'
                ]),
                'is_active' => true,
                'is_default' => true,
                'min_amount' => 0.50,
                'max_amount' => 50000.00,
                'timeout_seconds' => 1800, // 30 minutes
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'aba_pay',
                'environment' => 'production',
                'merchant_id' => env('ABA_PAY_PROD_MERCHANT_ID'),
                'api_key' => env('ABA_PAY_PROD_API_KEY'),
                'webhook_secret' => env('ABA_PAY_PROD_WEBHOOK_SECRET'),
                'api_url' => 'https://checkout.payway.com.kh/api/payment-gateway/v1/payments/purchase',
                'checkout_url' => 'https://checkout.payway.com.kh/api/payment-gateway/v1/payments/purchase',
                'configuration' => json_encode([
                    'default_currency' => 'USD',
                    'default_payment_option' => 'aba_pay',
                    'default_view_type' => 'hosted_view',
                    'timeout_minutes' => 30,
                    'auto_capture' => true,
                    'enable_logging' => true,
                ]),
                'supported_currencies' => json_encode(['USD', 'KHR']),
                'supported_payment_options' => json_encode([
                    'aba_pay', 'khqr', 'cards', 'google_pay', 'wechat_pay'
                ]),
                'is_active' => false, // Disabled by default for production
                'is_default' => false,
                'min_amount' => 0.50,
                'max_amount' => 50000.00,
                'timeout_seconds' => 1800, // 30 minutes
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('payment_configurations')->insert($configurations);
    }
}
