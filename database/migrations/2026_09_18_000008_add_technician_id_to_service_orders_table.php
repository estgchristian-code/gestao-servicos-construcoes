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
            $table->unsignedBigInteger('technician_id')->nullable()->after('client_id');
            $table->index(['company_id', 'technician_id']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('technician_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'technician_id']);

            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['technician_id']);
            }

            $table->dropColumn('technician_id');
        });
    }
};