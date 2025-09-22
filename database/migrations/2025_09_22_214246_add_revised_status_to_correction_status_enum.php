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
        // Для SQLite просто добавляем новую колонку с правильным типом
        // SQLite не поддерживает изменение ENUM, поэтому используем TEXT
        Schema::table('products', function (Blueprint $table) {
            $table->string('correction_status_new')->nullable()->after('correction_status');
        });

        // Копируем данные в новую колонку
        DB::statement('UPDATE products SET correction_status_new = correction_status');

        // Удаляем старую колонку
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('correction_status');
        });

        // Переименовываем новую колонку
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('correction_status_new', 'correction_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возвращаем обратно к исходному enum (только 'none', 'correction')
        Schema::table('products', function (Blueprint $table) {
            $table->string('correction_status_old')->nullable()->after('correction_status');
        });

        // Копируем только валидные значения
        DB::statement("UPDATE products SET correction_status_old = CASE 
            WHEN correction_status IN ('none', 'correction') THEN correction_status 
            ELSE 'none' 
        END");

        // Удаляем новую колонку
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('correction_status');
        });

        // Переименовываем обратно
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('correction_status_old', 'correction_status');
        });
    }
};
