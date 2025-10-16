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
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->nullable()->default(0)->change();
            $table->decimal('vat_amount', 10, 2)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->change();
            $table->decimal('vat_amount', 10, 2)->change();
        });
    }
};
