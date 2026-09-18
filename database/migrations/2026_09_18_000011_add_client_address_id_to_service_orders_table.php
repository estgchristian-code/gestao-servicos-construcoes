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
        Schema::table('service_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('client_address_id')->nullable()->after('client_id');
            $table->index(['company_id', 'client_address_id']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('client_address_id')->references('id')->on('client_addresses')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'client_address_id']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['client_address_id']);
            }

            $table->dropColumn('client_address_id');
        });
    }
};