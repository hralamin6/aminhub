<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->decimal('opening_balance', 14, 2)->default(0)->after('user_id');
            $table->decimal('total_purchase', 14, 2)->default(0)->after('opening_balance');
            $table->decimal('total_paid', 14, 2)->default(0)->after('total_purchase');
            $table->decimal('total_due', 14, 2)->default(0)->after('total_paid');
            // 'type' is replaced by Spatie Role
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'total_purchase', 'total_paid', 'total_due']);
        });
    }
};
