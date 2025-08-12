<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aba_pay_transactions', function (Blueprint $table) {
            $table->string('payway_status_code')->nullable()->after('status')->comment('PayWay status code (00, 01, 02, 03)');
            $table->json('payway_response')->nullable()->after('response_data')->comment('Complete PayWay API response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aba_pay_transactions', function (Blueprint $table) {
            $table->dropColumn(['payway_status_code', 'payway_response']);
        });
    }
};
