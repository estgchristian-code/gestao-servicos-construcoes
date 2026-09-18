<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->unsignedBigInteger('service_order_id')->nullable()->after('status');
            $table->unique('service_order_id');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('service_order_id')->references('id')->on('service_orders')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['service_order_id']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['service_order_id']);
            }

            $table->dropColumn('service_order_id');
        });
    }
};