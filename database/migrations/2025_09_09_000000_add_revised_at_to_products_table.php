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
        // Проверяем, существует ли колонка revised_at
        if (! Schema::hasColumn('products', 'revised_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->timestamp('revised_at')->nullable()->after('correction_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Проверяем, существует ли колонка revised_at перед удалением
        if (Schema::hasColumn('products', 'revised_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('revised_at');
            });
        }
    }
};
