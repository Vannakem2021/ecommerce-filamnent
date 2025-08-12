<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name' => 'ABA Pay',
                'code' => 'aba_pay',
                'provider' => 'aba_pay',
                'is_active' => true,
                'sort_order' => 1,
                'description' => 'Pay securely with ABA Pay - supports ABA PAY, KHQR, and card payments',
                'icon' => 'fas fa-mobile-alt',
                'min_amount' => 0.50,
                'max_amount' => 50000.00,
                'supported_currencies' => json_encode(['USD', 'KHR']),
                'configuration' => json_encode([
                    'payment_options' => ['aba_pay', 'khqr', 'cards', 'google_pay'],
                    'view_type' => 'hosted_view',
                    'supports_pre_auth' => true,
                    'supports_mobile' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cash on Delivery',
                'code' => 'cod',
                'provider' => 'manual',
                'is_active' => true,
                'sort_order' => 2,
                'description' => 'Pay with cash when your order is delivered',
                'icon' => 'fas fa-money-bill-wave',
                'min_amount' => 1.00,
                'max_amount' => 1000.00,
                'supported_currencies' => json_encode(['USD', 'KHR']),
                'configuration' => json_encode([
                    'requires_confirmation' => true,
                    'auto_status' => 'pending',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bank Transfer',
                'code' => 'bank_transfer',
                'provider' => 'manual',
                'is_active' => false,
                'sort_order' => 3,
                'description' => 'Transfer payment directly to our bank account',
                'icon' => 'fas fa-university',
                'min_amount' => 5.00,
                'max_amount' => 10000.00,
                'supported_currencies' => json_encode(['USD', 'KHR']),
                'configuration' => json_encode([
                    'requires_confirmation' => true,
                    'auto_status' => 'pending',
                    'bank_details_required' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('payment_methods')->insert($paymentMethods);
    }
}
