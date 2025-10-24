<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Обновляем пустые поля name в таблице users
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite версия
            DB::statement("
                UPDATE users 
                SET name = CASE 
                    WHEN TRIM(COALESCE(first_name, '')) != '' AND TRIM(COALESCE(last_name, '')) != '' THEN 
                        TRIM(first_name) || ' ' || TRIM(last_name)
                    WHEN TRIM(COALESCE(first_name, '')) != '' THEN 
                        TRIM(first_name)
                    WHEN TRIM(COALESCE(last_name, '')) != '' THEN 
                        TRIM(last_name)
                    WHEN TRIM(COALESCE(username, '')) != '' THEN 
                        TRIM(username)
                    WHEN TRIM(COALESCE(email, '')) != '' THEN 
                        SUBSTR(TRIM(email), 1, INSTR(TRIM(email), '@') - 1)
                    ELSE 
                        'Пользователь'
                END
                WHERE name IS NULL 
                   OR TRIM(name) = '' 
                   OR name = ''
            ");
        } else {
            // MySQL версия
            DB::statement("
                UPDATE users 
                SET name = CASE 
                    WHEN TRIM(COALESCE(first_name, '')) != '' AND TRIM(COALESCE(last_name, '')) != '' THEN 
                        CONCAT(TRIM(first_name), ' ', TRIM(last_name))
                    WHEN TRIM(COALESCE(first_name, '')) != '' THEN 
                        TRIM(first_name)
                    WHEN TRIM(COALESCE(last_name, '')) != '' THEN 
                        TRIM(last_name)
                    WHEN TRIM(COALESCE(username, '')) != '' THEN 
                        TRIM(username)
                    WHEN TRIM(COALESCE(email, '')) != '' THEN 
                        SUBSTRING_INDEX(TRIM(email), '@', 1)
                    ELSE 
                        'Пользователь'
                END
                WHERE name IS NULL 
                   OR TRIM(name) = '' 
                   OR name = ''
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // В down() ничего не делаем, так как это исправление данных
        // Откат может привести к потере корректных данных
    }
};
