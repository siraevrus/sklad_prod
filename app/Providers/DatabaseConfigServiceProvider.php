<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DatabaseConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Отключаем ONLY_FULL_GROUP_BY для MySQL
        // Обёрнуто в try-catch для случаев, когда БД недоступна
        try {
            if (config('database.default') === 'mysql') {
                DB::statement("SET sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
            }
        } catch (\Exception $e) {
            // БД недоступна - игнорируем ошибку (может быть при разработке или миграциях)
            if (config('app.debug')) {
                \Log::debug('Database config initialization skipped', ['error' => $e->getMessage()]);
            }
        }
    }
}
